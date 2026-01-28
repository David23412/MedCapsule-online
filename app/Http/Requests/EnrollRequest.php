<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;

class EnrollRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * FIXED: Check for integer role (1 = admin)
     *
     * @return bool
     */
    public function authorize()
    {
        // FIXED: Only admins can enroll users - check integer role
        return auth()->check() && auth()->user()->role === User::ROLE_ADMIN;
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
