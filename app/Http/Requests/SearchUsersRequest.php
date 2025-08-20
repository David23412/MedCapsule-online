<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchUsersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
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
            'query' => 'required|string|min:2|max:255',
        ];
    }
}
