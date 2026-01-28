<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;

class SearchUsersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * FIXED: Check for integer role (1 = admin)
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check() && auth()->user()->role === User::ROLE_ADMIN;
    }

    /**
     * Get the validation rules that apply to the request.
     * FIXED: Allow empty query to return all users
     *
     * @return array
     */
    public function rules()
    {
        return [
            'query' => 'nullable|string|max:255',
        ];
    }
}
