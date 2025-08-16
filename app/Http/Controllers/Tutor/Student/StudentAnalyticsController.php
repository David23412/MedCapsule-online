<?php

namespace App\Http\Controllers\Tutor\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudentProgressRequest;
use App\Services\StudentProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * 🚀 PERFORMANCE BEAST: Student Analytics Controller
 * Handles: Advanced analytics and performance comparisons
 * Lines: ~90 (well under 200 limit)
 */
class StudentAnalyticsController extends Controller
{
    protected $studentProgressService;

    public function __construct(StudentProgressService $studentProgressService)
    {
        $this->middleware(['auth']);
        $this->studentProgressService = $studentProgressService;
    }

    /**
     * Get performance analytics for student
     */
    public function getPerformanceAnalytics(StudentProgressRequest $request, int $studentId): JsonResponse
    {
        try {
            $validatedData = $request->getValidatedWithDefaults();
            
            $cacheKey = "student_analytics_{$studentId}_" . md5(serialize($validatedData));
            
            $analytics = Cache::remember($cacheKey, 1800, function () use ($studentId, $validatedData) {
                return $this->studentProgressService->getPerformanceAnalytics($studentId, $validatedData);
            });

            return response()->json([
                'success' => true,
                'analytics' => $analytics,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get performance analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comparison analytics between students
     */
    public function getComparisonAnalytics(StudentProgressRequest $request, int $studentId): JsonResponse
    {
        try {
            $validatedData = $request->getValidatedWithDefaults();
            
            $cacheKey = "student_comparison_{$studentId}_" . md5(serialize($validatedData));
            
            $comparison = Cache::remember($cacheKey, 3600, function () use ($studentId, $validatedData) {
                return $this->studentProgressService->getComparisonAnalytics($studentId, $validatedData);
            });

            return response()->json([
                'success' => true,
                'comparison' => $comparison,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get comparison analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Student dashboard with comprehensive analytics
     */
    public function dashboard(StudentProgressRequest $request, int $studentId): JsonResponse
    {
        try {
            $validatedData = $request->getValidatedWithDefaults();
            
            $cacheKey = "student_dashboard_{$studentId}_" . md5(serialize($validatedData));
            
            $dashboardData = Cache::remember($cacheKey, 600, function () use ($studentId, $validatedData) {
                return [
                    'progress' => $this->studentProgressService->getStudentProgress($studentId, $validatedData),
                    'analytics' => $this->studentProgressService->getPerformanceAnalytics($studentId, $validatedData),
                    'recent_activity' => $this->studentProgressService->getRecentActivity($studentId, 10)
                ];
            });

            return response()->json([
                'success' => true,
                'dashboard' => $dashboardData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load student dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}