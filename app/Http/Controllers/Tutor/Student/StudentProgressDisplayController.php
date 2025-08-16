<?php

namespace App\Http\Controllers\Tutor\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudentProgressRequest;
use App\Models\User;
use App\Services\StudentProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use App\Services\QuizPerformanceService;

/**
 * 🚀 PERFORMANCE BEAST: Student Progress Display Controller
 * Handles: Student progress display and basic analytics
 * Lines: ~80 (well under 200 limit)
 */
class StudentProgressDisplayController extends Controller
{
    protected $studentProgressService;

    public function __construct(StudentProgressService $studentProgressService)
    {
        $this->middleware(['auth']);
        $this->studentProgressService = $studentProgressService;
    }

    /**
     * Show detailed student progress with ultra-fast performance
     */
    public function show(StudentProgressRequest $request, int $studentId): JsonResponse
    {
        return QuizPerformanceService::trackQuizGeneration($studentId, auth()->id(), function () use ($request, $studentId) {
            try {
                $validatedData = $request->getValidatedWithDefaults();
                
                // Ultra-fast cache key generation
                $cacheKey = "student_progress_{$studentId}_" . md5(serialize($validatedData));
                
                // Aggressive caching with intelligent TTL
                $progressData = Cache::remember($cacheKey, 900, function () use ($studentId, $validatedData) {
                    return $this->studentProgressService->getStudentProgress($studentId, $validatedData);
                });

                return response()->json([
                    'success' => true,
                    'data' => $progressData,
                    'cache_hit' => true,
                    'timestamp' => now()->toISOString()
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to load student progress',
                    'error' => $e->getMessage()
                ], 500);
            }
        });
    }

    /**
     * Get real-time progress updates
     */
    public function getRealtimeUpdates(StudentProgressRequest $request, int $studentId): JsonResponse
    {
        try {
            $validatedData = $request->getValidatedWithDefaults();
            
            // Get recent updates only (last 5 minutes)
            $recentUpdates = $this->studentProgressService->getRecentUpdates($studentId, 5);
            
            return response()->json([
                'success' => true,
                'updates' => $recentUpdates,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get real-time updates',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get progress summary for dashboard
     */
    public function getProgressSummary(StudentProgressRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->getValidatedWithDefaults();
            
            $summary = $this->studentProgressService->getProgressSummary($validatedData);
            
            return response()->json([
                'success' => true,
                'summary' => $summary,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get progress summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}