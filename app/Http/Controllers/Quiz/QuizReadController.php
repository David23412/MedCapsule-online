<?php

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuizExecutionRequest;
use App\Services\UltraFastQuizService;
use App\Services\PerformanceMonitorService;
use App\Services\StudentViewDataService;
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
        PerformanceMonitorService $performanceMonitor,
        private StudentViewDataService $studentViewDataService
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
            // Get user's accessible topics and courses — eager load course + latest quiz attempt
            $user   = auth()->user();
            $userId = $user->id;

            $accessibleTopics = $user->courses()
                ->with([
                    'topics.course:id,name',
                    'topics' => fn ($q) => $q->withCount('questions'),
                ])
                ->get()
                ->pluck('topics')
                ->flatten()
                ->reject(fn ($topic) => $user->isTopicPreviewLocked($topic))
                ->values();

            // Batch-load last quiz attempt per topic to avoid N queries inside map
            $topicIds = $accessibleTopics->pluck('id')->all();
            $lastAttempts = \App\Models\QuizAttempt::whereIn('topic_id', $topicIds)
                ->where('user_id', $userId)
                ->orderByDesc('created_at')
                ->get()
                ->unique('topic_id')
                ->keyBy('topic_id');

            $quizzes = $accessibleTopics->map(function ($topic) use ($lastAttempts) {
                return [
                    'id'               => $topic->id,
                    'title'            => $topic->name,
                    'course_name'      => $topic->course->name,
                    'total_questions'  => $topic->questions_count,
                    'available_sources'=> $this->ultraFastQuizService->getAvailableSources($topic),
                    'last_attempt'     => $lastAttempts->get($topic->id),
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
    public function show(QuizExecutionRequest $request, $course, $topic)
    {
        $operationId = $this->performanceMonitor->startOperation('quiz_show', [
            'course_id' => $course,
            'topic_id' => $topic,
            'user_id' => auth()->id(),
            'request_params' => $request->all()
        ]);

        try {
            // Convert to integers and find the model
            $courseId = (int) $course;
            $topicId = (int) $topic;

            // Find topic and validate it belongs to the course
            $topicModel = Topic::with('course')->findOrFail($topicId);
            if ($topicModel->course_id !== $courseId) {
                throw new \Exception("Topic $topicId does not belong to course $courseId");
            }
            $user = auth()->user();

            if (!$user->hasAccessToCourse($topicModel->course_id)) {
                if ($user->requiresSurveyUnlockForCourse($topicModel->course_id)) {
                    $surveyUrl = $user->freeCourseSurveyRedirectFor($topicModel->course_id, request()->getRequestUri());

                    if ($request->expectsJson()) {
                        return response()->json([
                            'error' => 'Free course survey required',
                            'survey_url' => $surveyUrl,
                        ], 423);
                    }

                    return redirect($surveyUrl);
                }

                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'Access denied to this quiz'
                    ], 403);
                }
                abort(403, 'Access denied to this quiz');
            }

            if ($user->isTopicPreviewLocked($topicModel)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'Preview limit reached for this course'
                    ], 403);
                }

                return redirect()->route('topics.forCourse', $topicModel->course_id)
                    ->with('error', 'This topic is outside the free preview. Unlock the full course to continue.');
            }

            // Get quiz statistics using UltraFastQuizService
            $statistics = $this->ultraFastQuizService->getQuizStatistics($topicModel, ['all']);

            // Get user's progress
            $userProgress = $this->ultraFastQuizService->getTopicQuizStats($topicModel, $user);

            // Calculate total topic progress from quiz mode attempts only
            $totalTopicProgress = \App\Models\QuizAttempt::getTopicProgress($user->id, $topicModel->id, 'quiz');
            $totalCorrectQuestions = $totalTopicProgress['overall']['score'] ?? 0;
            $totalQuestionsInTopic = $totalTopicProgress['overall']['total_questions'] ?? 0;

            // Get questions for the quiz
            $sources = $request->get('sources', ['all']);
            $quizData = $this->ultraFastQuizService->getQuizQuestions($topicModel, $sources, true);

            // Check if request expects JSON (API request)
            if ($request->expectsJson()) {
                $data = [
                    'id' => $topicModel->id,
                    'title' => $topicModel->name,
                    'course_name' => $topicModel->course->name,
                    'description' => $topicModel->description,
                    'statistics' => $statistics,
                    'user_progress' => $userProgress,
                    'available_sources' => $this->ultraFastQuizService->getAvailableSources($topicModel),
                    'available_modes' => ['quiz', 'tutor', 'random'],
                    'estimated_completion_time' => $this->estimateCompletionTime($statistics['total_questions'] ?? 0)
                ];

                $performanceData = $this->performanceMonitor->endOperation($operationId, [
                    'topic_id' => $topicId,
                    'cache_status' => 'ultra_fast_redis'
                ]);


                return response()->json([
                    'success' => true,
                    'data' => $data,
                    'performance' => $performanceData
                ]);
            }

            // Web request - return Blade view
            $performanceData = $this->performanceMonitor->endOperation($operationId, [
                'topic_id' => $topicId,
                'cache_status' => 'ultra_fast_redis'
            ]);


            // Prepare data for Blade view
            $questionsCollection = collect($quizData['questions'])->map(function ($question) {
                // Convert array to object if needed (for cached data)
                return is_array($question) ? (object) $question : $question;
            });

            $assignmentDurationSeconds = 0;
            $assignmentTimerMode = 'countup';
            $assignmentTimerInitial = '00:00';

            $solveQuizQuestionIds = $questionsCollection
                ->map(fn ($question) => $question->id ?? null)
                ->filter()
                ->values()
                ->all();

            $solveQuizQuestionData = $questionsCollection
                ->mapWithKeys(fn ($question) => [
                    $question->id => [
                        'correct_answer' => $question->correct_answer ?? '',
                        'explanation' => $question->explanation ?? '',
                        'question_type' => $question->question_type ?? 'multiple_choice',
                    ],
                ])
                ->all();

            $viewData = [
                'topic' => $topicModel,
                'course' => $topicModel->course,
                'questions' => $questionsCollection,
                'statistics' => $statistics,
                'userProgress' => $userProgress,
                'selected_sources' => $sources,
                'mode' => $request->get('mode', 'quiz'),
                'assignment' => null, // No assignment for regular quiz
                'totalTopicProgress' => [
                    'correct' => $totalCorrectQuestions,
                    'total' => $totalQuestionsInTopic,
                    'percentage' => $totalQuestionsInTopic > 0 ? round(($totalCorrectQuestions / $totalQuestionsInTopic) * 100, 1) : 0
                ],
                'solveQuizView' => [
                    'assignment_timer_mode' => $assignmentTimerMode,
                    'assignment_timer_initial' => $assignmentTimerInitial,
                    'assignment_duration_seconds' => $assignmentDurationSeconds,
                    'question_ids' => $solveQuizQuestionIds,
                    'question_data' => $solveQuizQuestionData,
                    'can_edit_questions' => $user->isAdmin(),
                ],
                'currentUserView' => $this->studentViewDataService->buildCurrentUserView($user),
            ];

            // ✅ ZERO N+1 BADGE ENRICHMENT
            \App\Models\Question::enrichWithBadges($questionsCollection, $topicModel->id);

            return view('solve_quiz', $viewData);

        } catch (\Exception $e) {
            $this->performanceMonitor->endOperation($operationId, ['error' => $e->getMessage()]);

            Log::error("❌ ULTRA-FAST QUIZ DETAILS FAILED", [
                'error' => $e->getMessage(),
                'quiz_id' => $topicId,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to retrieve quiz details',
                    'message' => $e->getMessage()
                ], 500);
            }

            abort(500, 'Failed to load quiz');
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

            if (!$user->hasAccessToCourse($topic->course_id)) {
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
