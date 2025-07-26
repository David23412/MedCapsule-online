<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CoursePreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Course preview accessible to all
    }

    public function rules(): array
    {
        return [
            'type' => 'nullable|in:medical,technical,general',
            'include_stats' => 'nullable|boolean',
            'include_topics' => 'nullable|boolean',
            'include_questions' => 'nullable|boolean',
            'format' => 'nullable|in:json,html,xml'
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Invalid course type',
            'format.in' => 'Invalid format type'
        ];
    }

    public function getValidatedWithDefaults(): array
    {
        $validated = $this->validated();
        
        return array_merge([
            'type' => 'general',
            'include_stats' => true,
            'include_topics' => true,
            'include_questions' => false,
            'format' => 'json'
        ], $validated);
    }
}