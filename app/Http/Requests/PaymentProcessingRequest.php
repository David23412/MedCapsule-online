<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentProcessingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'payment_method' => 'required|string|in:bank_transfer,mobile_money,cash',
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'reference_number' => 'nullable|string|max:100',
            'proof_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
            'notes' => 'nullable|string|max:500'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'payment_method.required' => 'Payment method is required.',
            'payment_method.in' => 'Invalid payment method selected.',
            'amount.required' => 'Payment amount is required.',
            'amount.min' => 'Payment amount must be greater than 0.',
            'amount.max' => 'Payment amount is too large.',
            'proof_image.image' => 'Proof must be a valid image file.',
            'proof_image.max' => 'Proof image must not exceed 5MB.',
            'notes.max' => 'Notes cannot exceed 500 characters.'
        ];
    }

    /**
     * Get validated data with defaults
     */
    public function getValidatedWithDefaults(): array
    {
        $validated = $this->validated();
        
        return array_merge([
            'payment_method' => 'bank_transfer',
            'reference_number' => null,
            'notes' => null,
            'user_id' => auth()->id(),
            'status' => 'pending'
        ], $validated);
    }
}