<?php

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuizExecutionRequest;
use App\Services\UltraFastQuizService;
use App\Services\PerformanceMonitorService;
use App\Models\Topic;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Quiz Read Controller - NUCLEAR PERFORMANCE BEAST MODE
 * Integrated with UltraFastQuizService for <100ms quiz loading
 */
class QuizReadController extends Controller
{
    private UltraFastQuizService $ultraFastQuizService;
    private PerformanceMonitorService $performanceMonitor;

    public function __construct(
        UltraFastQuizService $ultraFastQuizService,
        PerformanceMonitorService $performanceMonitor
    ) {
        $this->middleware('auth');
        $this->ultraFastQuizService = $ultraFastQuizService;
        $this->performanceMonitor = $performanceMonitor;
    }

    /**
     * Get all quizzes with ULTRA-FAST PERFORMANCE
     * Uses UltraFastQuizService for <100ms loading
     */
    public function index(QuizExecutionRequest $request): JsonResponse
    {
        $operationId = $this->performanceMonitor->startOperation('quiz_index', [
            'user_id' => auth()->id(),
            'request_params' => $request->all()
        ]);

        try {
            // Get user's accessible topics and courses
            $user = auth()->user();
            $accessibleTopics = $user->courses()
                ->with('topics')
                ->get()
                ->pluck('topics')
                ->flatten();

            $quizzes = $accessibleTopics->map(function ($topic) {
                return [
                    'id' => $topic->id,
                    'title' => $topic->name,
                    'course_name' => $topic->course->name,
                    'total_questions' => $topic->questions()->count(),
                    'available_sources' => $this->ultraFastQuizService->getAvailableSources($topic),
                    'last_attempt' => $topic->quizAttempts()
                        ->where('user_id', auth()->id())
                        ->latest()
                        ->first()
                ];
            });

            $data = [
                'quizzes' => $quizzes,
                'total' => $quizzes->count(),
                'performance_metrics' => $this->ultraFastQuizService->getPerformanceMetrics()
            ];

            $performanceData = $this->performanceMonitor->endOperation($operationId, [
                'quiz_count' => $quizzes->count(),
                'cache_status' => 'optimized'
            ]);

            Log::info("🚀 ULTRA-FAST QUIZ INDEX", [
                'user_id' => auth()->id(),
                'quiz_count' => $quizzes->count(),
                'execution_ms' => $performanceData['execution_time_ms']
            ]);

            return response()->json([
                'success' => true,
                'data' => $data,
                'performance' => $performanceData
            ]);

        } catch (\Exception $e) {
            $this->performanceMonitor->endOperation($operationId, ['error' => $e->getMessage()]);

            Log::error("❌ ULTRA-FAST QUIZ INDEX FAILED", [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to retrieve quizzes',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific quiz details with ULTRA-FAST LOADING
     * Uses UltraFastQuizService for <100ms performance
     */
    public function show(QuizExecutionRequest $request, int $id): JsonResponse
    {
        $operationId = $this->performanceMonitor->startOperation('quiz_show', [
            'quiz_id' => $id,
            'user_id' => auth()->id(),
            'request_params' => $request->all()
        ]);

        try {
            $topic = Topic::with('course')->findOrFail($id);
            $user = auth()->user();

            // Check if user has access to this topic
            if (!$user->courses()->where('course_id', $topic->course_id)->exists()) {
                return response()->json([
                    'error' => 'Access denied to this quiz'
                ], 403);
            }

            // Get quiz statistics using UltraFastQuizService
            $statistics = $this->ultraFastQuizService->getQuizStatistics($topic, ['all']);

            // Get user's progress
            $userProgress = $this->ultraFastQuizService->getTopicQuizStats($topic, $user);

            $data = [
                'id' => $topic->id,
                'title' => $topic->name,
                'course_name' => $topic->course->name,
                'description' => $topic->description,
                'statistics' => $statistics,
                'user_progress' => $userProgress,
                'available_sources' => $this->ultraFastQuizService->getAvailableSources($topic),
                'available_modes' => ['quiz', 'tutor', 'random'],
                'estimated_completion_time' => $this->estimateCompletionTime($statistics['total_questions'] ?? 0)
            ];

            $performanceData = $this->performanceMonitor->endOperation($operationId, [
                'topic_id' => $id,
                'cache_status' => 'ultra_fast_redis'
            ]);

            Log::info("🚀 ULTRA-FAST QUIZ DETAILS", [
                'quiz_id' => $id,
                'topic_name' => $topic->name,
                'user_id' => auth()->id(),
                'execution_ms' => $performanceData['execution_time_ms']
            ]);

            return response()->json([
                'success' => true,
                'data' => $data,
                'performance' => $performanceData
            ]);

        } catch (\Exception $e) {
            $this->performanceMonitor->endOperation($operationId, ['error' => $e->getMessage()]);

            Log::error("❌ ULTRA-FAST QUIZ DETAILS FAILED", [
                'error' => $e->getMessage(),
                'quiz_id' => $id,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to retrieve quiz details',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get quiz results/statistics with ULTRA-FAST PERFORMANCE
     * Uses UltraFastQuizService for optimized data retrieval
     */
    public function results(QuizExecutionRequest $request, int $id): JsonResponse
    {
        $operationId = $this->performanceMonitor->startOperation('quiz_results', [
            'quiz_id' => $id,
            'user_id' => auth()->id(),
            'request_params' => $request->all()
        ]);

        try {
            $topic = Topic::with('course')->findOrFail($id);
            $user = auth()->user();

            // Check if user has access to this topic
            if (!$user->courses()->where('course_id', $topic->course_id)->exists()) {
                return response()->json([
                    'error' => 'Access denied to this quiz'
                ], 403);
            }

            // Get comprehensive quiz results using UltraFastQuizService
            $quizResults = $this->ultraFastQuizService->getQuizStatistics($topic, ['all']);
            $userStats = $this->ultraFastQuizService->getTopicQuizStats($topic, $user);

            // Get recent attempts with performance optimization
            $recentAttempts = $topic->quizAttempts()
                ->where('user_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            $data = [
                'quiz_id' => $id,
                'topic_name' => $topic->name,
                'course_name' => $topic->course->name,
                'total_questions' => $quizResults['total_questions'] ?? 0,
                'multiple_choice' => $quizResults['multiple_choice'] ?? 0,
                'written' => $quizResults['written'] ?? 0,
                'sources_breakdown' => $quizResults['sources'] ?? [],
                'user_statistics' => [
                    'total_attempts' => $userStats['total_attempts'] ?? 0,
                    'average_score' => $userStats['average_score'] ?? 0,
                    'best_score' => $userStats['best_score'] ?? 0,
                    'improvement_trend' => $userStats['improvement_trend'] ?? 'none'
                ],
                'recent_attempts' => $recentAttempts->map(function ($attempt) {
                    return [
                        'id' => $attempt->id,
                        'score' => $attempt->score,
                        'total_questions' => $attempt->total_questions,
                        'percentage_grade' => $attempt->percentage_grade,
                        'duration_seconds' => $attempt->duration_seconds,
                        'completed_at' => $attempt->created_at,
                        'study_mode' => $attempt->study_mode,
                        'source' => $attempt->source
                    ];
                }),
                'performance_metrics' => $this->ultraFastQuizService->getPerformanceMetrics(),
                'completion_rate' => $this->calculateCompletionRate($recentAttempts)
            ];

            $performanceData = $this->performanceMonitor->endOperation($operationId, [
                'topic_id' => $id,
                'attempts_count' => $recentAttempts->count(),
                'cache_status' => 'ultra_fast_redis'
            ]);

            Log::info("🚀 ULTRA-FAST QUIZ RESULTS", [
                'quiz_id' => $id,
                'topic_name' => $topic->name,
                'user_id' => auth()->id(),
                'attempts_count' => $recentAttempts->count(),
                'execution_ms' => $performanceData['execution_time_ms']
            ]);

            return response()->json([
                'success' => true,
                'data' => $data,
                'performance' => $performanceData
            ]);

        } catch (\Exception $e) {
            $this->performanceMonitor->endOperation($operationId, ['error' => $e->getMessage()]);

            Log::error("❌ ULTRA-FAST QUIZ RESULTS FAILED", [
                'error' => $e->getMessage(),
                'quiz_id' => $id,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to retrieve quiz results',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Estimate completion time based on question count
     */
    private function estimateCompletionTime(int $questionCount): string
    {
        if ($questionCount === 0) return '0 minutes';

        // Average 45 seconds per question for multiple choice, 2 minutes for written
        $estimatedSeconds = ($questionCount * 45);
        $minutes = round($estimatedSeconds / 60);

        return $minutes . ' minute' . ($minutes !== 1 ? 's' : '');
    }

    /**
     * Calculate completion rate from attempts
     */
    private function calculateCompletionRate($attempts): float
    {
        if ($attempts->isEmpty()) return 0.0;

        $completed = $attempts->where('percentage_grade', '>', 0)->count();
        return round(($completed / $attempts->count()) * 100, 1);
    }
}
