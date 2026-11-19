<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Announcement extends Model
{
    protected $table = 'change_request_announcements';

    protected $fillable = [
        'change_no',
        'create_date',
        'subject',
        'operate_date',
        'operate_time',
        'user_operate',
        'remark',
        'alert_badge',
        'alert_message',
    ];

    protected $casts = [
        'create_date' => 'datetime',
    ];

    /**
     * Generate the next change number with pessimistic locking for concurrency safety.
     * Format: YYYYMM-IMxx (e.g. 202611-IM16)
     */
    public static function generateNextChangeNo(): string
    {
        $prefix = Carbon::now()->format('Ym') . '-IM';

        // Lock latest row in this month's prefix to prevent race condition
        $lastAnnouncement = self::where('change_no', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->first();

        if ($lastAnnouncement && !empty($lastAnnouncement->change_no)) {
            $parts = explode('-IM', $lastAnnouncement->change_no);
            $lastNo = isset($parts[1]) && is_numeric($parts[1]) ? (int)$parts[1] : 15;
            $nextNo = $lastNo + 1;
        } else {
            // Default starting number from business logic
            $nextNo = 16;
        }

        return "{$prefix}{$nextNo}";
    }

    protected static function booted(): void
    {
        static::creating(function (Announcement $announcement) {
            if (empty($announcement->change_no)) {
                $announcement->change_no = self::generateNextChangeNo();
            }
            if (empty($announcement->create_date)) {
                $announcement->create_date = now();
            }
        });
    }
}
