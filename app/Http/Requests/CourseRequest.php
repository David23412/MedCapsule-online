<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('tutor'));
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0|max:9999.99',
            'is_active' => 'boolean',
            'category_id' => 'nullable|integer|exists:categories,id',
            'difficulty_level' => 'nullable|in:beginner,intermediate,advanced',
            'estimated_duration' => 'nullable|integer|min:1|max:1000'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Course name is required',
            'price.required' => 'Course price is required',
            'price.numeric' => 'Price must be a valid number',
            'price.min' => 'Price cannot be negative',
            'price.max' => 'Price cannot exceed $9,999.99'
        ];
    }
}