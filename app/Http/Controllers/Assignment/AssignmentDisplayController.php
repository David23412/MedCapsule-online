<?php

namespace App\Http\Controllers\Assignment;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Services\AssignmentService;

/**
 * 🚀 PERFORMANCE BEAST: Assignment Display Controller
 * Handles: Assignment listing and display
 * Lines: ~60 (well under 200 limit)
 */
class AssignmentDisplayController extends Controller
{
    protected AssignmentService $assignmentService;

    public function __construct(AssignmentService $assignmentService)
    {
        $this->middleware(['auth', 'tutor']);
        $this->assignmentService = $assignmentService;
    }

    /**
     * Display assignment index
     */
    public function index()
    {
        $assignments = $this->assignmentService->getAssignmentsForTutor(auth()->id());
        
        return view('assignments.index', compact('assignments'));
    }

    /**
     * Show assignment details
     */
    public function show(Assignment $assignment)
    {
        $assignmentData = $this->assignmentService->getAssignmentDetails($assignment);
        
        return view('assignments.show', compact('assignment', 'assignmentData'));
    }

    /**
     * Get assignment progress
     */
    public function getProgress(Assignment $assignment)
    {
        $progress = $this->assignmentService->getAssignmentProgress($assignment);
        
        return response()->json([
            'success' => true,
            'progress' => $progress
        ]);
    }

    /**
     * Get counter data for dashboard
     */
    public function getCounterData()
    {
        $counterData = $this->assignmentService->getCounterData(auth()->id());
        
        return response()->json([
            'success' => true,
            'data' => $counterData
        ]);
    }
}