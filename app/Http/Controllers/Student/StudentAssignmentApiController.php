<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Services\AssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class StudentAssignmentApiController extends Controller
{
    private AssignmentService $assignmentService;

    public function __construct(AssignmentService $assignmentService)
    {
        $this->middleware('auth', 'role:student');
        $this->assignmentService = $assignmentService;
    }

    /**
     * Get assignment countdown data (AJAX)
     */
    public function getCountdownData(Assignment $assignment): JsonResponse
    {
        if (app()->environment('local', 'testing')) {
        }
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
        if (app()->environment('local', 'testing')) {
        }
        $analytics = $this->assignmentService->getStudentPerformanceAnalytics(auth()->id());
        
        return response()->json($analytics);
    }

    /**
     * Get assignments for topics page categorization (AJAX)
     */
    public function getAssignmentsForCourse(Request $request): JsonResponse
    {
        if (app()->environment('local', 'testing')) {
        }
        $courseId = $request->input('course_id');
        
        if (!$courseId) {
            return response()->json(['assignments' => []]);
        }

        $currentUser = Auth::user();

        $assignments = Assignment::select('id', 'user_id', 'topic_id', 'course_id', 'title', 'deadline', 'created_at')
                                ->with([
                                    'topic:id,name,course_id',
                                ])
                                ->where('user_id', $currentUser->id)
                                ->where('course_id', $courseId)
                                ->get()
                                ->map(function($assignment) use ($currentUser) {
                                    $status = $assignment->getStatusForUser($currentUser);

                                    return [
                                        'assignment' => $assignment,
                                        'status' => $status,
                                    ];
                                })
                                ->filter(function($item) {
                                    return in_array($item['status'], [Assignment::STATUS_ACTIVE, Assignment::STATUS_OVERDUE], true);
                                })
                                ->map(function($item) {
                                    $assignment = $item['assignment'];

                                    return [
                                        'id' => $assignment->id,
                                        'title' => $assignment->title,
                                        'topic_name' => $assignment->topic->name,
                                        'topic_id' => $assignment->topic_id,
                                        'deadline' => $assignment->deadline->toISOString(),
                                        'time_remaining_human' => $assignment->getTimeRemainingHuman(),
                                        'status' => $item['status'],
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
        if (app()->environment('local', 'testing')) {
        }
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
}
