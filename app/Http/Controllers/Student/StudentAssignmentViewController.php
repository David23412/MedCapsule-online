<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Services\AssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class StudentAssignmentViewController extends Controller
{
    private AssignmentService $assignmentService;

    public function __construct(AssignmentService $assignmentService)
    {
        $this->middleware('auth', 'role:student');
        $this->assignmentService = $assignmentService;
    }

    /**
     * Display student assignment dashboard - LETHAL CACHED
     */
    public function index(Request $request): View
    {
        Log::info('Method index called', ['user_id' => auth()->id(), 'timestamp' => now()]);
        
        $filters = $request->only(['status', 'course_id']);
        
        // LETHAL CACHING: Ultra-aggressive caching for student assignments
        $cacheKey = 'student_assignments_' . auth()->id() . '_' . md5(serialize($filters)) . '_' . now()->format('Y-m-d-H');
        
        $data = \Illuminate\Support\Facades\Cache::remember($cacheKey, 1800, function () use ($filters) {
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
            
            return compact('groupedAssignments', 'analytics');
        });

        return view('student.assignments.index', array_merge($data, compact('filters')));
    }

    /**
     * Display assignment details for student
     */
    public function show(Assignment $assignment): View
    {
        Log::info('Method show called', ['user_id' => auth()->id(), 'timestamp' => now()]);
        $this->authorize('view', $assignment);
        
        $assignment->load(['tutor', 'topic', 'course']);
        
        return view('student.assignments.show', compact('assignment'));
    }

    /**
     * Display assignment completion history
     */
    public function history(): View
    {
        Log::info('Method history called', ['user_id' => auth()->id(), 'timestamp' => now()]);
        $completedAssignments = $this->assignmentService->getStudentAssignments(
            auth()->id(), 
            ['status' => Assignment::STATUS_COMPLETED]
        );

        $analytics = $this->assignmentService->getStudentPerformanceAnalytics(auth()->id());

        return view('student.assignments.history', compact('completedAssignments', 'analytics'));
    }

    /**
     * Start assignment (redirect to topic quiz)
     */
    public function start(Assignment $assignment): RedirectResponse
    {
        Log::info('Method start called', ['user_id' => auth()->id(), 'timestamp' => now()]);
        $this->authorize('view', $assignment);

        if (!$assignment->canBeCompleted()) {
            return redirect()->back()->withErrors(['error' => 'This assignment cannot be started at this time.']);
        }

        return redirect()->route('quiz.start', [
            'course' => $assignment->course_id,
            'topic' => $assignment->topic_id,
            'assignment_id' => $assignment->id
        ]);
    }
}