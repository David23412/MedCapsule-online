<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'payment_ids' => 'required|array|min:1|max:50',
            'payment_ids.*' => 'integer|exists:payments,id',
            'action' => 'required|in:approve,reject,request_more_info',
            'rejection_reason' => 'required_if:action,reject|nullable|string|max:500',
            'admin_notes' => 'nullable|string|max:1000',
            'notify_users' => 'nullable|boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'payment_ids.required' => 'At least one payment must be selected',
            'payment_ids.max' => 'Cannot process more than 50 payments at once',
            'payment_ids.*.exists' => 'One or more payments do not exist',
            'action.required' => 'Action is required',
            'rejection_reason.required_if' => 'Rejection reason is required when rejecting payments'
        ];
    }
}