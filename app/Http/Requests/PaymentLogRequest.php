<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'payment_id' => 'nullable|integer|exists:payments,id',
            'action' => 'nullable|in:created,updated,verified,rejected,refunded',
            'date_from' => 'nullable|date|before_or_equal:date_to',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'user_id' => 'nullable|integer|exists:users,id',
            'admin_id' => 'nullable|integer|exists:users,id',
            'export_format' => 'nullable|in:csv,excel,pdf',
            'per_page' => 'nullable|integer|min:10|max:100'
        ];
    }

    public function messages(): array
    {
        return [
            'payment_id.exists' => 'Payment does not exist',
            'date_from.before_or_equal' => 'Start date must be before or equal to end date',
            'date_to.after_or_equal' => 'End date must be after or equal to start date',
            'user_id.exists' => 'User does not exist',
            'admin_id.exists' => 'Admin does not exist'
        ];
    }
}