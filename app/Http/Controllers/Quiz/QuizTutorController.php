<?php

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;
use App\Services\QuizService;
use App\Services\CacheService;
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

    public function __construct(QuizService $quizService, CacheService $cacheService)
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
     * Check tutor answer with nuclear optimization
     */
    public function checkTutorAnswer(Request $request, Question $question)
    {
        $startTime = microtime(true);
        $requestId = uniqid('tutor_check_');
        
        try {
            $userId = auth()->id();
            
            Log::info('📌 Tutor Answer Check', [
                'request_id' => $requestId,
                'user_id' => $userId,
                'question_id' => $question->id
            ]);

            $validated = $request->validate([
                'answer' => 'required|string|max:1000'
            ]);

            $answer = $validated['answer'];
            
            if (empty($answer)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Answer is required'
                ], 400);
            }

            // Check answer with caching
            $cacheKey = "question_check_{$question->id}_{$answer}";
            $result = Cache::remember($cacheKey, 3600, function() use ($question, $answer) {
                $isCorrect = strtolower(trim($answer)) === strtolower(trim($question->correct_answer));
                
                return [
                    'is_correct' => $isCorrect,
                    'correct_answer' => $question->correct_answer,
                    'explanation' => $question->explanation,
                    'question_type' => $question->question_type,
                    'options' => [
                        'a' => $question->option_a,
                        'b' => $question->option_b,
                        'c' => $question->option_c,
                        'd' => $question->option_d
                    ]
                ];
            });

            $executionTime = (microtime(true) - $startTime) * 1000;
            
            Log::info('✅ Tutor answer checked', [
                'request_id' => $requestId,
                'question_id' => $question->id,
                'is_correct' => $result['is_correct'],
                'execution_time_ms' => round($executionTime, 2)
            ]);

            return response()->json([
                'success' => true,
                'is_correct' => $result['is_correct'],
                'correct_answer' => $result['correct_answer'],
                'explanation' => $result['explanation'],
                'question_type' => $result['question_type'],
                'options' => $result['options'],
                'performance_metrics' => [
                    'processing_time_ms' => round($executionTime, 2)
                ]
            ]);

        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::error('❌ Error in checkTutorAnswer:', [
                'request_id' => $requestId,
                'question_id' => $question->id,
                'error' => $e->getMessage(),
                'execution_time_ms' => round($executionTime, 2)
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking the answer. Please try again.'
            ], 500);
        }
    }
}