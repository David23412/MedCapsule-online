<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $role = (int) (auth()->user()->role ?? 0);

        return in_array($role, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_TUTOR], true);
    }

    public function rules(): array
    {
        return [
            'topic_id' => 'required|integer|exists:topics,id',
            'question' => 'required|string|max:2000',
            'question_type' => 'required|in:multiple_choice,written',
            'explanation' => 'nullable|string|max:4000',
            'question_image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:2048',
            'image_url' => 'nullable|url|max:2000',
            'option_a' => 'required_if:question_type,multiple_choice|nullable|string|max:1000',
            'option_b' => 'required_if:question_type,multiple_choice|nullable|string|max:1000',
            'option_c' => 'required_if:question_type,multiple_choice|nullable|string|max:1000',
            'option_d' => 'required_if:question_type,multiple_choice|nullable|string|max:1000',
            'option_e' => 'nullable|string|max:1000',
            'correct_answer' => 'required_if:question_type,multiple_choice|nullable|in:A,B,C,D,E',
            'written_correct_answer' => 'required_if:question_type,written|nullable|string|max:2000',
            'alternative_answers' => 'nullable|string|max:4000',
            'source' => 'required|string|max:255',
            'concept_names' => 'nullable|string|max:4000',
        ];
    }

    public function messages(): array
    {
        return [
            'topic_id.required' => 'Topic selection is required',
            'question.required' => 'Question text is required',
            'correct_answer.required_if' => 'Correct answer is required for multiple choice questions',
            'written_correct_answer.required_if' => 'Correct answer is required for written questions',
        ];
    }
}
