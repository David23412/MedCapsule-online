<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'course_ids' => 'required|array|min:1',
            'course_ids.*' => 'integer|exists:courses,id',
            'enrollment_status' => 'nullable|in:active,inactive,suspended',
            'enrollment_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:enrollment_date',
            'notes' => 'nullable|string|max:500'
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'User selection is required',
            'user_id.exists' => 'Selected user does not exist',
            'course_ids.required' => 'At least one course must be selected',
            'course_ids.*.exists' => 'One or more courses do not exist',
            'expiry_date.after' => 'Expiry date must be after enrollment date'
        ];
    }
}