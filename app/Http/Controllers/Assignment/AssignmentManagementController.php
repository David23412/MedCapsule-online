<?php

namespace App\Http\Controllers\Assignment;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Services\AssignmentService;
use Illuminate\Http\Request;

/**
 * 🚀 PERFORMANCE BEAST: Assignment Management Controller
 * Handles: Assignment CRUD operations
 * Lines: ~90 (well under 200 limit)
 */
class AssignmentManagementController extends Controller
{
    protected AssignmentService $assignmentService;

    public function __construct(AssignmentService $assignmentService)
    {
        $this->middleware(['auth', 'tutor']);
        $this->assignmentService = $assignmentService;
    }

    /**
     * Show bulk create form
     */
    public function bulkCreate()
    {
        return view('assignments.bulk-create');
    }

    /**
     * Store bulk assignments
     */
    public function bulkStore(Request $request)
    {
        try {
            $result = $this->assignmentService->bulkCreateAssignments($request->all(), auth()->id());
            
            return response()->json([
                'success' => true,
                'message' => 'Assignments created successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create assignments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show edit form
     */
    public function edit(Assignment $assignment)
    {
        return view('assignments.edit', compact('assignment'));
    }

    /**
     * Update assignment
     */
    public function update(Request $request, Assignment $assignment)
    {
        try {
            $updatedAssignment = $this->assignmentService->updateAssignment($assignment, $request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Assignment updated successfully',
                'assignment' => $updatedAssignment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update assignment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete assignment
     */
    public function destroy(Assignment $assignment)
    {
        try {
            $this->assignmentService->deleteAssignment($assignment);
            
            return response()->json([
                'success' => true,
                'message' => 'Assignment deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete assignment: ' . $e->getMessage()
            ], 500);
        }
    }
}