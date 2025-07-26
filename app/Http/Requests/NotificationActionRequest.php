<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotificationActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'notification_ids' => 'required|array|min:1|max:100',
            'notification_ids.*' => 'integer|exists:notifications,id',
            'action' => 'required|in:mark_read,mark_unread,delete,archive',
            'bulk_action' => 'nullable|boolean',
            'redirect_url' => 'nullable|url'
        ];
    }

    public function messages(): array
    {
        return [
            'notification_ids.required' => 'At least one notification must be selected',
            'notification_ids.max' => 'Cannot process more than 100 notifications at once',
            'notification_ids.*.exists' => 'One or more notifications do not exist',
            'action.required' => 'Action is required',
            'action.in' => 'Invalid action selected'
        ];
    }
}