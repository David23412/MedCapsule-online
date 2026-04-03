<?php

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;
use App\Services\QuizService;
use App\Services\GradingService;
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
    protected $gradingService;

    public function __construct(QuizService $quizService, UltraConsolidatedCacheService $cacheService, GradingService $gradingService)
    {
        $this->middleware(['auth']);
        $this->quizService = $quizService;
        $this->cacheService = $cacheService;
        $this->gradingService = $gradingService;
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
            
            
            // Check access with caching
            $accessKey = "user_access_{$user->id}_{$course->id}";
            $hasAccess = Cache::remember($accessKey, 300, function() use ($user, $course) {
                return $user->isAdmin() || $user->hasAccessToCourse($course->id);
            });

            if (!$hasAccess) {
                if ($user->requiresSurveyUnlockForCourse($course)) {
                    return redirect($user->freeCourseSurveyRedirectFor($course, request()->getRequestUri()));
                }

                return redirect()->route('topics.forCourse', $course->id)
                    ->with('error', 'You must be granted access to this course to take quizzes.');
            }

            if (!$user->isAdmin() && $user->isTopicPreviewLocked($topic)) {
                return redirect()->route('topics.forCourse', $course->id)
                    ->with('error', 'This topic is outside the free preview. Unlock the full course to continue.');
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
            
            // ✅ ZERO N+1 BADGE ENRICHMENT
            \App\Models\Question::enrichWithBadges($quizData['questions'], $topic->id);

            return view('tutor_mode', [
                'topic' => $quizData['topic'],
                'questions' => $quizData['questions'],
                'course' => $quizData['course'],
                'selected_sources' => $quizData['sources'],
                'mode' => 'tutor',
                'currentUserView' => [
                    'profile_image_url' => $user->getProfileImageUrl(),
                    'profile_fallback_url' => 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&size=200&background=007bff&color=ffffff',
                ],
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
     * 🔥 TUTOR ANSWER CHECKING - WITH MISTAKE TRACKING & PROGRESS UPDATE
     */
    public function checkTutorAnswer(Request $request, Question $question)
    {
        try {
            $user = auth()->user();
            $answer = $request->input('answer', '');
            
            // Use GradingService to properly grade, track mistakes, and update progress
            $result = $this->gradingService->gradeTutorAnswer($user, $question, $answer);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Error in checkTutorAnswer:', [
                'error' => $e->getMessage(),
                'question_id' => $question->id,
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error checking answer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle tutor mode session completion - CRITICAL FIX: Save quiz attempt to database
     */
    public function completeTutorSession(Request $request)
    {
        try {
            $user = auth()->user();
            $sessionData = $request->all();


            // Extract data from NEW PAYLOAD STRUCTURE
            $results = $sessionData['results'] ?? [];
            $userAnswers = $sessionData['user_answers'] ?? [];
            $answeredQuestions = $sessionData['answered_questions'] ?? 0;


            // GET COURSE AND TOPIC FROM SESSION/URL PARAMS
            $topicId = request()->input('topic_id', session('current_topic_id', request('topic_id')));
            $courseId = request()->input('course_id', session('current_course_id', request('course_id')));

            // Fallback to default values if not found
            if (!$topicId) {
                $topicId = 1; // Default topic ID
            }
            if (!$courseId) {
                $courseId = 1; // Default course ID
            }


            // GET QUESTIONS TO EXTRACT SOURCES
            $questions = [];
            if ($topicId && $courseId) {
                $topic = Topic::find($topicId);
                if ($topic) {
                    $questions = $topic->questions()->get();
                }
            }

            // GROUP ANSWERS BY SOURCE AND CALCULATE SCORES
            $sourceGroups = [];
            $totalCorrect = 0;
            $totalQuestions = 0;

            foreach ($questions as $question) {
                $questionId = $question->id;
                $source = $question->source ?? 'General';
                $userAnswer = isset($userAnswers[$questionId]) ? $userAnswers[$questionId] : null;

                $isCorrect = false;
                if ($userAnswer) {
                    $isCorrect = $question->isAnswerCorrect((string) $userAnswer);
                    $totalQuestions++;
                    if ($isCorrect) {
                        $totalCorrect++;
                    } else {
                        // 🔥 RECORD MISTAKE IMMEDIATELY - This was missing!
                        \App\Models\Mistake::recordFailure($user->id, $question, $userAnswer);
                    }
                }

                // Group by source
                if (!isset($sourceGroups[$source])) {
                    $sourceGroups[$source] = [
                        'correct' => 0,
                        'total' => 0,
                        'question_data' => []
                    ];
                }

                if ($userAnswer) {
                    $sourceGroups[$source]['question_data'][] = [
                        'question_id' => $questionId,
                        'user_answer' => $userAnswer,
                        'is_correct' => $isCorrect
                    ];

                    $sourceGroups[$source]['total']++;
                    if ($isCorrect) {
                        $sourceGroups[$source]['correct']++;
                    }
                }
            }


            // CREATE QUIZ ATTEMPTS FOR EACH SOURCE
            $createdAttempts = [];
            foreach ($sourceGroups as $source => $groupData) {
                if ($groupData['total'] > 0) {
                    $percentage = ($groupData['correct'] / $groupData['total']) * 100;

                    $quizAttemptData = [
                        'user_id' => $user->id,
                        'topic_id' => $topicId,
                        'quiz_type' => 'normal',
                        'study_mode' => 'tutor',
                        'source' => $source,
                        'score' => $groupData['correct'],
                        'total_questions' => $groupData['total'],
                        'percentage_grade' => round($percentage, 2),
                        'duration_seconds' => count($userAnswers), // Use count as duration estimate
                        'question_data' => $groupData['question_data'],
                        'correct_answers' => $groupData['correct']
                    ];

                    // Create the quiz attempt using the optimized method
                    $result = \App\Models\QuizAttempt::createWithProgress($quizAttemptData, false);

                    $createdAttempts[] = $result;

                }
            }

            // UPDATE FRONTEND WITH CORRECT CONTEXT
            $course = Course::find($courseId);

            // 🔥 CLEAR MISTAKE CACHE - Ensure mistakes show up instantly!
            if ($totalQuestions > $totalCorrect) {
                \App\Http\Controllers\Quiz\QuizMistakeController::clearUserMistakeCaches($user->id);
            }

            // 🔥 CLEAR PROGRESS CACHE - Source modal updates instantly!
            \App\Models\QuizAttempt::clearProgressCache($user->id, $topicId, 'tutor');

            // Trigger tutor mode completion notifications
            \App\Services\NotificationTriggerService::onQuizCompleted($user, [
                'score' => $totalCorrect,
                'total_questions' => $totalQuestions,
                'course_name' => $course->name ?? 'Course',
                'topic_name' => $topic->name ?? 'Topic',
                'course_id' => $courseId,
                'topic_id' => $topicId,
                'mode' => 'tutor'
            ]);


            return response()->json([
                'success' => true,
                'message' => 'Tutor session completed and progress saved successfully',
                'data' => [
                    'attempts_created' => count($createdAttempts),
                    'total_correct' => $totalCorrect,
                    'total_questions' => $totalQuestions,
                    'sources_processed' => count($sourceGroups)
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ ERROR COMPLETING TUTOR SESSION:', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'stack_trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error completing tutor session: ' . $e->getMessage()
            ], 500);
        }
    }
}
