<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TutorAnswerRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'answer' => 'required|string|max:1000',
        ];
    }
}