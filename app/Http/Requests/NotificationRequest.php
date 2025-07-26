<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'type' => 'required|in:info,success,warning,error',
            'recipient_ids' => 'nullable|array',
            'recipient_ids.*' => 'integer|exists:users,id',
            'send_email' => 'boolean',
            'send_sms' => 'boolean',
            'scheduled_at' => 'nullable|date|after:now'
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Notification title is required',
            'message.required' => 'Notification message is required',
            'type.required' => 'Notification type is required',
            'recipient_ids.*.exists' => 'One or more recipients do not exist'
        ];
    }
}