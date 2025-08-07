<?php

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Assignment;
use App\Services\QuizService;
use App\Services\GradingService;
use App\Services\Quiz\QuizValidationService;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class QuizExecutionController extends Controller
{
    protected $quizService;
    protected $gradingService;
    protected $quizValidationService;

    public function __construct(
        QuizService $quizService,
        GradingService $gradingService,
        QuizValidationService $quizValidationService
    ) {
        $this->quizService = $quizService;
        $this->gradingService = $gradingService;
        $this->quizValidationService = $quizValidationService;
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
                return Assignment::select('id', 'topic_id', 'status', 'created_at', 'course_id', 'duration_minutes', 'assignment_mode', 'assigned_source', 'deadline', 'title')
                    ->with(['topic:id,name,course_id', 'topic.course:id,name'])
                    ->find($assignmentId);
            });
            
            if ($assignment) {
                Log::info('Assignment found', [
                    'assignment_id' => $assignment->id,
                    'status' => $assignment->status,
                    'current_user_id' => $user->id
                ]);
            } else {
                Log::warning('Assignment not found', ['assignment_id' => $assignmentId]);
            }
            
            // 🔥 CRITICAL: Check if assignment is locked for this user (one-shot rule)
            if ($assignment && $assignment->isLockedForUser($user->id)) {
                $lockReason = $assignment->getLockReasonForUser($user->id);
                return redirect()->route('topics.forCourse', $course->id)
                    ->with('error', $lockReason);
            }
            
            // LETHAL FIX: Override sources for source-specific assignments
            if ($assignment && $assignment->assignment_mode === 'source_specific' && $assignment->assigned_source) {
                $sources = [$assignment->assigned_source];
            }
        }

        // Get questions using service
        $result = $this->quizService->generateQuizQuestions($topic, $sources, $user);
        
        if (!$result['success']) {
            return redirect()->route('topics.forCourse', $course->id)
                ->with('error', $result['message']);
        }
        
        $questions = $result['questions'];

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
     * LETHAL QUIZ GRADING - Ultra-fast with minimal processing
     */
    public function grade(Request $request, Course $course, Topic $topic)
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'Authentication required.'
            ], 401);
        }
        
        $userId = $user->id;
        $requestId = uniqid('quiz_grade_');
        
        // ULTRA EFFICIENT: Handle both JSON and FormData seamlessly with backward compatibility
        $requestData = $request->isJson() ? $request->json()->all() : $request->all();
        
        // COMPATIBILITY: Ensure backward compatibility with existing forms
        if (!$request->isJson() && !isset($requestData['duration_seconds']) && isset($requestData['time_taken'])) {
            $requestData['duration_seconds'] = $requestData['time_taken'];
        }
        
        // SECURITY: Check for oversized payloads to prevent DoS attacks
        $requestSize = strlen(json_encode($requestData));
        if ($requestSize > 1048576) { // 1MB limit
            Log::warning('Oversized payload detected in quiz grading', [
                'user_id' => $userId,
                'request_size' => $requestSize,
                'request_id' => $requestId
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Request payload too large. Please reduce the size of your answers.'
            ], 413); // 413 Payload Too Large
        }
        
        // EFFICIENCY BOOST: Log pre-computed data and performance metrics
        $processingStartTime = microtime(true);
        
        if (isset($requestData['computed_locally']) && $requestData['computed_locally']) {
            Log::info('🚀 ULTRA EFFICIENT: Received pre-computed tutor data', [
                'user_id' => $userId,
                'request_id' => $requestId,
                'pre_computed' => true,
                'answers_count' => count($requestData['answers'] ?? []),
                'client_processing_time' => $requestData['client_processing_time'] ?? null,
                'payload_size' => $requestSize
            ]);
        }
        
        try {
            // Assignment check
            $assignmentId = $request->input('assignment_id');
            $isAssignment = $assignmentId !== null;
            
            // ULTRA EFFICIENT: Create unified request for validation service
            $validationRequest = new Request($requestData);
            $validationRequest->setMethod('POST');
            
            // Validation using service
            $validated = $this->quizValidationService->validateQuizSubmission($validationRequest, $isAssignment, $userId, $requestId);
            
            // Access validation - check if user has access to course
            if (!$user->isAdmin() && !$user->hasAccessToCourse($course->id) && $course->price && $course->price > 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'Access denied to this course.'
                ], 403);
            }
            
            $assignmentId = $request->input('assignment_id');
            
            // 🔥 CRITICAL: Check if assignment is locked for this user (one-shot rule)
            $assignment = null;
            if ($assignmentId) {
                $assignment = Assignment::find($assignmentId);
                
                if ($assignment && $assignment->isLockedForUser($userId)) {
                    $lockReason = $assignment->getLockReasonForUser($userId);
                    
                    \Log::warning('Assignment locked for user - blocking submission', [
                        'assignment_id' => $assignmentId,
                        'user_id' => $userId,
                        'lock_reason' => $lockReason,
                        'request_id' => $requestId
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'error' => $lockReason
                    ], 403);
                }
            }
            
            // Process grading using service
            $gradingResult = $this->gradingService->gradeQuiz(
                $user,
                $validated['answers'],
                $validated['duration_seconds'],
                $validated['mode'] ?? 'quiz',
                $topic,
                $course,
                $assignmentId
            );
            
            // LETHAL CACHE INVALIDATION
            app(CacheService::class)->forget("user_progress_{$userId}_{$topic->id}");
            app(CacheService::class)->forget("quiz_cache_{$userId}_{$topic->id}");
            
            // LETHAL DEBUG: Log assignment completion
            $assignmentCompleted = $assignmentId ? true : false;
            if ($assignmentId) {
                \Log::info('Assignment completed', [
                    'assignment_id' => $assignmentId,
                    'user_id' => $userId,
                    'topic_id' => $topic->id,
                    'percentage' => $gradingResult['percentageGrade'] ?? 0
                ]);
            }
            
            // Get user quiz count for notifications
            $userQuizCount = \App\Models\QuizAttempt::where('user_id', $userId)->count();
            
            // 🔔 CRITICAL NOTIFICATION INTEGRATION - Quiz Completion
            \App\Services\NotificationTriggerService::onQuizCompleted(auth()->user(), [
                'score' => $gradingResult['score'],
                'total_questions' => $gradingResult['totalQuestions'],
                'percentage' => $gradingResult['percentageGrade'],
                'course_name' => $course->name ?? $topic->name,
                'topic_name' => $topic->name,
                'user_quiz_count' => $userQuizCount,
                'assignment_completed' => $assignmentCompleted
            ]);
            
            return response()->json([
                'success' => true,
                'score' => $gradingResult['score'],
                'total' => $gradingResult['totalQuestions'],
                'percentage' => $gradingResult['percentageGrade'],
                'encouragement' => $gradingResult['encouragement'],
                'source_progress' => $gradingResult['source_progress'] ?? [],
                'correct_answers' => $gradingResult['correct_answers'] ?? [],
                'xp_earned' => $gradingResult['xp_earned'] ?? 0,
                'level_up' => $gradingResult['level_up'] ?? false,
                'new_level' => $gradingResult['new_level'] ?? null,
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


}