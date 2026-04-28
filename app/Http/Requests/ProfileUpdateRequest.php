<?php

namespace App\Http\Requests;

use App\Support\IdentityInput;
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
                Rule::unique('users', 'email')->ignore(auth()->id()),
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
            'phone' => ['nullable', 'string', 'size:11', 'regex:/^01[0125][0-9]{8}$/', Rule::unique('users', 'number')->ignore(auth()->id())],
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
            'phone.size' => 'Phone number must be exactly 11 digits',
            'phone.regex' => 'Please enter a valid 11-digit Egyptian mobile number',
            'phone.unique' => 'This phone number is already registered',
            'avatar.mimes' => 'Avatar must be an image (JPG, JPEG, PNG)',
            'avatar.max' => 'Avatar cannot exceed 2MB',
            'profile_picture.mimes' => 'Profile picture must be an image (JPG, JPEG, PNG, GIF)',
            'profile_picture.max' => 'Profile picture cannot exceed 2MB'
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => IdentityInput::normalizeName($this->input('name')),
            'email' => IdentityInput::normalizeEmail($this->input('email')),
            'phone' => $this->filled('phone') ? IdentityInput::normalizeEgyptianPhone($this->input('phone')) : null,
        ]);
    }
}
