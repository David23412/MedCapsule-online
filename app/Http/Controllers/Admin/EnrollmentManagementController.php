<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EnrollmentManagementService;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Enrollment Management Controller
 * Handles enrollment operations with proper service layer
 */
class EnrollmentManagementController extends Controller
{
    private EnrollmentManagementService $enrollmentService;
    private LoggerInterface $logger;

    public function __construct(
        EnrollmentManagementService $enrollmentService,
        LoggerInterface $logger
    ) {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->enrollmentService = $enrollmentService;
        $this->logger = $logger;
    }

    /**
     * Enroll user in course
     */
    public function enroll(Request $request)
    {
        try {
            $this->logger->info('Enrollment request received', $request->all());

            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'course_id' => 'required|integer|exists:courses,id'
            ]);
            
            $enrollment = $this->enrollmentService->enrollUser(
                $validated['user_id'],
                $validated['course_id']
            );

            return response()->json([
                'success' => true,
                'message' => "User {$enrollment['user_name']} enrolled in {$enrollment['course_name']} successfully.",
                'enrollment' => $enrollment
            ]);

        } catch (Exception $e) {
            $this->logger->error('Course enrollment failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to enroll user in course: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revoke user enrollment
     */
    public function revoke(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'course_id' => 'required|integer|exists:courses,id'
            ]);
            
            $this->enrollmentService->revokeEnrollment(
                $validated['user_id'],
                $validated['course_id']
            );

            return response()->json([
                'success' => true,
                'message' => 'Access revoked successfully.'
            ]);

        } catch (Exception $e) {
            $this->logger->error('Failed to revoke access: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke access.'
            ], 500);
        }
    }

    /**
     * Get enrollments
     */
    public function getEnrollments(Request $request)
    {
        try {
            $enrollments = $this->enrollmentService->getAllEnrollments();

            return response()->json([
                'success' => true,
                'enrollments' => $enrollments
            ]);

        } catch (Exception $e) {
            $this->logger->error('Failed to get enrollments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load enrollments.'
            ], 500);
        }
    }

    /**
     * Get user enrollments
     */
    public function getUserEnrollments(Request $request)
    {
        try {
            $userId = $request->get('user_id');
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User ID is required.'
                ], 400);
            }

            $courses = $this->enrollmentService->getUserEnrollments($userId);

            return response()->json([
                'success' => true,
                'courses' => $courses
            ]);

        } catch (Exception $e) {
            $this->logger->error('Failed to get user enrollments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load user enrollment data.'
            ], 500);
        }
    }
}