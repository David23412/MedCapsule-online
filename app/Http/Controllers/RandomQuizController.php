<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Question;
use App\Models\Topic;
use App\Models\Mistake;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Course;
use Illuminate\Support\Facades\Cache;
use App\Models\SpacedRepetition;
use App\Models\StudySession;
use App\Models\QuizAttempt;
use App\Models\UserTopicProgress;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;
use App\Models\User;

class RandomQuizController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function generateRandomQuiz(Request $request)
    {
        try {
            // Log the incoming request
            Log::info('Random Quiz Request', [
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);

            // Validate request
            $request->validate([
                'question_limit' => 'required|integer|min:10|max:100',
                'selected_courses' => 'required|array|min:1',
                'selected_courses.*' => 'exists:courses,id',
                'time_limit' => 'required|numeric|min:0.167|max:60'
            ]);

            $user = Auth::user();
            $selectedCourses = $request->selected_courses;
            $timeLimit = $request->time_limit;
            

            // Get random questions from selected enrolled courses
            $randomQuestions = Question::whereHas('topic', function($query) use ($selectedCourses) {
                    $query->whereIn('course_id', $selectedCourses);
                })
                ->inRandomOrder()
                ->limit($request->question_limit)
                ->with(['topic', 'topic.course']) // Eager load relationships
                ->get();

            if ($randomQuestions->isEmpty()) {
                Log::warning('No questions found for selected courses', [
                    'user_id' => $user->id,
                    'selected_courses' => $selectedCourses
                ]);
                return back()->with('error', 'No questions available from the selected courses.');
            }

            // Store questions in session
            session(['random_quiz_questions' => $randomQuestions]);
            session(['random_quiz_time_limit' => round($timeLimit * 60)]); // Convert minutes to seconds and round

            return view('quiz.random_quiz', [
                'questions' => $randomQuestions,
                'totalQuestions' => $randomQuestions->count(),
                'selectedCourses' => Course::whereIn('id', $selectedCourses)->get(),
                'time_limit' => round($timeLimit * 60) // Convert minutes to seconds and round
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating random quiz:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);

            return back()->with('error', 'An error occurred while generating the quiz. Please try again.');
        }
    }

    public function gradeRandomQuiz(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            
            // Validate request data
            $request->validate([
                'answers' => 'required|array',
                'answers.*' => 'nullable|string',
                'time_taken' => 'required|integer|min:0'
            ]);

            $answers = $request->input('answers', []);
            $timeTaken = $request->input('time_taken', 0);
            $timeLimit = session('random_quiz_time_limit');
            
            // Get questions from session with validation
            $questions = session('random_quiz_questions');
            if (!$questions || !$questions->count()) {
                throw new \Exception('Quiz session expired or no questions found');
            }

            // Validate time limit if set
            if ($timeLimit && $timeTaken > $timeLimit * 1.1) { // Allow 10% buffer for network latency
                throw new \Exception('Time limit exceeded');
            }

            $score = 0;
            $totalQuestions = $questions->count();
            $incorrectAnswers = [];
            $correctAnswersMap = [];
            $questionData = [];

            // Process each question with proper validation
            foreach ($questions as $question) {
                $submittedAnswer = $answers[$question->id] ?? null;
                
                // Store the expected correct answer
                $correctAnswersMap[$question->id] = $question->correct_answer;
                
                // Handle written questions
                if ($question->question_type === Question::TYPE_WRITTEN) {
                    if (is_null($submittedAnswer) || trim((string)$submittedAnswer) === '') {
                        $submittedAnswer = 'unanswered';
                        $isCorrect = false;
                    } else {
                        $isCorrect = $question->isAnswerCorrect($submittedAnswer);
                    }
                } else {
                    // Handle multiple choice questions
                    if (!in_array($submittedAnswer, ['A', 'B', 'C', 'D', null])) {
                        throw new \Exception("Invalid answer format for question {$question->id}");
                    }
                    $isCorrect = $question->isAnswerCorrect($submittedAnswer);
                }

                // Track question data
                $questionData[] = [
                    'question_id' => $question->id,
                    'user_answer' => $submittedAnswer,
                    'is_correct' => $isCorrect,
                    'question_type' => $question->question_type
                ];

                if (!$isCorrect) {
                    $incorrectAnswers[$question->id] = [
                        'submitted_answer' => $submittedAnswer === null ? 'unanswered' : $submittedAnswer,
                        'question_text' => $question->question,
                        'correct_answer' => $question->correct_answer,
                        'image_path' => $question->image_url ?? null,
                        'question_type' => $question->question_type,
                        'explanation' => $question->explanation
                    ];
                } else {
                    $score++;
                }
            }

            $percentageGrade = ($score / $totalQuestions) * 100;

            // Update user stats with error handling
            try {
                $user->increment('correct_answers_count', $score);
                $user->increment('total_questions_attempted', $totalQuestions);
                $user->increment('study_time_seconds', $timeTaken);
            } catch (\Exception $e) {
                \Log::error('Failed to update user stats', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id
                ]);
                // Continue execution as this is not critical
            }

            // Create quiz attempt with validation
            try {
                $quizAttempt = QuizAttempt::create([
                    'user_id' => $user->id,
                    'topic_id' => null,
                    'quiz_type' => 'random',
                    'study_mode' => 'random',
                    'source' => 'random',
                    'duration_seconds' => $timeTaken,
                    'percentage_grade' => $percentageGrade,
                    'score' => $score,
                    'total_questions' => $totalQuestions,
                    'question_data' => $questionData
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                throw new \Exception('Invalid quiz attempt data: ' . json_encode($e->errors()));
            }

            // Create mistake records with error handling
            foreach ($incorrectAnswers as $questionId => $mistakeData) {
                try {
                    Mistake::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'question_id' => $questionId
                        ],
                        [
                            'submitted_answer' => $mistakeData['submitted_answer'],
                            'question_text' => $mistakeData['question_text'],
                            'correct_answer' => $mistakeData['correct_answer'],
                            'image_path' => $mistakeData['image_path'],
                            'quick_correct_count' => 0,
                            'quiz_correct_count' => 0,
                            'mastered' => false,
                            'last_attempt_date' => now()
                        ]
                    );
                } catch (\Exception $e) {
                    \Log::error('Failed to create mistake record', [
                        'error' => $e->getMessage(),
                        'question_id' => $questionId,
                        'user_id' => $user->id
                    ]);
                    // Continue execution as this is not critical
                }
            }

            // Handle milestone notifications
            try {
                $this->handleMilestones($user, $score);
            } catch (\Exception $e) {
                \Log::error('Failed to handle milestones', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id
                ]);
                // Continue execution as this is not critical
            }

            DB::commit();

            // Clear quiz session data
            session()->forget(['random_quiz_questions', 'random_quiz_time_limit']);

            \Log::info('Random quiz graded successfully', [
                'user_id' => $user->id,
                'score' => $score,
                'total' => $totalQuestions,
                'percentage' => $percentageGrade,
                'duration' => $timeTaken,
                'mistakes_count' => count($incorrectAnswers),
                'quiz_attempt_id' => $quizAttempt->id
            ]);

            return response()->json([
                'success' => true,
                'score' => $score,
                'total' => $totalQuestions,
                'percentage' => $percentageGrade,
                'correct_answers' => $correctAnswersMap,
                'quiz_attempt_id' => $quizAttempt->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Error in gradeRandomQuiz:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            $errorMessage = 'An error occurred while grading the quiz.';
            if (app()->environment('local')) {
                $errorMessage .= ' ' . $e->getMessage();
            }

            return response()->json([
                'success' => false,
                'error' => $errorMessage
            ], 500);
        }
    }

    /**
     * Handle milestone notifications and rewards
     */
    private function handleMilestones($user, $score)
    {
        // Check for correct answers milestone
        $correctAnswersCount = $user->correct_answers_count;
        if ($correctAnswersCount >= 100 && $correctAnswersCount % 100 === 0) {
            app(NotificationService::class)->createCorrectAnswersMilestoneNotification(
                $user,
                $correctAnswersCount
            );
        }

        // Check for level up based on new XP total
        $oldXP = $user->xp;
        $earnedXP = $user->earnXP($score, $totalQuestions);
        $user->update(['xp' => ($oldXP + $earnedXP)]);
        $this->checkForLevelUp($user);

        // Update study streak
        $oldStreak = $user->study_streak_days ?? 0;
        $newStreak = $user->updateStudyStreak();
        
        // Send notification if streak increased
        if ($newStreak > $oldStreak) {
            app(NotificationService::class)->createStudyStreakNotification(
                $user,
                $newStreak
            );
        }
    }

    private function getScoreMessage($percentage)
    {
        if ($percentage === 100) {
            return "Perfect score! Outstanding performance!";
        } elseif ($percentage >= 80) {
            return "Excellent work! Keep it up!";
        } elseif ($percentage >= 70) {
            return "Good job! Room for improvement.";
        } else {
            return "Keep practicing to improve your score.";
        }
    }

    /**
     * Check if the user has leveled up and send notification if needed
     */
    private function checkForLevelUp(User $user): void
    {
        try {
            // Get the current level after updates
            $currentLevel = $user->getCurrentLevel();
            
            // Calculate what level the user was at before the new XP was added
            // This should be based on the XP before this quiz attempt
            $previousXP = max(0, ($user->xp ?? 0) - $user->earnXP($user->correct_answers_count, $user->total_questions_attempted));
            $previousLevel = floor($previousXP / 1000) + 1;
            
            // If the level increased, create a level up notification
            if ($currentLevel > $previousLevel) {
                $xpForNextLevel = $user->getXPForNextLevel();
                
                app(NotificationService::class)->createLevelUpNotification(
                    $user,
                    $currentLevel,
                    $xpForNextLevel
                );
                
                Log::info('User leveled up', [
                    'user_id' => $user->id,
                    'from_level' => $previousLevel,
                    'to_level' => $currentLevel,
                    'xp_for_next' => $xpForNextLevel
                ]);
            }
        } catch (\Exception $e) {
            // Never let a notification failure affect the quiz experience
            Log::error('Failed to process level up notification', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);
        }
    }
} 