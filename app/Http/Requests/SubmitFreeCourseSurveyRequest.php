<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitFreeCourseSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'answers' => ['required', 'array'],
            'redirect_to' => ['nullable', 'string', 'max:2048'],
        ];

        foreach (config('free_course_survey.questions', []) as $question) {
            $questionId = $question['id'];
            $requiredRule = ($question['required'] ?? false) ? 'required' : 'nullable';

            if (($question['type'] ?? null) === 'single_choice') {
                $rules["answers.{$questionId}"] = [$requiredRule, Rule::in(array_keys($question['options'] ?? []))];
                continue;
            }

            $rules["answers.{$questionId}"] = [$requiredRule, 'string', 'max:' . ($question['max_length'] ?? 1000)];
        }

        return $rules;
    }
}
