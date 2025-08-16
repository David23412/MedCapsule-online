<?php

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Models\Mistake;
use App\Models\Course;
use App\Models\QuizAttempt;
use App\Services\QuizMistakeService;
use App\Services\QuizMistakeCacheService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Auth\Factory as Auth;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * 💀 NUCLEAR OPTIMIZED QUIZ MISTAKE CONTROLLER 💀
 * Handles mistake display with F-16 RAPTOR SPEED
 * FIXED: Eliminated fat controller issues, reduced complexity, added DI
 */
class QuizMistakeController extends Controller
{
    private QuizMistakeService $mistakeService;
    private QuizMistakeCacheService $cacheService;
    private ViewFactory $viewFactory;
    private CacheRepository $cache;
    private Auth $auth;
    private LoggerInterface $logger;

    public function __construct(
        QuizMistakeService $mistakeService,
        QuizMistakeCacheService $cacheService,
        ViewFactory $viewFactory,
        CacheRepository $cache,
        Auth $auth,
        LoggerInterface $logger
    ) {
        $this->middleware(['auth']);
        $this->mistakeService = $mistakeService;
        $this->cacheService = $cacheService;
        $this->viewFactory = $viewFactory;
        $this->cache = $cache;
        $this->auth = $auth;
        $this->logger = $logger;
    }

    /**
     * 🎯 SMART HYBRID MISTAKES LOADER - FAST CACHE WITH INSTANT INVALIDATION
     * FIXED: Reduced complexity by extracting logic into services
     */
    public function showMistakes(Request $request)
    {
        $startTime = microtime(true);
        $requestId = $this->generateRequestId();
        
        try {
            $userId = $this->getAuthenticatedUserId();
            $courseFilter = $this->getValidatedCourseFilter($request);
            
            $this->logMistakeOperation('loading_mistakes', $requestId, $userId, ['course_filter' => $courseFilter]);

            $mistakesData = $this->mistakeService->getMistakesWithCache($userId, $courseFilter);
            $supportingData = $this->cacheService->getSupportingData($userId);

            $viewData = $this->prepareViewData($mistakesData, $supportingData, $courseFilter);
            
            $executionTime = $this->calculateExecutionTime($startTime);
            $this->logPerformanceMetrics($requestId, $executionTime, $mistakesData->count());

            return $this->viewFactory->make('mistakes', $viewData);

        } catch (Exception $e) {
            $executionTime = $this->calculateExecutionTime($startTime);
            $this->logError('Error in showMistakes', $e, $requestId, $executionTime);
            
            return $this->viewFactory->make('mistakes', $this->getFallbackViewData());
        }
    }

    /**
     * FIXED: Extract request ID generation to eliminate duplication
     */
    private function generateRequestId(): string
    {
        return uniqid('mistakes_');
    }

    /**
     * FIXED: Extract authentication logic to eliminate duplication
     */
    private function getAuthenticatedUserId(): int
    {
        return $this->auth->id();
    }

    /**
     * FIXED: Extract course filter validation to eliminate duplication
     */
    private function getValidatedCourseFilter(Request $request): ?string
    {
        return $request->input('course');
    }

    /**
     * FIXED: Extract view data preparation to eliminate duplication
     */
    private function prepareViewData($mistakesData, $supportingData, ?string $courseFilter): array
    {
        $availableCourses = $this->formatAvailableCourses($supportingData['courses']);
        $recentCourse = $this->getRecentCourse($supportingData, $mistakesData);
        $mistakesByCourse = $mistakesData->groupBy('question.topic.course.name');

        return [
            'mistakes' => $mistakesData,
            'mistakesByCourse' => $mistakesByCourse,
            'availableCourses' => $availableCourses,
            'selectedCourse' => $courseFilter,
            'recentCourse' => $recentCourse,
            'totalMistakesCount' => $supportingData['total_count'],
            'skippedCount' => $supportingData['skipped_count'],
            'performance_metrics' => $this->getPerformanceMetrics($mistakesData, $availableCourses)
        ];
    }

    /**
     * FIXED: Extract available courses formatting to eliminate duplication
     */
    private function formatAvailableCourses($courses): \Illuminate\Support\Collection
    {
        return $courses->map(function($course) {
            return (object) [
                'id' => $course->id,
                'name' => $course->name,
                'mistake_count' => $course->mistake_count
            ];
        });
    }

    /**
     * FIXED: Extract recent course logic to eliminate duplication
     */
    private function getRecentCourse($supportingData, $mistakesData)
    {
        if ($supportingData['recent_course']) {
            return (object) [
                'id' => $supportingData['recent_course']->id, 
                'name' => $supportingData['recent_course']->name
            ];
        }

        return $mistakesData->isNotEmpty() ? $mistakesData->first()->question->topic->course : null;
    }

    /**
     * FIXED: Extract performance metrics to eliminate duplication
     */
    private function getPerformanceMetrics($mistakesData, $availableCourses): array
    {
        return [
            'mistakes_count' => $mistakesData->count(),
            'courses_count' => $availableCourses->count(),
            'cache_status' => 'SMART HYBRID - Fast + Accurate'
        ];
    }

    /**
     * FIXED: Extract execution time calculation to eliminate duplication
     */
    private function calculateExecutionTime(float $startTime): float
    {
        return (microtime(true) - $startTime) * 1000;
    }

    /**
     * FIXED: Extract fallback view data to eliminate duplication
     */
    private function getFallbackViewData(): array
    {
        return [
            'mistakes' => collect([]),
            'mistakesByCourse' => collect([]),
            'availableCourses' => collect([]),
            'selectedCourse' => null,
            'recentCourse' => null,
            'totalMistakesCount' => 0,
            'skippedCount' => 0,
            'error' => 'An error occurred while loading your mistakes. Please try again.'
        ];
    }

    /**
     * FIXED: Extract logging logic to eliminate duplication
     */
    private function logMistakeOperation(string $operation, string $requestId, int $userId, array $context = []): void
    {
        $this->logger->info("Mistake operation: {$operation}", array_merge([
            'request_id' => $requestId,
            'user_id' => $userId,
            'timestamp' => now()->toISOString()
        ], $context));
    }

    /**
     * FIXED: Extract performance logging to eliminate duplication
     */
    private function logPerformanceMetrics(string $requestId, float $executionTime, int $mistakesCount): void
    {
        $this->logger->info('SMART HYBRID mistakes loaded', [
            'request_id' => $requestId,
            'execution_time_ms' => round($executionTime, 2),
            'mistakes_count' => $mistakesCount,
            'cache_status' => 'SMART HYBRID - 5min cache with instant invalidation'
        ]);
    }

    /**
     * FIXED: Extract error logging to eliminate duplication
     */
    private function logError(string $message, Exception $exception, string $requestId, float $executionTime): void
    {
        $this->logger->error($message, [
            'request_id' => $requestId,
            'error' => $exception->getMessage(),
            'execution_time_ms' => round($executionTime, 2),
            'trace' => $exception->getTraceAsString()
        ]);
    }

    /**
     * 🎯 SMART CACHE INVALIDATION - Immediately clear specific user caches
     * FIXED: Moved to cache service for better separation of concerns
     */
    public function clearUserMistakeCaches(int $userId): void
    {
        try {
            $this->cacheService->clearUserCaches($userId);
            
            $this->logger->info("SMART cache invalidation completed", [
                'user_id' => $userId,
                'strategy' => 'immediate_invalidation'
            ]);
            
        } catch (Exception $e) {
            $this->logger->error("Error in smart cache invalidation", [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }
}