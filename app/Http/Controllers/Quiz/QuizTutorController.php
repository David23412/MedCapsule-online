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
}