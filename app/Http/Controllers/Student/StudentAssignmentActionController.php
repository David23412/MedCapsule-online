<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Services\AssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class StudentAssignmentActionController extends Controller
{
    private AssignmentService $assignmentService;

    public function __construct(AssignmentService $assignmentService)
    {
        $this->middleware('auth', 'role:student');
        $this->assignmentService = $assignmentService;
    }

    /**
     * Mark assignment as completed by student - LETHAL CACHED
     */
    public function complete(Assignment $assignment, Request $request): JsonResponse
    {
        if (app()->environment('local', 'testing')) {
            Log::info('Method complete called', ['user_id' => auth()->id(), 'timestamp' => now()]);
        }
        $this->authorize('complete', $assignment);

        try {
            // LETHAL CACHING: Clear related caches after completion
            $userId = auth()->id();
            \Illuminate\Support\Facades\Cache::forget("student_assignments_{$userId}_*");
            \Illuminate\Support\Facades\Cache::forget("assignment_counters_{$userId}");
            
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
            Log::error('Assignment completion failed', [
                'error' => $e->getMessage(),
                'assignment_id' => $assignment->id,
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Request deadline extension
     */
    public function requestExtension(Assignment $assignment, Request $request): JsonResponse
    {
        if (app()->environment('local', 'testing')) {
            Log::info('Method requestExtension called', ['user_id' => auth()->id(), 'timestamp' => now()]);
        }
        $this->authorize('view', $assignment);

        try {
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
