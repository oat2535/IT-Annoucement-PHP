<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'change_no' => ['nullable', 'string', 'max:50'],
            'subject' => ['required', 'string', 'max:255'],
            'operate_date' => ['required', 'string', 'max:100'],
            'operate_time' => ['required', 'string', 'max:50'],
            'user_operate' => ['required', 'string', 'max:200'],
            'remark' => ['nullable', 'string'],
            'alert_badge' => ['nullable', 'string', 'max:100'],
            'alert_message' => ['nullable', 'string'],
            'create_date' => ['nullable', 'date'],
        ];
    }
}
