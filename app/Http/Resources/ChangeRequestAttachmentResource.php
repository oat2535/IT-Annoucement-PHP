<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChangeRequestAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'change_request_id' => $this->change_request_id,
            'file_name' => $this->file_name ?: basename($this->file),
            'url' => $this->url,
            'file_size' => $this->file_size,
            'mime_type' => $this->mime_type,
            'uploaded_at' => $this->uploaded_at?->format('Y-m-d H:i:s'),
        ];
    }
}
