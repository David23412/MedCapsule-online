<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\User;
use App\Models\Topic;
use App\Models\Course;
use App\Services\AssignmentService;
use App\Http\Requests\AssignmentRequest;
use App\Http\Requests\BulkAssignmentRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class AssignmentController extends Controller
{
    private AssignmentService $assignmentService;

    public function __construct(AssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if ($user->role === 'admin' || $user->role === 'tutor') {
                return $next($request);
            }
            abort(403, 'Unauthorized access');
        });
    }

    /**
     * Display a listing of assignments
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'course_id', 'student_id', 'due_within_hours', 'overdue']);
        $assignments = $this->assignmentService->getTutorAssignments(auth()->id(), $filters);
        
        // Get courses that have students assigned to this tutor
        $studentIds = auth()->user()->assignedStudents()->pluck('users.id');
        $courses = Course::whereHas('users', function($query) use ($studentIds) {
            $query->whereIn('id', $studentIds);
        })->get();

        $students = auth()->user()->assignedStudents;

        return view('assignments.index', compact('assignments', 'courses', 'students', 'filters'));
    }

    /**
     * Show the form for creating a new assignment
     */
    public function create(): View
    {
        // Get courses that have students assigned to this tutor
        $studentIds = auth()->user()->assignedStudents()->pluck('users.id');
        $courses = Course::whereHas('users', function($query) use ($studentIds) {
            $query->whereIn('id', $studentIds);
        })->get();

        $students = auth()->user()->assignedStudents;

        return view('assignments.create', compact('courses', 'students'));
    }

    /**
     * Store a newly created assignment
     */
    public function store(AssignmentRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            $data['tutor_id'] = auth()->id();

            $assignment = $this->assignmentService->createAssignment($data);

            return redirect()->route('assignments.index')
                           ->with('success', 'Assignment created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                           ->withInput()
                           ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified assignment
     */
    public function show(Assignment $assignment): View
    {
        $this->authorize('view', $assignment);
        
        $assignment->load(['student', 'topic', 'course']);
        
        return view('assignments.show', compact('assignment'));
    }

    /**
     * Show the form for editing the assignment
     */
    public function edit(Assignment $assignment): View
    {
        $this->authorize('update', $assignment);
        
        $assignment->load(['student', 'topic', 'course']);
        
        $courses = Course::whereHas('users', function($query) {
            $query->whereHas('tutorStudents', function($q) {
                $q->where('tutor_id', auth()->id());
            });
        })->get();

        $students = User::whereHas('tutorRelationships', function($query) {
            $query->where('tutor_id', auth()->id());
        })->get();

        return view('assignments.edit', compact('assignment', 'courses', 'students'));
    }

    /**
     * Update the specified assignment
     */
    public function update(AssignmentRequest $request, Assignment $assignment): RedirectResponse
    {
        $this->authorize('update', $assignment);

        try {
            $data = $request->validated();
            $this->assignmentService->updateAssignment($assignment, $data);

            return redirect()->route('assignments.show', $assignment)
                           ->with('success', 'Assignment updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                           ->withInput()
                           ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified assignment
     */
    public function destroy(Assignment $assignment): RedirectResponse
    {
        $this->authorize('delete', $assignment);

        try {
            $this->assignmentService->deleteAssignment($assignment);
            
            return redirect()->route('assignments.index')
                           ->with('success', 'Assignment deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                           ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show bulk assignment creation form
     */
    public function bulkCreate(): View
    {
        $courses = Course::whereHas('users', function($query) {
            $query->whereHas('tutorStudents', function($q) {
                $q->where('tutor_id', auth()->id());
            });
        })->get();

        $students = User::whereHas('tutorRelationships', function($query) {
            $query->where('tutor_id', auth()->id());
        })->get();

        return view('assignments.bulk-create', compact('courses', 'students'));
    }

    /**
     * Process bulk assignment creation
     */
    public function bulkStore(BulkAssignmentRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            $studentIds = $data['student_ids'];
            unset($data['student_ids']);

            $assignments = $this->assignmentService->createBulkAssignments(
                auth()->id(),
                $studentIds,
                $data
            );

            return redirect()->route('assignments.index')
                           ->with('success', "Successfully created {$assignments->count()} assignments!");
        } catch (\Exception $e) {
            return redirect()->back()
                           ->withInput()
                           ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show assignment analytics dashboard
     */
    public function analytics(): View
    {
        $analytics = $this->assignmentService->getAssignmentAnalytics(auth()->id());
        
        return view('assignments.analytics', compact('analytics'));
    }

    /**
     * Extend assignment deadline
     */
    public function extendDeadline(Assignment $assignment, Request $request): JsonResponse
    {
        $this->authorize('update', $assignment);

        $request->validate([
            'new_deadline' => 'required|date|after:now'
        ]);

        try {
            $newDeadline = Carbon::parse($request->new_deadline);
            $updatedAssignment = $this->assignmentService->extendDeadline($assignment, $newDeadline);

            return response()->json([
                'success' => true,
                'message' => 'Deadline extended successfully!',
                'assignment' => [
                    'id' => $updatedAssignment->id,
                    'deadline' => $updatedAssignment->deadline->format('Y-m-d H:i:s'),
                    'status' => $updatedAssignment->status,
                    'time_remaining_human' => $updatedAssignment->getTimeRemainingHuman()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get assignment counter data for dashboard
     */
    public function getCounterData(): JsonResponse
    {
        $stats = Assignment::getStatistics(auth()->id());
        
        return response()->json([
            'active_assignments' => $stats['active'],
            'overdue_assignments' => $stats['overdue'],
            'total_assignments' => $stats['total'],
            'completion_rate' => $stats['completion_rate']
        ]);
    }

    /**
     * Get topics for a course (AJAX)
     */
    public function getTopicsForCourse(Course $course): JsonResponse
    {
        $topics = Topic::where('course_id', $course->id)
                      ->where('is_locked', false)
                      ->orderBy('display_order')
                      ->get(['id', 'name']);

        return response()->json($topics);
    }

    /**
     * Get students for a course (AJAX)
     */
    public function getStudentsForCourse(Course $course): JsonResponse
    {
        $students = User::whereHas('tutorRelationships', function($query) {
                          $query->where('tutor_id', auth()->id());
                      })
                      ->whereHas('courses', function($query) use ($course) {
                          $query->where('courses.id', $course->id);
                      })
                      ->get(['id', 'name', 'username']);

        return response()->json($students);
    }

    /**
     * Mark assignment as completed (for tutors)
     */
    public function markCompleted(Assignment $assignment, Request $request): JsonResponse
    {
        $this->authorize('update', $assignment);

        $request->validate([
            'score' => 'nullable|integer|min:0|max:100'
        ]);

        try {
            $score = $request->input('score');
            $result = $this->assignmentService->completeAssignment($assignment, $score);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Assignment marked as completed!',
                    'assignment' => [
                        'id' => $assignment->id,
                        'status' => $assignment->fresh()->status,
                        'completed_at' => $assignment->fresh()->completed_at,
                        'score' => $assignment->fresh()->score
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
     * Update overdue assignments (AJAX endpoint)
     */
    public function updateOverdue(): JsonResponse
    {
        try {
            $updated = $this->assignmentService->updateOverdueAssignments();
            
            return response()->json([
                'success' => true,
                'updated_count' => $updated,
                'message' => "Updated {$updated} overdue assignments."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}