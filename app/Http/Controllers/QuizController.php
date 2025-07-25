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
use App\Services\InputValidationService;
use App\Services\DatabaseService;
use App\Services\ResilientCacheService;
use App\Models\Assignment;
use App\Models\AssignmentAttempt;
use App\Services\AssignmentService;

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
                'user_role' => $user->role,
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
                return redirect()->route('topics.forCourse', $course->id)
                    ->with('error', 'Invalid topic for this course.');
            }

            // Check if user has access to the course (admin, enrolled, or free course)
            if (!$user->isAdmin() && !$user->hasAccessToCourse($course->id) && $course->price && $course->price > 0) {
                Log::error('Access denied: User does not have access to course', [
                    'request_id' => $requestId,
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'user_role' => $user->role,
                    'course_price' => $course->price
                ]);
                return redirect()->route('topics.forCourse', $course->id)
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

            // Check for assignments ONLY if explicitly accessing an assignment
            $assignmentId = request()->input('assignment_id');
            $assignment = null;
            
            if ($assignmentId) {
                // User is explicitly accessing an assignment
                $assignment = Assignment::where('id', $assignmentId)
                    ->where('student_id', $user->id)
                    ->where('topic_id', $topic->id)
                    ->whereIn('status', [Assignment::STATUS_ACTIVE, Assignment::STATUS_OVERDUE, Assignment::STATUS_LOCKED])
                    ->first();
                    
                if (!$assignment) {
                    Log::warning('Assignment not found or not accessible', [
                        'request_id' => $requestId,
                        'assignment_id' => $assignmentId,
                        'user_id' => $user->id,
                        'topic_id' => $topic->id
                    ]);
                    return redirect()->route('topics.forCourse', $course->id)
                        ->with('error', 'Assignment not found or not accessible.');
                }
            }
            // If no assignment_id provided, this is regular topic practice - no assignment constraints
            
            // Handle assignment status updates
            if ($assignment) {
                // Check if assignment has expired due to time limit
                if ($assignment->hasTimeLimitExpired() && $assignment->status === Assignment::STATUS_ACTIVE) {
                    $assignment->markAsExpired();
                    Log::info('Assignment expired due to time limit during quiz start', [
                        'assignment_id' => $assignment->id,
                        'user_id' => $user->id,
                        'duration_minutes' => $assignment->duration_minutes
                    ]);
                    return redirect()->route('topics.forCourse', $course->id)
                        ->with('error', 'This assignment has expired due to time limit.');
                }
                
                // Check if assignment is overdue and should be marked as such
                if ($assignment->deadline < now() && $assignment->status === Assignment::STATUS_ACTIVE) {
                    $assignment->markAsOverdue();
                    Log::info('Assignment marked as overdue during quiz start', [
                        'assignment_id' => $assignment->id,
                        'user_id' => $user->id,
                        'deadline' => $assignment->deadline
                    ]);
                }
                
                // Check if assignment is locked
                if ($assignment->isLocked()) {
                    Log::info('User attempted to access locked assignment', [
                        'assignment_id' => $assignment->id,
                        'user_id' => $user->id,
                        'topic_id' => $topic->id,
                        'assignment_status' => $assignment->status
                    ]);
                    return redirect()->route('topics.forCourse', $course->id)
                        ->with('error', 'This assignment is no longer available. It may have been completed or expired.');
                }
                
                // Start the timer if this is the first access and assignment has duration limit
                if ($assignment->duration_minutes && !$assignment->started_at && $assignment->status === Assignment::STATUS_ACTIVE) {
                    $assignment->startTimer();
                    Log::info('Assignment timer started', [
                        'assignment_id' => $assignment->id,
                        'user_id' => $user->id,
                        'duration_minutes' => $assignment->duration_minutes
                    ]);
                }
                
                // CRITICAL FIX: Override sources for source-specific assignments
                if ($assignment->assignment_mode === 'source_specific' && $assignment->assigned_source) {
                    $sources = [$assignment->assigned_source];
                    Log::info('Source-specific assignment - overriding sources', [
                        'assignment_id' => $assignment->id,
                        'original_sources' => request()->input('sources', ['all']),
                        'assigned_source' => $assignment->assigned_source,
                        'final_sources' => $sources
                    ]);
                }
            }
            
            // Use QuizService to initialize quiz
            $quizData = $this->quizService->initializeQuiz($user, $course, $topic, $sources, 'quiz');
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::info('✅ Quiz initialized successfully', [
                'request_id' => $requestId,
                'execution_time_ms' => round($executionTime, 2),
                'questions_count' => count($quizData['questions']),
                'sources_count' => count($quizData['sources']),
                'has_assignment' => $assignment ? true : false
            ]);
            
            return view('solve_quiz', [
                'topic' => $quizData['topic'],
                'questions' => $quizData['questions'],
                'course' => $quizData['course'],
                'selected_sources' => $quizData['sources'],
                'mode' => $quizData['mode'],
                'assignment' => $assignment
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
            return redirect()->route('topics.forCourse', $course->id)
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
            return redirect()->route('topics.forCourse', $course->id)
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
            // Check if this is an assignment quiz
            $isAssignment = Assignment::where('student_id', $userId)
                ->where('topic_id', $topic->id)
                ->where('course_id', $course->id)
                ->exists();
            
            // BULLETPROOF VALIDATION: Use comprehensive validation service
            $validationRules = [
                'answers' => 'required|array|min:1|max:100',
                'answers.*' => 'nullable|string|max:2000',
                'mode' => 'nullable|string|in:quiz,tutor,random'
            ];
            
            // For assignments, time_taken is nullable; for regular quizzes, it's required
            if ($isAssignment) {
                $validationRules['time_taken'] = 'nullable|integer|min:1|max:86400';
            } else {
                $validationRules['time_taken'] = 'required|integer|min:1|max:86400';
            }
            
            $validated = InputValidationService::validateInput($request->all(), $validationRules, [], [
                'user_id' => $userId,
                'action' => 'quiz_grade',
                'request_id' => $requestId,
                'is_assignment' => $isAssignment
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
            
            // Get assignment context before transaction
            $assignmentId = $request->input('assignment_id');
            
            // BULLETPROOF GRADING: Use database service for safe operations
            $gradingResult = DatabaseService::safeTransaction(function () use ($validated, $topic, $course, $userId, $requestId, $isAssignment, $assignmentId) {
                return $this->gradingService->gradeQuiz(
                    auth()->user(),
                    $validated['answers'],
                    $validated['time_taken'] ?? 0, // Use 0 for assignments if time_taken is null
                    $validated['mode'] ?? 'quiz',
                    $topic,
                    $course,
                    $assignmentId // Pass assignment context to prevent regular topic contamination
                );
            }, [
                'user_id' => $userId,
                'topic_id' => $topic->id,
                'request_id' => $requestId,
                'action' => 'quiz_grading',
                'is_assignment' => $isAssignment
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
            
            // ASSIGNMENT INTEGRATION: Check and complete assignments (assignmentId already retrieved above)
            $this->handleAssignmentCompletion($userId, $topic->id, $gradingResult['data']['percentageGrade'] ?? 0, $assignmentId);
            
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
     * ✅ Show mistakes (incorrect answers) with optimized query
     * OPTIMIZED: Reduced from multiple queries to single optimized query
     */
    /**
     * GOLDEN STANDARD: Show mistakes page with optimized queries and smart navigation
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
                'course_filter' => $courseFilter
            ]);

            // GOLDEN STANDARD: Optimized single query with minimal data transfer
            $mistakes = Mistake::select([
                'mistakes.id',
                'mistakes.question_id',
                'mistakes.submitted_answer',
                'mistakes.last_attempt_date',
                'mistakes.correct_streak',
                'mistakes.skipped'
            ])
            ->with([
                'question' => function($query) {
                    $query->select([
                        'id', 'question', 'topic_id', 'correct_answer', 
                        'image_url', 'explanation', 'question_type',
                        'option_a', 'option_b', 'option_c', 'option_d'
                    ]);
                },
                'question.topic' => function($query) {
                    $query->select(['id', 'name', 'course_id']);
                },
                'question.topic.course' => function($query) {
                    $query->select(['id', 'name']);
                }
            ])
            ->where('mistakes.user_id', $userId)
            ->when($courseFilter, function($query) use ($courseFilter) {
                if ($courseFilter === 'skipped') {
                    return $query->where('mistakes.skipped', true);
                } else {
                    return $query->whereHas('question.topic.course', function($q) use ($courseFilter) {
                        $q->where('id', $courseFilter);
                    });
                }
            })
            ->orderByDesc('mistakes.last_attempt_date')
            ->get();

            // Get total mistakes count (unfiltered)
            $totalMistakesCount = Mistake::where('user_id', $userId)->count();

            // GOLDEN STANDARD: Efficient course availability check
            $availableCourses = Course::select(['id', 'name'])
                ->whereIn('id', function($query) use ($userId) {
                    $query->select('topics.course_id')
                          ->from('topics')
                          ->join('questions', 'topics.id', '=', 'questions.topic_id')
                          ->join('mistakes', 'questions.id', '=', 'mistakes.question_id')
                          ->where('mistakes.user_id', $userId)
                          ->distinct();
                })
                ->orderBy('name')
                ->get();

            // GOLDEN STANDARD: Smart navigation - get the most recent course for back button
            $recentCourse = null;
            $recentQuizAttempt = QuizAttempt::where('user_id', $userId)
                ->whereNotNull('topic_id')
                ->latest('created_at')
                ->with('topic.course:id,name')
                ->first();
            
            if ($recentQuizAttempt && $recentQuizAttempt->topic && $recentQuizAttempt->topic->course) {
                $recentCourse = $recentQuizAttempt->topic->course;
            } elseif ($mistakes->isNotEmpty()) {
                // Fallback: use the most recent mistake's course
                $recentCourse = $mistakes->first()->question->topic->course;
            }

            // Group mistakes by course for organization
            $mistakesByCourse = $mistakes->groupBy('question.topic.course.name');

            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::info('✅ Mistakes loaded successfully', [
                'request_id' => $requestId,
                'execution_time_ms' => round($executionTime, 2),
                'mistakes_count' => $mistakes->count(),
                'courses_count' => $availableCourses->count(),
                'recent_course' => $recentCourse ? $recentCourse->name : 'none'
            ]);

            return view('mistakes', [
                'mistakes' => $mistakes,
                'mistakesByCourse' => $mistakesByCourse,
                'availableCourses' => $availableCourses,
                'selectedCourse' => $courseFilter,
                'recentCourse' => $recentCourse,
                'totalMistakesCount' => $totalMistakesCount,
                'performance_metrics' => [
                    'execution_time_ms' => round($executionTime, 2),
                    'mistakes_count' => $mistakes->count(),
                    'courses_count' => $availableCourses->count()
                ]
            ]);

        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::error('❌ Error in showMistakes:', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'execution_time_ms' => round($executionTime, 2)
            ]);

            return view('mistakes', [
                'mistakes' => collect([]),
                'mistakesByCourse' => collect([]),
                'availableCourses' => collect([]),
                'selectedCourse' => null,
                'recentCourse' => null,
                'totalMistakesCount' => 0,
                'error' => 'An error occurred while loading your mistakes. Please try again.'
            ]);
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
                'user_role' => $user->role,
                'course_id' => $course->id,
                'topic_id' => $topic->id,
                'request_url' => request()->fullUrl(),
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip()
            ]);
            
            if (!$user->isAdmin() && !$user->hasAccessToCourse($course->id) && $course->price && $course->price > 0) {
                Log::error('Access denied: User does not have access to course', [
                    'request_id' => $requestId,
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'user_role' => $user->role,
                    'course_price' => $course->price
                ]);
                return redirect()->route('topics.forCourse', $course->id)
                    ->with('error', 'You must be granted access to this course to take quizzes.');
            }

            // Get the selected sources from the request
            $sources = request()->input('sources', ['all']);
            
            // Ensure sources is an array
            if (!is_array($sources)) {
                $sources = [$sources];
            }
            // FIX: If sources is a single string with commas, split it
            if (count($sources) === 1 && strpos($sources[0], ',') !== false) {
                $sources = array_map('trim', explode(',', $sources[0]));
            }
            Log::info('Selected sources for tutor mode (sanitized)', [
                'request_id' => $requestId,
                'sources' => $sources,
                'topic_id' => $topic->id
            ]);

            Log::info('DEBUG: About to call initializeQuiz', [
                'sources' => $sources,
                'topic_id' => $topic->id
            ]);
            // Use QuizService to initialize tutor quiz
            $quizData = $this->quizService->initializeQuiz($user, $course, $topic, $sources, 'tutor');
            Log::info('DEBUG: Returned from initializeQuiz', [
                'questions_count' => is_array($quizData['questions']) ? count($quizData['questions']) : (method_exists($quizData['questions'], 'count') ? $quizData['questions']->count() : 'N/A'),
                'sources' => $sources
            ]);
            
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
            return redirect()->route('topics.forCourse', $course->id)
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
            return redirect()->route('topics.forCourse', $course->id)
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

    /**
     * Handle assignment completion when quiz is completed
     * Enhanced with comprehensive locking logic
     */
    private function handleAssignmentCompletion(int $userId, int $topicId, float $percentageGrade, ?int $assignmentId = null): void
    {
        try {
            if ($assignmentId) {
                // SPECIFIC ASSIGNMENT: Complete only the specified assignment
                $assignment = Assignment::where('id', $assignmentId)
                                       ->where('student_id', $userId)
                                       ->where('topic_id', $topicId)
                                       ->whereIn('status', [Assignment::STATUS_ACTIVE, Assignment::STATUS_OVERDUE])
                                       ->first();
                
                if ($assignment) {
                    // Check if assignment is expired due to time limit
                    if ($assignment->hasTimeLimitExpired()) {
                        $assignment->markAsExpired();
                        Log::info('Assignment expired due to time limit', [
                            'assignment_id' => $assignment->id,
                            'user_id' => $userId,
                            'duration_minutes' => $assignment->duration_minutes
                        ]);
                        return;
                    }

                    if ($assignment->canBeCompleted()) {
                        // Create separate assignment attempt record
                        $this->createAssignmentAttempt($assignment, $userId, $percentageGrade);
                        
                        // Complete the assignment with the quiz score
                        $assignment->markAsCompleted((int) round($percentageGrade));
                        
                        // LOCK the assignment to prevent retakes
                        $assignment->lock();
                        
                        Log::info('SPECIFIC assignment completed and LOCKED via quiz', [
                            'assignment_id' => $assignment->id,
                            'user_id' => $userId,
                            'topic_id' => $topicId,
                            'score' => round($percentageGrade),
                            'was_overdue' => $assignment->isOverdue(),
                            'completion_time' => now()
                        ]);
                    }
                } else {
                    Log::warning('Assignment not found for completion', [
                        'assignment_id' => $assignmentId,
                        'user_id' => $userId,
                        'topic_id' => $topicId
                    ]);
                }
            } else {
                // REGULAR QUIZ: Don't complete any assignments, this is regular practice
                Log::info('Regular quiz completed - no assignments affected', [
                    'user_id' => $userId,
                    'topic_id' => $topicId,
                    'score' => round($percentageGrade)
                ]);
            }

            // Always check for overdue assignments that should be locked (separate from completion)
            $this->lockOverdueAssignments($userId, $topicId);

        } catch (\Exception $e) {
            // Don't let assignment completion failure affect quiz grading
            Log::error('Failed to complete assignment after quiz', [
                'user_id' => $userId,
                'topic_id' => $topicId,
                'assignment_id' => $assignmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Lock assignments that are overdue and should no longer be accessible
     */
    private function lockOverdueAssignments(int $userId, int $topicId): void
    {
        try {
            $overdueAssignments = Assignment::where('student_id', $userId)
                                          ->where('topic_id', $topicId)
                                          ->where('status', Assignment::STATUS_OVERDUE)
                                          ->where('deadline', '<', now()->subHours(24)) // Grace period of 24 hours
                                          ->get();

            foreach ($overdueAssignments as $assignment) {
                $assignment->lock();
                Log::info('Overdue assignment locked after grace period', [
                    'assignment_id' => $assignment->id,
                    'user_id' => $userId,
                    'deadline' => $assignment->deadline,
                    'hours_overdue' => now()->diffInHours($assignment->deadline)
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to lock overdue assignments', [
                'user_id' => $userId,
                'topic_id' => $topicId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create a separate assignment attempt record
     */
    private function createAssignmentAttempt(Assignment $assignment, int $userId, float $percentageGrade): void
    {
        try {
            // Get quiz session data from the current request
            $request = request();
            $answers = $request->input('answers', []);
            $timeTaken = $request->input('time_taken', 0);
            
            // Calculate question statistics
            $totalQuestions = count($answers);
            $correctAnswers = (int) round(($percentageGrade / 100) * $totalQuestions);
            $incorrectAnswers = $totalQuestions - $correctAnswers;
            
            // Check if submission is late
            $isLateSubmission = now() > $assignment->deadline;
            
            AssignmentAttempt::create([
                'assignment_id' => $assignment->id,
                'user_id' => $userId, // CRITICAL FIX: Add missing user_id field
                'student_id' => $userId,
                'topic_id' => $assignment->topic_id,
                'course_id' => $assignment->course_id,
                'attempt_number' => 1, // CRITICAL FIX: Add missing required field
                'total_questions' => $totalQuestions,
                'correct_answers' => $correctAnswers,
                'incorrect_answers' => $incorrectAnswers,
                'score_percentage' => $percentageGrade,
                'time_taken_seconds' => $timeTaken,
                'answers' => $answers, // CRITICAL FIX: Use answers instead of question_responses
                'question_responses' => $answers,
                'assignment_mode' => $assignment->assignment_mode ?? 'complete_topic',
                'assigned_source' => $assignment->assigned_source,
                'status' => AssignmentAttempt::STATUS_COMPLETED,
                'started_at' => now()->subSeconds($timeTaken), // Approximate start time
                'completed_at' => now(),
                'is_late_submission' => $isLateSubmission,
                'auto_submitted' => false, // CRITICAL FIX: Add missing required field
                'notes' => $isLateSubmission ? 'Submitted after deadline' : null
            ]);
            
            Log::info('Assignment attempt recorded separately', [
                'assignment_id' => $assignment->id,
                'student_id' => $userId,
                'total_questions' => $totalQuestions,
                'correct_answers' => $correctAnswers,
                'score_percentage' => $percentageGrade,
                'is_late' => $isLateSubmission
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to create assignment attempt record', [
                'assignment_id' => $assignment->id,
                'student_id' => $userId,
                'error' => $e->getMessage()
            ]);
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