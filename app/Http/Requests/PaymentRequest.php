<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'course_id' => 'required|integer|exists:courses,id',
            'amount' => 'required|numeric|min:0|max:9999.99',
            'payment_method' => 'required|in:bank_transfer,mobile_money,card',
            'reference_number' => 'nullable|string|max:100',
            'payment_proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'notes' => 'nullable|string|max:500'
        ];
    }

    public function messages(): array
    {
        return [
            'course_id.required' => 'Course selection is required',
            'amount.required' => 'Payment amount is required',
            'payment_method.required' => 'Payment method is required',
            'payment_proof.mimes' => 'Payment proof must be an image or PDF',
            'payment_proof.max' => 'Payment proof cannot exceed 5MB'
        ];
    }
}