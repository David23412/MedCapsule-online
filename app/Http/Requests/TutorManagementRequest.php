<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TutorManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'tutor_id' => 'required|integer|exists:users,id',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'integer|exists:users,id',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'integer|exists:courses,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'in:view_progress,assign_topics,grade_assignments,send_notifications',
            'start_date' => 'nullable|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date'
        ];
    }

    public function messages(): array
    {
        return [
            'tutor_id.required' => 'Tutor selection is required',
            'student_ids.required' => 'At least one student must be selected',
            'student_ids.min' => 'At least one student must be selected',
            'student_ids.*.exists' => 'One or more students do not exist',
            'course_ids.*.exists' => 'One or more courses do not exist',
            'end_date.after' => 'End date must be after start date'
        ];
    }
}