<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChangeRequestRequest;
use App\Http\Resources\ChangeRequestResource;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestAttachment;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChangeRequestController extends Controller
{
    public function __construct(
        protected FileService $fileService
    ) {}

    /**
     * List change requests with eager-loaded relations and filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ChangeRequest::with([
            'changeType',
            'changeCategory',
            'systemAffected',
            'branchLocation',
            'riskLevel',
            'attachments',
        ]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('request_no', 'ilike', "%{$search}%")
                  ->orWhere('project_name', 'ilike', "%{$search}%")
                  ->orWhere('project_owner', 'ilike', "%{$search}%")
                  ->orWhere('main_responsible_person', 'ilike', "%{$search}%");
            });
        }

        if ($branchId = $request->input('branch_location_id')) {
            $query->where('branch_location_id', $branchId);
        }
        if ($categoryId = $request->input('change_category_id')) {
            $query->where('change_category_id', $categoryId);
        }
        if ($typeId = $request->input('change_type_id')) {
            $query->where('change_type_id', $typeId);
        }
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $perPage = min((int)$request->input('per_page', 20), 100);
        $requests = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ChangeRequestResource::collection($requests),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ]);
    }

    /**
     * Store new change request and handle attachments.
     */
    public function store(StoreChangeRequestRequest $request): JsonResponse
    {
        $changeRequest = DB::transaction(function () use ($request) {
            $data = $request->validated();
            unset($data['attachments']);

            // Normalize lookup fields from form
            $lookupMap = [
                'change_type' => 'change_type_id',
                'change_category' => 'change_category_id',
                'system_affected' => 'system_affected_id',
                'branch_location' => 'branch_location_id',
                'risk_level' => 'risk_level_id',
            ];
            foreach ($lookupMap as $shortKey => $dbKey) {
                if (empty($data[$dbKey]) && !empty($data[$shortKey])) {
                    $data[$dbKey] = $data[$shortKey];
                }
                unset($data[$shortKey]);
            }

            if (empty($data['request_no'])) {
                $data['request_no'] = ChangeRequest::generateNextRequestNo();
            }

            $cr = ChangeRequest::create($data);

            // Handle file attachments (support array and single file)
            if ($request->hasFile('attachments')) {
                $rawFiles = $request->file('attachments');
                $files = is_array($rawFiles) ? $rawFiles : [$rawFiles];
                foreach ($files as $file) {
                    if ($file instanceof \Illuminate\Http\UploadedFile && $file->isValid()) {
                        $stored = $this->fileService->storeAttachment($file);
                        $cr->attachments()->create([
                            'file' => $stored['file_path'],
                            'file_name' => $stored['original_name'],
                            'file_size' => $stored['file_size'],
                            'mime_type' => $stored['mime_type'],
                            'uploaded_at' => now(),
                        ]);
                    }
                }
            }

            return $cr->load([
                'changeType',
                'changeCategory',
                'systemAffected',
                'branchLocation',
                'riskLevel',
                'attachments',
            ]);
        });

        return response()->json([
            'status' => 'success',
            'success' => true,
            'message' => "บันทึกข้อมูล Change Request เรียบร้อยแล้ว (แนบเอกสาร {$changeRequest->attachments->count()} ไฟล์)",
            'id' => $changeRequest->id,
            'request_no' => $changeRequest->request_no,
            'project_name' => $changeRequest->project_name,
            'attachments_count' => $changeRequest->attachments->count(),
            'attachments' => $changeRequest->attachments->map(fn($att) => [
                'id' => $att->id,
                'name' => $att->file_name,
                'url' => $att->url,
            ]),
            'data' => new ChangeRequestResource($changeRequest),
        ], 201);
    }

    /**
     * Show single change request with all relations.
     */
    public function show(string $id): JsonResponse
    {
        $changeRequest = is_numeric($id)
            ? ChangeRequest::with(['changeType', 'changeCategory', 'systemAffected', 'branchLocation', 'riskLevel', 'attachments'])->find($id)
            : ChangeRequest::with(['changeType', 'changeCategory', 'systemAffected', 'branchLocation', 'riskLevel', 'attachments'])->where('request_no', $id)->first();

        if (!$changeRequest) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => "ไม่พบข้อมูล Change Request: {$id}",
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'success' => true,
            'data' => new ChangeRequestResource($changeRequest),
        ]);
    }

    /**
     * Generate and preview/download FM-IT-029 Docx Document.
     */
    public function previewDocument(string $id): BinaryFileResponse|JsonResponse
    {
        $changeRequest = is_numeric($id)
            ? ChangeRequest::with(['changeType', 'changeCategory', 'systemAffected', 'branchLocation', 'riskLevel'])->find($id)
            : ChangeRequest::with(['changeType', 'changeCategory', 'systemAffected', 'branchLocation', 'riskLevel'])->where('request_no', $id)->first();

        if (!$changeRequest) {
            return response()->json(['success' => false, 'message' => 'ไม่พบข้อมูล'], 404);
        }

        $filePath = $this->fileService->generateDocx($changeRequest);

        return response()->download($filePath, "FM-IT-029_{$changeRequest->request_no}.docx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    /**
     * Export change requests to CSV stream.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $query = ChangeRequest::with([
            'changeType',
            'changeCategory',
            'systemAffected',
            'branchLocation',
            'riskLevel',
        ]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('request_no', 'ilike', "%{$search}%")
                  ->orWhere('project_name', 'ilike', "%{$search}%")
                  ->orWhere('project_owner', 'ilike', "%{$search}%")
                  ->orWhere('main_responsible_person', 'ilike', "%{$search}%");
            });
        }

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="change_requests_' . date('Ymd_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM

            fputcsv($handle, [
                'เลขที่คำขอ',
                'ชื่อโครงการ',
                'เจ้าของโครงการ',
                'ผู้รับผิดชอบหลัก',
                'ประเภทการเปลี่ยนแปลง',
                'หมวดหมู่',
                'ระบบที่ได้รับผลกระทบ',
                'สาขา/สถานที่',
                'ระดับความเสี่ยง',
                'วันที่เริ่มต้น',
                'วันที่สิ้นสุด',
                'วันที่สร้าง',
            ]);

            $query->orderBy('id', 'desc')->chunk(100, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->request_no,
                        $row->project_name,
                        $row->project_owner,
                        $row->main_responsible_person,
                        $row->changeType?->name ?? '-',
                        $row->changeCategory?->name ?? '-',
                        $row->systemAffected?->name ?? '-',
                        $row->branchLocation?->name ?? '-',
                        $row->riskLevel?->name ?? '-',
                        $row->start_date?->format('Y-m-d') ?? '-',
                        $row->end_date?->format('Y-m-d') ?? '-',
                        $row->created_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
