<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CourseEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'course_id' => 'required|integer|exists:courses,id',
            'enrollment_type' => 'nullable|in:free,paid,trial',
            'payment_method' => 'required_if:enrollment_type,paid|nullable|in:bank_transfer,mobile_money,card',
            'payment_proof' => 'required_if:enrollment_type,paid|nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'terms_accepted' => 'required|boolean|accepted'
        ];
    }

    public function messages(): array
    {
        return [
            'course_id.required' => 'Course selection is required',
            'course_id.exists' => 'Selected course does not exist',
            'payment_method.required_if' => 'Payment method is required for paid enrollment',
            'payment_proof.required_if' => 'Payment proof is required for paid enrollment',
            'payment_proof.mimes' => 'Payment proof must be an image or PDF',
            'payment_proof.max' => 'Payment proof cannot exceed 5MB',
            'terms_accepted.accepted' => 'You must accept the terms and conditions'
        ];
    }
}