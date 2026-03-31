<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudentProgressRequest;
use App\Models\User;
use App\Models\Course;
use App\Models\QuizAttempt;
use App\Services\QuizPerformanceService;
use App\Services\QuizUltraConsolidatedCacheService;
use App\Services\StudentProgressService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * ULTRA-OPTIMIZED STUDENT PROGRESS CONTROLLER
 * Lightning-speed student progress tracking and analytics
 */
class StudentProgressController extends Controller
{
    protected $studentProgressService;

    public function __construct(StudentProgressService $studentProgressService)
    {
        // Don't call parent::__construct() to avoid auth middleware conflicts
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
                $this->getAccessibleStudent($studentId);
                
                // Ultra-fast cache key generation
                $cacheKey = QuizUltraConsolidatedCacheService::generateCacheKey('student_progress', [
                    'user_id' => $studentId,
                    'tutor_id' => auth()->id(),
                    'params' => $validatedData
                ]);
                
                // Cache only the aggregate result; loading the full relationship graph here
                // adds memory and query overhead without affecting the response payload.
                $progressData = Cache::remember($cacheKey, 900, function () use ($studentId, $validatedData) {
                    return $this->studentProgressService->getComprehensiveProgress($studentId, $validatedData);
                });

                return $this->jsonResponse(true, 'Student progress retrieved successfully', $progressData);

            } catch (AuthorizationException $e) {
                return $this->jsonResponse(false, $e->getMessage(), [], 403);
            } catch (ModelNotFoundException $e) {
                return $this->jsonResponse(false, 'Student not found', [], 404);
            } catch (\Exception $e) {
                Log::error('Student progress retrieval failed', [
                    'user_id' => $studentId,
                    'tutor_id' => auth()->id(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return $this->jsonResponse(false, 'Failed to retrieve student progress', [], 500);
            }
        });
    }

    /**
     * Get real-time updates with ultra-fast performance
     */
    public function getRealtimeUpdates(StudentProgressRequest $request, int $studentId): JsonResponse
    {
        try {
            $validatedData = $request->getValidatedWithDefaults();
            
            // Ultra-fast cache key for real-time data
            $cacheKey = QuizUltraConsolidatedCacheService::generateCacheKey('student_realtime', [
                'user_id' => $studentId,
                'timestamp' => now()->format('Y-m-d-H-i')
            ]);
            
            // Short-term caching for real-time data
            $realtimeData = Cache::remember($cacheKey, 60, function () use ($studentId, $validatedData) {
                return $this->studentProgressService->getRealtimeUpdates($studentId, $validatedData);
            });

            return $this->jsonResponse(true, 'Real-time updates retrieved', $realtimeData);

        } catch (\Exception $e) {
            Log::error('Real-time updates failed', [
                'user_id' => $studentId,
                'error' => $e->getMessage()
            ]);

            return $this->jsonResponse(false, 'Failed to get real-time updates', [], 500);
        }
    }

    /**
     * Get performance analytics with optimized queries
     */
    public function getPerformanceAnalytics(StudentProgressRequest $request, int $studentId): JsonResponse
    {
        try {
            $validatedData = $request->getValidatedWithDefaults();
            
            // Performance analytics with aggressive caching
            $cacheKey = QuizUltraConsolidatedCacheService::generateCacheKey('analytics', [
                'user_id' => $studentId,
                'params' => $validatedData
            ]);
            
            $analyticsData = Cache::remember($cacheKey, 1800, function () use ($studentId, $validatedData) {
                return $this->studentProgressService->getPerformanceAnalytics($studentId, $validatedData);
            });

            return $this->jsonResponse(true, 'Performance analytics retrieved', $analyticsData);

        } catch (\Exception $e) {
            Log::error('Performance analytics failed', [
                'user_id' => $studentId,
                'error' => $e->getMessage()
            ]);

            return $this->jsonResponse(false, 'Failed to get performance analytics', [], 500);
        }
    }

    /**
     * ULTRA-FAST student progress dashboard with comprehensive analytics
     */
    public function dashboard(StudentProgressRequest $request, int $studentId): JsonResponse
    {
        return QuizPerformanceService::trackQuizGeneration($studentId, auth()->id(), function () use ($request, $studentId) {
            try {
                $validatedData = $request->getValidatedWithDefaults();
                $student = $this->getAccessibleStudent($studentId);
                
                // Cache key based on student and recent updates
                $cacheKey = QuizUltraConsolidatedCacheService::generateCacheKey('student_dashboard', [
                    'user_id' => $studentId,
                    'tutor_id' => auth()->id(),
                    'timestamp' => $student->updated_at->timestamp
                ]);
                
                // Get detailed analytics with 15-minute cache
                $analytics = Cache::remember($cacheKey, 900, function () use ($studentId, $validatedData) {
                    return $this->studentProgressService->getComprehensiveProgress($studentId, array_merge($validatedData, [
                        'include_details' => true,
                        'include_trends' => true,
                        'include_recommendations' => true
                    ]));
                });

                return $this->jsonResponse(true, 'Student dashboard data retrieved', [
                    'student' => $student->only(['id', 'name', 'email', 'created_at', 'last_active_at']),
                    'analytics' => $analytics,
                    'cached_at' => now()->toISOString()
                ]);

            } catch (AuthorizationException $e) {
                return $this->jsonResponse(false, $e->getMessage(), [], 403);
            } catch (ModelNotFoundException $e) {
                return $this->jsonResponse(false, 'Student not found', [], 404);
            } catch (\Exception $e) {
                Log::error('Student dashboard failed', [
                    'user_id' => $studentId,
                    'tutor_id' => auth()->id(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return $this->jsonResponse(false, 'Failed to load student dashboard', [], 500);
            }
        });
    }

    /**
     * Get comprehensive student comparison analytics
     */
    public function getComparisonAnalytics(StudentProgressRequest $request, int $studentId): JsonResponse
    {
        try {
            $validatedData = $request->getValidatedWithDefaults();
            
            // Ultra-fast comparison analytics
            $cacheKey = QuizUltraConsolidatedCacheService::generateCacheKey('comparison_analytics', [
                'user_id' => $studentId,
                'tutor_id' => auth()->id(),
                'params' => $validatedData
            ]);
            
            $comparisonData = Cache::remember($cacheKey, 1200, function () use ($studentId, $validatedData) {
                return $this->studentProgressService->getComparisonAnalytics($studentId, $validatedData);
            });

            return $this->jsonResponse(true, 'Comparison analytics retrieved', $comparisonData);

        } catch (\Exception $e) {
            Log::error('Comparison analytics failed', [
                'user_id' => $studentId,
                'error' => $e->getMessage()
            ]);

            return $this->jsonResponse(false, 'Failed to get comparison analytics', [], 500);
        }
    }

    /**
     * Export student progress data with optimized formatting
     */
    public function exportProgress(StudentProgressRequest $request, int $studentId): JsonResponse
    {
        try {
            $validatedData = $request->getValidatedWithDefaults();
            
            // Generate export data with caching
            $exportData = $this->studentProgressService->generateExportData($studentId, $validatedData);
            
            return $this->jsonResponse(true, 'Export data generated', [
                'export_data' => $exportData,
                'generated_at' => now()->toISOString(),
                'user_id' => $studentId
            ]);

        } catch (\Exception $e) {
            Log::error('Progress export failed', [
                'user_id' => $studentId,
                'error' => $e->getMessage()
            ]);

            return $this->jsonResponse(false, 'Failed to export progress data', [], 500);
        }
    }

    /**
     * Bulk update student progress tracking
     */
    public function bulkUpdateProgress(StudentProgressRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->getValidatedWithDefaults();
            $studentIds = $validatedData['user_ids'] ?? [];
            
            if (empty($studentIds)) {
                return $this->jsonResponse(false, 'No student IDs provided', [], 400);
            }
            
            // Bulk update with optimized queries
            $results = $this->studentProgressService->bulkUpdateProgress($studentIds, $validatedData);
            
            return $this->jsonResponse(true, 'Bulk progress update completed', [
                'updated_count' => count($results),
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk progress update failed', [
                'error' => $e->getMessage(),
                'tutor_id' => auth()->id()
            ]);

            return $this->jsonResponse(false, 'Bulk update failed', [], 500);
        }
    }

    /**
     * Get student progress summary for multiple students
     */
    public function getProgressSummary(StudentProgressRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->getValidatedWithDefaults();
            
            // Ultra-fast summary with aggressive caching
            $cacheKey = QuizUltraConsolidatedCacheService::generateCacheKey('progress_summary', [
                'tutor_id' => auth()->id(),
                'params' => $validatedData
            ]);
            
            $summaryData = Cache::remember($cacheKey, 600, function () use ($validatedData) {
                return $this->studentProgressService->getProgressSummary(auth()->id(), $validatedData);
            });

            return $this->jsonResponse(true, 'Progress summary retrieved', $summaryData);

        } catch (\Exception $e) {
            Log::error('Progress summary failed', [
                'tutor_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return $this->jsonResponse(false, 'Failed to get progress summary', [], 500);
        }
    }

    private function getAccessibleStudent(int $studentId): User
    {
        $student = User::query()
            ->select('id', 'name', 'email', 'created_at', 'last_active_at', 'updated_at')
            ->findOrFail($studentId);

        $authUser = auth()->user();
        if ($authUser && $authUser->isAdmin()) {
            return $student;
        }

        $tutorCourseIds = $authUser?->courses()->pluck('courses.id') ?? collect();
        $hasAccess = !$tutorCourseIds->isEmpty() && DB::table('course_user')
            ->where('user_id', $studentId)
            ->where('is_enrolled', true)
            ->whereIn('course_id', $tutorCourseIds)
            ->exists();

        if (!$hasAccess) {
            throw new AuthorizationException('Unauthorized access to student data');
        }

        return $student;
    }
}
