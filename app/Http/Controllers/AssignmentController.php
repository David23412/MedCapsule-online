<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Assignment;
use App\Services\AssignmentService;

class AssignmentController extends Controller
{
    protected $assignmentService;

    public function __construct(AssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }
    public function index()
    {
        $data = $this->assignmentService->getAssignmentsData(
            auth()->id(),
            request('status'),
            request('course_id')
        );

        // 🚀 SMART CACHING: Prevent browser caching of assignment pages
        return response()
            ->view('assignments.index', $data)
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0')
            ->header('X-Accel-Expires', '0');
    }

    public function bulkCreate()
    {
        $data = $this->assignmentService->getBulkCreateData(auth()->id());
        return view('assignments.create', $data);
    }

    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'topic_id' => 'required|exists:topics,id',
            'deadline' => 'required|date|after:now',
            'duration_minutes' => 'nullable|numeric|min:0',
            'assignment_mode' => 'required|in:complete_topic,source_specific',
            'assigned_source' => 'nullable|string',
            'title' => 'required|string|max:255',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:users,id',
        ]);

        $count = $this->assignmentService->createBulkAssignments($validated, auth()->id());

        // 🚀 INSTANT CACHE INVALIDATION - Clear assignment cache immediately
        \Cache::forget("assignments_data_" . auth()->id() . "_null_null");
        \Cache::tags(['assignments'])->flush();

        return redirect()->route('assignments.index', ['refresh' => 'true'])
            ->with('success', "Successfully created {$count} assignment(s)!")
            ->with('created_count', $count)
            ->with('force_refresh', true); // Signal frontend to refresh immediately
    }

    public function show(Assignment $assignment)
    {
        // LETHAL LOADING - Only essential data
        $assignment->load(['user:id,name,email', 'course:id,name', 'topic:id,name']);
        return view('assignments.show', compact('assignment'));
    }

    public function edit(Assignment $assignment)
    {
        // LETHAL LOADING - Only essential data
        $assignment->load(['assignedUsers:id,name,email', 'course:id,name', 'topic:id,name']);
        
        // Check if this is an AJAX request (modal)
        if (request()->ajax()) {
            return view('assignments.edit_form', compact('assignment'));
        }
        
        return view('assignments.edit', compact('assignment'));
    }

    public function update(Request $request, Assignment $assignment)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'deadline' => 'nullable|date',
            'duration_minutes' => 'nullable|numeric|min:0|max:1440',
            'status' => 'nullable|in:active,overdue',
            'user_extensions' => 'nullable|array',
            'user_extensions.*' => 'date',
        ]);

        try {
            $result = $this->assignmentService->updateAssignment($assignment, $validated);

            // Handle user-specific deadline extensions
            if (isset($validated['user_extensions']) && !empty($validated['user_extensions'])) {
                $this->assignmentService->updateUserExtensions($assignment, $validated['user_extensions']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Assignment updated successfully!',
                'assignment' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * 👑 ADMIN SUPERIOR PRIVILEGES: Enhanced destroy method
     * 
     * Admins can delete ANY assignment regardless of creator
     * Tutors can delete assignments (with potential restrictions)
     */
    public function destroy(Assignment $assignment)
    {
        $user = auth()->user();
        
        // 👑 ADMIN SUPERIOR PRIVILEGES - Can delete ANY assignment
        if ($user->role === 'admin') {
            \Log::info('👑 Admin deleting assignment with superior privileges', [
                'admin_id' => $user->id,
                'admin_name' => $user->name,
                'assignment_id' => $assignment->id,
                'assignment_title' => $assignment->title,
                'request_method' => request()->method(),
                'request_url' => request()->fullUrl(),
                'ip_address' => request()->ip()
            ]);
        } else {
            // Regular tutor deletion logging
            \Log::info('🎓 Tutor deleting assignment', [
                'tutor_id' => $user->id,
                'tutor_name' => $user->name,
                'assignment_id' => $assignment->id,
                'assignment_title' => $assignment->title,
                'request_method' => request()->method(),
                'request_url' => request()->fullUrl()
            ]);
        }

        try {
            $result = $this->assignmentService->deleteAssignment($assignment);
            
            \Log::info('🗑️ Assignment deletion result', [
                'assignment_id' => $assignment->id,
                'deleted_by' => $user->role,
                'user_id' => $user->id,
                'result' => $result
            ]);
            
            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => $user->role === 'admin' 
                        ? 'Assignment deleted successfully (Admin privileges)'
                        : 'Assignment deleted successfully',
                    'assignment_id' => $assignment->id,
                    'deleted_by' => $user->role
                ]);
            } else {
                \Log::warning('🗑️ Assignment deletion failed', [
                    'assignment_id' => $assignment->id,
                    'user_role' => $user->role,
                    'service_result' => $result
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete assignment'
                ], 500);
            }
        } catch (\Exception $e) {
            \Log::error('🗑️ Assignment deletion error', [
                'assignment_id' => $assignment->id,
                'user_role' => $user->role,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error deleting assignment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getTopicsForCourse($courseId)
    {
        return $this->assignmentService->getTopicsForCourse($courseId);
    }

    public function getStudentsForCourse($courseId)
    {
        return $this->assignmentService->getStudentsForCourse($courseId);
    }



    public function extendDeadline(Request $request, Assignment $assignment)
    {
        $validated = $request->validate([
            'new_deadline' => 'required|date|after:now',
        ]);

        $result = $this->assignmentService->extendDeadline($assignment, $validated['new_deadline']);
        return response()->json($result);
    }

    public function markCompleted(Request $request, Assignment $assignment)
    {
        $validated = $request->validate([
            'score_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $result = $this->assignmentService->markCompleted($assignment, $validated['score_percentage'] ?? null);
        return response()->json($result);
    }

    public function getCounterData()
    {
        $result = $this->assignmentService->getCounterData(auth()->id());
        return response()->json($result);
    }

    public function updateOverdue()
    {
        $result = $this->assignmentService->updateOverdue();
        return response()->json($result);
    }

    public function enforceRealTimeStatus()
    {
        $result = $this->assignmentService->enforceRealTimeStatus();
        return response()->json($result);
    }

    /**
     * Get assignment progress for ALL assigned users (for admin/tutor view)
     */
    public function getProgress(Assignment $assignment)
    {
        try {
            // Load assignment with necessary relationships
            $assignment->load(['course:id,name', 'topic:id,name']);
            
            // Get all users assigned to this assignment with their progress
            $assignmentUsers = $assignment->usersWithProgress()->get();
            
            $students = [];
            $completedCount = 0;
            
            foreach ($assignmentUsers as $user) {
                $latestAttempt = $user->quizAttempts->first();
                $status = 'not_started';
                $score = null;
                
                if ($latestAttempt) {
                    if ($latestAttempt->completed_at) {
                        $status = 'completed';
                        $score = $latestAttempt->percentage;
                        $completedCount++;
                    } else {
                        $status = $assignment->isOverdue() ? 'overdue' : 'in_progress';
                    }
                } else {
                    $status = $assignment->isOverdue() ? 'overdue' : 'not_started';
                }
                
                $students[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $status,
                    'score' => $score
                ];
            }
            
            return response()->json([
                'success' => true,
                'assignment' => [
                    'id' => $assignment->id,
                    'title' => $assignment->title,
                    'course' => $assignment->course->name ?? 'Unknown Course',
                    'topic' => $assignment->topic->name ?? 'Unknown Topic',
                    'deadline' => $assignment->deadline->format('M j, Y g:i A'),
                    'is_overdue' => $assignment->isOverdue()
                ],
                'students' => $students,
                'stats' => [
                    'total' => count($students),
                    'completed' => $completedCount,
                    'progress_percentage' => count($students) > 0 ? round(($completedCount / count($students)) * 100) : 0
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error loading assignment progress', [
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load assignment progress: ' . $e->getMessage()
            ], 500);
        }
    }


}