<?php

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Services\QuizService;
use App\Services\Core\UltraConsolidatedCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * 💀 NUCLEAR OPTIMIZED QUIZ TUTOR CONTROLLER 💀
 * Handles tutor mode with F-16 RAPTOR SPEED
 */
class QuizTutorController extends Controller
{
    protected $quizService;
    protected $cacheService;

    public function __construct(QuizService $quizService, UltraConsolidatedCacheService $cacheService)
    {
        $this->middleware(['auth']);
        $this->quizService = $quizService;
        $this->cacheService = $cacheService;
    }

    /**
     * Start tutor quiz with nuclear optimization
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
                'course_id' => $course->id,
                'topic_id' => $topic->id
            ]);
            
            // Check access with caching
            $accessKey = "user_access_{$user->id}_{$course->id}";
            $hasAccess = Cache::remember($accessKey, 300, function() use ($user, $course) {
                return $user->isAdmin() || $user->hasAccessToCourse($course->id) || (!$course->price || $course->price <= 0);
            });

            if (!$hasAccess) {
                return redirect()->route('topics.forCourse', $course->id)
                    ->with('error', 'You must be granted access to this course to take quizzes.');
            }

            // Get sources
            $sources = request()->input('sources', ['all']);
            if (!is_array($sources)) {
                $sources = [$sources];
            }
            
            if (count($sources) === 1 && strpos($sources[0], ',') !== false) {
                $sources = array_map('trim', explode(',', $sources[0]));
            }

            // Initialize tutor quiz
            $quizData = $this->quizService->initializeQuiz($user, $course, $topic, $sources, 'tutor');
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::info('✅ Tutor quiz initialized successfully', [
                'request_id' => $requestId,
                'execution_time_ms' => round($executionTime, 2),
                'questions_count' => count($quizData['questions'])
            ]);
            
            return view('tutor_mode', [
                'topic' => $quizData['topic'],
                'questions' => $quizData['questions'],
                'course' => $quizData['course'],
                'selected_sources' => $quizData['sources'],
                'mode' => 'tutor'
            ]);

        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::error('❌ Error in startTutorQuiz:', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'execution_time_ms' => round($executionTime, 2)
            ]);
            return redirect()->route('topics.forCourse', $course->id)
                ->with('error', 'An error occurred while loading the tutor quiz. Please try again.');
        }
    }

    /**
     * 💀 LETHAL SPEED ANSWER CHECKING - BYPASS EVERYTHING 💀
     */
    public function checkTutorAnswer(Request $request, Question $question)
    {
        // INSTANT ANSWER EXTRACTION - NO VALIDATION
        $answer = $request->input('answer', '');
        
        // INSTANT COMPARISON - NO CACHING OVERHEAD
        $isCorrect = strtolower(trim($answer)) === strtolower(trim($question->correct_answer));
        
        // MINIMAL JSON RESPONSE - NO EXTRAS
        return response()->json([
            'success' => true,
            'is_correct' => $isCorrect,
            'correct_answer' => $question->correct_answer,
            'explanation' => $question->explanation
        ]);
    }

    /**
     * Handle tutor mode session completion - CRITICAL FIX: Save quiz attempt to database
     */
    public function completeTutorSession(Request $request)
    {
        try {
            $user = auth()->user();
            $sessionData = $request->all();
            
            $correctAnswers = $sessionData['correct_answers'] ?? 0;
            $totalQuestions = $sessionData['total_questions'] ?? 1;
            $courseId = $sessionData['course_id'] ?? null;
            $topicId = $sessionData['topic_id'] ?? null;
            $source = $sessionData['source'] ?? 'General';
            $questionData = $sessionData['question_data'] ?? [];
            $durationSeconds = $sessionData['duration_seconds'] ?? 0;
            
            // Get course and topic names
            $course = $courseId ? Course::find($courseId) : null;
            $topic = $topicId ? Topic::find($topicId) : null;
            
            // CRITICAL FIX: Create quiz attempt record for tutor mode
            if ($topicId && $totalQuestions > 0) {
                $percentage = ($correctAnswers / $totalQuestions) * 100;
                
                $quizAttemptData = [
                    'user_id' => $user->id,
                    'topic_id' => $topicId,
                    'quiz_type' => 'tutor',
                    'study_mode' => 'tutor',
                    'source' => $source,
                    'score' => $correctAnswers,
                    'total_questions' => $totalQuestions,
                    'percentage_grade' => round($percentage, 2),
                    'duration_seconds' => $durationSeconds,
                    'question_data' => $questionData,
                    'correct_answers' => $correctAnswers
                ];
                
                // Create the quiz attempt using the optimized method
                $result = QuizAttempt::createWithProgress($quizAttemptData);
                
                Log::info('🔥 TUTOR MODE: Quiz attempt created successfully', [
                    'user_id' => $user->id,
                    'topic_id' => $topicId,
                    'source' => $source,
                    'score' => $correctAnswers,
                    'total' => $totalQuestions,
                    'percentage' => $percentage,
                    'attempt_id' => $result['attempt']->id ?? null
                ]);
            }
            
            // Trigger tutor mode completion notifications
            \App\Services\NotificationTriggerService::onQuizCompleted($user, [
                'score' => $correctAnswers,
                'total_questions' => $totalQuestions,
                'course_name' => $course->name ?? 'Course',
                'topic_name' => $topic->name ?? 'Topic',
                'course_id' => $courseId,
                'topic_id' => $topicId,
                'mode' => 'tutor'
            ]);
            
            Log::info('Tutor session completed with database record', [
                'user_id' => $user->id,
                'correct_answers' => $correctAnswers,
                'total_questions' => $totalQuestions,
                'course_id' => $courseId,
                'topic_id' => $topicId,
                'source' => $source
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Tutor session completed and progress saved successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error completing tutor session:', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error completing tutor session: ' . $e->getMessage()
            ], 500);
        }
    }
}