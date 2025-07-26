<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('tutor'));
    }

    public function rules(): array
    {
        return [
            'operation' => 'required|in:delete,activate,deactivate,assign,unassign,grade',
            'resource_type' => 'required|in:users,courses,topics,questions,assignments',
            'resource_ids' => 'required|array|min:1|max:100',
            'resource_ids.*' => 'integer|min:1',
            'additional_data' => 'nullable|array',
            'confirm_operation' => 'required|boolean|accepted',
            'backup_before_operation' => 'nullable|boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'operation.required' => 'Operation type is required',
            'resource_type.required' => 'Resource type is required',
            'resource_ids.required' => 'At least one resource must be selected',
            'resource_ids.min' => 'At least one resource must be selected',
            'resource_ids.max' => 'Cannot process more than 100 resources at once',
            'confirm_operation.accepted' => 'You must confirm this bulk operation'
        ];
    }
}