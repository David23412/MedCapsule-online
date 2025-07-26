<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignmentCreateRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && in_array(auth()->user()->role, ['admin', 'tutor']);
    }

    public function rules()
    {
        return [
            'student_id' => 'required|integer|exists:users,id',
            'course_id' => 'required|integer|exists:courses,id',
            'topic_id' => 'required|integer|exists:topics,id',
            'assignment_mode' => 'required|string|in:topic_practice,source_specific',
            'assigned_source' => 'nullable|string|max:255',
            'deadline' => 'nullable|date|after:now',
            'duration_minutes' => 'nullable|integer|min:1|max:1440',
            'instructions' => 'nullable|string|max:1000',
        ];
    }
}