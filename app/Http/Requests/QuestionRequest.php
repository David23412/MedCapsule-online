<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('tutor'));
    }

    public function rules(): array
    {
        return [
            'question_text' => 'required|string|max:2000',
            'topic_id' => 'required|integer|exists:topics,id',
            'difficulty' => 'required|in:easy,medium,hard',
            'question_type' => 'required|in:multiple_choice,true_false,fill_blank',
            'correct_answer' => 'required|string|max:500',
            'wrong_answers' => 'required|array|min:1|max:4',
            'wrong_answers.*' => 'required|string|max:500',
            'explanation' => 'nullable|string|max:1000',
            'points' => 'nullable|integer|min:1|max:10'
        ];
    }

    public function messages(): array
    {
        return [
            'question_text.required' => 'Question text is required',
            'topic_id.required' => 'Topic selection is required',
            'correct_answer.required' => 'Correct answer is required',
            'wrong_answers.required' => 'Wrong answers are required',
            'wrong_answers.min' => 'At least one wrong answer is required'
        ];
    }
}