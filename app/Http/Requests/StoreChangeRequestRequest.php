<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChangeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_no' => ['nullable', 'string', 'max:50'],
            'project_name' => ['required', 'string', 'max:255'],
            'project_owner' => ['required', 'string', 'max:255'],
            'objectives' => ['required', 'string'],
            'contractor_company' => ['nullable', 'string', 'max:255'],
            'main_responsible_person' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],

            // Lookup IDs
            'change_type_id' => ['nullable', 'exists:change_request_types,id'],
            'change_category_id' => ['nullable', 'exists:change_request_categories,id'],
            'system_affected_id' => ['nullable', 'exists:change_request_system_affecteds,id'],
            'branch_location_id' => ['nullable', 'exists:change_request_branch_locations,id'],
            'risk_level_id' => ['nullable', 'exists:change_request_risk_levels,id'],

            // Purpose & Specs
            'usage_purpose' => ['nullable', 'string', 'max:50'],
            'usage_type' => ['nullable', 'string', 'max:50'],
            'app_type' => ['nullable', 'string', 'max:255'],
            'infra_type' => ['nullable', 'string', 'max:255'],
            'software_dev_test' => ['nullable', 'string', 'max:50'],
            'database_change' => ['nullable', 'string', 'max:255'],
            'documents_provided' => ['nullable', 'string', 'max:255'],
            'other_documents' => ['nullable', 'string'],

            // Resources
            'vm_count' => ['nullable', 'integer', 'min:0'],
            'require_public_ip' => ['nullable', 'boolean'],
            'domain_name' => ['nullable', 'string', 'max:255'],
            'cpu_vcore' => ['nullable', 'string', 'max:50'],
            'ram_gb' => ['nullable', 'string', 'max:50'],
            'storage_gb' => ['nullable', 'string', 'max:50'],
            'os' => ['nullable', 'string', 'max:50'],
            'os_version' => ['nullable', 'string', 'max:100'],

            // Network & Security
            'ports' => ['nullable', 'string', 'max:255'],
            'has_authentication' => ['nullable', 'boolean'],
            'access_type' => ['nullable', 'string', 'max:100'],
            'require_https' => ['nullable', 'boolean'],

            // Other
            'expected_traffic' => ['nullable', 'string', 'max:255'],
            'dependencies' => ['nullable', 'string', 'max:255'],
            'require_dr_ha' => ['nullable', 'boolean'],
            'additional_notes' => ['nullable', 'string'],

            // Signatures
            'requester_name' => ['nullable', 'string', 'max:255'],
            'requester_date' => ['nullable', 'date'],
            'evaluator_name' => ['nullable', 'string', 'max:255'],
            'evaluator_date' => ['nullable', 'date'],
            'approver_name' => ['nullable', 'string', 'max:255'],
            'approver_date' => ['nullable', 'date'],

            // Files
            'attachments' => ['nullable'],
            'attachments.*' => ['nullable', 'file', 'max:51200'], // max 50MB per file
        ];
    }
}
