<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChangeRequest extends Model
{
    protected $table = 'change_requests';

    protected $fillable = [
        'request_no',
        'change_type_id',
        'change_category_id',
        'system_affected_id',
        'branch_location_id',
        'risk_level_id',
        'project_name',
        'project_owner',
        'objectives',
        'contractor_company',
        'main_responsible_person',
        'start_date',
        'end_date',
        'usage_purpose',
        'usage_type',
        'app_type',
        'infra_type',
        'software_dev_test',
        'database_change',
        'documents_provided',
        'other_documents',
        'vm_count',
        'require_public_ip',
        'domain_name',
        'cpu_vcore',
        'ram_gb',
        'storage_gb',
        'os',
        'os_version',
        'ports',
        'has_authentication',
        'access_type',
        'require_https',
        'expected_traffic',
        'dependencies',
        'require_dr_ha',
        'additional_notes',
        'requester_name',
        'requester_date',
        'evaluator_name',
        'evaluator_date',
        'approver_name',
        'approver_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'requester_date' => 'date',
        'evaluator_date' => 'date',
        'approver_date' => 'date',
        'require_public_ip' => 'boolean',
        'has_authentication' => 'boolean',
        'require_https' => 'boolean',
        'require_dr_ha' => 'boolean',
        'vm_count' => 'integer',
    ];

    public function changeType(): BelongsTo
    {
        return $this->belongsTo(ChangeType::class);
    }

    public function changeCategory(): BelongsTo
    {
        return $this->belongsTo(ChangeCategory::class);
    }

    public function systemAffected(): BelongsTo
    {
        return $this->belongsTo(SystemAffected::class);
    }

    public function branchLocation(): BelongsTo
    {
        return $this->belongsTo(BranchLocation::class);
    }

    public function riskLevel(): BelongsTo
    {
        return $this->belongsTo(RiskLevel::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ChangeRequestAttachment::class);
    }

    /**
     * Generate the next request number with pessimistic locking for concurrency safety.
     * Format: CHG-YYYY-0001
     */
    public static function generateNextRequestNo(): string
    {
        $prefix = 'CHG-' . Carbon::now()->format('Y') . '-';

        $lastRequest = self::where('request_no', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->first();

        if ($lastRequest && !empty($lastRequest->request_no)) {
            $numPart = str_replace($prefix, '', $lastRequest->request_no);
            $lastNo = is_numeric($numPart) ? (int)$numPart : 0;
            $nextNo = $lastNo + 1;
        } else {
            $nextNo = 1;
        }

        return sprintf('%s%04d', $prefix, $nextNo);
    }

    protected static function booted(): void
    {
        static::creating(function (ChangeRequest $changeRequest) {
            if (empty($changeRequest->request_no)) {
                $changeRequest->request_no = self::generateNextRequestNo();
            }
        });
    }
}
