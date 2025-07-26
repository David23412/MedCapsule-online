<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('student');
    }

    public function rules(): array
    {
        return [
            'assignment_id' => 'required|integer|exists:assignments,id',
            'action' => 'required|in:start,submit,complete,view',
            'answers' => 'required_if:action,submit|array',
            'answers.*' => 'nullable|string|max:1000',
            'time_spent' => 'nullable|integer|min:0|max:86400',
            'notes' => 'nullable|string|max:500'
        ];
    }

    public function messages(): array
    {
        return [
            'assignment_id.required' => 'Assignment ID is required',
            'assignment_id.exists' => 'Assignment does not exist',
            'action.required' => 'Action is required',
            'action.in' => 'Invalid action',
            'answers.required_if' => 'Answers are required when submitting',
            'time_spent.max' => 'Time spent cannot exceed 24 hours'
        ];
    }
}