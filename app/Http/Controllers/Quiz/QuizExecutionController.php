<?php

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Assignment;
use App\Services\Quiz\QuizExecutionService;
use App\Services\Quiz\QuizValidationService;
use App\Services\ResilientCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class QuizExecutionController extends Controller
{
    protected $quizExecutionService;
    protected $quizValidationService;

    public function __construct(
        QuizExecutionService $quizExecutionService,
        QuizValidationService $quizValidationService
    ) {
        $this->quizExecutionService = $quizExecutionService;
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
        $questions = $this->quizExecutionService->getQuestionsWithSmartValidation($topic, $sources);

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
        $userId = auth()->id();
        $requestId = uniqid('quiz_grade_');
        
        try {
            // Assignment check using service
            $assignmentCheck = $this->quizExecutionService->getAssignmentCheck($userId, $topic, $course);
            $isAssignment = $assignmentCheck !== null;
            
            // Validation using service
            $validated = $this->quizValidationService->validateQuizSubmission($request, $isAssignment, $userId, $requestId);
            
            // Access validation using service
            if (!$this->quizExecutionService->validateUserAccess($userId, $course, $topic)) {
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
            $gradingResult = $this->quizExecutionService->processQuizGrading($validated, $topic, $course, $userId, $assignmentId);
            
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
                'user_quiz_count' => $this->quizExecutionService->getUserQuizCount(auth()->id()),
                'assignment_completed' => $assignmentCompleted,
                'mode' => 'quiz' // Regular quiz mode
            ]);

            // 🔔 ASSIGNMENT COMPLETION NOTIFICATION - Separate from quiz notification
            if ($assignmentCompleted && $assignmentId) {
                $assignment = \App\Models\Assignment::find($assignmentId);
                if ($assignment) {
                    \App\Services\NotificationTriggerService::onAssignmentCompleted(auth()->user(), [
                        'score' => $result['score'],
                        'total_questions' => $result['totalQuestions'],
                        'percentage' => $result['percentageGrade'],
                        'assignment_title' => $assignment->title,
                        'assignment_id' => $assignmentId,
                        'mode' => 'assignment'
                    ]);
                }
            }
            
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


}