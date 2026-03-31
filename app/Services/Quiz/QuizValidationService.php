<?php

namespace App\Services\Quiz;

use App\Services\InputValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QuizValidationService
{
    /**
     * Validate quiz submission data
     */
    public function validateQuizSubmission(Request $request, bool $isAssignment, int $userId, string $requestId): array
    {
        // Pre-validation: Check for oversized payloads to prevent DoS attacks
        $requestSize = strlen(json_encode($request->all()));
        if ($requestSize > 1048576) { // 1MB limit
            Log::warning('Oversized payload detected', [
                'user_id' => $userId,
                'request_size' => $requestSize,
                'request_id' => $requestId
            ]);
            
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json([
                    'success' => false,
                    'error' => 'Request payload too large. Please reduce the size of your answers.'
                ], 413) // 413 Payload Too Large
            );
        }
        
        $validationRules = [
            'answers' => 'required|array|min:1|max:100',
            'answers.*' => 'nullable|string|max:2000',
            'mode' => 'nullable|string|in:quiz,tutor,random,assignment',
            'study_mode' => 'nullable|string|in:quiz,tutor,random,assignment,mistake_review',
            'duration_seconds' => $isAssignment 
                ? 'nullable|integer|min:1|max:86400'
                : 'required|integer|min:1|max:86400'
        ];
        
        try {
            $validated = InputValidationService::validateInput($request->all(), $validationRules, [], [
                'user_id' => $userId,
                'action' => 'quiz_grade',
                'request_id' => $requestId,
                'is_assignment' => $isAssignment
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Quiz submission validation failed', [
                'user_id' => $userId,
                'errors' => $e->errors(),
                'request_id' => $requestId
            ]);
            
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json([
                    'success' => false,
                    'error' => 'Invalid quiz submission data.',
                    'validation_errors' => $e->errors()
                ], 422)
            );
        } catch (\Exception $e) {
            Log::error('Quiz submission validation system error', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'request_id' => $requestId
            ]);
            
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json([
                    'success' => false,
                    'error' => 'Quiz submission validation failed. Please try again.'
                ], 500)
            );
        }

        // Ensure duration_seconds is an integer
        if (isset($validated['duration_seconds'])) {
            $validated['duration_seconds'] = (int) $validated['duration_seconds'];
        }

        // Handle mode and study_mode compatibility
        if (isset($validated['mode']) && !empty($validated['mode'])) {
            $validated['study_mode'] = $validated['mode'];
        } elseif (isset($validated['study_mode']) && !empty($validated['study_mode'])) {
            $validated['mode'] = $validated['study_mode'];
        } else {
            $validated['mode'] = $isAssignment ? 'assignment' : 'quiz';
            $validated['study_mode'] = $validated['mode'];
        }

        // ULTRA EFFICIENT: Handle pre-computed data from frontend
        if (isset($validated['computed_locally']) && $validated['computed_locally']) {
        }

        // Ensure duration_seconds has a minimum value for tutor mode
        if ($validated['mode'] === 'tutor' && (!isset($validated['duration_seconds']) || $validated['duration_seconds'] < 1)) {
            $validated['duration_seconds'] = 1; // Minimum 1 second for tutor mode
        }

        return $validated;
    }
}