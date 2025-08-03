<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string',
            'email' => 'required|email',
        ];
    }
    
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);
        
        // If no key is specified, return all validated data
        if ($key === null) {
            return $validated;
        }
        
        // If key is specified, return that specific value or default
        return $validated[$key] ?? $default;
    }
}