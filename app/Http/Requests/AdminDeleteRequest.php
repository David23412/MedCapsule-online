<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminDeleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'resource_type' => 'required|in:course,topic,question,user,payment',
            'resource_id' => 'required|integer|min:1',
            'confirm_delete' => 'required|boolean|accepted',
            'cascade_delete' => 'nullable|boolean',
            'backup_before_delete' => 'nullable|boolean',
            'deletion_reason' => 'nullable|string|max:500',
            'notify_affected_users' => 'nullable|boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'resource_type.required' => 'Resource type is required',
            'resource_type.in' => 'Invalid resource type',
            'resource_id.required' => 'Resource ID is required',
            'resource_id.min' => 'Resource ID must be greater than 0',
            'confirm_delete.accepted' => 'You must confirm this deletion'
        ];
    }
}