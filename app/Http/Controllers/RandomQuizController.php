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
use App\Models\StudySession;
use App\Models\QuizAttempt;
use App\Models\UserTopicProgress;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;
use App\Models\User;
use App\Services\UserStatsService;
use App\Services\DatabaseService;
use App\Services\ResilientCacheService;
use App\Services\InputValidationService;

class RandomQuizController extends Controller
{
    protected $userStatsService;

    public function __construct(UserStatsService $userStatsService)
    {
        $this->middleware('auth');
        $this->userStatsService = $userStatsService;
    }

    public function generateRandomQuiz(Request $request)
    {
        $startTime = microtime(true);
        $requestId = uniqid('random_quiz_gen_');
        
        try {
            // Log the incoming request
            Log::info('Random Quiz Generation Request', [
                'request_id' => $requestId,
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
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
                    'request_id' => $requestId,
                    'user_id' => $user->id,
                    'selected_courses' => $selectedCourses
                ]);
                return back()->with('error', 'No questions available from the selected courses.');
            }

            // Store questions in session
            session(['random_quiz_questions' => $randomQuestions]);
            session(['random_quiz_time_limit' => round($timeLimit * 60)]); // Convert minutes to seconds and round

            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::info('✅ Random quiz generated successfully', [
                'request_id' => $requestId,
                'execution_time_ms' => round($executionTime, 2),
                'questions_count' => $randomQuestions->count(),
                'selected_courses_count' => count($selectedCourses),
                'time_limit_seconds' => round($timeLimit * 60)
            ]);

            return view('quiz.random_quiz', [
                'questions' => $randomQuestions,
                'totalQuestions' => $randomQuestions->count(),
                'selectedCourses' => Course::whereIn('id', $selectedCourses)->get(),
                'time_limit' => round($timeLimit * 60) // Convert minutes to seconds and round
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::warning('Random quiz generation failed - validation error:', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'validation_errors' => $e->errors(),
                'user_id' => auth()->id(),
                'execution_time_ms' => round($executionTime, 2)
            ]);
            throw $e; // Re-throw to let Laravel handle the validation response
        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::error('❌ Error generating random quiz:', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'execution_time_ms' => round($executionTime, 2),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);

            return back()->with('error', 'An error occurred while generating the quiz. Please try again.');
        }
    }

    public function gradeRandomQuiz(Request $request)
    {
        $startTime = microtime(true);
        $requestId = uniqid('random_quiz_grade_');
        $userId = auth()->id();

        // BULLETPROOF INPUT VALIDATION
        $validationResult = InputValidationService::validateInput($request->all(), [
            'answers' => 'required|array|max:100',
            'answers.*' => 'nullable|string|max:2000|no_sql_injection',
            'time_taken' => 'required|integer|min:0|max:86400'
        ], [], [
            'user_id' => $userId,
            'action' => 'random_quiz_grade',
            'request_id' => $requestId
        ]);

        // BULLETPROOF TRANSACTION
        $result = DatabaseService::safeTransaction(function () use ($request, $requestId, $validationResult, $userId, $startTime) {
            $user = Auth::user();
            
            Log::info('Random Quiz Grading Started', [
                'request_id' => $requestId,
                'user_id' => $user->id,
                'ip_address' => request()->ip(),
                'questions_count' => count($validationResult['answers'] ?? [])
            ]);

            $answers = $validationResult['answers'] ?? [];
            $timeTaken = $validationResult['time_taken'] ?? 0;
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

            // Create quiz attempt with validation
            try {
                $quizAttempt = QuizAttempt::create([
                    'user_id' => $user->id,
                    'topic_id' => null,
                    'quiz_type' => 'random',
                    'study_mode' => null,
                    'source' => null,
                    'duration_seconds' => $timeTaken,
                    'percentage_grade' => $percentageGrade,
                    'score' => $score,
                    'total_questions' => $totalQuestions,
                    'question_data' => $questionData
                ]);
                // Update user stats after quiz attempt
                $this->userStatsService->updateAfterQuizAttempt($user, $totalQuestions, $score, $timeTaken);
            } catch (\Exception $e) {
                throw new \Exception('Invalid quiz attempt data: ' . json_encode($e->getMessage()));
            }

            // Create mistake records with error handling
            foreach ($incorrectAnswers as $questionId => $mistakeData) {
                $mistakeResult = DatabaseService::safeQuery(function() use ($user, $questionId, $mistakeData) {
                    $existing = Mistake::where('user_id', $user->id)
                                     ->where('question_id', $questionId)
                                     ->first();
                    
                    if ($existing) {
                        // Update existing mistake
                        $existing->update([
                            'last_attempt_date' => now(),
                            'incorrect_attempts' => $existing->incorrect_attempts + 1,
                            'last_incorrect_answer' => $mistakeData['submitted_answer']
                        ]);
                        return $existing;
                    } else {
                        // Create new mistake
                        return Mistake::create([
                            'user_id' => $user->id,
                            'question_id' => $questionId,
                            'last_attempt_date' => now(),
                            'incorrect_attempts' => 1,
                            'last_incorrect_answer' => $mistakeData['submitted_answer']
                        ]);
                    }
                }, null);
                
                if ($mistakeResult === null) {
                    Log::error('Failed to create/update mistake record', [
                        'request_id' => $requestId,
                        'user_id' => $user->id,
                        'question_id' => $questionId
                    ]);
                    // Continue processing other mistakes
                }
            }

            // Handle milestones
            $this->handleMilestones($user, $score);

            // Clear session data
            session()->forget(['random_quiz_questions', 'random_quiz_time_limit']);

            return [
                'success' => true,
                'score' => $score,
                'total_questions' => $totalQuestions,
                'percentage_grade' => $percentageGrade,
                'incorrect_answers' => $incorrectAnswers,
                'correct_answers_map' => $correctAnswersMap,
                'message' => $this->getScoreMessage($percentageGrade),
                'quiz_attempt_id' => $quizAttempt->id
            ];
        }, [
            'user_id' => $userId,
            'action' => 'random_quiz_grading',
            'request_id' => $requestId
        ]);

        // Handle the result from the transaction
        if (!$result['success']) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::error('❌ Random quiz transaction failed:', [
                'request_id' => $requestId,
                'error' => $result['error'],
                'user_id' => $userId,
                'execution_time_ms' => round($executionTime, 2)
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while grading the quiz. Please try again.'
            ], 500);
        }

        $executionTime = (microtime(true) - $startTime) * 1000;
        Log::info('✅ Random quiz graded successfully', [
            'request_id' => $requestId,
            'execution_time_ms' => round($executionTime, 2),
            'score' => $result['data']['score'],
            'total_questions' => $result['data']['total_questions'],
            'percentage_grade' => $result['data']['percentage_grade'],
            'quiz_attempt_id' => $result['data']['quiz_attempt_id']
        ]);

        return response()->json($result['data']);
    }

    private function handleMilestones($user, $score)
    {
        try {
            // Check for perfect score milestone
            if ($score === 100) {
                Log::info('Perfect score milestone achieved', [
                    'user_id' => $user->id,
                    'score' => $score
                ]);
                // Could add notification or badge here
            }
            
            // Check for high score milestone (90%+)
            if ($score >= 90) {
                Log::info('High score milestone achieved', [
                    'user_id' => $user->id,
                    'score' => $score
                ]);
                // Could add notification or badge here
            }
        } catch (\Exception $e) {
            Log::error('Failed to handle milestones', [
                'user_id' => $user->id,
                'score' => $score,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getScoreMessage($percentage)
    {
        if ($percentage >= 90) {
            return "Outstanding! You've achieved a perfect score! 🌟";
        } elseif ($percentage >= 80) {
            return "Excellent work! You're doing great! 🎉";
        } elseif ($percentage >= 70) {
            return "Good job! Keep up the good work! 💪";
        } elseif ($percentage >= 60) {
            return "You're making progress! Keep practicing! 📚";
        } else {
            return "Don't give up! Review the material and try again! 💪";
        }
    }

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
                
                // Create level up notification
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