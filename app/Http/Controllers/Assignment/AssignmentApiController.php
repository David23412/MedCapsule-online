<?php

namespace App\Http\Controllers\Assignment;

use App\Http\Controllers\Controller;
use App\Services\AssignmentService;

/**
 * 🚀 PERFORMANCE BEAST: Assignment API Controller
 * Handles: Assignment API endpoints for AJAX calls
 * Lines: ~50 (well under 200 limit)
 */
class AssignmentApiController extends Controller
{
    protected AssignmentService $assignmentService;

    public function __construct(AssignmentService $assignmentService)
    {
        $this->middleware(['auth', 'tutor']);
        $this->assignmentService = $assignmentService;
    }

    /**
     * Get topics for a course
     */
    public function getTopicsForCourse($courseId)
    {
        try {
            $topics = $this->assignmentService->getTopicsForCourse($courseId);
            
            return response()->json($topics);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load topics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get students for a course
     */
    public function getStudentsForCourse($courseId)
    {
        try {
            $students = $this->assignmentService->getStudentsForCourse($courseId);
            
            return response()->json($students);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load students: ' . $e->getMessage()
            ], 500);
        }
    }
}