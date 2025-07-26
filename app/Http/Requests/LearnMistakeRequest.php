<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LearnMistakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'course' => 'nullable|integer|exists:courses,id',
            'difficulty' => 'nullable|in:easy,medium,hard',
            'limit' => 'nullable|integer|min:1|max:100',
            'sort_by' => 'nullable|in:recent,difficulty,frequency',
            'include_mastered' => 'nullable|boolean',
            'topic_ids' => 'nullable|array',
            'topic_ids.*' => 'integer|exists:topics,id'
        ];
    }

    public function messages(): array
    {
        return [
            'course.exists' => 'Selected course does not exist',
            'difficulty.in' => 'Invalid difficulty level',
            'limit.min' => 'Minimum 1 mistake',
            'limit.max' => 'Maximum 100 mistakes',
            'topic_ids.*.exists' => 'One or more topics do not exist'
        ];
    }

    public function getValidatedWithDefaults(): array
    {
        $validated = $this->validated();
        
        return array_merge([
            'limit' => 20,
            'sort_by' => 'recent',
            'include_mastered' => false,
            'user_id' => auth()->id()
        ], $validated);
    }
}