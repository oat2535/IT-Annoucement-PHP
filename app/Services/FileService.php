<?php

namespace App\Services;

use App\Models\ChangeRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileService
{
    /**
     * Sanitize and preserve Thai characters in filenames.
     */
    public static function sanitizeThaiFilename(string $name): string
    {
        $name = trim(str_replace(' ', '_', $name));
        $sanitized = preg_replace('/[^\w.\-\x{0E00}-\x{0E7F}]/u', '', $name);

        if (empty($sanitized) || in_array($sanitized, ['.', '..'], true)) {
            $sanitized = 'file_' . time();
        }

        return $sanitized;
    }

    /**
     * Store uploaded attachment in public disk with date-based folder structure.
     */
    public function storeAttachment(UploadedFile $file): array
    {
        $originalName = $file->getClientOriginalName();
        $safeName = self::sanitizeThaiFilename($originalName);
        $uniqueName = pathinfo($safeName, PATHINFO_FILENAME) . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        $subPath = 'change_requests/' . date('Y/m/d');
        $storedPath = $file->storeAs($subPath, $uniqueName, 'public');

        return [
            'file_path' => $storedPath,
            'original_name' => $originalName,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ];
    }

    /**
     * Generate FM-IT-029 Docx Document from ChangeRequest.
     */
    public function generateDocx(ChangeRequest $changeRequest): string
    {
        $templatePath = storage_path('app/templates/FM-IT-029_แบบคำขออนุญาต แก้ไขเปลี่ยนแปลงติดตั้ง.docx');

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Template document not found at: {$templatePath}");
        }

        $template = new TemplateProcessor($templatePath);

        // Fill template placeholders
        $template->setValue('request_no', $changeRequest->request_no ?? '-');
        $template->setValue('project_name', $changeRequest->project_name ?? '-');
        $template->setValue('project_owner', $changeRequest->project_owner ?? '-');
        $template->setValue('objectives', $changeRequest->objectives ?? '-');
        $template->setValue('contractor_company', $changeRequest->contractor_company ?? '-');
        $template->setValue('main_responsible_person', $changeRequest->main_responsible_person ?? '-');
        $template->setValue('start_date', $changeRequest->start_date?->format('d/m/Y') ?? '-');
        $template->setValue('end_date', $changeRequest->end_date?->format('d/m/Y') ?? '-');

        // Categorization
        $template->setValue('change_type', $changeRequest->changeType?->name ?? '-');
        $template->setValue('change_category', $changeRequest->changeCategory?->name ?? '-');
        $template->setValue('system_affected', $changeRequest->systemAffected?->name ?? '-');
        $template->setValue('branch_location', $changeRequest->branchLocation?->name ?? '-');
        $template->setValue('risk_level', $changeRequest->riskLevel?->name ?? '-');

        // Usage purpose & Specifications
        $template->setValue('usage_purpose', $changeRequest->usage_purpose ?? '-');
        $template->setValue('usage_type', $changeRequest->usage_type ?? '-');
        $template->setValue('app_type', $changeRequest->app_type ?? '-');
        $template->setValue('infra_type', $changeRequest->infra_type ?? '-');
        $template->setValue('software_dev_test', $changeRequest->software_dev_test ?? '-');
        $template->setValue('database_change', $changeRequest->database_change ?? '-');
        $template->setValue('documents_provided', $changeRequest->documents_provided ?? '-');
        $template->setValue('other_documents', $changeRequest->other_documents ?? '-');

        // Resources
        $template->setValue('vm_count', $changeRequest->vm_count ?? '-');
        $template->setValue('require_public_ip', $changeRequest->require_public_ip ? 'Yes' : 'No');
        $template->setValue('domain_name', $changeRequest->domain_name ?? '-');
        $template->setValue('cpu_vcore', $changeRequest->cpu_vcore ?? '-');
        $template->setValue('ram_gb', $changeRequest->ram_gb ?? '-');
        $template->setValue('storage_gb', $changeRequest->storage_gb ?? '-');
        $template->setValue('os', $changeRequest->os ?? '-');
        $template->setValue('os_version', $changeRequest->os_version ?? '-');

        // Network & Security
        $template->setValue('ports', $changeRequest->ports ?? '-');
        $template->setValue('has_authentication', $changeRequest->has_authentication ? 'Yes' : 'No');
        $template->setValue('access_type', $changeRequest->access_type ?? '-');
        $template->setValue('require_https', $changeRequest->require_https ? 'Yes' : 'No');

        // Other & Signatures
        $template->setValue('expected_traffic', $changeRequest->expected_traffic ?? '-');
        $template->setValue('dependencies', $changeRequest->dependencies ?? '-');
        $template->setValue('require_dr_ha', $changeRequest->require_dr_ha ? 'Yes' : 'No');
        $template->setValue('additional_notes', $changeRequest->additional_notes ?? '-');

        $template->setValue('requester_name', $changeRequest->requester_name ?? '-');
        $template->setValue('requester_date', $changeRequest->requester_date?->format('d/m/Y') ?? '-');
        $template->setValue('evaluator_name', $changeRequest->evaluator_name ?? '-');
        $template->setValue('evaluator_date', $changeRequest->evaluator_date?->format('d/m/Y') ?? '-');
        $template->setValue('approver_name', $changeRequest->approver_name ?? '-');
        $template->setValue('approver_date', $changeRequest->approver_date?->format('d/m/Y') ?? '-');

        $outputPath = storage_path("app/public/generated/{$changeRequest->request_no}.docx");
        @mkdir(dirname($outputPath), 0755, true);
        $template->saveAs($outputPath);

        return $outputPath;
    }
}
