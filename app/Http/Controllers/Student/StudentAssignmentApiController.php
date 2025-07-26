<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Services\AssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

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
        Log::info('Method getCountdownData called', ['user_id' => auth()->id(), 'timestamp' => now()]);
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
        Log::info('Method getStats called', ['user_id' => auth()->id(), 'timestamp' => now()]);
        $analytics = $this->assignmentService->getStudentPerformanceAnalytics(auth()->id());
        
        return response()->json($analytics);
    }

    /**
     * Get assignments for topics page categorization (AJAX)
     */
    public function getAssignmentsForCourse(Request $request): JsonResponse
    {
        Log::info('Method getAssignmentsForCourse called', ['user_id' => auth()->id(), 'timestamp' => now()]);
        $courseId = $request->input('course_id');
        
        if (!$courseId) {
            return response()->json(['assignments' => []]);
        }

        // LETHAL EAGER LOADING: Load assignments with comprehensive related data
        $assignments = Assignment::select('id', 'user_id', 'topic_id', 'course_id', 'title', 'status', 'deadline', 'created_at', 'tutor_id')
                                ->with([
                                    'topic:id,name,course_id',
                                    'topic.course:id,name',
                                    'tutor:id,name,email',
                                    'student:id,name,email'
                                ])
                                ->where('user_id', auth()->id())
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
        Log::info('Method getProgressWidget called', ['user_id' => auth()->id(), 'timestamp' => now()]);
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