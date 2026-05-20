<?php

namespace App\Http\Requests;

use App\Support\IdentityInput;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|max:255',
            'password' => 'required|string|max:255',
            'remember' => 'nullable|boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email is required',
            'email.email' => 'Please enter a valid email address',
            'password.required' => 'Password is required'
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => IdentityInput::normalizeEmail($this->input('email')),
        ]);
    }
}
