<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\EnrollmentService;

/**
 * ✅ REFACTORED: UserEnrollmentController - Focused on enrollment operations
 * Extracted from fat UserCourseController (620+ lines) for better maintainability
 */
class UserEnrollmentController extends Controller
{
    protected $enrollmentService;

    public function __construct(EnrollmentService $enrollmentService)
    {
        $this->enrollmentService = $enrollmentService;
    }

    /**
     * Enroll a user in a course
     */
    public function enroll(Request $request)
    {
        try {
            Log::info('🚀 GRANT ACCESS REQUEST', $request->all());

            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'course_id' => 'required|integer|exists:courses,id'
            ]);
            
            $result = $this->enrollmentService->enrollUser($validated['user_id'], $validated['course_id']);
            
            if ($result['success']) {
                Log::info('✅ User successfully enrolled');
                return response()->json([
                    'success' => true,
                    'message' => $result['message']
                ]);
            } else {
                Log::warning('⚠️ Enrollment failed', $result);
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('❌ Enrollment error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while enrolling the user.'
            ], 500);
        }
    }

    /**
     * Revoke user access from a course
     */
    public function revoke(Request $request)
    {
        try {
            Log::info('🔄 REVOKE ACCESS REQUEST', $request->all());

            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'course_id' => 'required|integer|exists:courses,id'
            ]);

            $result = $this->enrollmentService->revokeUser($validated['user_id'], $validated['course_id']);
            
            if ($result['success']) {
                Log::info('✅ User access successfully revoked');
                return response()->json([
                    'success' => true,
                    'message' => $result['message']
                ]);
            } else {
                Log::warning('⚠️ Revoke failed', $result);
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('❌ Revoke error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while revoking user access.'
            ], 500);
        }
    }

    /**
     * Get user enrollments
     */
    public function getEnrollments(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'sometimes|integer|exists:users,id',
                'course_id' => 'sometimes|integer|exists:courses,id',
                'status' => 'sometimes|string|in:enrolled,revoked,all'
            ]);

            $enrollments = $this->enrollmentService->getEnrollments($validated);

            return response()->json([
                'success' => true,
                'data' => $enrollments
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Get enrollments error:', [
                'message' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching enrollments.'
            ], 500);
        }
    }

    /**
     * Get specific user's enrollments
     */
    public function getUserEnrollments(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id'
            ]);

            $enrollments = $this->enrollmentService->getUserEnrollments($validated['user_id']);

            return response()->json([
                'success' => true,
                'data' => $enrollments
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Get user enrollments error:', [
                'message' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching user enrollments.'
            ], 500);
        }
    }
}