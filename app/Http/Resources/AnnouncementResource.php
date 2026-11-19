<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnnouncementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'change_no' => $this->change_no,
            'create_date' => $this->create_date?->format('Y-m-d H:i:s'),
            'subject' => $this->subject,
            'operate_date' => $this->operate_date,
            'operate_time' => $this->operate_time,
            'user_operate' => $this->user_operate,
            'remark' => $this->remark,
            'alert_badge' => $this->alert_badge,
            'alert_message' => $this->alert_message,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
