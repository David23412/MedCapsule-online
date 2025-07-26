<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuizGenerationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request - LETHAL OPTIMIZED
     */
    public function rules(): array
    {
        return [
            'source' => 'nullable|string|max:255',
            'question_count' => 'integer|min:1|max:50',
            'difficulty' => 'nullable|in:easy,medium,hard',
            'time_limit' => 'nullable|integer|min:60|max:3600',
            'topic_id' => 'required|integer|exists:topics,id',
            'quiz_type' => 'nullable|in:practice,assignment,exam',
            'randomize_questions' => 'nullable|boolean',
            'randomize_answers' => 'nullable|boolean',
            'show_correct_answers' => 'nullable|boolean',
            'allow_review' => 'nullable|boolean',
            'cache_duration' => 'nullable|integer|min:300|max:7200'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'question_count.min' => 'Quiz must have at least 1 question.',
            'question_count.max' => 'Quiz cannot have more than 50 questions.',
            'time_limit.min' => 'Time limit must be at least 60 seconds.',
            'time_limit.max' => 'Time limit cannot exceed 1 hour.'
        ];
    }

    /**
     * Get validated data with defaults - LETHAL OPTIMIZED
     */
    public function getValidatedWithDefaults(): array
    {
        $validated = $this->validated();
        
        return array_merge([
            'source' => null,
            'question_count' => 10,
            'difficulty' => 'medium',
            'time_limit' => 1800,
            'quiz_type' => 'practice',
            'randomize_questions' => true,
            'randomize_answers' => true,
            'show_correct_answers' => true,
            'allow_review' => true,
            'cache_duration' => 3600,
            'user_id' => auth()->id(),
            'generated_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ], $validated);
    }

    /**
     * LETHAL OPTIMIZATION: Get cache key for quiz generation
     */
    public function getCacheKey(): string
    {
        $validated = $this->validated();
        
        return 'quiz_generation_' . md5(serialize([
            'topic_id' => $validated['topic_id'] ?? null,
            'question_count' => $validated['question_count'] ?? 10,
            'difficulty' => $validated['difficulty'] ?? 'medium',
            'quiz_type' => $validated['quiz_type'] ?? 'practice',
            'user_id' => auth()->id(),
            'date' => now()->format('Y-m-d-H')
        ]));
    }

    /**
     * LETHAL OPTIMIZATION: Check if request should use cached result
     */
    public function shouldUseCache(): bool
    {
        return ($this->input('quiz_type') === 'practice') && 
               ($this->input('cache_duration', 3600) > 0);
    }
}