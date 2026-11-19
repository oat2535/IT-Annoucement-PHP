<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnnouncementRequest;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnnouncementController extends Controller
{
    /**
     * List announcements with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Announcement::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('change_no', 'ilike', "%{$search}%")
                  ->orWhere('subject', 'ilike', "%{$search}%")
                  ->orWhere('user_operate', 'ilike', "%{$search}%")
                  ->orWhere('remark', 'ilike', "%{$search}%");
            });
        }

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('create_date', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('create_date', '<=', $endDate);
        }

        $perPage = min((int)$request->input('per_page', 20), 100);
        $announcements = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => AnnouncementResource::collection($announcements),
            'meta' => [
                'current_page' => $announcements->currentPage(),
                'last_page' => $announcements->lastPage(),
                'per_page' => $announcements->perPage(),
                'total' => $announcements->total(),
            ],
        ]);
    }

    /**
     * Store or update announcement with transaction.
     */
    public function store(StoreAnnouncementRequest $request): JsonResponse
    {
        $announcement = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $changeNo = trim($data['change_no'] ?? '');

            if (!empty($changeNo)) {
                $existing = Announcement::where('change_no', $changeNo)->first();
                if ($existing) {
                    $existing->update($data);
                    return $existing;
                }
            } else {
                $data['change_no'] = Announcement::generateNextChangeNo();
            }

            return Announcement::create($data);
        });

        return response()->json([
            'status' => 'success',
            'success' => true,
            'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว',
            'change_no' => $announcement->change_no,
            'data' => new AnnouncementResource($announcement),
        ], 201);
    }

    /**
     * Show single announcement.
     */
    public function show(string $id): JsonResponse
    {
        $announcement = is_numeric($id)
            ? Announcement::find($id)
            : Announcement::where('change_no', $id)->first();

        if (!$announcement) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'ไม่พบข้อมูลประกาศ',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'success' => true,
            'data' => new AnnouncementResource($announcement),
        ]);
    }

    /**
     * Check if change_no already exists.
     */
    public function checkChangeNo(Request $request): JsonResponse
    {
        $changeNo = trim($request->input('change_no') ?: $request->query('change_no', ''));
        $exists = !empty($changeNo) && Announcement::where('change_no', $changeNo)->exists();

        return response()->json([
            'status' => 'success',
            'success' => true,
            'exists' => $exists,
            'change_no' => $changeNo,
        ]);
    }

    /**
     * Export announcements to CSV using memory-friendly streaming.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $query = Announcement::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('change_no', 'ilike', "%{$search}%")
                  ->orWhere('subject', 'ilike', "%{$search}%")
                  ->orWhere('user_operate', 'ilike', "%{$search}%");
            });
        }

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('create_date', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('create_date', '<=', $endDate);
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="announcements_' . date('Ymd_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility with Thai language
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['ID', 'Change No', 'วันที่สร้าง', 'หัวข้อ', 'วันที่ดำเนินการ', 'เวลาที่ดำเนินการ', 'ผู้ดำเนินการ', 'หมายเหตุ', 'Badge', 'ข้อความแจ้งเตือน']);

            $query->orderBy('id', 'desc')->chunk(100, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->id,
                        $row->change_no,
                        $row->create_date?->format('Y-m-d H:i:s'),
                        $row->subject,
                        $row->operate_date,
                        $row->operate_time,
                        $row->user_operate,
                        $row->remark,
                        $row->alert_badge,
                        $row->alert_message,
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Save exported announcement banner image (Base64 / File).
     */
    public function saveExportedImage(Request $request): JsonResponse
    {
        $imageData = $request->input('image_data') ?: $request->input('image');
        if (!$imageData) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Image data is required',
            ], 422);
        }

        $filename = $request->input('filename') ?: ($request->input('change_no', 'exported_' . time()) . '.png');
        $category = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$request->input('category', 'exports')) ?: 'exports';
        $cleanFilename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
        if (!str_ends_with(strtolower($cleanFilename), '.png')) {
            $cleanFilename .= '.png';
        }

        if (str_contains($imageData, ',')) {
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
        }

        $decoded = base64_decode($imageData);
        if ($decoded === false) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Base64 decode failed',
            ], 422);
        }

        $filePath = "{$category}/{$cleanFilename}";
        Storage::disk('public')->put($filePath, $decoded);
        $url = Storage::disk('public')->url($filePath);

        return response()->json([
            'status' => 'success',
            'success' => true,
            'message' => 'Image saved to media successfully',
            'url' => $url,
            'filename' => $cleanFilename,
            'file_name' => $cleanFilename,
        ]);
    }
}
