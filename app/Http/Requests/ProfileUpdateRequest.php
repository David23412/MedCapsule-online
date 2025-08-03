<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore(auth()->id())],
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:1000',
            'avatar' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'profile_picture' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:2048',
            'timezone' => 'nullable|string|max:50',
            'language' => 'nullable|in:en,fr,es,de',
            'notifications_enabled' => 'boolean',
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required',
            'email.required' => 'Email is required',
            'email.unique' => 'This email is already taken',
            'avatar.mimes' => 'Avatar must be an image (JPG, JPEG, PNG)',
            'avatar.max' => 'Avatar cannot exceed 2MB',
            'profile_picture.mimes' => 'Profile picture must be an image (JPG, JPEG, PNG, GIF)',
            'profile_picture.max' => 'Profile picture cannot exceed 2MB'
        ];
    }
}