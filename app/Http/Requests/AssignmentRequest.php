<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * LETHAL ASSIGNMENT REQUEST - MAXIMUM VALIDATION EFFICIENCY
 */
class AssignmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && (
            auth()->user()->hasRole('tutor') || 
            auth()->user()->hasRole('admin')
        );
    }

    /**
     * Get the validation rules that apply to the request - LETHAL OPTIMIZED
     */
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'topic_id' => 'required|integer|exists:topics,id',
            'course_id' => 'required|integer|exists:courses,id',
            'assignment_mode' => 'required|in:practice,exam,timed_practice',
            'question_count' => 'required|integer|min:1|max:100',
            'time_limit' => 'nullable|integer|min:300|max:7200',
            'deadline' => 'required|date|after:now',
            'max_attempts' => 'nullable|integer|min:1|max:10',
            'passing_score' => 'nullable|integer|min:0|max:100',
            'randomize_questions' => 'nullable|boolean',
            'randomize_answers' => 'nullable|boolean',
            'show_results_immediately' => 'nullable|boolean',
            'allow_review_after_completion' => 'nullable|boolean',
            'difficulty_level' => 'nullable|in:easy,medium,hard,mixed',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'integer|exists:users,id',
            'auto_grade' => 'nullable|boolean',
            'send_notifications' => 'nullable|boolean'
        ];

        // LETHAL OPTIMIZATION: Conditional validation based on assignment mode
        if ($this->input('assignment_mode') === 'timed_practice' || $this->input('assignment_mode') === 'exam') {
            $rules['time_limit'] = 'required|integer|min:300|max:7200';
        }

        if ($this->input('assignment_mode') === 'exam') {
            $rules['passing_score'] = 'required|integer|min:50|max:100';
            $rules['max_attempts'] = 'required|integer|min:1|max:3';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Assignment title is required',
            'title.max' => 'Assignment title cannot exceed 255 characters',
            'topic_id.required' => 'Please select a topic for this assignment',
            'topic_id.exists' => 'Selected topic does not exist',
            'course_id.required' => 'Please select a course for this assignment',
            'course_id.exists' => 'Selected course does not exist',
            'assignment_mode.required' => 'Please select an assignment mode',
            'assignment_mode.in' => 'Invalid assignment mode selected',
            'question_count.required' => 'Number of questions is required',
            'question_count.min' => 'Assignment must have at least 1 question',
            'question_count.max' => 'Assignment cannot have more than 100 questions',
            'time_limit.min' => 'Time limit must be at least 5 minutes',
            'time_limit.max' => 'Time limit cannot exceed 2 hours',
            'deadline.required' => 'Assignment deadline is required',
            'deadline.after' => 'Assignment deadline must be in the future',
            'max_attempts.min' => 'Must allow at least 1 attempt',
            'max_attempts.max' => 'Cannot allow more than 10 attempts',
            'passing_score.min' => 'Passing score cannot be less than 0%',
            'passing_score.max' => 'Passing score cannot exceed 100%',
            'student_ids.array' => 'Student selection must be an array',
            'student_ids.*.exists' => 'One or more selected students do not exist'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'topic_id' => 'topic',
            'course_id' => 'course',
            'assignment_mode' => 'assignment type',
            'question_count' => 'number of questions',
            'time_limit' => 'time limit',
            'max_attempts' => 'maximum attempts',
            'passing_score' => 'passing score',
            'student_ids' => 'selected students',
            'difficulty_level' => 'difficulty level'
        ];
    }

    /**
     * LETHAL OPTIMIZATION: Get validated data with intelligent defaults
     */
    public function getValidatedWithDefaults(): array
    {
        $validated = $this->validated();
        
        $defaults = [
            'description' => '',
            'time_limit' => $this->getDefaultTimeLimit(),
            'max_attempts' => $this->getDefaultMaxAttempts(),
            'passing_score' => $this->getDefaultPassingScore(),
            'randomize_questions' => true,
            'randomize_answers' => true,
            'show_results_immediately' => $this->shouldShowResultsImmediately(),
            'allow_review_after_completion' => true,
            'difficulty_level' => 'medium',
            'auto_grade' => true,
            'send_notifications' => true,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'status' => 'active'
        ];

        return array_merge($defaults, $validated);
    }

    /**
     * LETHAL OPTIMIZATION: Get default time limit based on assignment mode
     */
    private function getDefaultTimeLimit(): ?int
    {
        return match($this->input('assignment_mode')) {
            'exam' => 3600, // 1 hour for exams
            'timed_practice' => 1800, // 30 minutes for timed practice
            default => null // No time limit for regular practice
        };
    }

    /**
     * LETHAL OPTIMIZATION: Get default max attempts based on assignment mode
     */
    private function getDefaultMaxAttempts(): int
    {
        return match($this->input('assignment_mode')) {
            'exam' => 1, // Single attempt for exams
            'timed_practice' => 3, // 3 attempts for timed practice
            default => 5 // 5 attempts for regular practice
        };
    }

    /**
     * LETHAL OPTIMIZATION: Get default passing score based on assignment mode
     */
    private function getDefaultPassingScore(): int
    {
        return match($this->input('assignment_mode')) {
            'exam' => 70, // 70% for exams
            'timed_practice' => 60, // 60% for timed practice
            default => 50 // 50% for regular practice
        };
    }

    /**
     * LETHAL OPTIMIZATION: Determine if results should be shown immediately
     */
    private function shouldShowResultsImmediately(): bool
    {
        return $this->input('assignment_mode') !== 'exam';
    }

    /**
     * LETHAL OPTIMIZATION: Get cache key for assignment
     */
    public function getCacheKey(): string
    {
        return 'assignment_' . md5(serialize([
            'topic_id' => $this->input('topic_id'),
            'course_id' => $this->input('course_id'),
            'assignment_mode' => $this->input('assignment_mode'),
            'question_count' => $this->input('question_count'),
            'created_by' => auth()->id(),
            'timestamp' => now()->format('Y-m-d-H')
        ]));
    }
}