<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MistakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'question_id' => 'required|integer|exists:questions,id',
            'user_answer' => 'required|string|max:500',
            'correct_answer' => 'required|string|max:500',
            'explanation' => 'nullable|string|max:1000',
            'difficulty' => 'required|in:easy,medium,hard',
            'topic_id' => 'required|integer|exists:topics,id',
            'quiz_attempt_id' => 'nullable|integer|exists:quiz_attempts,id'
        ];
    }

    public function messages(): array
    {
        return [
            'question_id.required' => 'Question ID is required',
            'user_answer.required' => 'User answer is required',
            'correct_answer.required' => 'Correct answer is required',
            'difficulty.required' => 'Difficulty level is required',
            'topic_id.required' => 'Topic ID is required'
        ];
    }
}