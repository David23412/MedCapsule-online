<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'payment_id' => 'required|integer|exists:payments,id',
            'status' => 'nullable|in:pending,processing,completed,failed,refunded',
            'reference_number' => 'nullable|string|max:100',
            'admin_notes' => 'nullable|string|max:1000',
            'notify_user' => 'nullable|boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'payment_id.required' => 'Payment ID is required',
            'payment_id.exists' => 'Payment does not exist',
            'status.in' => 'Invalid payment status',
            'reference_number.max' => 'Reference number cannot exceed 100 characters',
            'admin_notes.max' => 'Admin notes cannot exceed 1000 characters'
        ];
    }
}