<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuizTutorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'course_id' => 'required|integer|exists:courses,id',
            'topic_id' => 'required|integer|exists:topics,id',
            'question_count' => 'nullable|integer|min:1|max:50',
            'difficulty' => 'nullable|in:easy,medium,hard,mixed',
            'time_limit' => 'nullable|integer|min:300|max:7200',
            'tutor_mode' => 'nullable|in:guided,practice,review',
            'show_explanations' => 'nullable|boolean',
            'randomize_questions' => 'nullable|boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'course_id.required' => 'Course selection is required',
            'course_id.exists' => 'Selected course does not exist',
            'topic_id.required' => 'Topic selection is required',
            'topic_id.exists' => 'Selected topic does not exist',
            'question_count.min' => 'Must have at least 1 question',
            'question_count.max' => 'Cannot exceed 50 questions',
            'time_limit.min' => 'Time limit must be at least 5 minutes',
            'time_limit.max' => 'Time limit cannot exceed 2 hours'
        ];
    }
}