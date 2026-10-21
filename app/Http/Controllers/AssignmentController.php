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
        $assignment->load(['assignedUsers:users.id,name,email', 'course:id,name', 'topic:id,name']);
        return view('assignments.show', compact('assignment'));
    }

    public function edit(Assignment $assignment)
    {
        // LETHAL LOADING - Only essential data
        $assignment->load(['assignedUsers:users.id,name,email', 'course:id,name', 'topic:id,name']);
        
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
            'user_extensions' => 'nullable|array',
            'user_extensions.*' => 'date',
        ]);

        try {
            $result = $this->assignmentService->updateAssignment($assignment, $validated);

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
        if ($user->isAdmin()) {
        } else {
            // Regular tutor deletion logging
        }

        try {
            $result = $this->assignmentService->deleteAssignment($assignment);
            
            
            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => $user->isAdmin() 
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

    public function getTopicSources(int $topicId)
    {
        try {
            $sources = \Illuminate\Support\Facades\DB::table('questions')
                ->where('topic_id', $topicId)
                ->select('source')
                ->distinct()
                ->whereNotNull('source')
                ->where('source', '!=', '')
                ->pluck('source')
                ->toArray();

            return response()->json($sources);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error fetching topic sources: ' . $e->getMessage(), [
                'topic_id' => $topicId,
                'error' => $e->getMessage()
            ]);

            return response()->json(['success' => false, 'message' => 'Unable to load topic sources'], 500);
        }
    }

    public function extendDeadline(Request $request, Assignment $assignment)
    {
        $validated = $request->validate([
            'new_deadline' => 'required|date|after:now',
        ]);

        $result = $this->assignmentService->extendDeadline($assignment, \Carbon\Carbon::parse($validated['new_deadline']));
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
        $result = $this->assignmentService->refreshStatuses(auth()->id());
        return response()->json($result);
    }

    public function enforceRealTimeStatus()
    {
        $result = $this->assignmentService->refreshStatuses(auth()->id());
        return response()->json($result);
    }

    /**
     * Get assignment progress for ALL assigned users (for admin/tutor view)
     */
    public function getProgress(Assignment $assignment)
    {
        $viewer = auth()->user();
        $canViewClass = $viewer->isAdmin()
            || ($viewer->isTutor() && $viewer->courses()->where('courses.id', $assignment->course_id)->exists());
        $canViewOwn = !$canViewClass && $viewer->isStudent() && $assignment->isAssignedToUser($viewer->id);
        abort_unless($canViewClass || $canViewOwn, 403, 'You do not have access to this assignment progress.');

        try {
            if (!$canViewClass) {
                $assignment->load(['attempts' => function ($query) use ($viewer) {
                    $query->where('user_id', $viewer->id);
                }]);
                $progress = $assignment->getUserProgressWithStatus($viewer);

                return response()->json([
                    'success' => true,
                    'progress' => (float) $progress['percentage'],
                    'status' => $progress['status'],
                    'status_string' => $progress['status_string'],
                ]);
            }

            // Load assignment with necessary relationships + all attempts for N+1 prevention
            $assignment->load(['course:id,name', 'topic:id,name', 'attempts']);
            
            // Get all users assigned to this assignment with their progress
            $assignmentUsers = $assignment->assignedUsers()->get();
            $assignment->setRelation('assignedUsers', $assignmentUsers);
            
            $students = [];
            $completedCount = 0;

            foreach ($assignmentUsers as $user) {
                $progress = $assignment->getUserProgressWithStatus($user);

                if ($progress['status'] === Assignment::STATUS_COMPLETED) {
                    $completedCount++;
                }

                $students[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $progress['status'],
                    'status_string' => $progress['status_string'],
                    'score' => $progress['score'],
                    'completed_at' => $progress['completed'] ? $progress['latest_attempt'] : null
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
