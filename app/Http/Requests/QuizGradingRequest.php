<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class QuizGradingRequest extends FormRequest
{
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
                'required',
                'array',
                'max:100', // Maximum 100 questions per quiz
                function ($attribute, $value, $fail) {
                    $this->validateAnswersSize($value, $fail);
                }
            ],
            'answers.*' => [
                'nullable',
                'string',
                'max:10000', // Maximum 10KB per answer to prevent memory exhaustion
                function ($attribute, $value, $fail) {
                    $this->validateAnswerContent($value, $fail);
                }
            ],
            'time_taken' => [
                'required',
                'integer',
                'min:0',
                'max:86400' // Maximum 24 hours
            ],
            'mode' => [
                'nullable',
                'string',
                Rule::in(['quiz', 'tutor', 'random'])
            ]
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'answers.required' => 'Quiz answers are required.',
            'answers.array' => 'Answers must be provided in the correct format.',
            'answers.max' => 'Too many questions submitted. Maximum 100 questions allowed.',
            'answers.*.max' => 'Answer too long. Maximum 10,000 characters allowed per answer.',
            'time_taken.required' => 'Time taken is required.',
            'time_taken.integer' => 'Time taken must be a valid number.',
            'time_taken.min' => 'Time taken cannot be negative.',
            'time_taken.max' => 'Time taken cannot exceed 24 hours.',
            'mode.in' => 'Invalid quiz mode. Must be quiz, tutor, or random.'
        ];
    }

    /**
     * Validate the total size of all answers to prevent memory exhaustion
     */
    protected function validateAnswersSize($answers, $fail): void
    {
        if (!is_array($answers)) {
            $fail('Answers must be an array.');
            return;
        }

        $totalSize = 0;
        $questionCount = 0;

        foreach ($answers as $questionId => $answer) {
            // Validate question ID
            if (!is_numeric($questionId) || $questionId <= 0) {
                $fail("Invalid question ID: {$questionId}");
                return;
            }

            // Check individual answer size
            if (is_string($answer)) {
                $answerSize = strlen($answer);
                $totalSize += $answerSize;

                // Check for excessively large individual answers
                if ($answerSize > 50000) { // 50KB limit
                    $fail("Answer for question {$questionId} is too large. Maximum 50KB allowed.");
                    return;
                }
            }

            $questionCount++;

            // Prevent too many questions
            if ($questionCount > 100) {
                $fail('Too many questions submitted. Maximum 100 questions allowed.');
                return;
            }
        }

        // Check total payload size (prevent DoS through large submissions)
        if ($totalSize > 1000000) { // 1MB total limit
            $fail('Total submission size too large. Maximum 1MB allowed for all answers combined.');
            return;
        }

        Log::info('Quiz validation passed', [
            'total_size_bytes' => $totalSize,
            'question_count' => $questionCount,
            'user_id' => auth()->id()
        ]);
    }

    /**
     * Validate individual answer content for security and safety
     */
    protected function validateAnswerContent($answer, $fail): void
    {
        if (!is_string($answer)) {
            return; // Non-string answers are handled by other rules
        }

        // Check for null bytes (potential security issue)
        if (strpos($answer, "\0") !== false) {
            $fail('Answer contains invalid characters.');
            return;
        }

        // Check for extremely long words (potential buffer overflow)
        $words = explode(' ', $answer);
        foreach ($words as $word) {
            if (strlen($word) > 1000) {
                $fail('Answer contains words that are too long.');
                return;
            }
        }

        // Check for potential script injection (basic check)
        if (preg_match('/<script[^>]*>.*?<\/script>/i', $answer)) {
            $fail('Answer contains potentially dangerous content.');
            return;
        }

        // Check for excessive repetition (potential DoS)
        if (preg_match('/(.{10,})\1{50,}/', $answer)) {
            $fail('Answer contains excessive repetition.');
            return;
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Additional cross-field validation
            $this->validateRequestIntegrity($validator);
        });
    }

    /**
     * Validate overall request integrity
     */
    protected function validateRequestIntegrity(Validator $validator): void
    {
        $data = $this->all();

        // Check if time_taken is reasonable for the number of questions
        if (isset($data['answers']) && isset($data['time_taken'])) {
            $questionCount = count($data['answers']);
            $timeTaken = $data['time_taken'];

            // Minimum 1 second per question (prevent instant submissions)
            if ($timeTaken < $questionCount) {
                $validator->errors()->add('time_taken', 'Time taken is too short for the number of questions.');
            }

            // Maximum 1 hour per question (prevent session hijacking)
            if ($timeTaken > $questionCount * 3600) {
                $validator->errors()->add('time_taken', 'Time taken is too long for the number of questions.');
            }
        }

        // Log suspicious activity
        if ($validator->errors()->any()) {
            Log::warning('Quiz validation failed', [
                'user_id' => auth()->id(),
                'errors' => $validator->errors()->toArray(),
                'request_data' => [
                    'answer_count' => isset($data['answers']) ? count($data['answers']) : 0,
                    'time_taken' => $data['time_taken'] ?? null,
                    'mode' => $data['mode'] ?? null,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]
            ]);
        }
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors();

        // Log the failed validation for security monitoring
        Log::warning('Quiz grading validation failed', [
            'user_id' => auth()->id(),
            'errors' => $errors->toArray(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'request_size' => strlen(json_encode($this->all()))
        ]);

        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'error' => 'Invalid quiz submission data.',
                'validation_errors' => $errors->toArray()
            ], 422)
        );
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize and prepare data before validation
        $answers = $this->input('answers', []);
        
        if (is_array($answers)) {
            // Remove any non-string, non-null values from answers
            $sanitizedAnswers = [];
            foreach ($answers as $questionId => $answer) {
                if (is_numeric($questionId) && ($answer === null || is_string($answer))) {
                    $sanitizedAnswers[$questionId] = $answer;
                }
            }
            
            $this->merge(['answers' => $sanitizedAnswers]);
        }

        // Ensure time_taken is numeric
        if ($this->has('time_taken') && !is_numeric($this->input('time_taken'))) {
            $this->merge(['time_taken' => 0]);
        }

        // Ensure mode is a string
        if ($this->has('mode') && !is_string($this->input('mode'))) {
            $this->merge(['mode' => 'quiz']);
        }
    }
}