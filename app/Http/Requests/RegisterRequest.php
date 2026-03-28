<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'number' => ['required', 'string', 'min:4', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'university' => ['required', 'string', 'max:255', Rule::in([
                'Assiut University',
                'Cairo University',
                'Al Kasr Al Ainy',
                'Alexandria University',
            ])],
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
            'role' => 'nullable|in:student,tutor',
            'terms' => 'required|accepted'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required',
            'email.required' => 'Email is required',
            'email.unique' => 'This email is already registered',
            'number.required' => 'Number is required',
            'number.min' => 'Number must be at least 4 characters',
            'number.regex' => 'Number may only contain digits, spaces, parentheses, plus signs, or hyphens',
            'university.required' => 'University is required',
            'university.in' => 'Please select a valid university from the list',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'password.confirmed' => 'Password confirmation does not match',
            'terms.accepted' => 'You must accept the terms and conditions'
        ];
    }
}
