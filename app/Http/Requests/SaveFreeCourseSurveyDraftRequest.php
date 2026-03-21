<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveFreeCourseSurveyDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'answers' => ['nullable', 'array'],
            'current_step' => ['nullable', 'integer', 'min:1', 'max:' . count(config('free_course_survey.questions', []))],
            'redirect_to' => ['nullable', 'string', 'max:2048'],
        ];

        foreach (config('free_course_survey.questions', []) as $question) {
            $questionId = $question['id'];

            if (($question['type'] ?? null) === 'single_choice') {
                $rules["answers.{$questionId}"] = ['nullable', Rule::in(array_keys($question['options'] ?? []))];
                continue;
            }

            $rules["answers.{$questionId}"] = ['nullable', 'string', 'max:' . ($question['max_length'] ?? 1000)];
        }

        return $rules;
    }
}
