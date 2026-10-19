<?php

namespace App\Http\Requests;

use App\Support\IdentityInput;
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
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (IdentityInput::isSuspiciousName((string) $value)) {
                        $fail('Please enter a valid full name.');
                    }
                },
            ],
            'email' => [
                'required',
                'email:rfc',
                'max:255',
                'unique:users,email',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $email = IdentityInput::normalizeEmail((string) $value);

                    if (IdentityInput::hasPlusAlias($email)) {
                        $fail('Plus-address email aliases are not allowed.');
                    }

                    if (IdentityInput::isDisposableEmailDomain($email)) {
                        $fail('Please use a permanent email address.');
                    }
                },
            ],
            'number' => [
                'required',
                'string',
                'size:11',
                'regex:/^01[0125][0-9]{8}$/',
                'unique:users,number',
            ],
            'university' => ['required', 'string', 'max:255', Rule::in([
                'Assiut University',
                'Cairo University',
                'Al Kasr Al Ainy',
                'Alexandria University',
            ])],
            'password' => 'required|string|min:8|max:255|confirmed',
            'password_confirmation' => 'required|string|min:8|max:255',
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
            'number.size' => 'The mobile number must be exactly 11 digits',
            'number.regex' => 'Please enter a valid 11-digit Egyptian mobile number (starting with 010, 011, 012, or 015)',
            'number.unique' => 'This phone number is already registered',
            'university.required' => 'University is required',
            'university.in' => 'Please select a valid university from the list',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'password.confirmed' => 'Password confirmation does not match',
            'terms.accepted' => 'You must accept the terms and conditions'
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => IdentityInput::normalizeName($this->input('name')),
            'email' => IdentityInput::normalizeEmail($this->input('email')),
            'number' => IdentityInput::normalizeEgyptianPhone($this->input('number')),
        ]);
    }
}
