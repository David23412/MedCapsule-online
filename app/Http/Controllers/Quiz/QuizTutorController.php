<?php

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;
use App\Services\QuizService;
use App\Services\GradingService;
use App\Services\TutorSourceProgressService;
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
    protected TutorSourceProgressService $tutorSourceProgressService;

    public function __construct(
        QuizService $quizService,
        UltraConsolidatedCacheService $cacheService,
        GradingService $gradingService,
        TutorSourceProgressService $tutorSourceProgressService
    )
    {
        $this->middleware(['auth']);
        $this->quizService = $quizService;
        $this->cacheService = $cacheService;
        $this->gradingService = $gradingService;
        $this->tutorSourceProgressService = $tutorSourceProgressService;
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

            $tutorScope = request()->input('tutor_scope', 'full');
            if (!in_array($tutorScope, ['full', 'remaining'], true)) {
                $tutorScope = 'full';
            }

            // Initialize tutor quiz
            $quizData = $this->quizService->initializeQuiz($user, $course, $topic, $sources, 'tutor', [
                'tutor_scope' => $tutorScope,
            ]);
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // ✅ ZERO N+1 BADGE ENRICHMENT
            \App\Models\Question::enrichWithBadges($quizData['questions'], $topic->id);

            return view('tutor_mode', [
                'topic' => $quizData['topic'],
                'questions' => $quizData['questions'],
                'course' => $quizData['course'],
                'selected_sources' => $quizData['sources'],
                'tutor_scope' => $tutorScope,
                'tutor_session_key' => session('tutor_session_key'),
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
            $questions = collect();
            $topic = null;
            if ($topicId && $courseId) {
                $topic = Topic::find($topicId);
                $sessionQuestionIds = collect(session('tutor_session_questions.sources', []))
                    ->flatten()
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->values()
                    ->all();

                if (!empty($sessionQuestionIds)) {
                    $questions = Question::query()
                        ->whereIn('id', $sessionQuestionIds)
                        ->get()
                        ->sortBy(fn (Question $question) => array_search($question->id, $sessionQuestionIds, true))
                        ->values();
                } elseif ($topic) {
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
                        'question_data' => [],
                        'questions' => collect(),
                    ];
                }

                if ($userAnswer) {
                    $sourceGroups[$source]['questions']->push($question);
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

            if ($topic) {
                $this->tutorSourceProgressService->recordSessionAnswers($user->id, $topic, $sourceGroups);
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


            if ((int) session('tutor_session_questions.topic_id') === (int) $topicId
                && (int) session('tutor_session_questions.course_id') === (int) $courseId) {
                session(['tutor_session_questions.completed' => true]);
            }

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

    /**
     * Admin-only: Update a question inline from tutor/quiz mode.
     * Supports both MCQ and written question types.
     */
    public function updateQuestion(Request $request, Question $question)
    {
        $questionType = $request->input('question_type') ?? $question->question_type ?? 'multiple_choice';

        $rules = [
            'question'      => 'required|string|max:5000',
            'question_type' => 'nullable|string|in:multiple_choice,written',
            'explanation'   => 'nullable|string|max:10000',
        ];

        if ($questionType === 'written') {
            $rules['correct_answer']      = 'required|string|max:5000';
            $rules['alternative_answers'] = 'nullable|string|max:10000';
        } else {
            $rules['option_a']       = 'nullable|string|max:1000';
            $rules['option_b']       = 'nullable|string|max:1000';
            $rules['option_c']       = 'nullable|string|max:1000';
            $rules['option_d']       = 'nullable|string|max:1000';
            $rules['option_e']       = 'nullable|string|max:1000';
            $rules['correct_answer'] = 'required|string|in:A,B,C,D,E';
        }

        $request->validate($rules);
        if ($questionType !== 'written') {
            $field = 'option_' . strtolower($request->input('correct_answer'));
            $answerText = trim((string) $request->input($field, $question->{$field}));
            if ($answerText === '' || $answerText === '-') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'correct_answer' => 'The selected correct answer must have option text.',
                ]);
            }
        }

        try {
            $before = $question->only([
                'question', 'question_type', 'option_a', 'option_b', 'option_c',
                'option_d', 'option_e', 'correct_answer', 'alternative_answers', 'explanation',
            ]);

            $updateData = [
                'question'      => $request->input('question'),
                'question_type' => $questionType,
                'explanation'   => $request->input('explanation', $question->explanation),
                'correct_answer' => $request->input('correct_answer'),
            ];

            if ($questionType === 'written') {
                $updateData['alternative_answers'] = $request->input('alternative_answers', $question->alternative_answers);
                // Clear MCQ fields for written questions
                $updateData['option_a'] = null;
                $updateData['option_b'] = null;
                $updateData['option_c'] = null;
                $updateData['option_d'] = null;
                $updateData['option_e'] = null;
            } else {
                $updateData['option_a'] = $request->input('option_a', $question->option_a);
                $updateData['option_b'] = $request->input('option_b', $question->option_b);
                $updateData['option_c'] = $request->input('option_c', $question->option_c);
                $updateData['option_d'] = $request->input('option_d', $question->option_d);
                $updateData['option_e'] = $request->input('option_e', $question->option_e);
                $updateData['alternative_answers'] = null;
            }

            $question->update($updateData);

            // Clear any related caches so the qbank reflects changes immediately
            Cache::forget("question_{$question->id}");
            if ($question->topic_id) {
                Cache::forget("topic_{$question->topic_id}_questions");
                if ($courseId = $question->topic?->course_id) {
                    Cache::forget("course_{$courseId}_questions_search_payload_v10");
                }
            }

            // Audit log for admin question edits
            Log::info('Admin edited question inline', [
                'admin_id'    => auth()->id(),
                'question_id' => $question->id,
                'topic_id'    => $question->topic_id,
                'before'      => $before,
                'after'       => $question->only([
                    'question', 'question_type', 'option_a', 'option_b', 'option_c',
                    'option_d', 'option_e', 'correct_answer', 'alternative_answers', 'explanation',
                ]),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Question updated successfully',
                'data'    => [
                    'id'                  => $question->id,
                    'question'            => $question->question,
                    'question_type'       => $question->question_type,
                    'option_a'            => $question->option_a,
                    'option_b'            => $question->option_b,
                    'option_c'            => $question->option_c,
                    'option_d'            => $question->option_d,
                    'option_e'            => $question->option_e,
                    'correct_answer'      => $question->correct_answer,
                    'alternative_answers' => $question->alternative_answers,
                    'explanation'         => $question->explanation,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating question inline', [
                'error'       => $e->getMessage(),
                'question_id' => $question->id,
                'user_id'     => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update question. Please try again.',
            ], 500);
        }
    }

    /**
     * Admin-only: Delete a question inline from quiz/tutor mode.
     */
    public function deleteQuestion(Request $request, Question $question)
    {
        try {
            $questionId = $question->id;
            $topicId    = $question->topic_id;

            $question->delete();

            // Clear caches
            Cache::forget("question_{$questionId}");
            if ($topicId) {
                Cache::forget("topic_{$topicId}_questions");
                Cache::forget("topic:{$topicId}:questions_count");
            }

            Log::info('Admin deleted question inline', [
                'admin_id'    => auth()->id(),
                'question_id' => $questionId,
                'topic_id'    => $topicId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Question deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting question inline', [
                'error'       => $e->getMessage(),
                'question_id' => $question->id,
                'user_id'     => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete question. Please try again.',
            ], 500);
        }
    }
}
