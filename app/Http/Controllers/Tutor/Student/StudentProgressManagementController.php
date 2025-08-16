<?php

namespace App\Http\Controllers\Tutor\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudentProgressRequest;
use App\Services\StudentProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * 🚀 PERFORMANCE BEAST: Student Progress Management Controller
 * Handles: Progress updates, exports, and bulk operations
 * Lines: ~80 (well under 200 limit)
 */
class StudentProgressManagementController extends Controller
{
    protected $studentProgressService;

    public function __construct(StudentProgressService $studentProgressService)
    {
        $this->middleware(['auth']);
        $this->studentProgressService = $studentProgressService;
    }

    /**
     * Export student progress data
     */
    public function exportProgress(StudentProgressRequest $request, int $studentId): JsonResponse
    {
        try {
            $validatedData = $request->getValidatedWithDefaults();
            
            $exportData = $this->studentProgressService->exportStudentProgress($studentId, $validatedData);
            
            Log::info('Student progress exported', [
                'student_id' => $studentId,
                'exported_by' => auth()->id(),
                'export_format' => $validatedData['format'] ?? 'json'
            ]);

            return response()->json([
                'success' => true,
                'export_data' => $exportData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Student progress export failed', [
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to export progress data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update student progress
     */
    public function bulkUpdateProgress(StudentProgressRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->getValidatedWithDefaults();
            
            $updateResult = $this->studentProgressService->bulkUpdateProgress($validatedData);
            
            Log::info('Bulk progress update completed', [
                'updated_by' => auth()->id(),
                'students_updated' => $updateResult['updated_count'] ?? 0
            ]);

            return response()->json([
                'success' => true,
                'result' => $updateResult,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk progress update failed', [
                'error' => $e->getMessage(),
                'updated_by' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update progress data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}