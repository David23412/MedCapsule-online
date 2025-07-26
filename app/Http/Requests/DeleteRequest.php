<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * LETHAL DELETE REQUEST - MAXIMUM VALIDATION EFFICIENCY
 */
class DeleteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id' => 'required|integer|min:1',
            'confirm_delete' => 'sometimes|boolean',
            'cascade_delete' => 'sometimes|boolean',
            'backup_before_delete' => 'sometimes|boolean'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'id.required' => 'Resource ID is required for deletion',
            'id.integer' => 'Resource ID must be a valid integer',
            'id.min' => 'Resource ID must be greater than 0'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'id' => 'resource identifier',
            'confirm_delete' => 'deletion confirmation',
            'cascade_delete' => 'cascade deletion option',
            'backup_before_delete' => 'backup option'
        ];
    }
}