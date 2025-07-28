<?php

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Assignment;
use App\Services\GradingService;
use App\Services\QuizService;
use App\Services\CacheService;
use App\Services\UserStatsService;
use App\Services\DatabaseService;
use App\Services\InputValidationService;
use App\Services\ResilientCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $this->gradingService = $gradingService;
        $this->quizService = $quizService;
        $this->cacheService = $cacheService;
        $this->userStatsService = $userStatsService;
    }

    /**
     * LETHAL ASSIGNMENT CHECK - Ultra-fast with caching
     */
    private function getAssignmentCheck(int $userId, Topic $topic, Course $course)
    {
        $cacheKey = "assignment_check_{$userId}_{$topic->id}_{$course->id}";
        
        return Cache::remember($cacheKey, 300, function () use ($userId, $topic, $course) {
            return Assignment::select('id', 'user_id', 'topic_id', 'status', 'deadline', 'duration_minutes')
                ->where('user_id', $userId)
                ->where('topic_id', $topic->id)
                ->where('course_id', $course->id)
                ->whereIn('status', [Assignment::STATUS_ACTIVE, Assignment::STATUS_COMPLETED]) // Include completed assignments
                ->first();
        });
    }

    /**
     * LETHAL VALIDATION - Minimal rules, ultra-fast
     */
    private function validateQuizSubmission(Request $request, bool $isAssignment, int $userId, string $requestId): array
    {
        $validationRules = [
            'answers' => 'required|array|min:1|max:100',
            'answers.*' => 'nullable|string|max:2000',
            'mode' => 'nullable|string|in:quiz,tutor,random,assignment',
            'study_mode' => 'nullable|string|in:quiz,tutor,random,assignment,mistake_review'
        ];
        
        // LETHAL FIX: Handle both time_taken and duration_seconds for compatibility
        $validationRules['time_taken'] = $isAssignment 
            ? 'nullable|integer|min:1|max:86400'
            : 'required|integer|min:1|max:86400';
        
        try {
            $validated = InputValidationService::validateInput($request->all(), $validationRules, [], [
                'user_id' => $userId,
                'action' => 'quiz_grade',
                'request_id' => $requestId,
                'is_assignment' => $isAssignment
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Quiz submission validation failed', [
                'user_id' => $userId,
                'errors' => $e->errors(),
                'request_id' => $requestId
            ]);
            
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json([
                    'success' => false,
                    'error' => 'Invalid quiz submission data.',
                    'validation_errors' => $e->errors()
                ], 422)
            );
        } catch (\Exception $e) {
            Log::error('Quiz submission validation system error', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'request_id' => $requestId
            ]);
            
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json([
                    'success' => false,
                    'error' => 'Quiz submission validation failed. Please try again.'
                ], 500)
            );
        }

        // LETHAL FIX: Convert time_taken to duration_seconds for consistency
        if (isset($validated['time_taken'])) {
            $validated['duration_seconds'] = (int) $validated['time_taken'];
            unset($validated['time_taken']);
        }

        // LETHAL FIX: Handle mode and study_mode compatibility
        if (isset($validated['mode']) && !empty($validated['mode'])) {
            $validated['study_mode'] = $validated['mode'];
        } elseif (isset($validated['study_mode']) && !empty($validated['study_mode'])) {
            $validated['mode'] = $validated['study_mode'];
        } else {
            // LETHAL FIX: Ensure mode is set properly
            $validated['mode'] = $isAssignment ? 'assignment' : 'quiz';
            $validated['study_mode'] = $validated['mode'];
        }

        return $validated;
    }

    /**
     * LETHAL ACCESS VALIDATION - Ultra-fast with caching
     */
    private function validateUserAccess(int $userId, Course $course, Topic $topic): void
    {
        $cacheKey = "user_access_{$userId}_{$course->id}_{$topic->id}";
        
        Cache::remember($cacheKey, 300, function () use ($userId, $course) {
            $user = auth()->user();
            return $user->isAdmin() || $user->hasAccessToCourse($course->id) || (!$course->price || $course->price <= 0);
        });
    }

    /**
     * LETHAL QUIZ START - Ultra-optimized with minimal queries
     */
    public function startQuiz(Course $course, Topic $topic)
    {
        $user = auth()->user();
        
        // DEBUG: Log the request parameters
        Log::info('Quiz start requested', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'topic_id' => $topic->id,
            'assignment_id' => request()->input('assignment_id'),
            'sources' => request()->input('sources'),
            'all_params' => request()->all()
        ]);
        
        // LETHAL VALIDATION - Single query check
        if ($topic->course_id !== $course->id) {
            return redirect()->route('topics.forCourse', $course->id)
                ->with('error', 'Invalid topic for this course.');
        }

        // LETHAL ACCESS CHECK - Cached validation
        $accessKey = "user_access_{$user->id}_{$course->id}";
        $hasAccess = Cache::remember($accessKey, 300, function() use ($user, $course) {
            return $user->isAdmin() || $user->hasAccessToCourse($course->id) || (!$course->price || $course->price <= 0);
        });

        if (!$hasAccess) {
            return redirect()->route('topics.forCourse', $course->id)
                ->with('error', 'You must be granted access to this course to take quizzes.');
        }

        // LETHAL DATA LOADING - Single queries only
        $sources = request()->input('sources', ['all']);
        if (!is_array($sources)) {
            $sources = [$sources];
        }
        
        $assignmentId = request()->input('assignment_id');
        $assignment = null;
        
        if ($assignmentId) {
            Log::info('Assignment ID provided', ['assignment_id' => $assignmentId]);
            
            $assignment = Cache::remember("assignment_{$assignmentId}", 300, function () use ($assignmentId) {
                return Assignment::select('id', 'user_id', 'topic_id', 'status', 'created_at', 'tutor_id', 'course_id', 'duration_minutes', 'assignment_mode', 'assigned_source', 'deadline', 'title')
                    ->with(['topic:id,name,course_id', 'topic.course:id,name', 'tutor:id,name,email', 'user:id,name,email'])
                    ->find($assignmentId);
            });
            
            if ($assignment) {
                Log::info('Assignment found', [
                    'assignment_id' => $assignment->id,
                    'status' => $assignment->status,
                    'user_id' => $assignment->user_id,
                    'current_user_id' => $user->id
                ]);
            } else {
                Log::warning('Assignment not found', ['assignment_id' => $assignmentId]);
            }
            
            // LETHAL VALIDATION: Prevent retaking completed assignments
            if ($assignment && $assignment->status === Assignment::STATUS_COMPLETED) {
                return redirect()->route('topics.forCourse', $course->id)
                    ->with('error', 'This assignment has already been completed and cannot be retaken.');
            }
            
            // LETHAL FIX: Override sources for source-specific assignments
            if ($assignment && $assignment->assignment_mode === 'source_specific' && $assignment->assigned_source) {
                $sources = [$assignment->assigned_source];
            }
        }

        // OPTIMIZED FIX: SMART QUIZ GENERATION - Fast caching with deletion detection
        $questions = $this->getQuestionsWithSmartValidation($topic, $sources);

        if ($questions->isEmpty()) {
            return redirect()->route('topics.forCourse', $course->id)
                ->with('error', 'No questions available for this topic.');
        }

        // OPTIMIZED VIEW DATA - Minimal data only
        $viewData = [
            'course' => $course,
            'topic' => $topic,
            'questions' => $questions,
            'sources' => $sources,
            'assignment' => $assignment,
            'mode' => $assignment ? 'assignment' : 'quiz'
        ];

        return view('solve_quiz', $viewData);
    }

    /**
     * OPTIMIZED FIX: Get questions with smart validation - fast AND safe
     */
    private function getQuestionsWithSmartValidation(Topic $topic, array $sources): \Illuminate\Database\Eloquent\Collection
    {
        // SMART CACHE KEY with question count for deletion detection
        $questionCount = \App\Models\Question::where('topic_id', $topic->id)
            ->when(!in_array('all', $sources), function ($query) use ($sources) {
                $query->whereIn('source', $sources);
            })
            ->count();
            
        $cacheKey = "solve_quiz_{$topic->id}_" . md5(serialize($sources)) . "_count_{$questionCount}";
        
        // SMART CACHING: Try cache first, validate if needed
        $questions = \Illuminate\Support\Facades\Cache::get($cacheKey);
        
        if ($questions === null) {
            // FRESH QUERY: Get questions from database
            $questions = $this->getQuestionsFromDatabaseOptimized($topic, $sources);
            
            // SMART CACHE: Store with question count for deletion detection
            \Illuminate\Support\Facades\Cache::put($cacheKey, $questions, 1800); // 30 minutes
        } else {
            // SMART VALIDATION: Quick check if question count changed (indicates deletion)
            $currentCount = \App\Models\Question::where('topic_id', $topic->id)
                ->when(!in_array('all', $sources), function ($query) use ($sources) {
                    $query->whereIn('source', $sources);
                })
                ->count();
                
            if ($currentCount !== $questionCount) {
                // DELETION DETECTED: Clear cache and get fresh data
                \Illuminate\Support\Facades\Cache::forget($cacheKey);
                $questions = $this->getQuestionsFromDatabaseOptimized($topic, $sources);
                \Illuminate\Support\Facades\Cache::put($cacheKey, $questions, 1800);
                
                \Log::info('OPTIMIZED FIX: Deletion detected in solve quiz, cache refreshed', [
                    'topic_id' => $topic->id,
                    'old_count' => $questionCount,
                    'new_count' => $currentCount
                ]);
            }
        }
        
        \Log::info('OPTIMIZED FIX: Questions retrieved with smart validation for solve quiz', [
            'topic_id' => $topic->id,
            'questions_count' => $questions->count(),
            'sources' => $sources,
            'cache_hit' => $questions !== null,
            'validation_type' => 'smart_count_check'
        ]);
        
        return $questions;
    }

    /**
     * OPTIMIZED DATABASE QUERY: Get questions with efficient deletion validation
     */
    private function getQuestionsFromDatabaseOptimized(Topic $topic, array $sources): \Illuminate\Database\Eloquent\Collection
    {
        // EFFICIENT QUERY: Build with minimal validation
        $questionsQuery = \App\Models\Question::select([
            'id', 'topic_id', 'question', 'question_type', 
            'option_a', 'option_b', 'option_c', 'option_d',
            'correct_answer', 'explanation', 'image_url', 'source',
            'alternative_answers', 'similarity_threshold'
        ])
        ->where('topic_id', $topic->id)
        ->whereNotNull('question') // Ensure question content exists
        ->where('question', '!=', '') // Ensure question is not empty
        ->with(['topic:id,name,course_id']); // Eager load minimal topic data
        
        // Apply source filtering if specific sources are selected
        if (!in_array('all', $sources)) {
            $questionsQuery->whereIn('source', $sources);
        }
        
        // EFFICIENT VALIDATION: Single query with EXISTS check
        $questions = $questionsQuery->get();
        
        // OPTIMIZED VERIFICATION: Batch check for existence
        $questionIds = $questions->pluck('id')->toArray();
        $existingIds = \App\Models\Question::whereIn('id', $questionIds)
            ->whereNotNull('question')
            ->where('question', '!=', '')
            ->pluck('id')
            ->toArray();
            
        $validQuestions = $questions->filter(function ($question) use ($existingIds) {
            return in_array($question->id, $existingIds);
        });
        
        if ($validQuestions->count() !== $questions->count()) {
            \Log::warning('OPTIMIZED FIX: Deleted questions filtered out in solve quiz', [
                'original_count' => $questions->count(),
                'valid_count' => $validQuestions->count(),
                'filtered_count' => $questions->count() - $validQuestions->count()
            ]);
        }
        
        return $validQuestions;
    }

    /**
     * LETHAL QUIZ GRADING - Ultra-fast with minimal processing
     */
    public function grade(Request $request, Course $course, Topic $topic)
    {
        $userId = auth()->id();
        $requestId = uniqid('quiz_grade_');
        
        try {
            // LETHAL ASSIGNMENT CHECK
            $assignmentCheck = $this->getAssignmentCheck($userId, $topic, $course);
            $isAssignment = $assignmentCheck !== null;
            
            // LETHAL VALIDATION
            $validated = $this->validateQuizSubmission($request, $isAssignment, $userId, $requestId);
            
            // LETHAL ACCESS VALIDATION
            $this->validateUserAccess($userId, $course, $topic);
            
            $assignmentId = $request->input('assignment_id');
            
            // LETHAL VALIDATION: Only check assignment status if it's not being completed in this request
            $assignment = null;
            if ($assignmentId) {
                $assignment = Assignment::select('id', 'status', 'user_id', 'updated_at')
                    ->where('id', $assignmentId)
                    ->where('user_id', $userId)
                    ->first();
                
                // LETHAL DEBUG: Log assignment status for debugging
                \Log::info('Assignment status check', [
                    'assignment_id' => $assignmentId,
                    'user_id' => $userId,
                    'assignment_status' => $assignment ? $assignment->status : 'not_found',
                    'updated_at' => $assignment ? $assignment->updated_at : null,
                    'request_id' => $requestId
                ]);
                
                // Only block if assignment was completed more than 1 minute ago
                if ($assignment && $assignment->status === Assignment::STATUS_COMPLETED) {
                    $completedRecently = $assignment->updated_at && 
                        $assignment->updated_at->diffInSeconds(now()) < 60;
                    
                    \Log::info('Assignment completed check', [
                        'assignment_id' => $assignmentId,
                        'completed_recently' => $completedRecently,
                        'seconds_since_update' => $assignment->updated_at ? $assignment->updated_at->diffInSeconds(now()) : null
                    ]);
                    
                    if (!$completedRecently) {
                        \Log::warning('Assignment already completed - blocking retake', [
                            'assignment_id' => $assignmentId,
                            'user_id' => $userId,
                            'status' => $assignment->status,
                            'updated_at' => $assignment->updated_at
                        ]);
                        
                        return response()->json([
                            'success' => false,
                            'error' => 'This assignment has already been completed and cannot be retaken.'
                        ], 403);
                    }
                }
            }
            
            // LETHAL GRADING - Single transaction with assignment completion
            $gradingResult = DatabaseService::safeTransaction(function () use ($validated, $topic, $course, $userId, $assignmentId) {
                $result = $this->gradingService->gradeQuiz(
                    auth()->user(),
                    $validated['answers'],
                    $validated['duration_seconds'] ?? 0,
                    $validated['mode'] ?? 'quiz',
                    $topic,
                    $course,
                    $assignmentId
                );
                
                // LETHAL ASSIGNMENT COMPLETION - Inside transaction to prevent race conditions
                if ($assignmentId && $result['success']) {
                    $this->handleAssignmentCompletion($userId, $topic->id, $result['data']['percentageGrade'] ?? 0, $assignmentId);
                }
                
                return $result;
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
            
            // LETHAL CACHE INVALIDATION
            ResilientCacheService::safeForget("user_progress_{$userId}_{$topic->id}");
            ResilientCacheService::safeForget("quiz_cache_{$userId}_{$topic->id}");
            
            // LETHAL DEBUG: Log assignment completion
            $assignmentCompleted = $assignmentId ? true : false;
            if ($assignmentId) {
                \Log::info('Assignment completed', [
                    'assignment_id' => $assignmentId,
                    'user_id' => $userId,
                    'topic_id' => $topic->id,
                    'percentage' => $gradingResult['data']['percentageGrade'] ?? 0
                ]);
            }
            
            $result = $gradingResult['data'];
            
            // 🔔 CRITICAL NOTIFICATION INTEGRATION - Quiz Completion
            \App\Services\NotificationTriggerService::onQuizCompleted(auth()->user(), [
                'score' => $result['score'],
                'total_questions' => $result['totalQuestions'],
                'percentage' => $result['percentageGrade'],
                'course_name' => $course->name ?? $topic->name,
                'topic_name' => $topic->name,
                'user_quiz_count' => $this->getUserQuizCount(auth()->id()),
                'assignment_completed' => $assignmentCompleted
            ]);
            
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
                'assignment_completed' => $assignmentCompleted,
                'assignment_id' => $assignmentId
            ])->header('Content-Type', 'application/json')->header('Access-Control-Allow-Origin', '*');
            
        } catch (\Exception $e) {
            Log::error('Quiz grading failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while grading the quiz. Please try again.'
            ], 500)->header('Content-Type', 'application/json')->header('Access-Control-Allow-Origin', '*');
        }
    }

    /**
     * Get user's total quiz count for first attempt detection
     */
    private function getUserQuizCount(int $userId): int
    {
        $cacheKey = "user_quiz_count_{$userId}";
        return Cache::remember($cacheKey, 3600, function () use ($userId) {
            return DB::table('user_stats')
                ->where('user_id', $userId)
                ->value('total_quizzes_taken') ?? 0;
        });
    }

    /**
     * LETHAL ASSIGNMENT COMPLETION - Ultra-fast processing
     */
    private function handleAssignmentCompletion(int $userId, int $topicId, float $percentageGrade, ?int $assignmentId = null): void
    {
        if (!$assignmentId) {
            return;
        }

        // LETHAL UPDATE - Only set score if assignment not already completed
        $updated = DB::table('assignments')
            ->where('id', $assignmentId)
            ->where('user_id', $userId)
            ->where('status', '!=', Assignment::STATUS_COMPLETED)
            ->update([
                'status' => Assignment::STATUS_COMPLETED,
                'score_percentage' => $percentageGrade,
                'updated_at' => now()
            ]);

        // 🔥 LETHAL FIX: Dispatch AssignmentCompleted event if assignment was actually updated
        if ($updated > 0) {
            try {
                $assignment = Assignment::find($assignmentId);
                if ($assignment) {
                    event(new \App\Events\AssignmentStatusChanged($assignment, Assignment::STATUS_ACTIVE, Assignment::STATUS_COMPLETED));
                }
            } catch (\Exception $e) {
                Log::error('Failed to dispatch assignment completion event', [
                    'assignment_id' => $assignmentId,
                    'user_id' => $userId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // LETHAL CACHE INVALIDATION - More specific patterns
        Cache::forget("assignments_index_{$userId}_*");
        Cache::forget("assignment_{$assignmentId}");
        Cache::forget("assignment_counters_{$userId}");
        
        // Clear all assignment-related caches for this user
        $cacheKeys = [
            "assignments_index_{$userId}_*",
            "assignment_{$assignmentId}",
            "assignment_counters_{$userId}",
            "bulk_create_data_{$userId}"
        ];
        
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
        
        // LETHAL CACHE CLEARING - Clear all assignment caches for this user
        $allCacheKeys = Cache::get('assignment_cache_keys_' . $userId, []);
        foreach ($allCacheKeys as $cacheKey) {
            Cache::forget($cacheKey);
        }
        
        // Clear any cached assignment data
        Cache::forget("user_assignments_{$userId}");
        Cache::forget("topic_assignments_{$topicId}_{$userId}");
    }
}