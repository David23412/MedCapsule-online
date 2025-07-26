<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuizStartRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'sources' => 'nullable|array',
            'sources.*' => 'string|max:255',
            'assignment_id' => 'nullable|integer|exists:assignments,id',
            'mode' => 'nullable|string|in:quiz,tutor,random',
        ];
    }
}