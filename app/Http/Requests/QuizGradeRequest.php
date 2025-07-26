<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuizGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'answers' => 'required|array|min:1|max:100',
            'answers.*' => 'nullable|string|max:2000',
            'time_taken' => 'required|integer|min:1|max:86400',
            'mode' => 'nullable|string|in:quiz,tutor,random',
            'assignment_id' => 'nullable|integer|exists:assignments,id',
            'topic_id' => 'required|integer|exists:topics,id',
            'course_id' => 'required|integer|exists:courses,id',
            'quiz_type' => 'nullable|in:practice,assignment,exam'
        ];
    }

    public function messages(): array
    {
        return [
            'answers.required' => 'Quiz answers are required',
            'answers.min' => 'At least one answer is required',
            'answers.max' => 'Cannot submit more than 100 answers',
            'time_taken.required' => 'Time taken is required',
            'time_taken.min' => 'Time taken must be at least 1 second',
            'time_taken.max' => 'Time taken cannot exceed 24 hours',
            'topic_id.required' => 'Topic ID is required',
            'topic_id.exists' => 'Selected topic does not exist',
            'course_id.required' => 'Course ID is required',
            'course_id.exists' => 'Selected course does not exist'
        ];
    }
}