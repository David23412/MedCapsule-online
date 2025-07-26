<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TopicLockingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('tutor');
    }

    public function rules(): array
    {
        return [
            'topic_id' => 'required|integer|exists:topics,id',
            'lock_reason' => 'nullable|string|max:500',
            'lock_duration' => 'nullable|integer|min:1|max:168', // Max 1 week in hours
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'integer|exists:users,id',
            'notify_students' => 'boolean',
            'allow_practice_mode' => 'boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'topic_id.required' => 'Topic selection is required',
            'topic_id.exists' => 'Selected topic does not exist',
            'lock_duration.max' => 'Lock duration cannot exceed 1 week',
            'student_ids.*.exists' => 'One or more students do not exist'
        ];
    }
}