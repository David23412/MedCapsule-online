<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TutorDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('tutor');
    }

    public function rules(): array
    {
        return [
            'date_from' => 'nullable|date|before_or_equal:date_to',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'integer|exists:users,id',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'integer|exists:courses,id',
            'view_type' => 'nullable|in:overview,detailed,analytics',
            'sort_by' => 'nullable|in:name,progress,last_activity,score',
            'sort_direction' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:10|max:100'
        ];
    }

    public function messages(): array
    {
        return [
            'date_from.before_or_equal' => 'Start date must be before or equal to end date',
            'date_to.after_or_equal' => 'End date must be after or equal to start date',
            'student_ids.*.exists' => 'One or more students do not exist',
            'course_ids.*.exists' => 'One or more courses do not exist'
        ];
    }
}