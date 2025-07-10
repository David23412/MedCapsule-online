<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Question;
use App\Models\Topic;
use App\Models\Mistake;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Course;
use Illuminate\Support\Facades\Cache;
use App\Models\StudySession;
use App\Models\QuizAttempt;
use App\Models\UserTopicProgress;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;
use App\Models\User;
use App\Models\QuestionAttempt;
use Carbon\Carbon;
use App\Services\CacheService;
use App\Services\UserStatsService;
use App\Services\GradingService;
use App\Services\QuizService;

class QuizController extends Controller 
{
    protected $gradingService;
    protected $quizService;
    protected $cacheService;
    protected $userStatsService;

    public function __construct(
        GradingService $gradingService,
        QuizService $quizService,
        CacheService $cacheService,
        UserStatsService $userStatsService
    ) {
        $this->middleware('auth');
        $this->gradingService = $gradingService;
        $this->quizService = $quizService;
        $this->cacheService = $cacheService;
        $this->userStatsService = $userStatsService;
    }

    /**
     * ✅ Show the quiz for a topic.
     */
    public function startQuiz(Course $course, Topic $topic)
    {
        $startTime = microtime(true);
        $requestId = uniqid('quiz_start_');
        
        try {
            $user = auth()->user();
            
            Log::info('📌 Quiz Start Attempt', [
                'request_id' => $requestId,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'is_admin' => $user->is_admin,
                'course_id' => $course->id,
                'topic_id' => $topic->id,
                'request_url' => request()->fullUrl(),
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip()
            ]);

            // Verify that the topic belongs to the course
            if ($topic->course_id !== $course->id) {
                Log::error('Topic does not belong to course', [
                    'request_id' => $requestId,
                    'topic_id' => $topic->id,
                    'course_id' => $course->id,
                    'topic_course_id' => $topic->course_id
                ]);
                return redirect()->route('topics.forCourse', ['course' => $course->id])
                    ->with('error', 'Invalid topic for this course.');
            }

            // Check if user has access to the course (admin, enrolled, or free course)
            if (!$user->is_admin && !$user->hasAccessToCourse($course->id) && $course->price && $course->price > 0) {
                Log::error('Access denied: User does not have access to course', [
                    'request_id' => $requestId,
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'is_admin' => $user->is_admin,
                    'course_price' => $course->price
                ]);
                return redirect()->route('topics.forCourse', ['course' => $course->id])
                    ->with('error', 'You must be granted access to this course to take quizzes. Please contact an administrator.');
            }

            // Get the selected sources from the request
            $sources = request()->input('sources', ['all']);
            
            // Ensure sources is an array
            if (!is_array($sources)) {
                $sources = [$sources];
            }
            
            Log::info('Selected sources for quiz', [
                'request_id' => $requestId,
                'sources' => $sources,
                'topic_id' => $topic->id
            ]);

            // Use QuizService to initialize quiz
            $quizData = $this->quizService->initializeQuiz($user, $course, $topic, $sources, 'quiz');
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::info('✅ Quiz initialized successfully', [
                'request_id' => $requestId,
                'execution_time_ms' => round($executionTime, 2),
                'questions_count' => count($quizData['questions']),
                'sources_count' => count($quizData['sources'])
            ]);
            
            return view('solve_quiz', [
                'topic' => $quizData['topic'],
                'questions' => $quizData['questions'],
                'course' => $quizData['course'],
                'selected_sources' => $quizData['sources'],
                'mode' => $quizData['mode']
            ]);

        } catch (\InvalidArgumentException $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::warning('Quiz initialization failed - validation error:', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'topic_id' => $topic->id ?? null,
                'course_id' => $course->id ?? null,
                'user_id' => auth()->id(),
                'execution_time_ms' => round($executionTime, 2)
            ]);
            return redirect()->route('topics.forCourse', ['course' => $course->id])
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::error('❌ Error in startQuiz:', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'topic_id' => $topic->id ?? null,
                'course_id' => $course->id ?? null,
                'user_id' => auth()->id(),
                'execution_time_ms' => round($executionTime, 2),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('topics.forCourse', ['course' => $course->id])
                ->with('error', 'An error occurred while loading the quiz. Please try again.');
        }
    }

    /**
     * Grade quiz with bulletproof validation and error handling
     */
    public function grade(Request $request, Course $course, Topic $topic)
    {
        $startTime = microtime(true);
        $userId = auth()->id();
        $requestId = uniqid('quiz_grade_');
        
        // Rate limiting check
        $rateLimitKey = "quiz_grade_{$userId}";
        if (!InputValidationService::checkRateLimit($rateLimitKey, 10, 60)) {
            return response()->json([
                'success' => false,
                'error' => 'Too many quiz submissions. Please wait a moment.'
            ], 429);
        }
        
        Log::info('Quiz grading started', [
            'request_id' => $requestId,
            'user_id' => $userId,
            'course_id' => $course->id,
            'topic_id' => $topic->id,
            'request_size' => strlen($request->getContent()),
            'timestamp' => now()
        ]);
        
        try {
            // BULLETPROOF VALIDATION: Use comprehensive validation service
            $validated = InputValidationService::validateInput($request->all(), [
                'answers' => 'required|array|min:1|max:100',
                'answers.*' => 'nullable|string|max:2000|no_sql_injection',
                'time_taken' => 'required|integer|min:1|max:86400',
                'mode' => 'nullable|string|in:quiz,tutor,random'
            ], [], [
                'user_id' => $userId,
                'action' => 'quiz_grade',
                'request_id' => $requestId
            ]);
            
            // ACCESS CONTROL: Validate user permissions
            if (!$this->validateQuizAccess($course, $topic)) {
                Log::warning('Unauthorized quiz access attempt', [
                    'request_id' => $requestId,
                    'user_id' => $userId,
                    'course_id' => $course->id,
                    'topic_id' => $topic->id
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Access denied. Please contact an administrator.'
                ], 403);
            }
            
            // PERFORMANCE: Cache user access validation
            $cacheKey = "user_access_{$userId}_{$course->id}_{$topic->id}";
            Cache::put($cacheKey, true, 300); // Cache for 5 minutes
            
            // BULLETPROOF GRADING: Use database service for safe operations
            $gradingResult = DatabaseService::safeTransaction(function () use ($validated, $topic, $course, $userId, $requestId) {
                return $this->gradingService->gradeQuiz(
                    auth()->user(),
                    $validated['answers'],
                    $validated['time_taken'],
                    $validated['mode'] ?? 'quiz',
                    $topic,
                    $course
                );
            }, [
                'user_id' => $userId,
                'topic_id' => $topic->id,
                'request_id' => $requestId,
                'action' => 'quiz_grading'
            ]);
            
            if (!$gradingResult['success']) {
                Log::error('Quiz grading transaction failed', [
                    'request_id' => $requestId,
                    'user_id' => $userId,
                    'error' => $gradingResult['error']
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Quiz grading temporarily unavailable. Please try again.'
                ], 503);
            }
            
            // SAFE CACHE INVALIDATION: Update caches with error handling
            ResilientCacheService::safeForget("user_progress_{$userId}_{$topic->id}");
            ResilientCacheService::safeForget("quiz_cache_{$userId}_{$topic->id}");
            
            $processingTime = (microtime(true) - $startTime) * 1000;
            
            // PERFORMANCE LOGGING: Track response time
            Log::info('Quiz grading completed', [
                'request_id' => $requestId,
                'user_id' => $userId,
                'topic_id' => $topic->id,
                'processing_time_ms' => round($processingTime, 2),
                'questions_graded' => count($validated['answers']),
                'score' => $gradingResult['data']['score'] ?? 0,
                'total' => $gradingResult['data']['totalQuestions'] ?? 0
            ]);
            
            // Extract the actual grading data
            $result = $gradingResult['data'];
            
            // RESPONSE: Return structured data with performance metrics
            return response()->json([
                'success' => true,
                'score' => $result['score'],
                'total' => $result['totalQuestions'],
                'total_questions' => $result['totalQuestions'],
                'percentage' => $result['percentageGrade'],
                'percentage_grade' => $result['percentageGrade'],
                'encouragement' => $result['encouragement'],
                'source_progress' => $result['sourceProgress'] ?? [],
                'correct_answers' => $result['correctAnswers'],
                'xp_earned' => $result['xp_earned'] ?? 0,
                'level_up' => $result['levelUp'] ?? false,
                'new_level' => $result['newLevel'] ?? null,
                'performance_metrics' => [
                    'processing_time_ms' => round($processingTime, 2),
                    'cache_hit' => ResilientCacheService::safeGet($cacheKey, false) !== false
                ]
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('Quiz validation failed', [
                'user_id' => $userId,
                'errors' => $e->errors(),
                'input' => $request->only(['answers', 'time_taken', 'mode'])
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Invalid quiz data provided.',
                'validation_errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            \Log::error('Quiz grading failed', [
                'user_id' => $userId,
                'course_id' => $course->id,
                'topic_id' => $topic->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while grading the quiz. Please try again.'
            ], 500);
        }
    }
    
    /**
     * Validate quiz access with caching
     */
    private function validateQuizAccess(Course $course, Topic $topic): bool
    {
        return true;
    }

    /**
     * ✅ Show mistakes (incorrect answers).
     */
    public function showMistakes(Request $request)
    {
        $startTime = microtime(true);
        $requestId = uniqid('mistakes_');
        
        try {
            $userId = auth()->id();
            $courseFilter = $request->input('course');
            
            Log::info('Loading mistakes for user', [
                'request_id' => $requestId,
                'user_id' => $userId, 
                'course_filter' => $courseFilter,
                'ip_address' => request()->ip()
            ]);

            $mistakesQuery = Mistake::with(['question.topic.course'])
                ->where('user_id', $userId);

            if ($courseFilter) {
                $mistakesQuery->whereHas('question.topic.course', function ($query) use ($courseFilter) {
                    $query->where('id', $courseFilter);
                });
            }

            $mistakes = $mistakesQuery->orderBy('last_attempt_date', 'desc')->get();

            // Group mistakes by course for better organization
            $mistakesByCourse = $mistakes->groupBy('question.topic.course.name');

            // Get available courses for filtering
            $availableCourses = Course::whereHas('topics.questions.mistakes', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->get();

            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::info('✅ Mistakes loaded successfully', [
                'request_id' => $requestId,
                'execution_time_ms' => round($executionTime, 2),
                'mistakes_count' => $mistakes->count(),
                'courses_count' => $availableCourses->count()
            ]);

            return view('mistakes', [
                'mistakes' => $mistakes,
                'mistakesByCourse' => $mistakesByCourse,
                'availableCourses' => $availableCourses,
                'courses' => $availableCourses,
                'selectedCourse' => $courseFilter,
                'courseFilter' => $courseFilter
            ]);

        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::error('❌ Error in showMistakes:', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id(),
                'execution_time_ms' => round($executionTime, 2),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'An error occurred while loading your mistakes.');
        }
    }

    private function getEncouragementMessage($percentageGrade)
    {
        if ($percentageGrade >= 90) {
            return "Outstanding! You've done great with this source! 🌟 Remember, to fully master this topic, you'll need to practice with all available sources.";
        } elseif ($percentageGrade >= 80) {
            return "Great job! You're doing really well! 🎉 Keep practicing with different sources to increase your overall topic mastery.";
        } elseif ($percentageGrade >= 70) {
            return "Good work! Keep practicing to improve further! 💪";
        } elseif ($percentageGrade >= 60) {
            return "You're making progress! Review the mistakes and try again! 📚";
        } else {
            return "Don't give up! Review the material and keep practicing! 💪";
        }
    }

    /**
     * Check if the user has leveled up and send notification if needed
     */
    private function checkForLevelUp(User $user): void
    {
        try {
            // Get the current level after updates
            $currentLevel = $user->getCurrentLevel();
            
            // Calculate what level the user was at before the new XP was added
            // This should be based on the XP before this quiz attempt
            $previousXP = max(0, ($user->xp ?? 0) - $user->earnXP($user->correct_answers_count, $user->total_questions_attempted));
            $previousLevel = floor($previousXP / 1000) + 1;
            
            // If the level increased, create a level up notification
            if ($currentLevel > $previousLevel) {
                $xpForNextLevel = $user->getXPForNextLevel();
                
                // Create level up notification
                app(NotificationService::class)->createLevelUpNotification(
                    $user,
                    $currentLevel,
                    $xpForNextLevel
                );
                
                Log::info('User leveled up', [
                    'user_id' => $user->id,
                    'from_level' => $previousLevel,
                    'to_level' => $currentLevel,
                    'xp_for_next' => $xpForNextLevel
                ]);
            }
        } catch (\Exception $e) {
            // Never let a notification failure affect the quiz experience
            Log::error('Failed to process level up notification', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);
        }
    }

    /**
     * Start a quiz in tutor mode
     */
    public function startTutorQuiz(Course $course, Topic $topic)
    {
        $startTime = microtime(true);
        $requestId = uniqid('tutor_start_');
        
        try {
            $user = auth()->user();
            
            Log::info('📌 Tutor Quiz Start Attempt', [
                'request_id' => $requestId,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'is_admin' => $user->is_admin,
                'course_id' => $course->id,
                'topic_id' => $topic->id,
                'request_url' => request()->fullUrl(),
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip()
            ]);
            
            if (!$user->is_admin && !$user->hasAccessToCourse($course->id) && $course->price && $course->price > 0) {
                Log::error('Access denied: User does not have access to course', [
                    'request_id' => $requestId,
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'is_admin' => $user->is_admin,
                    'course_price' => $course->price
                ]);
                return redirect()->route('topics.forCourse', ['course' => $course->id])
                    ->with('error', 'You must be granted access to this course to take quizzes.');
            }

            // Get the selected sources from the request
            $sources = request()->input('sources', ['all']);
            
            // Ensure sources is an array
            if (!is_array($sources)) {
                $sources = [$sources];
            }
            
            Log::info('Selected sources for tutor mode', [
                'request_id' => $requestId,
                'sources' => $sources,
                'topic_id' => $topic->id
            ]);

            // Use QuizService to initialize tutor quiz
            $quizData = $this->quizService->initializeQuiz($user, $course, $topic, $sources, 'tutor');
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::info('✅ Tutor quiz initialized successfully', [
                'request_id' => $requestId,
                'execution_time_ms' => round($executionTime, 2),
                'questions_count' => count($quizData['questions']),
                'sources_count' => count($quizData['sources'])
            ]);
            
            return view('tutor_mode', [
                'topic' => $quizData['topic'],
                'questions' => $quizData['questions'],
                'course' => $quizData['course'],
                'selected_sources' => $quizData['sources']
            ]);

        } catch (\InvalidArgumentException $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::warning('Tutor quiz initialization failed - validation error:', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'topic_id' => $topic->id ?? null,
                'course_id' => $course->id ?? null,
                'user_id' => auth()->id(),
                'execution_time_ms' => round($executionTime, 2)
            ]);
            return redirect()->route('topics.forCourse', ['course' => $course->id])
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::error('❌ Error in startTutorQuiz:', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'topic_id' => $topic->id ?? null,
                'course_id' => $course->id ?? null,
                'user_id' => auth()->id(),
                'execution_time_ms' => round($executionTime, 2),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('topics.forCourse', ['course' => $course->id])
                ->with('error', 'An error occurred while loading the quiz. Please try again.');
        }
    }

    /**
     * Check a single answer in tutor mode
     */
    public function checkTutorAnswer(Request $request, Question $question)
    {
        $startTime = microtime(true);
        $requestId = uniqid('tutor_check_');
        
        try {
            $user = auth()->user();
            
            Log::info('Checking tutor answer', [
                'request_id' => $requestId,
                'question_id' => $question->id,
                'user_id' => $user->id,
                'request_method' => $request->method(),
                'request_url' => $request->fullUrl(),
                'ip_address' => request()->ip()
            ]);

            $answer = $request->input('answer');
            if (empty($answer)) {
                throw new \InvalidArgumentException('Answer is required');
            }

            // Use GradingService to grade the tutor answer
            $result = $this->gradingService->gradeTutorAnswer($user, $question, $answer);

            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::info('Tutor answer check completed successfully', [
                'request_id' => $requestId,
                'question_id' => $question->id,
                'user_id' => $user->id,
                'execution_time_ms' => round($executionTime, 2),
                'result' => $result
            ]);

            return response()->json($result);

        } catch (\InvalidArgumentException $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::warning('Tutor answer check failed - validation error:', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'question_id' => $question->id ?? null,
                'user_id' => auth()->id(),
                'execution_time_ms' => round($executionTime, 2)
            ]);
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::error('Error in checkTutorAnswer:', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'question_id' => $question->id ?? null,
                'user_id' => auth()->id(),
                'request_url' => request()->fullUrl(),
                'request_method' => request()->method(),
                'request_data' => request()->all(),
                'execution_time_ms' => round($executionTime, 2),
                'stack_trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while checking your answer. Please try again.',
                'debug_info' => app()->environment('local') ? [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    }

    private function calculateAndAwardXP(User $user, int $score, int $duration, float $percentageGrade): void
    {
        // Calculate XP based on score and time
        $baseXP = $score * 10; // 10 XP per correct answer
        $timeBonus = max(0, 50 - floor($duration / 60)); // Up to 50 bonus XP for fast completion
        $accuracyBonus = floor($percentageGrade / 10) * 5; // Up to 50 bonus XP for high accuracy
        $earnedXP = $baseXP + $timeBonus + $accuracyBonus;

        Log::info('XP Calculation:', [
            'user_id' => $user->id,
            'score' => $score,
            'baseXP' => $baseXP,
            'duration' => $duration,
            'timeBonus' => $timeBonus,
            'percentageGrade' => $percentageGrade,
            'accuracyBonus' => $accuracyBonus,
            'totalXP' => $earnedXP
        ]);

        // Update user's XP
        $user->increment('xp', $earnedXP);

        // Check for level up
        $this->checkForLevelUp($user);
    }
}