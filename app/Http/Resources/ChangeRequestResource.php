<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChangeRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_no' => $this->request_no,

            // Categorization
            'change_type_id' => $this->change_type_id,
            'change_type' => $this->whenLoaded('changeType', fn() => [
                'id' => $this->changeType->id,
                'name' => $this->changeType->name,
            ], [
                'id' => $this->change_type_id,
                'name' => $this->changeType?->name,
            ]),
            'change_category_id' => $this->change_category_id,
            'change_category' => $this->whenLoaded('changeCategory', fn() => [
                'id' => $this->changeCategory->id,
                'name' => $this->changeCategory->name,
            ], [
                'id' => $this->change_category_id,
                'name' => $this->changeCategory?->name,
            ]),
            'system_affected_id' => $this->system_affected_id,
            'system_affected' => $this->whenLoaded('systemAffected', fn() => [
                'id' => $this->systemAffected->id,
                'name' => $this->systemAffected->name,
            ], [
                'id' => $this->system_affected_id,
                'name' => $this->systemAffected?->name,
            ]),
            'branch_location_id' => $this->branch_location_id,
            'branch_location' => $this->whenLoaded('branchLocation', fn() => [
                'id' => $this->branchLocation->id,
                'name' => $this->branchLocation->name,
            ], [
                'id' => $this->branch_location_id,
                'name' => $this->branchLocation?->name,
            ]),
            'risk_level_id' => $this->risk_level_id,
            'risk_level' => $this->whenLoaded('riskLevel', fn() => [
                'id' => $this->riskLevel->id,
                'name' => $this->riskLevel->name,
            ], [
                'id' => $this->risk_level_id,
                'name' => $this->riskLevel?->name,
            ]),

            // General Info
            'project_name' => $this->project_name,
            'project_owner' => $this->project_owner,
            'objectives' => $this->objectives,
            'contractor_company' => $this->contractor_company,
            'main_responsible_person' => $this->main_responsible_person,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),

            // Usage & Spec
            'usage_purpose' => $this->usage_purpose,
            'usage_type' => $this->usage_type,
            'app_type' => $this->app_type,
            'infra_type' => $this->infra_type,
            'software_dev_test' => $this->software_dev_test,
            'database_change' => $this->database_change,
            'documents_provided' => $this->documents_provided,
            'other_documents' => $this->other_documents,

            // Resources
            'vm_count' => $this->vm_count,
            'require_public_ip' => $this->require_public_ip,
            'domain_name' => $this->domain_name,
            'cpu_vcore' => $this->cpu_vcore,
            'ram_gb' => $this->ram_gb,
            'storage_gb' => $this->storage_gb,
            'os' => $this->os,
            'os_version' => $this->os_version,

            // Network & Security
            'ports' => $this->ports,
            'has_authentication' => $this->has_authentication,
            'access_type' => $this->access_type,
            'require_https' => $this->require_https,

            // Other
            'expected_traffic' => $this->expected_traffic,
            'dependencies' => $this->dependencies,
            'require_dr_ha' => $this->require_dr_ha,
            'additional_notes' => $this->additional_notes,

            // Signatures
            'requester_name' => $this->requester_name,
            'requester_date' => $this->requester_date?->format('Y-m-d'),
            'evaluator_name' => $this->evaluator_name,
            'evaluator_date' => $this->evaluator_date?->format('Y-m-d'),
            'approver_name' => $this->approver_name,
            'approver_date' => $this->approver_date?->format('Y-m-d'),

            // Attachments
            'attachments' => ChangeRequestAttachmentResource::collection($this->whenLoaded('attachments')),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
