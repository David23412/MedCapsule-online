<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class QuizExecutionRequest extends FormRequest
{
    private const MAX_SINGLE_ANSWER_BYTES = 50_000;
    private const MAX_TOTAL_ANSWER_BYTES = 1_000_000;
    private const PAYLOAD_ERROR_PREFIX = '__payload_too_large__';

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
            'answers' => [
                'nullable',
                'array',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $this->validateAnswersSize($value, $fail);
                },
            ],
            'answers.*' => [
                'nullable',
                'string',
                'max:' . self::MAX_SINGLE_ANSWER_BYTES,
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $this->validateAnswerContent($value, $fail);
                },
            ],
            'time_taken' => 'nullable|integer|min:0|max:7200', // Max 2 hours
            'duration_seconds' => 'nullable|integer|min:0|max:7200',
            'quiz_mode' => 'nullable|string|in:practice,exam,quiz,tutor,random',
            'question_ids' => 'nullable|array',
            'question_ids.*' => 'integer|exists:questions,id',
            'mode' => 'nullable|string|in:practice,exam,quiz,tutor,random',
            'assignment_id' => 'nullable|integer|exists:assignments,id',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $answers = $this->input('answers', []);
            $duration = (int) ($this->input('duration_seconds', $this->input('time_taken', 0)));

            if (is_array($answers) && $duration > 0 && count($answers) > 0 && $duration < count($answers)) {
                $validator->errors()->add('time_taken', 'Time taken is too short for the number of questions.');
            }
        });
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

    protected function failedValidation(Validator $validator): void
    {
        $errors = collect($validator->errors()->toArray());
        $status = 422;

        if ($errors->flatten()->contains(fn (string $message) => str_starts_with($message, self::PAYLOAD_ERROR_PREFIX))) {
            $status = 413;
            $errors = $errors->map(fn (array $messages) => array_map(
                fn (string $message): string => str_replace(self::PAYLOAD_ERROR_PREFIX, '', $message),
                $messages
            ));
        }

        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'error' => $status === 413 ? 'Request payload too large.' : 'Invalid quiz submission data.',
                'validation_errors' => $errors->toArray(),
            ], $status)
        );
    }

    protected function prepareForValidation(): void
    {
        $answers = $this->input('answers', []);

        if (is_array($answers)) {
            $sanitizedAnswers = [];

            foreach ($answers as $questionId => $answer) {
                if (! is_numeric($questionId)) {
                    continue;
                }

                $sanitizedAnswers[$questionId] = is_string($answer) ? trim($answer) : $answer;
            }

            $this->merge(['answers' => $sanitizedAnswers]);
        }
    }

    private function validateAnswersSize(mixed $answers, \Closure $fail): void
    {
        if (! is_array($answers)) {
            return;
        }

        $totalSize = 0;

        foreach ($answers as $questionId => $answer) {
            if (! is_string($answer)) {
                continue;
            }

            $answerSize = strlen($answer);
            $totalSize += $answerSize;

            if ($answerSize > self::MAX_SINGLE_ANSWER_BYTES) {
                $fail(self::PAYLOAD_ERROR_PREFIX . "Answer for question {$questionId} is too large.");
                return;
            }
        }

        if ($totalSize > self::MAX_TOTAL_ANSWER_BYTES) {
            $fail(self::PAYLOAD_ERROR_PREFIX . 'Total submission size too large.');
        }
    }

    private function validateAnswerContent(mixed $answer, \Closure $fail): void
    {
        if (! is_string($answer) || $answer === '') {
            return;
        }

        if (strpos($answer, "\0") !== false) {
            $fail('Answer contains invalid characters.');
            return;
        }

        if (preg_match('/<script[^>]*>.*?<\/script>/is', $answer)) {
            $fail('Answer contains potentially dangerous content.');
        }
    }
}
