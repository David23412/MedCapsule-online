<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('tutor'));
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'course_id' => 'required|integer|exists:courses,id',
            'order_index' => 'nullable|integer|min:0',
            'difficulty_level' => 'nullable|in:easy,medium,hard',
            'estimated_time' => 'nullable|integer|min:1|max:480'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Topic name is required',
            'course_id.required' => 'Course selection is required',
            'course_id.exists' => 'Selected course does not exist'
        ];
    }
}
