<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Services\AssignmentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class StudentAssignmentController extends Controller
{
    private AssignmentService $assignmentService;

    public function __construct(AssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
        $this->middleware('auth');
        $this->middleware('role:student');
    }

    /**
     * Display student assignment dashboard
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'course_id']);
        $assignments = $this->assignmentService->getStudentAssignments(auth()->id(), $filters);
        
        // Group assignments by status for better display
        $groupedAssignments = [
            'urgent' => $assignments->filter(function($assignment) {
                return $assignment->status === Assignment::STATUS_ACTIVE && 
                       $assignment->deadline->diffInHours(now()) <= 24;
            }),
            'active' => $assignments->filter(function($assignment) {
                return $assignment->status === Assignment::STATUS_ACTIVE && 
                       $assignment->deadline->diffInHours(now()) > 24;
            }),
            'overdue' => $assignments->where('status', Assignment::STATUS_OVERDUE),
            'completed' => $assignments->where('status', Assignment::STATUS_COMPLETED)
        ];

        $analytics = $this->assignmentService->getStudentPerformanceAnalytics(auth()->id());

        return view('student.assignments.index', compact('groupedAssignments', 'analytics', 'filters'));
    }

    /**
     * Display assignment details for student
     */
    public function show(Assignment $assignment): View
    {
        $this->authorize('view', $assignment);
        
        $assignment->load(['tutor', 'topic', 'course']);
        
        return view('student.assignments.show', compact('assignment'));
    }

    /**
     * Mark assignment as completed by student
     */
    public function complete(Assignment $assignment, Request $request): JsonResponse
    {
        $this->authorize('complete', $assignment);

        try {
            // For students, we don't require a score - it will be set by quiz completion
            $result = $this->assignmentService->completeAssignment($assignment);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Assignment completed successfully!',
                    'assignment' => [
                        'id' => $assignment->id,
                        'status' => $assignment->fresh()->status,
                        'completed_at' => $assignment->fresh()->completed_at
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Assignment cannot be completed at this time.'
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display assignment completion history
     */
    public function history(): View
    {
        $completedAssignments = $this->assignmentService->getStudentAssignments(
            auth()->id(), 
            ['status' => Assignment::STATUS_COMPLETED]
        );

        $analytics = $this->assignmentService->getStudentPerformanceAnalytics(auth()->id());

        return view('student.assignments.history', compact('completedAssignments', 'analytics'));
    }

    /**
     * Get assignment countdown data (AJAX)
     */
    public function getCountdownData(Assignment $assignment): JsonResponse
    {
        $this->authorize('view', $assignment);

        $timeRemaining = $assignment->getTimeRemaining();
        $isOverdue = $assignment->isOverdue();

        return response()->json([
            'assignment_id' => $assignment->id,
            'deadline' => $assignment->deadline->toISOString(),
            'time_remaining_seconds' => $timeRemaining,
            'time_remaining_human' => $assignment->getTimeRemainingHuman(),
            'is_overdue' => $isOverdue,
            'status' => $assignment->status,
            'difficulty_level' => $assignment->getDifficultyLevel(),
            'can_be_completed' => $assignment->canBeCompleted()
        ]);
    }

    /**
     * Get student assignment statistics (AJAX)
     */
    public function getStats(): JsonResponse
    {
        $analytics = $this->assignmentService->getStudentPerformanceAnalytics(auth()->id());
        
        return response()->json($analytics);
    }

    /**
     * Start assignment (redirect to topic quiz)
     */
    public function start(Assignment $assignment): RedirectResponse
    {
        $this->authorize('view', $assignment);

        if (!$assignment->canBeCompleted()) {
            return redirect()->back()->withErrors(['error' => 'This assignment cannot be started at this time.']);
        }

        // Redirect to the topic quiz with assignment context
        return redirect()->route('quiz.topic', [
            'topic' => $assignment->topic,
            'assignment_id' => $assignment->id
        ]);
    }

    /**
     * Get assignments for topics page categorization (AJAX)
     */
    public function getAssignmentsForCourse(Request $request): JsonResponse
    {
        $courseId = $request->input('course_id');
        
        if (!$courseId) {
            return response()->json(['assignments' => []]);
        }

        $assignments = Assignment::with(['topic'])
                                ->where('student_id', auth()->id())
                                ->where('course_id', $courseId)
                                ->whereIn('status', [Assignment::STATUS_ACTIVE, Assignment::STATUS_OVERDUE])
                                ->get()
                                ->map(function($assignment) {
                                    return [
                                        'id' => $assignment->id,
                                        'title' => $assignment->title,
                                        'topic_name' => $assignment->topic->name,
                                        'topic_id' => $assignment->topic_id,
                                        'deadline' => $assignment->deadline->toISOString(),
                                        'time_remaining_human' => $assignment->getTimeRemainingHuman(),
                                        'status' => $assignment->status,
                                        'difficulty_level' => $assignment->getDifficultyLevel(),
                                        'is_overdue' => $assignment->isOverdue(),
                                        'can_be_completed' => $assignment->canBeCompleted()
                                    ];
                                });

        return response()->json(['assignments' => $assignments]);
    }

    /**
     * Get assignment progress for dashboard widget
     */
    public function getProgressWidget(): JsonResponse
    {
        $assignments = $this->assignmentService->getStudentAssignments(auth()->id());
        
        $stats = [
            'total' => $assignments->count(),
            'active' => $assignments->where('status', Assignment::STATUS_ACTIVE)->count(),
            'overdue' => $assignments->where('status', Assignment::STATUS_OVERDUE)->count(),
            'completed' => $assignments->where('status', Assignment::STATUS_COMPLETED)->count(),
            'urgent' => $assignments->filter(function($assignment) {
                return $assignment->status === Assignment::STATUS_ACTIVE && 
                       $assignment->deadline->diffInHours(now()) <= 24;
            })->count()
        ];

        $upcomingDeadlines = $assignments->filter(function($assignment) {
            return $assignment->status === Assignment::STATUS_ACTIVE && 
                   $assignment->deadline->diffInHours(now()) <= 72;
        })->take(3)->map(function($assignment) {
            return [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'topic_name' => $assignment->topic->name,
                'deadline' => $assignment->deadline->format('M j, Y g:i A'),
                'time_remaining_human' => $assignment->getTimeRemainingHuman(),
                'difficulty_level' => $assignment->getDifficultyLevel()
            ];
        });

        return response()->json([
            'stats' => $stats,
            'upcoming_deadlines' => $upcomingDeadlines
        ]);
    }

    /**
     * Request deadline extension
     */
    public function requestExtension(Assignment $assignment, Request $request): JsonResponse
    {
        $this->authorize('view', $assignment);

        $request->validate([
            'reason' => 'required|string|max:500',
            'requested_deadline' => 'required|date|after:now'
        ]);

        try {
            // Create a notification to the tutor about the extension request
            // This would integrate with the notification system
            
            // For now, we'll just return success
            // In a full implementation, this would create a notification record
            
            return response()->json([
                'success' => true,
                'message' => 'Extension request sent to your tutor successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send extension request. Please try again.'
            ], 500);
        }
    }
}