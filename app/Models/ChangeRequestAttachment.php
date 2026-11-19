<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ChangeRequestAttachment extends Model
{
    protected $table = 'change_request_attachments';

    protected $fillable = [
        'change_request_id',
        'file',
        'file_name',
        'file_size',
        'mime_type',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'file_size' => 'integer',
    ];

    protected $appends = ['url'];

    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(ChangeRequest::class);
    }

    public function getUrlAttribute(): string
    {
        if (empty($this->file)) {
            return '';
        }

        // If file already starts with http/https
        if (str_starts_with($this->file, 'http://') || str_starts_with($this->file, 'https://')) {
            $parts = parse_url($this->file);
            if (!empty($parts['path']) && str_starts_with($parts['path'], '/storage/')) {
                return $parts['path'];
            }
            return $this->file;
        }

        $path = ltrim($this->file, '/');
        if (str_starts_with($path, 'storage/')) {
            return '/' . $path;
        }

        return '/storage/' . $path;
    }
}
