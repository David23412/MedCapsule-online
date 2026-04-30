<?php

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Http\Requests\RandomQuizRequest;
use App\Services\QuizService;
use App\Services\QuizPerformanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Course;
use App\Models\Question;
use Illuminate\Support\Collection;

/**
 * Optimized Random Quiz Controller
 * Handles random quiz generation with maximum performance
 */
class OptimizedRandomQuizController extends Controller
{
    private const RANDOM_SESSION_TTL_SECONDS = 1800;

    protected $quizService;

    public function __construct(QuizService $quizService)
    {
        $this->middleware('auth');
        $this->quizService = $quizService;
    }

    /**
     * Show random quiz page
     */
    public function showRandomQuiz(Request $request)
    {
        // Get parameters from the modal form
        $questionCount = (int) $request->input('question_limit', 10);
        $durationMinutes = (float) $request->input('duration_minutes', 15);
        $selectedCourseIds = $request->input('selected_courses', []);
        $selectedSources = $request->input('sources', ['all']);
        
        if (!is_array($selectedSources)) {
            $selectedSources = [$selectedSources];
        }

        // Handle 'all' option within the array
        if (in_array('all', $selectedSources)) {
            $selectedSources = ['all'];
        }

        $user = auth()->user();
        $sessionKey = $this->buildRandomSessionKey($user->id, 'quiz', $questionCount, $durationMinutes, $selectedCourseIds, $selectedSources);

        $options = [
            'sources' => $selectedSources,
            'course_ids' => $selectedCourseIds,
            'difficulty' => 'mixed'
        ];

        $result = $this->resolveRandomQuizSession($request, $sessionKey, $user, $questionCount, $options);

        if (!$result['success']) {
            return redirect()->back()->with('error', 'Unable to generate random quiz. Please try again.');
        }

        // If we got fewer questions than requested, adjust the count
        $actualQuestionCount = count($result['questions']);
        if ($actualQuestionCount < $questionCount) {
        }

        // Keep selected course labels limited to fully accessible courses.
        $selectedCourses = collect();
        if (!empty($selectedCourseIds)) {
            $courses = Course::query()
                ->select('id', 'name')
                ->whereIn('id', $selectedCourseIds)
                ->get();
            $accessLevels = $user->getCourseAccessLevels($courses->pluck('id')->all());
            $selectedCourses = $courses
                ->filter(fn (Course $course) => ($accessLevels[$course->id] ?? null) === \App\Models\User::COURSE_ACCESS_FULL)
                ->values();
        }

        // Fallback to first fully accessible enrolled course if none selected
        if ($selectedCourses->isEmpty()) {
            $enrolledCourses = $user->courses()
                ->select('courses.id', 'courses.name')
                ->get();
            $accessLevels = $user->getCourseAccessLevels($enrolledCourses->pluck('id')->all());
            $selectedCourses = $enrolledCourses
                ->filter(fn (Course $course) => ($accessLevels[$course->id] ?? null) === \App\Models\User::COURSE_ACCESS_FULL)
                ->take(1)
                ->values();
            if ($selectedCourses->isEmpty()) {
                $selectedCourses = collect([new \App\Models\Course(['id' => 1, 'name' => 'General'])]);
            }
        }

        // ✅ ZERO N+1 BADGE ENRICHMENT for random quiz
        \App\Models\Question::enrichWithBadges($result['questions'], 0);

        return view('quiz.random_quiz', [
            'questions' => $result['questions'],
            'selectedCourses' => $selectedCourses,
            'duration_minutes' => $durationMinutes,
            'requested_questions' => $questionCount,
            'actual_questions' => $actualQuestionCount,
            'quiz_mode' => 'quiz',
            'random_session_key' => $sessionKey,
        ]);
    }

    /**
     * Show random tutor quiz page
     */
    public function showRandomTutorQuiz(Request $request)
    {
        // Get parameters from the modal form
        $questionCount = (int) $request->input('question_limit', 10);
        $durationMinutes = (float) $request->input('duration_minutes', 15);
        $selectedCourseIds = $request->input('selected_courses', []);
        $selectedSources = $request->input('sources', ['all']);

        if (!is_array($selectedSources)) {
            $selectedSources = [$selectedSources];
        }

        // Handle 'all' option within the array
        if (in_array('all', $selectedSources)) {
            $selectedSources = ['all'];
        }

        $user = auth()->user();
        $sessionKey = $this->buildRandomSessionKey($user->id, 'tutor', $questionCount, $durationMinutes, $selectedCourseIds, $selectedSources);

        $options = [
            'sources' => $selectedSources,
            'course_ids' => $selectedCourseIds,
            'difficulty' => 'mixed'
        ];

        $result = $this->resolveRandomQuizSession($request, $sessionKey, $user, $questionCount, $options);

        if (!$result['success']) {
            return redirect()->back()->with('error', 'Unable to generate random tutor quiz. Please try again.');
        }

        // If we got fewer questions than requested, adjust the count
        $actualQuestionCount = count($result['questions']);
        if ($actualQuestionCount < $questionCount) {
        }

        // Keep selected course labels limited to fully accessible courses.
        $selectedCourses = collect();
        if (!empty($selectedCourseIds)) {
            $courses = Course::query()
                ->select('id', 'name')
                ->whereIn('id', $selectedCourseIds)
                ->get();
            $accessLevels = $user->getCourseAccessLevels($courses->pluck('id')->all());
            $selectedCourses = $courses
                ->filter(fn (Course $course) => ($accessLevels[$course->id] ?? null) === \App\Models\User::COURSE_ACCESS_FULL)
                ->values();
        }

        // Fallback to first fully accessible enrolled course if none selected
        if ($selectedCourses->isEmpty()) {
            $enrolledCourses = $user->courses()
                ->select('courses.id', 'courses.name')
                ->get();
            $accessLevels = $user->getCourseAccessLevels($enrolledCourses->pluck('id')->all());
            $selectedCourses = $enrolledCourses
                ->filter(fn (Course $course) => ($accessLevels[$course->id] ?? null) === \App\Models\User::COURSE_ACCESS_FULL)
                ->take(1)
                ->values();
            if ($selectedCourses->isEmpty()) {
                $selectedCourses = collect([new \App\Models\Course(['id' => 1, 'name' => 'General'])]);
            }
        }

        // ✅ ZERO N+1 BADGE ENRICHMENT for random tutor quiz
        \App\Models\Question::enrichWithBadges($result['questions'], 0);

        return view('tutor_mode', [
            'questions' => $result['questions'],
            'is_random_quiz' => true,
            'selectedCourses' => $selectedCourses,
            'duration_minutes' => $durationMinutes,
            'requested_questions' => $questionCount,
            'actual_questions' => $actualQuestionCount,
            // Pass dummy course/topic for unified template
            'course' => $selectedCourses->first() ?? new \App\Models\Course(['id' => 1, 'name' => 'Random Quiz']),
            'topic' => new \App\Models\Topic(['id' => 0, 'name' => 'Mixed Random Questions']),
            'random_session_key' => $sessionKey,
            'currentUserView' => [
                'profile_image_url' => auth()->user()->getProfileImageUrl(),
                'profile_fallback_url' => 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&size=200&background=007bff&color=ffffff',
            ],
        ]);
    }

    /**
     * Generate random quiz with ultra-fast performance - LETHAL FIX
     */
    public function generateRandomQuiz(RandomQuizRequest $request): JsonResponse
    {
        return QuizPerformanceService::trackQuizGeneration(0, auth()->id(), function () use ($request) {
            try {
                $validatedData = $request->getValidatedWithDefaults();
                
                // LETHAL FIX: NUCLEAR CACHE BYPASS - Force fresh generation every time
                $result = $this->quizService->generateRandomQuiz(
                    auth()->user(),
                    $validatedData['question_count'] ?? 10,
                    $validatedData
                );

                return response()->json([
                    'success' => true,
                    'data' => $result,
                    'cache_key' => 'nuclear_bypass_' . time(),
                    'performance' => [
                        'cached' => false,
                        'generation_type' => 'nuclear_fresh',
                        'validation_type' => 'nuclear_database_check'
                    ]
                ]);

            } catch (\Exception $e) {
                Log::error('LETHAL FIX: Random quiz generation failed', [
                    'user_id' => auth()->id(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Random quiz generation failed. Please try again.',
                    'error_code' => 'RANDOM_QUIZ_FAILED'
                ], 500);
            }
        });
    }

    /**
     * Grade random quiz with optimized performance
     */
    public function gradeRandomQuiz(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            // Validate the submission data
            $validated = $request->validate([
                'answers' => 'required|array',
                'answers.*' => 'nullable|string|max:2000',
                'time_taken' => 'required|integer|min:0',
                'mode' => 'nullable|string|in:quiz,tutor,random',
                'session_key' => 'nullable|string|max:120',
            ]);

            // For random quizzes, we need to handle grading differently since questions come from multiple topics
            // We'll create a synthetic topic/course context for the grading service
            $answers = $validated['answers'];
            $timeTaken = $validated['time_taken'];
            $mode = $validated['mode'] ?? 'random';

            // Get the questions to determine topics
            $questionIds = array_keys($answers);
            $questions = \App\Models\Question::whereIn('id', $questionIds)
                ->with(['topic', 'topic.course'])
                ->get();

            if ($questions->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid questions found for grading'
                ], 400);
            }

            // For random quiz, we'll use the first question's topic/course as context
            // This is a simplification - in a more complex system, you might want to handle multi-topic grading differently
            $firstQuestion = $questions->first();
            $topic = $firstQuestion->topic;
            $course = $topic->course;

            // Ensure topic and course exist
            if (!$topic || !$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid question context - missing topic or course'
                ], 400);
            }

            // Use the GradingService to grade the quiz
            $gradingResult = app(\App\Services\GradingService::class)->gradeQuiz(
                $user,
                $answers,
                $timeTaken,
                $mode,
                $topic,
                $course
            );

            // Ensure the response has the correct field names for the frontend
            $gradingResult['total'] = $gradingResult['totalQuestions'];
            $gradingResult['message'] = $gradingResult['encouragement'] ?? 'Quiz completed!';
            $userId = (int) $user->id;
            $score = (int) ($gradingResult['score'] ?? 0);
            $totalQuestions = (int) ($gradingResult['totalQuestions'] ?? 1);
            $percentageGrade = (float) ($gradingResult['percentageGrade'] ?? 0);
            $courseName = $course->name ?? 'Random Quiz';
            $topicName = $topic->name ?? 'Mixed Topics';

            app()->terminating(function () use ($user, $userId, $score, $totalQuestions, $percentageGrade, $courseName, $topicName) {
                \App\Services\NotificationTriggerService::onQuizCompleted($user, [
                    'score' => $score,
                    'total_questions' => $totalQuestions,
                    'percentage' => $percentageGrade,
                    'course_name' => $courseName,
                    'topic_name' => $topicName,
                    'user_quiz_count' => $this->getUserQuizCount($userId),
                    'quiz_type' => 'random',
                ]);
            });

            if (!empty($validated['session_key'])) {
                $request->session()->forget('active_random_quizzes.' . $validated['session_key']);
            }

            return response()->json($gradingResult);

        } catch (\Exception $e) {
            Log::error('Random quiz grading failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Random quiz grading failed. Please try again.',
                'error_code' => 'RANDOM_QUIZ_GRADING_FAILED'
            ], 500);
        }
    }

    /**
     * Get user's total quiz count for first attempt detection
     */
    private function getUserQuizCount(int $userId): int
    {
        $cacheKey = "user_quiz_count_{$userId}";
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($userId) {
            return \Illuminate\Support\Facades\DB::table('quiz_attempts')
                ->where('quiz_attempts.user_id', $userId)
                ->count();
        });
    }

    private function buildRandomSessionKey(
        int $userId,
        string $mode,
        int $questionCount,
        float $durationMinutes,
        array $selectedCourseIds,
        array $selectedSources
    ): string {
        $courseIds = array_values(array_map('intval', $selectedCourseIds));
        sort($courseIds);

        $sources = array_values(array_map('strval', $selectedSources));
        sort($sources);

        return sha1(json_encode([
            'user_id' => $userId,
            'mode' => $mode,
            'question_count' => $questionCount,
            'duration_minutes' => $durationMinutes,
            'course_ids' => $courseIds,
            'sources' => $sources,
        ]));
    }

    private function resolveRandomQuizSession(
        Request $request,
        string $sessionKey,
        $user,
        int $questionCount,
        array $options
    ): array {
        $sessionPayload = $request->session()->get("active_random_quizzes.{$sessionKey}", []);
        $savedAt = $sessionPayload['saved_at'] ?? null;

        if ($this->isRandomSessionExpired($savedAt)) {
            $request->session()->forget("active_random_quizzes.{$sessionKey}");
            $sessionPayload = [];
        }

        $storedQuestionIds = collect($sessionPayload['question_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        if (!empty($storedQuestionIds)) {
            $storedQuestions = $this->fetchQuestionsInOrder($storedQuestionIds);
            if ($storedQuestions->count() === count($storedQuestionIds)) {
                return [
                    'success' => true,
                    'questions' => $storedQuestions,
                    'questions_by_topic' => $storedQuestions->groupBy('topic_id'),
                    'total_questions' => $storedQuestions->count(),
                    'topics_covered' => $storedQuestions->pluck('topic_id')->unique()->values()->all(),
                ];
            }

            $request->session()->forget("active_random_quizzes.{$sessionKey}");
        }

        $result = $this->quizService->generateRandomQuiz($user, $questionCount, $options);
        if (($result['success'] ?? false) && !empty($result['questions'])) {
            $request->session()->put("active_random_quizzes.{$sessionKey}", [
                'question_ids' => collect($result['questions'])->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                'saved_at' => now()->toIso8601String(),
            ]);
        }

        return $result;
    }

    private function isRandomSessionExpired(?string $savedAt): bool
    {
        if (empty($savedAt)) {
            return true;
        }

        try {
            return now()->diffInSeconds(\Carbon\Carbon::parse($savedAt)) >= self::RANDOM_SESSION_TTL_SECONDS;
        } catch (\Throwable $exception) {
            return true;
        }
    }

    private function fetchQuestionsInOrder(array $questionIds): Collection
    {
        $idOrderMap = array_flip($questionIds);

        return Question::query()
            ->select([
                'id', 'topic_id', 'question', 'question_type',
                'option_a', 'option_b', 'option_c', 'option_d',
                'correct_answer', 'explanation', 'image_url', 'source',
                'alternative_answers'
            ])
            ->whereIn('id', $questionIds)
            ->get()
            ->sortBy(fn (Question $question) => $idOrderMap[$question->id] ?? PHP_INT_MAX)
            ->values();
    }

    /**
     * Get random quiz statistics
     */
    public function getRandomQuizStats(): JsonResponse
    {
        try {
            $cacheKey = \App\Services\QuizUltraConsolidatedCacheService::generateCacheKey('random_quiz_stats', [
                'user_id' => auth()->id()
            ]);
            
            $stats = Cache::remember($cacheKey, 600, function () {
                return $this->quizService->getRandomQuizStats(auth()->id());
            });

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Random quiz stats failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load quiz statistics'
            ], 500);
        }
    }
}
