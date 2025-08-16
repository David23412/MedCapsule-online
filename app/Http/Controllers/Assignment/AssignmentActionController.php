<?php

namespace App\Http\Controllers\Assignment;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Services\AssignmentService;
use Illuminate\Http\Request;

/**
 * 🚀 PERFORMANCE BEAST: Assignment Action Controller
 * Handles: Assignment actions and status updates
 * Lines: ~80 (well under 200 limit)
 */
class AssignmentActionController extends Controller
{
    protected AssignmentService $assignmentService;

    public function __construct(AssignmentService $assignmentService)
    {
        $this->middleware(['auth', 'tutor']);
        $this->assignmentService = $assignmentService;
    }

    /**
     * Extend assignment deadline
     */
    public function extendDeadline(Request $request, Assignment $assignment)
    {
        try {
            $result = $this->assignmentService->extendDeadline($assignment, $request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Deadline extended successfully',
                'new_deadline' => $result['new_deadline']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to extend deadline: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark assignment as completed
     */
    public function markCompleted(Request $request, Assignment $assignment)
    {
        try {
            $this->assignmentService->markAsCompleted($assignment, $request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Assignment marked as completed'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark as completed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update overdue assignments
     */
    public function updateOverdue()
    {
        try {
            $result = $this->assignmentService->updateOverdueAssignments();
            
            return response()->json([
                'success' => true,
                'updated_count' => $result['updated_count']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update overdue assignments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enforce real-time status updates
     */
    public function enforceRealTimeStatus()
    {
        try {
            $result = $this->assignmentService->enforceRealTimeStatus();
            
            return response()->json([
                'success' => true,
                'enforced_count' => $result['enforced_count']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to enforce real-time status: ' . $e->getMessage()
            ], 500);
        }
    }
}