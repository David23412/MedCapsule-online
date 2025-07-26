<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RandomQuizRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'integer|exists:courses,id',
            'question_count' => 'nullable|integer|min:1|max:50',
            'difficulty' => 'nullable|string|in:easy,medium,hard',
            'sources' => 'nullable|array',
            'sources.*' => 'string|max:255',
        ];
    }

    public function getValidatedWithDefaults()
    {
        $validated = $this->validated();
        
        return array_merge([
            'user_id' => auth()->id(),
            'course_ids' => [],
            'question_count' => 10,
            'difficulty' => 'medium',
            'sources' => ['all'],
        ], $validated);
    }
}