<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuizAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'topic_id' => 'required|integer|exists:topics,id',
            'assignment_id' => 'nullable|integer|exists:assignments,id',
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => 'required|integer|exists:questions,id',
            'answers.*.selected_answer' => 'required|string|max:500',
            'answers.*.time_spent' => 'nullable|integer|min:0',
            'total_time_spent' => 'required|integer|min:1',
            'quiz_type' => 'required|in:practice,assignment,exam'
        ];
    }

    public function messages(): array
    {
        return [
            'topic_id.required' => 'Topic ID is required',
            'answers.required' => 'Quiz answers are required',
            'answers.min' => 'At least one answer is required',
            'answers.*.question_id.required' => 'Question ID is required for each answer',
            'answers.*.selected_answer.required' => 'Selected answer is required',
            'total_time_spent.required' => 'Total time spent is required'
        ];
    }
}