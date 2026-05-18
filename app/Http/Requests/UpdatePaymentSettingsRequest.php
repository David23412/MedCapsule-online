<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'settings' => 'required|array',
            'settings.*' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'settings.required' => 'Payment settings are required.',
            'settings.array' => 'Payment settings must be an array.',
        ];
    }
}
