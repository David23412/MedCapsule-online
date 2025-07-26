<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OptimizedRandomQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'question_count' => 'nullable|integer|min:1|max:50',
            'difficulty' => 'nullable|in:easy,medium,hard,mixed',
            'time_limit' => 'nullable|integer|min:300|max:7200',
            'course_ids' => 'nullable|array|max:10',
            'course_ids.*' => 'integer|exists:courses,id',
            'topic_ids' => 'nullable|array|max:20',
            'topic_ids.*' => 'integer|exists:topics,id',
            'exclude_mastered' => 'nullable|boolean',
            'focus_weak_areas' => 'nullable|boolean',
            'randomize_order' => 'nullable|boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'question_count.min' => 'Must have at least 1 question',
            'question_count.max' => 'Cannot exceed 50 questions',
            'time_limit.min' => 'Time limit must be at least 5 minutes',
            'time_limit.max' => 'Time limit cannot exceed 2 hours',
            'course_ids.max' => 'Cannot select more than 10 courses',
            'course_ids.*.exists' => 'One or more courses do not exist',
            'topic_ids.max' => 'Cannot select more than 20 topics',
            'topic_ids.*.exists' => 'One or more topics do not exist'
        ];
    }

    public function getValidatedWithDefaults(): array
    {
        $validated = $this->validated();
        
        return array_merge([
            'question_count' => 10,
            'difficulty' => 'mixed',
            'time_limit' => null,
            'exclude_mastered' => false,
            'focus_weak_areas' => true,
            'randomize_order' => true,
            'user_id' => auth()->id()
        ], $validated);
    }
}