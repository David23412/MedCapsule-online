<?php

namespace App\Http\Controllers\Assignment;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignmentCreateRequest;
use App\Models\Assignment;
use App\Services\AssignmentService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

/**
 * 💀 NUCLEAR OPTIMIZED ASSIGNMENT CRUD CONTROLLER 💀
 * Handles basic CRUD operations with F-16 RAPTOR SPEED
 */
class AssignmentCrudController extends Controller
{
    private AssignmentService $assignmentService;

    public function __construct(AssignmentService $assignmentService)
    {
        $this->middleware(['auth']);
        $this->assignmentService = $assignmentService;
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (in_array($user->role, ['admin', 'tutor'])) {
                return $next($request);
            }
            abort(403, 'Unauthorized access. Only tutors and admins can manage assignments.');
        });
    }

    /**
     * Display assignments listing
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'course_id', 'student_id', 'due_within_hours', 'overdue']);
        
        // Ultra-aggressive caching for assignments listing
        $cacheKey = "assignments_index_" . auth()->id() . "_" . md5(serialize($filters));
        $assignments = \Illuminate\Support\Facades\Cache::remember($cacheKey, 600, function () use ($filters) {
            return $this->assignmentService->getTutorAssignments(auth()->id(), $filters);
        });
        
        return view('assignments.index', compact('assignments'));
    }

    /**
     * Store new assignment
     */
    public function store(AssignmentCreateRequest $request): RedirectResponse
    {
        try {
            $assignment = $this->assignmentService->createAssignment($request->validated());
            
            // 🔔 CRITICAL NOTIFICATION INTEGRATION - Assignment Created
            if ($assignment && $assignment->student) {
                \App\Services\NotificationTriggerService::onAssignmentCreated($assignment->student, $assignment);
            }
            
            return redirect()->route('assignments.index')
                ->with('success', 'Assignment created successfully.');
        } catch (\Exception $e) {
            Log::error('Assignment creation failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return back()->withInput()
                ->with('error', 'Failed to create assignment. Please try again.');
        }
    }

    /**
     * Show assignment details - LETHAL EAGER LOADING
     */
    public function show(Assignment $assignment): View
    {
        // LETHAL EAGER LOADING: Load assignment with comprehensive related data
        $assignment->load([
            'student:id,name,email,role,created_at',
            'student.quizAttempts:id,user_id,topic_id,score,created_at',
            'course:id,name,description,price',
            'course.topics:id,course_id,name',
            'topic:id,name,course_id',
            'topic.questions:id,topic_id,difficulty',
            'tutor:id,name,email'
        ]);
        
        return view('assignments.show', compact('assignment'));
    }

    /**
     * Show edit form - LETHAL EAGER LOADING
     */
    public function edit(Assignment $assignment): View
    {
        // LETHAL EAGER LOADING: Load assignment with comprehensive edit data
        $assignment->load([
            'student:id,name,email,role',
            'course:id,name,description',
            'course.topics:id,course_id,name',
            'topic:id,name,course_id',
            'tutor:id,name,email'
        ]);
        
        $courses = $this->assignmentService->getAvailableCourses();
        
        return view('assignments.edit', compact('assignment', 'courses'));
    }

    /**
     * Update assignment
     */
    public function update(AssignmentCreateRequest $request, Assignment $assignment): RedirectResponse
    {
        try {
            $this->assignmentService->updateAssignment($assignment, $request->validated());
            
            return redirect()->route('assignments.show', $assignment)
                ->with('success', 'Assignment updated successfully.');
        } catch (\Exception $e) {
            Log::error('Assignment update failed', [
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->withInput()
                ->with('error', 'Failed to update assignment. Please try again.');
        }
    }

    /**
     * Delete assignment
     */
    public function destroy(Assignment $assignment): RedirectResponse
    {
        try {
            $this->assignmentService->deleteAssignment($assignment);
            
            return redirect()->route('assignments.index')
                ->with('success', 'Assignment deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Assignment deletion failed', [
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Failed to delete assignment. Please try again.');
        }
    }
}