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

        return view('assignments.index', $data);
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

        return redirect()->route('assignments.index')
            ->with('success', "Successfully created {$count} assignment(s)!")
            ->with('created_count', $count);
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
        $assignment->load(['user:id,name,email', 'course:id,name', 'topic:id,name']);
        
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
        ]);

        $result = $this->assignmentService->updateAssignment($assignment, $validated);

        return response()->json($result);
    }

    public function destroy(Assignment $assignment)
    {
        $result = $this->assignmentService->deleteAssignment($assignment);
        return response()->json($result);
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
     * Get assignment progress for current user
     */
    public function getProgress(Assignment $assignment)
    {
        $userId = auth()->id();
        
        // Get user progress for this assignment
        $userProgress = $assignment->getUserProgress($userId);
        
        return response()->json([
            'success' => true,
            'assignment_id' => $assignment->id,
            'progress' => $userProgress['percentage'],
            'completed' => $userProgress['completed'],
            'attempts_count' => $userProgress['attempts_count'],
            'best_score' => $userProgress['best_score']
        ]);
    }
}