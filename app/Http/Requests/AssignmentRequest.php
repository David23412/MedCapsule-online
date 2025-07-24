<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Assignment;

class AssignmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'tutor';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'student_id' => 'required|exists:users,id',
            'topic_id' => 'required|exists:topics,id',
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'deadline' => 'required|date|after:now',
            'duration_minutes' => 'nullable|numeric|min:0.1|max:1440', // Min 0.1 minutes (6 seconds) for testing
            'assignment_mode' => 'nullable|in:source_specific,complete_topic',
            'assigned_source' => 'nullable|string|max:255'
        ];

        // For updates, allow current assignment to have same topic for same student
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $assignmentId = $this->route('assignment')->id ?? null;
            if ($assignmentId) {
                $rules['student_id'] = 'required|exists:users,id';
                $rules['topic_id'] = 'required|exists:topics,id';
            }
        }

        return $rules;
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'student_id.required' => 'Please select a student.',
            'student_id.exists' => 'The selected student is invalid.',
            'topic_id.required' => 'Please select a topic.',
            'topic_id.exists' => 'The selected topic is invalid.',
            'course_id.required' => 'Please select a course.',
            'course_id.exists' => 'The selected course is invalid.',
            'title.required' => 'Assignment title is required.',
            'title.max' => 'Assignment title cannot exceed 255 characters.',
            'description.max' => 'Assignment description cannot exceed 1000 characters.',
            'deadline.required' => 'Assignment deadline is required.',
            'deadline.date' => 'Please provide a valid deadline date.',
            'deadline.after' => 'Assignment deadline must be in the future.',
            'duration_minutes.numeric' => 'Duration must be a valid number of minutes.',
            'duration_minutes.min' => 'Duration must be at least 0.1 minutes (6 seconds).',
            'duration_minutes.max' => 'Duration cannot exceed 24 hours (1440 minutes).',
            'assignment_mode.in' => 'Assignment mode must be either source_specific or complete_topic.',
            'assigned_source.string' => 'Assigned source must be a valid string.',
            'assigned_source.max' => 'Assigned source cannot exceed 255 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate tutor-student relationship
            if ($this->student_id) {
                $hasRelationship = \DB::table('tutor_student_relationships')
                    ->where('tutor_id', auth()->id())
                    ->where('student_id', $this->student_id)
                    ->exists();

                if (!$hasRelationship) {
                    $validator->errors()->add('student_id', 'You do not have a relationship with this student.');
                }
            }

            // Validate topic belongs to course
            if ($this->topic_id && $this->course_id) {
                $topicBelongsToCourse = \DB::table('topics')
                    ->where('id', $this->topic_id)
                    ->where('course_id', $this->course_id)
                    ->exists();

                if (!$topicBelongsToCourse) {
                    $validator->errors()->add('topic_id', 'The selected topic does not belong to the selected course.');
                }
            }

            // Check for duplicate assignments (only for creation)
            if ($this->isMethod('POST') && $this->student_id && $this->topic_id) {
                $existingAssignment = Assignment::where('tutor_id', auth()->id())
                    ->where('student_id', $this->student_id)
                    ->where('topic_id', $this->topic_id)
                    ->exists();

                if ($existingAssignment) {
                    $validator->errors()->add('topic_id', 'This student already has an assignment for this topic.');
                }
            }

            // Validate student has access to the course
            if ($this->student_id && $this->course_id) {
                $studentHasAccess = \DB::table('course_user')
                    ->where('user_id', $this->student_id)
                    ->where('course_id', $this->course_id)
                    ->exists();

                if (!$studentHasAccess) {
                    $validator->errors()->add('student_id', 'The selected student does not have access to this course.');
                }
            }

            // Validate source for source-specific assignments
            if ($this->assignment_mode === 'source_specific' && empty($this->assigned_source)) {
                $validator->errors()->add('assigned_source', 'Source must be specified for source-specific assignments.');
            }

            // Validate that source exists for the topic if specified
            if (!empty($this->assigned_source) && $this->topic_id) {
                $sourceExists = \DB::table('questions')
                    ->where('topic_id', $this->topic_id)
                    ->where('source', $this->assigned_source)
                    ->exists();

                if (!$sourceExists) {
                    $validator->errors()->add('assigned_source', 'The specified source does not exist for this topic.');
                }
            }
        });
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'student_id' => 'student',
            'topic_id' => 'topic',
            'course_id' => 'course',
            'duration_minutes' => 'duration',
            'assignment_mode' => 'assignment mode',
            'assigned_source' => 'source',
        ];
    }
}