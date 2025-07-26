<?php

namespace App\Http\Controllers\Assignment;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Course;
use App\Services\AssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 💀 NUCLEAR OPTIMIZED ASSIGNMENT API CONTROLLER 💀
 * Handles API operations with F-16 RAPTOR SPEED
 */
class AssignmentApiController extends Controller
{
    private AssignmentService $assignmentService;

    public function __construct(AssignmentService $assignmentService)
    {
        $this->middleware(['auth']);
        $this->assignmentService = $assignmentService;
    }

    /**
     * Get assignment counter data with caching
     */
    public function getCounterData(): JsonResponse
    {
        try {
            $userId = auth()->id();
            
            $data = Cache::remember("assignment_counters_{$userId}", 300, function() use ($userId) {
                // Ultra-aggressive eager loading for assignment counter data
                $user = auth()->user()->load([
                    'assignments:id,user_id,title,status,deadline,completed_at',
                    'assignments.topic:id,name,course_id',
                    'assignments.topic.course:id,name',
                    'assignments.tutor:id,name,email'
                ]);
                
                return $this->assignmentService->getCounterData($userId);
            });
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('Counter data retrieval failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load counter data'
            ], 500);
        }
    }

    /**
     * Get topics for course with caching
     */
    public function getTopicsForCourse(Course $course): JsonResponse
    {
        try {
            $topics = Cache::remember("course_topics_{$course->id}", 600, function() use ($course) {
                return $course->topics()->select('id', 'name')->orderBy('name')->get();
            });
            
            return response()->json([
                'success' => true,
                'topics' => $topics
            ]);
        } catch (\Exception $e) {
            Log::error('Topics retrieval failed', [
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load topics'
            ], 500);
        }
    }

    /**
     * Get students for course with caching
     */
    public function getStudentsForCourse(Course $course): JsonResponse
    {
        try {
            $students = Cache::remember("course_students_{$course->id}", 300, function() use ($course) {
                return $course->users()
                    ->select('users.id', 'users.name', 'users.email')
                    ->where('users.role', 'student')
                    ->orderBy('users.name')
                    ->get();
            });
            
            return response()->json([
                'success' => true,
                'students' => $students
            ]);
        } catch (\Exception $e) {
            Log::error('Students retrieval failed', [
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load students'
            ], 500);
        }
    }

    /**
     * Extend assignment deadline
     */
    public function extendDeadline(Assignment $assignment, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hours' => 'required|integer|min:1|max:168'
        ]);

        try {
            $this->assignmentService->extendDeadline($assignment, $validated['hours']);
            
            return response()->json([
                'success' => true,
                'message' => "Deadline extended by {$validated['hours']} hours",
                'new_deadline' => $assignment->fresh()->deadline
            ]);
        } catch (\Exception $e) {
            Log::error('Deadline extension failed', [
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to extend deadline'
            ], 500);
        }
    }

    /**
     * Mark assignment as completed
     */
    public function markCompleted(Assignment $assignment, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'grade' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            $this->assignmentService->markCompleted($assignment, $validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Assignment marked as completed'
            ]);
        } catch (\Exception $e) {
            Log::error('Assignment completion failed', [
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark assignment as completed'
            ], 500);
        }
    }

    /**
     * Real-time status enforcement
     */
    public function enforceRealTimeStatus(Request $request): JsonResponse
    {
        try {
            $result = $this->assignmentService->enforceRealTimeStatus();
            
            return response()->json([
                'success' => true,
                'message' => 'Status enforcement completed',
                'updated_count' => $result['updated_count']
            ]);
        } catch (\Exception $e) {
            Log::error('Status enforcement failed', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Status enforcement failed'
            ], 500);
        }
    }
}