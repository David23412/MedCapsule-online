<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RevokeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Assuming only admins can revoke access.
        return auth()->check() && auth()->user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'course_id' => 'required|integer|exists:courses,id',
        ];
    }
}
