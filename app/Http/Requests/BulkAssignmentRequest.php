<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('tutor'));
    }

    public function rules(): array
    {
        return [
            'title_template' => 'required|string|max:255',
            'description_template' => 'nullable|string|max:1000',
            'course_ids' => 'required|array|min:1',
            'course_ids.*' => 'integer|exists:courses,id',
            'topic_ids' => 'required|array|min:1',
            'topic_ids.*' => 'integer|exists:topics,id',
            'student_ids' => 'required|array|min:1|max:100',
            'student_ids.*' => 'integer|exists:users,id',
            'assignment_mode' => 'required|in:practice,exam,timed_practice',
            'question_count' => 'required|integer|min:1|max:100',
            'time_limit' => 'nullable|integer|min:300|max:7200',
            'deadline' => 'required|date|after:now',
            'max_attempts' => 'nullable|integer|min:1|max:10',
            'passing_score' => 'nullable|integer|min:0|max:100',
            'randomize_questions' => 'nullable|boolean',
            'send_notifications' => 'nullable|boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'title_template.required' => 'Assignment title template is required',
            'course_ids.required' => 'At least one course must be selected',
            'topic_ids.required' => 'At least one topic must be selected',
            'student_ids.required' => 'At least one student must be selected',
            'student_ids.max' => 'Cannot assign to more than 100 students at once',
            'assignment_mode.required' => 'Assignment mode is required',
            'question_count.required' => 'Number of questions is required',
            'deadline.required' => 'Assignment deadline is required',
            'deadline.after' => 'Assignment deadline must be in the future'
        ];
    }
}