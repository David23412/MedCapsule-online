<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignmentCreateRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isTutor());
    }

    public function rules()
    {
        return [
            'course_id' => 'required|integer|exists:courses,id',
            'topic_id' => 'required|integer|exists:topics,id',
            'title' => 'required|string|max:255',
            'assignment_mode' => 'required|string|in:complete_topic,source_specific',
            'assigned_source' => 'nullable|string|max:255',
            'deadline' => 'required|date|after:now',
            'duration_minutes' => 'nullable|numeric|min:1|max:1440',
        ];
    }
}
