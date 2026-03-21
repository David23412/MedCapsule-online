<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuizExecutionRequest extends FormRequest
{
    /**
     * Backward-compatible quiz mode aliases.
     */
    private const QUIZ_MODE_ALIASES = [
        'practice' => 'quiz',
        'exam' => 'quiz',
        'quiz' => 'quiz',
        'tutor' => 'tutor',
        'random' => 'random',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'answers' => 'nullable|array',
            'answers.*' => 'nullable|string|max:1000',
            'time_taken' => 'nullable|integer|min:0|max:7200', // Max 2 hours
            'duration_seconds' => 'nullable|integer|min:0|max:7200',
            'quiz_mode' => 'nullable|string|in:practice,exam,quiz,tutor,random',
            'question_ids' => 'nullable|array',
            'question_ids.*' => 'integer|exists:questions,id',
            'mode' => 'nullable|string|in:practice,exam,quiz,tutor,random',
            'assignment_id' => 'nullable|integer|exists:assignments,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'answers.array' => 'Answers must be provided as an array.',
            'time_taken.integer' => 'Time taken must be a valid number.',
            'time_taken.max' => 'Quiz time cannot exceed 2 hours.',
            'quiz_mode.in' => 'Invalid quiz mode selected.',
            'question_ids.array' => 'Question IDs must be provided as an array.',
            'question_ids.*.exists' => 'One or more question IDs are invalid.'
        ];
    }

    /**
     * Get validated data with defaults
     */
    public function getValidatedWithDefaults(): array
    {
        $validated = $this->validated();

        $incomingMode = $validated['quiz_mode'] ?? $validated['mode'] ?? 'practice';
        $normalizedMode = self::QUIZ_MODE_ALIASES[$incomingMode] ?? 'quiz';
        $duration = $validated['duration_seconds'] ?? $validated['time_taken'] ?? 0;
        
        return array_merge([
            'answers' => [],
            'time_taken' => 0,
            'duration_seconds' => 0,
            'quiz_mode' => 'quiz',
            'mode' => 'quiz',
            'question_ids' => [],
            'user_id' => auth()->id(),
            'started_at' => now()
        ], $validated, [
            'quiz_mode' => $normalizedMode,
            'mode' => $normalizedMode,
            'duration_seconds' => $duration,
            'time_taken' => $duration,
        ]);
    }
}