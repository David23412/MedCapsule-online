<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CourseActionRequest extends FormRequest
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
    
    public function validated()
    {
        return [
            'name' => 'Test',
            'email' => 'test@example.com'
        ];
    }
}