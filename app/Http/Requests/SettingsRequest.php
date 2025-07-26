<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'theme' => 'nullable|in:light,dark,auto',
            'language' => 'nullable|in:en,fr,es,de,ar',
            'timezone' => 'nullable|string|max:50',
            'notifications_enabled' => 'nullable|boolean',
            'email_notifications' => 'nullable|boolean',
            'sms_notifications' => 'nullable|boolean',
            'sound_enabled' => 'nullable|boolean',
            'auto_save' => 'nullable|boolean',
            'session_timeout' => 'nullable|integer|min:5|max:480'
        ];
    }

    public function messages(): array
    {
        return [
            'theme.in' => 'Invalid theme selection',
            'language.in' => 'Invalid language selection',
            'session_timeout.min' => 'Session timeout must be at least 5 minutes',
            'session_timeout.max' => 'Session timeout cannot exceed 8 hours'
        ];
    }
}