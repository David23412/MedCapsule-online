<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentProgressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isTutor());
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'date_range' => 'nullable|string|in:week,month,quarter,year',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'integer|exists:courses,id',
            'include_details' => 'boolean',
            'metrics' => 'nullable|array',
            'metrics.*' => 'string|in:quiz_scores,time_spent,completion_rate,accuracy',
            'format' => 'nullable|string|in:json,csv,pdf'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'date_range.in' => 'Invalid date range. Must be week, month, quarter, or year.',
            'course_ids.array' => 'Course IDs must be provided as an array.',
            'course_ids.*.exists' => 'One or more course IDs are invalid.',
            'metrics.array' => 'Metrics must be provided as an array.',
            'metrics.*.in' => 'Invalid metric type selected.',
            'format.in' => 'Invalid format. Must be json, csv, or pdf.'
        ];
    }

    /**
     * Get validated data with defaults
     */
    public function getValidatedWithDefaults(): array
    {
        $validated = $this->validated();
        
        return array_merge([
            'date_range' => 'month',
            'course_ids' => [],
            'include_details' => true,
            'metrics' => ['quiz_scores', 'completion_rate'],
            'format' => 'json',
            'tutor_id' => auth()->id()
        ], $validated);
    }
}