<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Home page accessible to all
    }

    public function rules(): array
    {
        return [
            'filter' => 'nullable|in:recent,popular,recommended',
            'course_id' => 'nullable|integer|exists:courses,id',
            'difficulty' => 'nullable|in:easy,medium,hard',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:5|max:50'
        ];
    }

    public function messages(): array
    {
        return [
            'course_id.exists' => 'Selected course does not exist',
            'difficulty.in' => 'Invalid difficulty level',
            'search.max' => 'Search term cannot exceed 255 characters',
            'per_page.min' => 'Minimum 5 items per page',
            'per_page.max' => 'Maximum 50 items per page'
        ];
    }
}