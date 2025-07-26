<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentProgressFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->hasRole('tutor') || auth()->user()->hasRole('admin'));
    }

    public function rules(): array
    {
        return [
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'integer|exists:users,id',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'integer|exists:courses,id',
            'topic_ids' => 'nullable|array',
            'topic_ids.*' => 'integer|exists:topics,id',
            'date_from' => 'nullable|date|before_or_equal:date_to',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'progress_min' => 'nullable|integer|min:0|max:100',
            'progress_max' => 'nullable|integer|min:0|max:100|gte:progress_min',
            'score_min' => 'nullable|integer|min:0|max:100',
            'score_max' => 'nullable|integer|min:0|max:100|gte:score_min',
            'status' => 'nullable|in:not_started,in_progress,completed,failed',
            'sort_by' => 'nullable|in:name,progress,score,last_activity',
            'sort_direction' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:10|max:100'
        ];
    }

    public function messages(): array
    {
        return [
            'student_ids.*.exists' => 'One or more students do not exist',
            'course_ids.*.exists' => 'One or more courses do not exist',
            'topic_ids.*.exists' => 'One or more topics do not exist',
            'date_from.before_or_equal' => 'Start date must be before or equal to end date',
            'date_to.after_or_equal' => 'End date must be after or equal to start date',
            'progress_max.gte' => 'Maximum progress must be greater than or equal to minimum progress',
            'score_max.gte' => 'Maximum score must be greater than or equal to minimum score'
        ];
    }
}