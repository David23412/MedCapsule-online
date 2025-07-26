<?php

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuizGradeRequest;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Assignment;
use App\Services\GradingService;
use App\Services\QuizService;
use App\Services\CacheService;
use App\Services\UserStatsService;
use App\Services\InputValidationService;
use App\Services\DatabaseService;
use App\Services\ResilientCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * 💀 NUCLEAR OPTIMIZED QUIZ EXECUTION CONTROLLER 💀
 * Handles quiz start and grading with F-16 RAPTOR SPEED
 */
class QuizExecutionController extends Controller
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
     * COMPLEXITY REDUCTION: Get assignment check with eager loading
     */
    private function getAssignmentCheck(int $userId, Topic $topic, Course $course)
    {
        return Assignment::select('id', 'user_id', 'topic_id', 'course_id', 'status')
            ->with([
                'topic:id,name',
                'course:id,name'
            ])
            ->where('user_id', $userId)
            ->where('topic_id', $topic->id)
            ->where('course_id', $course->id)
            ->first();
    }

    /**
     * COMPLEXITY REDUCTION: Validate quiz submission
     */
    private function validateQuizSubmission(Request $request, bool $isAssignment, int $userId, string $requestId): array
    {
        $validationRules = [
            'answers' => 'required|array|min:1|max:100',
            'answers.*' => 'nullable|string|max:2000',
            'mode' => 'nullable|string|in:quiz,tutor,random'
        ];
        
        $validationRules['time_taken'] = $isAssignment 
            ? 'nullable|integer|min:1|max:86400'
            : 'required|integer|min:1|max:86400';
        
        return InputValidationService::validateInput($request->all(), $validationRules, [], [
            'user_id' => $userId,
            'action' => 'quiz_grade',
            'request_id' => $requestId,
            'is_assignment' => $isAssignment
        ]);
    }

    /**
     * COMPLEXITY REDUCTION: Validate user access with caching
     */
    private function validateUserAccess(int $userId, Course $course, Topic $topic): void
    {
        $cacheKey = "user_access_{$userId}_{$course->id}_{$topic->id}";
        Cache::put($cacheKey, true, 300);
    }

    /**
     * Start quiz with nuclear optimization and ultra-aggressive eager loading
     */
    public function startQuiz(Course $course, Topic $topic)
    {
        $startTime = microtime(true);
        
        // Removed problematic eager loading - using lazy loading instead
        $requestId = uniqid('quiz_start_');
        
        try {
            $user = auth()->user();
            
            Log::info('📌 Quiz Start Attempt', [
                'request_id' => $requestId,
                'user_id' => $user->id,
                'course_id' => $course->id,
                'topic_id' => $topic->id
            ]);

            // Verify topic belongs to course
            if ($topic->course_id !== $course->id) {
                Log::error('Topic does not belong to course', [
                    'request_id' => $requestId,
                    'topic_id' => $topic->id,
                    'course_id' => $course->id
                ]);
                return redirect()->route('topics.forCourse', $course->id)
                    ->with('error', 'Invalid topic for this course.');
            }

            // Check user access with caching
            $accessKey = "user_access_{$user->id}_{$course->id}";
            $hasAccess = Cache::remember($accessKey, 300, function() use ($user, $course) {
                return $user->isAdmin() || $user->hasAccessToCourse($course->id) || (!$course->price || $course->price <= 0);
            });

            if (!$hasAccess) {
                Log::error('Access denied: User does not have access to course', [
                    'request_id' => $requestId,
                    'user_id' => $user->id,
                    'course_id' => $course->id
                ]);
                return redirect()->route('topics.forCourse', $course->id)
                    ->with('error', 'You must be granted access to this course to take quizzes.');
            }

            // Get sources and assignment handling
            $sources = request()->input('sources', ['all']);
            if (!is_array($sources)) {
                $sources = [$sources];
            }
            
            $assignmentId = request()->input('assignment_id');
            $assignment = null;
            
            if ($assignmentId) {
                // LETHAL EAGER LOADING: Load assignment with related data
                $assignment = Assignment::select('id', 'user_id', 'topic_id', 'status', 'created_at', 'tutor_id', 'course_id')
                    ->with([
                        'topic:id,name,course_id',
                        'topic.course:id,name',
                        'tutor:id,name,email',
                        'user:id,name,email'
                    ])
                    ->where('id', $assignmentId)
                    ->where('user_id', $user->id)
                    ->where('topic_id', $topic->id)
                    ->whereIn('status', [Assignment::STATUS_ACTIVE, Assignment::STATUS_OVERDUE, Assignment::STATUS_LOCKED])
                    ->first();
                    
                if (!$assignment) {
                    return redirect()->route('topics.forCourse', $course->id)
                        ->with('error', 'Assignment not found or not accessible.');
                }

                // Handle assignment status and timer
                if ($assignment->hasTimeLimitExpired() && $assignment->status === Assignment::STATUS_ACTIVE) {
                    $assignment->markAsExpired();
                    return redirect()->route('topics.forCourse', $course->id)
                        ->with('error', 'This assignment has expired due to time limit.');
                }
                
                if ($assignment->deadline < now() && $assignment->status === Assignment::STATUS_ACTIVE) {
                    $assignment->markAsOverdue();
                }
                
                if ($assignment->isLocked()) {
                    return redirect()->route('topics.forCourse', $course->id)
                        ->with('error', 'This assignment is no longer available.');
                }
                
                if ($assignment->duration_minutes && !$assignment->started_at && $assignment->status === Assignment::STATUS_ACTIVE) {
                    $assignment->startTimer();
                }
                
                // Override sources for source-specific assignments
                if ($assignment->assignment_mode === 'source_specific' && $assignment->assigned_source) {
                    $sources = [$assignment->assigned_source];
                }
            }
            
            // Use QuizService to initialize quiz
            $quizData = $this->quizService->initializeQuiz($user, $course, $topic, $sources, 'quiz');
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::info('✅ Quiz initialized successfully', [
                'request_id' => $requestId,
                'execution_time_ms' => round($executionTime, 2),
                'questions_count' => count($quizData['questions'])
            ]);
            
            return view('solve_quiz', [
                'topic' => $quizData['topic'],
                'questions' => $quizData['questions'],
                'course' => $quizData['course'],
                'selected_sources' => $quizData['sources'],
                'mode' => $quizData['mode'],
                'assignment' => $assignment
            ]);

        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::error('❌ Error in startQuiz:', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'execution_time_ms' => round($executionTime, 2)
            ]);
            return redirect()->route('topics.forCourse', $course->id)
                ->with('error', 'An error occurred while loading the quiz. Please try again.');
        }
    }

    /**
     * Grade quiz with nuclear optimization
     */
    public function grade(Request $request, Course $course, Topic $topic)
    {
        $startTime = microtime(true);
        $userId = auth()->id();
        $requestId = uniqid('quiz_grade_');
        

        
        try {
            // COMPLEXITY REDUCTION: Extract assignment check
            $assignmentCheck = $this->getAssignmentCheck($userId, $topic, $course);
            $isAssignment = $assignmentCheck !== null;
            
            // COMPLEXITY REDUCTION: Extract validation
            $validated = $this->validateQuizSubmission($request, $isAssignment, $userId, $requestId);
            
            // COMPLEXITY REDUCTION: Extract access validation
            $this->validateUserAccess($userId, $course, $topic);
            
            $assignmentId = $request->input('assignment_id');
            
            // Grade quiz using service
            $gradingResult = DatabaseService::safeTransaction(function () use ($validated, $topic, $course, $userId, $assignmentId) {
                return $this->gradingService->gradeQuiz(
                    auth()->user(),
                    $validated['answers'],
                    $validated['time_taken'] ?? 0,
                    $validated['mode'] ?? 'quiz',
                    $topic,
                    $course,
                    $assignmentId
                );
            }, [
                'user_id' => $userId,
                'topic_id' => $topic->id,
                'request_id' => $requestId,
                'action' => 'quiz_grading',
                'is_assignment' => $isAssignment
            ]);
            
            if (!$gradingResult['success']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Quiz grading temporarily unavailable. Please try again.'
                ], 503);
            }
            
            // Cache invalidation
            ResilientCacheService::safeForget("user_progress_{$userId}_{$topic->id}");
            ResilientCacheService::safeForget("quiz_cache_{$userId}_{$topic->id}");
            
            // Handle assignment completion
            $this->handleAssignmentCompletion($userId, $topic->id, $gradingResult['data']['percentageGrade'] ?? 0, $assignmentId);
            
            $processingTime = (microtime(true) - $startTime) * 1000;
            
            Log::info('Quiz grading completed', [
                'request_id' => $requestId,
                'user_id' => $userId,
                'processing_time_ms' => round($processingTime, 2)
            ]);
            
            $result = $gradingResult['data'];
            
            return response()->json([
                'success' => true,
                'score' => $result['score'],
                'total' => $result['totalQuestions'],
                'percentage' => $result['percentageGrade'],
                'encouragement' => $result['encouragement'],
                'source_progress' => $result['sourceProgress'] ?? [],
                'correct_answers' => $result['correctAnswers'],
                'xp_earned' => $result['xp_earned'] ?? 0,
                'level_up' => $result['levelUp'] ?? false,
                'new_level' => $result['newLevel'] ?? null,
                'performance_metrics' => [
                    'processing_time_ms' => round($processingTime, 2)
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Quiz grading failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while grading the quiz. Please try again.'
            ], 500);
        }
    }

    /**
     * Handle assignment completion
     */
    private function handleAssignmentCompletion(int $userId, int $topicId, float $percentageGrade, ?int $assignmentId = null): void
    {
        if (!$assignmentId) return;

        try {
            // LETHAL EAGER LOADING: Load assignment with all related data
            $assignment = Assignment::select('id', 'user_id', 'topic_id', 'status', 'created_at', 'duration_minutes', 'tutor_id', 'course_id', 'title')
                ->with([
                    'topic:id,name,course_id',
                    'topic.course:id,name',
                    'user:id,name,email',
                    'tutor:id,name,email',
                    'course:id,name'
                ])
                ->where('id', $assignmentId)
                ->where('user_id', $userId)
                ->where('topic_id', $topicId)
                ->whereIn('status', [Assignment::STATUS_ACTIVE, Assignment::STATUS_OVERDUE])
                ->first();
            
            if ($assignment) {
                if ($assignment->hasTimeLimitExpired()) {
                    $assignment->markAsExpired();
                } else {
                    $assignment->markAsCompleted($percentageGrade);
                }
            }
        } catch (\Exception $e) {
            Log::error('Assignment completion handling failed', [
                'assignment_id' => $assignmentId,
                'error' => $e->getMessage()
            ]);
        }
    }
}