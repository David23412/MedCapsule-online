<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\QuizService;
use App\Services\GradingService;
use App\Services\NotificationService;
use App\Services\UserStatsService;

class QuizController extends Controller 
{
    protected $quizService;
    protected $gradingService;
    protected $notificationService;
    protected $userStatsService;

    public function __construct(
        QuizService $quizService,
        GradingService $gradingService,
        NotificationService $notificationService,
        UserStatsService $userStatsService
    ) {
        $this->middleware('auth');
        $this->quizService = $quizService;
        $this->gradingService = $gradingService;
        $this->notificationService = $notificationService;
        $this->userStatsService = $userStatsService;
    }

    /**
     * Show the quiz for a topic.
     */
    public function startQuiz(Course $course, Topic $topic)
    {
        try {
            $user = auth()->user();
            
            Log::info('📌 Quiz Start Attempt', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'topic_id' => $topic->id
            ]);

            // Validate access using QuizService
            $accessValidation = $this->quizService->validateQuizAccess($user, $course, $topic);
            if (!$accessValidation['access_granted']) {
                return redirect($accessValidation['redirect'])->with('error', $accessValidation['message']);
            }

            // Get the selected sources from the request
            $sources = request()->input('sources', ['all']);
            if (!is_array($sources)) {
                $sources = [$sources];
            }

            // Generate quiz questions using QuizService
            $quizResult = $this->quizService->generateQuizQuestions($topic, $sources, $user);
            
            if (!$quizResult['success']) {
                return redirect()->route('topics.forCourse', ['course' => $course->id])
                    ->with('error', $quizResult['message']);
            }

            // Store the selected sources in the session for reference during grading
            session(['selected_sources' => $sources]);

            return view('solve_quiz', [
                'topic' => $topic,
                'questions' => $quizResult['questions'],
                'course' => $course,
                'selected_sources' => $sources,
                'mode' => 'quiz'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error in startQuiz:', [
                'error' => $e->getMessage(),
                'topic_id' => $topic->id ?? null,
                'course_id' => $course->id ?? null,
                'user_id' => auth()->id()
            ]);
            return redirect()->route('topics.forCourse', ['course' => $course->id])
                ->with('error', 'An error occurred while loading the quiz. Please try again.');
        }
    }

    /**
     * Grade quiz using GradingService.
     */
    public function gradeQuiz(Request $request, Course $course, Topic $topic)
    {
        try {
            $user = auth()->user();
            $answers = $request->input('answers', []);
            $duration = $request->input('time_taken', 0);
            $mode = $request->input('mode', 'quiz');

            // Validate inputs
            if (empty($answers)) {
                return response()->json(['error' => 'No answers provided'], 422);
            }

            if (!is_numeric($duration) || $duration < 0) {
                return response()->json(['error' => 'Invalid duration'], 422);
            }

            // Validate payload size
            $maxAnswerLength = 2000;
            $maxTotalLength = 20000;
            $totalLength = 0;
            foreach ($answers as $qid => $answer) {
                if (is_string($answer) && mb_strlen($answer) > $maxAnswerLength) {
                    return response()->json([
                        'error' => "Answer for question $qid exceeds maximum allowed length of $maxAnswerLength characters."
                    ], 422);
                }
                if (is_string($answer)) {
                    $totalLength += mb_strlen($answer);
                }
            }
            if ($totalLength > $maxTotalLength) {
                return response()->json([
                    'error' => "Total answer payload exceeds maximum allowed length of $maxTotalLength characters."
                ], 422);
            }

            // Prepare context for grading
            $context = [
                'topic_id' => $topic->id,
                'course_id' => $course->id,
                'mode' => $mode,
                'selected_sources' => session('selected_sources', ['all'])
            ];

            // Grade the quiz using GradingService
            $results = $this->gradingService->gradeQuiz($user, $answers, $duration, $mode, $context);

            // Handle level up notification
            if ($results['level_up']) {
                $this->notificationService->createLevelUpNotification($user, $results['new_level'], $user->xpNeededForNextLevel());
            }

            // Update topic progress
            $this->userStatsService->updateTopicProgress($user, $topic, $results['grade_percentage']);

            Log::info('✅ Quiz graded successfully', [
                'user_id' => $user->id,
                'topic_id' => $topic->id,
                'score' => $results['score'],
                'grade_percentage' => $results['grade_percentage']
            ]);

            return response()->json([
                'success' => true,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error grading quiz:', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'topic_id' => $topic->id
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while grading the quiz. Please try again.'
            ], 500);
        }
    }

    /**
     * Start tutor mode quiz.
     */
    public function startTutorQuiz(Course $course, Topic $topic)
    {
        try {
            $user = auth()->user();
            
            // Validate access
            $accessValidation = $this->quizService->validateQuizAccess($user, $course, $topic);
            if (!$accessValidation['access_granted']) {
                return redirect($accessValidation['redirect'])->with('error', $accessValidation['message']);
            }

            // Get sources
            $sources = request()->input('sources', ['all']);
            if (!is_array($sources)) {
                $sources = [$sources];
            }

            // Generate tutor quiz
            $tutorResult = $this->quizService->generateTutorQuiz($topic, $sources, $user);
            
            if (!$tutorResult['success']) {
                return redirect()->route('topics.forCourse', ['course' => $course->id])
                    ->with('error', $tutorResult['message']);
            }

            session(['selected_sources' => $sources]);

            return view('solve_quiz', [
                'topic' => $topic,
                'questions' => $tutorResult['questions'],
                'course' => $course,
                'selected_sources' => $sources,
                'mode' => 'tutor'
            ]);

        } catch (\Exception $e) {
            Log::error('Error starting tutor quiz:', [
                'error' => $e->getMessage(),
                'topic_id' => $topic->id,
                'user_id' => auth()->id()
            ]);
            
            return redirect()->route('topics.forCourse', ['course' => $course->id])
                ->with('error', 'An error occurred while loading the tutor quiz.');
        }
    }

    /**
     * Check tutor answer (immediate feedback).
     */
    public function checkTutorAnswer(Request $request, Question $question)
    {
        try {
            $user = auth()->user();
            $userAnswer = $request->input('answer', '');
            $questionNumber = $request->input('question_number', 1);

            if (empty($userAnswer)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Please provide an answer.'
                ], 422);
            }

            // Grade the single answer
            $isCorrect = $this->gradingService->gradeAnswer($question, $userAnswer);
            
            // Record mistake if incorrect
            if (!$isCorrect) {
                $this->gradingService->recordMistake($user, $question, $userAnswer);
            }

            // Update user stats
            $this->userStatsService->updateUserStats($user, [
                'total_questions' => 1,
                'correct_answers' => $isCorrect ? 1 : 0
            ]);

            return response()->json([
                'success' => true,
                'is_correct' => $isCorrect,
                'correct_answer' => $question->correct_answer,
                'explanation' => $question->explanation,
                'question_number' => $questionNumber
            ]);

        } catch (\Exception $e) {
            Log::error('Error checking tutor answer:', [
                'error' => $e->getMessage(),
                'question_id' => $question->id,
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while checking your answer.'
            ], 500);
        }
    }

    /**
     * Show mistakes for review.
     */
    public function showMistakes(Request $request)
    {
        try {
            $user = auth()->user();
            $mistakes = $user->mistakes()
                ->with(['question.topic.course'])
                ->orderBy('last_attempted_at', 'desc')
                ->paginate(10);

            return view('mistakes.index', compact('mistakes'));

        } catch (\Exception $e) {
            Log::error('Error showing mistakes:', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return redirect()->back()->with('error', 'An error occurred while loading your mistakes.');
        }
    }

    /**
     * Get quiz statistics for a topic.
     */
    public function getTopicStats(Topic $topic)
    {
        try {
            $user = auth()->user();
            $stats = $this->quizService->getTopicQuizStats($topic, $user);

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting topic stats:', [
                'error' => $e->getMessage(),
                'topic_id' => $topic->id,
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while loading statistics.'
            ], 500);
        }
    }
} 