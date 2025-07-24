<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkAssignmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && in_array(auth()->user()->role, ['admin', 'tutor']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|exists:users,id',
            'topic_id' => 'required|exists:topics,id',
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'deadline' => 'required|date|after:now',
            'duration_minutes' => 'nullable|numeric|min:0.1|max:1440', // Min 0.1 minutes (6 seconds) for testing
            'assignment_mode' => 'nullable|in:source_specific,complete_topic',
            'assigned_source' => 'nullable|string|max:255',
            'duration_minutes' => 'nullable|numeric|min:0.1|max:1440', // Min 0.1 minutes (6 seconds) for testing
            'skip_existing' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'student_ids.required' => 'Please select at least one student.',
            'student_ids.array' => 'Student selection is invalid.',
            'student_ids.min' => 'Please select at least one student.',
            'student_ids.*.required' => 'All selected students must be valid.',
            'student_ids.*.exists' => 'One or more selected students are invalid.',
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
            'duration_minutes.numeric' => 'Duration must be a valid number of minutes.',
            'duration_minutes.min' => 'Duration must be at least 0.1 minutes (6 seconds).',
            'duration_minutes.max' => 'Duration cannot exceed 24 hours (1440 minutes).',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate all students have tutor relationship (skip for admins)
            if ($this->student_ids && is_array($this->student_ids) && auth()->user()->role !== 'admin') {
                foreach ($this->student_ids as $index => $studentId) {
                    $hasRelationship = \DB::table('tutor_student_relationships')
                        ->where('tutor_id', auth()->id())
                        ->where('student_id', $studentId)
                        ->exists();

                    if (!$hasRelationship) {
                        $validator->errors()->add("student_ids.{$index}", 'You do not have a relationship with this student.');
                    }

                    // Validate student has access to the course (skip for admins)
                    if ($this->course_id && auth()->user()->role !== 'admin') {
                        $studentHasAccess = \DB::table('course_user')
                            ->where('user_id', $studentId)
                            ->where('course_id', $this->course_id)
                            ->exists();

                        if (!$studentHasAccess) {
                            $validator->errors()->add("student_ids.{$index}", 'This student does not have access to the selected course.');
                        }
                    }
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

            // Check for existing assignments if not skipping
            if (!$this->skip_existing && $this->student_ids && $this->topic_id) {
                $existingCount = \DB::table('assignments')
                    ->where('tutor_id', auth()->id())
                    ->where('topic_id', $this->topic_id)
                    ->whereIn('student_id', $this->student_ids)
                    ->count();

                if ($existingCount > 0) {
                    $validator->errors()->add('student_ids', 
                        "Some students already have assignments for this topic. Check 'Skip existing assignments' to proceed.");
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
            'student_ids' => 'students',
            'topic_id' => 'topic',
            'course_id' => 'course',
            'duration_minutes' => 'duration',
            'assignment_mode' => 'assignment mode',
            'assigned_source' => 'source',
            'time_limit' => 'time limit',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure student_ids is an array
        if ($this->has('student_ids') && !is_array($this->student_ids)) {
            $this->merge([
                'student_ids' => explode(',', $this->student_ids)
            ]);
        }

        // Set default for skip_existing
        if (!$this->has('skip_existing')) {
            $this->merge(['skip_existing' => false]);
        }
    }
}