<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\BranchLocation;
use App\Models\ChangeCategory;
use App\Models\ChangeRequest;
use App\Models\ChangeType;
use App\Models\RiskLevel;
use App\Models\SystemAffected;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PortalController extends Controller
{
    public static function formatOperateDateDefaults(?Carbon $date = null): array
    {
        $date = $date ?: Carbon::now();
        $thaiWeekdays = ['วันอาทิตย์', 'วันจันทร์', 'วันอังคาร', 'วันพุธ', 'วันพฤหัสบดี', 'วันศุกร์', 'วันเสาร์'];
        $thaiMonths = [
            1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
            5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
            9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
        ];
        $buddhistYear = $date->year + 543;
        $thaiText = $thaiWeekdays[$date->dayOfWeek] . 'ที่ ' . $date->day . ' ' . $thaiMonths[$date->month] . ' ' . $buddhistYear;
        $englishText = $date->format('l, j F Y');

        return [$thaiText, $englishText];
    }

    public function portal(Request $request)
    {
        if ($changeNo = $request->query('change_no')) {
            return redirect()->route('announcement', ['change_no' => $changeNo]);
        }

        try {
            $totalAnnouncements = Announcement::count();
            $totalChangeRequests = ChangeRequest::count();
            $latestAnnouncement = Announcement::orderBy('id', 'desc')->first();
            $latestChangeRequest = ChangeRequest::orderBy('id', 'desc')->first();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Database query failed in PortalController: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            if (config('app.debug')) {
                throw $e;
            }

            return response()->view('errors.db_error', [
                'errorMessage' => $e->getMessage(),
            ], 500);
        }

        return view('portal', [
            'total_announcements' => $totalAnnouncements,
            'total_change_requests' => $totalChangeRequests,
            'latest_announcement' => $latestAnnouncement,
            'latest_change_request' => $latestChangeRequest,
        ]);
    }

    public function announcement(Request $request)
    {
        $nextChangeNo = Announcement::generateNextChangeNo();
        $allAnnouncements = Announcement::select('id', 'change_no', 'subject')->orderBy('id', 'desc')->get();
        [$defaultOperateDateTh, $defaultOperateDateEn] = self::formatOperateDateDefaults();

        return view('announcement', [
            'next_change_no' => $nextChangeNo,
            'all_announcements' => $allAnnouncements,
            'default_operate_date_th' => $defaultOperateDateTh,
            'default_operate_date_en' => $defaultOperateDateEn,
        ]);
    }

    public function report(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $search = trim((string)$request->query('search', ''));

        $hasFilter = !empty($startDate) || !empty($endDate) || $search !== '';

        if ($hasFilter) {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query = Announcement::query();

            if (!empty($startDate)) {
                $query->whereDate('create_date', '>=', $startDate);
            }
            if (!empty($endDate)) {
                $query->whereDate('create_date', '<=', $endDate);
            }
            if ($search !== '') {
                $query->where(function ($q) use ($search, $like) {
                    $q->where('change_no', $like, "%{$search}%")
                      ->orWhere('subject', $like, "%{$search}%")
                      ->orWhere('user_operate', $like, "%{$search}%")
                      ->orWhere('remark', $like, "%{$search}%");
                });
            }

            $announcements = $query->orderBy('id', 'desc')->get();
            $searched = true;
        } else {
            $announcements = collect();
            $searched = false;
        }

        return view('report', [
            'announcements' => $announcements,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'search' => $search,
            'searched' => $searched,
        ]);
    }

    public function changeRequest(Request $request)
    {
        $nextRequestNo = ChangeRequest::generateNextRequestNo();
        $allRequests = ChangeRequest::select('id', 'request_no', 'project_name')->orderBy('id', 'desc')->get();

        $requestNo = $request->query('request_no');
        $reqObj = null;
        $reqDataJson = '{}';
        $reqFiles = [];

        if ($requestNo) {
            $reqObj = ChangeRequest::with(['changeType', 'changeCategory', 'systemAffected', 'branchLocation', 'riskLevel', 'attachments'])
                ->where('request_no', $requestNo)
                ->first();

            if ($reqObj) {
                $reqDict = $reqObj->toArray();

                // Ensure foreign keys match form field names for select dropdowns
                $reqDict['change_type'] = $reqObj->change_type_id;
                $reqDict['change_category'] = $reqObj->change_category_id;
                $reqDict['system_affected'] = $reqObj->system_affected_id;
                $reqDict['branch_location'] = $reqObj->branch_location_id;
                $reqDict['risk_level'] = $reqObj->risk_level_id;

                // Remove attachments relation from reqDict so it doesn't target input[type="file"]
                unset($reqDict['attachments']);

                if ($reqObj->start_date) $reqDict['start_date'] = $reqObj->start_date->format('Y-m-d');
                if ($reqObj->end_date) $reqDict['end_date'] = $reqObj->end_date->format('Y-m-d');
                if ($reqObj->requester_date) $reqDict['requester_date'] = $reqObj->requester_date->format('Y-m-d');
                if ($reqObj->evaluator_date) $reqDict['evaluator_date'] = $reqObj->evaluator_date->format('Y-m-d');
                if ($reqObj->approver_date) $reqDict['approver_date'] = $reqObj->approver_date->format('Y-m-d');

                $reqDataJson = json_encode($reqDict, JSON_UNESCAPED_UNICODE);

                foreach ($reqObj->attachments as $att) {
                    $reqFiles[] = [
                        'id' => $att->id,
                        'name' => $att->file_name ?: basename($att->file),
                        'url' => $att->url,
                    ];
                }
            }
        }

        return view('change_request', [
            'next_request_no' => $nextRequestNo,
            'current_request_no' => $reqObj ? $reqObj->request_no : $nextRequestNo,
            'all_requests' => $allRequests,
            'change_types' => ChangeType::all(),
            'change_categories' => ChangeCategory::all(),
            'systems' => SystemAffected::all(),
            'branches' => BranchLocation::all(),
            'risks' => RiskLevel::all(),
            'req_obj' => $reqObj,
            'req_data_json' => $reqDataJson,
            'req_files_json' => json_encode($reqFiles, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function changeRequestReport(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $search = trim((string)$request->query('search', ''));

        $hasFilter = !empty($startDate) || !empty($endDate) || $search !== '';

        if ($hasFilter) {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query = ChangeRequest::query();

            if (!empty($startDate)) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if (!empty($endDate)) {
                $query->whereDate('created_at', '<=', $endDate);
            }
            if ($search !== '') {
                $query->where(function ($q) use ($search, $like) {
                    $q->where('request_no', $like, "%{$search}%")
                      ->orWhere('project_name', $like, "%{$search}%")
                      ->orWhere('project_owner', $like, "%{$search}%")
                      ->orWhere('main_responsible_person', $like, "%{$search}%");
                });
            }

            $requests = $query->orderBy('id', 'desc')->get();
            $searched = true;
        } else {
            $requests = collect();
            $searched = false;
        }

        return view('change_request_report', [
            'requests' => $requests,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'search' => $search,
            'searched' => $searched,
        ]);
    }
}
