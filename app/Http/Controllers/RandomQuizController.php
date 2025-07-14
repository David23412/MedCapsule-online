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

            // Enhanced validation with better error messages
            $validated = $request->validate([
                'question_limit' => 'required|integer|min:5|max:100',
                'selected_courses' => 'required|array|min:1|max:20',
                'selected_courses.*' => 'exists:courses,id',
                'time_limit' => 'required|numeric|min:0.083|max:60'
            ], [
                'question_limit.required' => 'Question limit is required',
                'question_limit.min' => 'Minimum 5 questions required',
                'question_limit.max' => 'Maximum 100 questions allowed',
                'selected_courses.required' => 'At least one course must be selected',
                'selected_courses.min' => 'At least one course must be selected',
                'selected_courses.max' => 'Maximum 20 courses allowed',
                'time_limit.required' => 'Time limit is required',
                'time_limit.min' => 'Minimum 5 minutes required',
                'time_limit.max' => 'Maximum 60 minutes allowed'
            ]);

            $user = Auth::user();
            $selectedCourses = $validated['selected_courses'];
            $timeLimit = $validated['time_limit'];
            $questionLimit = $validated['question_limit'];

            // ENHANCED: Get random questions from selected enrolled courses with better error handling
            $randomQuestions = Question::whereHas('topic', function($query) use ($selectedCourses) {
                    $query->whereIn('course_id', $selectedCourses);
                })
                ->where('deleted', false)
                ->whereNotNull('question')
                ->whereNotNull('correct_answer')
                ->inRandomOrder()
                ->limit($questionLimit)
                ->with(['topic', 'topic.course'])
                ->get();

            if ($randomQuestions->isEmpty()) {
                Log::warning('No questions found for selected courses', [
                    'request_id' => $requestId,
                    'user_id' => $user->id,
                    'selected_courses' => $selectedCourses
                ]);
                
                // ENHANCED: Return JSON response for AJAX requests, view for regular requests
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'No questions available from the selected courses. Please try selecting different courses.',
                        'redirect' => route('courses.index')
                    ], 422);
                }
                
                return redirect()->route('courses.index')->with('error', 'No questions available from the selected courses.');
            }

            // ENHANCED: Validate minimum questions requirement
            if ($randomQuestions->count() < min(5, $questionLimit)) {
                Log::warning('Insufficient questions found', [
                    'request_id' => $requestId,
                    'user_id' => $user->id,
                    'found_questions' => $randomQuestions->count(),
                    'requested_questions' => $questionLimit
                ]);
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error' => "Only {$randomQuestions->count()} questions available. Please select more courses or reduce the question limit.",
                        'available_questions' => $randomQuestions->count()
                    ], 422);
                }
                
                return redirect()->route('courses.index')->with('error', "Only {$randomQuestions->count()} questions available. Please select more courses or reduce the question limit.");
            }

            // Store questions in session with better organization
            session([
                'random_quiz_questions' => $randomQuestions->pluck('id')->toArray(),
                'random_quiz_time_limit' => round($timeLimit * 60),
                'random_quiz_courses' => $selectedCourses,
                'random_quiz_started_at' => now(),
                'random_quiz_settings' => [
                    'question_limit' => $questionLimit,
                    'time_limit_minutes' => $timeLimit,
                    'selected_courses' => $selectedCourses
                ]
            ]);

            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::info('✅ Random quiz generated successfully', [
                'request_id' => $requestId,
                'execution_time_ms' => round($executionTime, 2),
                'questions_count' => $randomQuestions->count(),
                'selected_courses_count' => count($selectedCourses),
                'time_limit_seconds' => round($timeLimit * 60)
            ]);

            // ENHANCED: Return optimized view with better data structure
            return view('quiz.random_quiz', [
                'questions' => $randomQuestions,
                'totalQuestions' => $randomQuestions->count(),
                'selectedCourses' => Course::whereIn('id', $selectedCourses)->get(),
                'time_limit' => round($timeLimit * 60),
                'time_limit_minutes' => $timeLimit,
                'question_limit' => $questionLimit,
                'quiz_settings' => [
                    'question_limit' => $questionLimit,
                    'time_limit_minutes' => $timeLimit,
                    'selected_courses' => $selectedCourses,
                    'started_at' => now()->toISOString()
                ],
                'performance_metrics' => [
                    'execution_time_ms' => round($executionTime, 2),
                    'questions_generated' => $randomQuestions->count(),
                    'courses_selected' => count($selectedCourses)
                ]
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
            
            // ENHANCED: Better error handling for validation
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid quiz settings provided.',
                    'validation_errors' => $e->errors()
                ], 422);
            }
            
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

            // ENHANCED: Better error handling for exceptions
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'An error occurred while generating the quiz. Please try again.',
                    'debug' => app()->environment('local') ? $e->getMessage() : null
                ], 500);
            }

            return redirect()->route('courses.index')->with('error', 'An error occurred while generating the quiz. Please try again.');
        }
    }

    public function gradeRandomQuiz(Request $request)
    {
        $startTime = microtime(true);
        $requestId = uniqid('random_quiz_grade_');
        $userId = auth()->id();

        try {
            Log::info('🎯 Random Quiz Grading Started', [
                'request_id' => $requestId,
                'user_id' => $userId,
                'ip_address' => $request->ip(),
                'request_data' => $request->all()
            ]);

            // GOLDEN STANDARD: Simple and robust validation
            $validated = $request->validate([
                'answers' => 'required|array|max:100',
                'answers.*' => 'nullable|string|max:2000',
                'time_taken' => 'required|integer|min:0|max:86400'
            ]);

            $user = Auth::user();
            $answers = $validated['answers'] ?? [];
            $timeTaken = $validated['time_taken'] ?? 0;
            
            // GOLDEN STANDARD: Get questions from session with validation
            $questionIds = session('random_quiz_questions');
            if (!$questionIds || empty($questionIds)) {
                Log::warning('❌ Random quiz session expired or missing', [
                    'request_id' => $requestId,
                    'user_id' => $userId,
                    'session_questions' => $questionIds
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Quiz session expired or no questions found. Please start a new random quiz.'
                ], 422);
            }

            // GOLDEN STANDARD: Get questions with proper validation
            $questions = Question::whereIn('id', $questionIds)
                ->where('deleted', false)
                ->get();

            if ($questions->count() !== count($questionIds)) {
                Log::error('❌ Questions mismatch', [
                    'request_id' => $requestId,
                    'expected_count' => count($questionIds),
                    'actual_count' => $questions->count()
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Some questions are no longer available. Please start a new quiz.'
                ], 422);
            }

            $score = 0;
            $totalQuestions = $questions->count();
            $incorrectAnswers = [];
            $correctAnswersMap = [];
            $questionData = [];

            // GOLDEN STANDARD: Process each question with robust validation
            foreach ($questions as $question) {
                $submittedAnswer = $answers[$question->id] ?? null;
                
                // Store the expected correct answer
                $correctAnswersMap[$question->id] = $question->correct_answer;
                
                // GOLDEN STANDARD: Handle all question types robustly
                if ($question->question_type === 'written') {
                    // Written question handling
                    if (is_null($submittedAnswer) || trim((string)$submittedAnswer) === '') {
                        $submittedAnswer = 'unanswered';
                        $isCorrect = false;
                    } else {
                        $isCorrect = $question->isAnswerCorrect($submittedAnswer);
                    }
                } else {
                    // Multiple choice question handling
                    if (is_null($submittedAnswer) || $submittedAnswer === '') {
                        $submittedAnswer = 'unanswered';
                        $isCorrect = false;
                    } else if (!in_array($submittedAnswer, ['A', 'B', 'C', 'D'])) {
                        Log::warning('⚠️ Invalid answer format', [
                            'request_id' => $requestId,
                            'question_id' => $question->id,
                            'submitted_answer' => $submittedAnswer
                        ]);
                        $submittedAnswer = 'invalid';
                        $isCorrect = false;
                    } else {
                        $isCorrect = $question->isAnswerCorrect($submittedAnswer);
                    }
                }

                // Track question data for quiz attempt
                $questionData[] = [
                    'question_id' => $question->id,
                    'user_answer' => $submittedAnswer,
                    'is_correct' => $isCorrect,
                    'question_type' => $question->question_type
                ];

                // GOLDEN STANDARD: Always record mistakes for incorrect/unanswered
                if (!$isCorrect) {
                    $incorrectAnswers[$question->id] = [
                        'submitted_answer' => $submittedAnswer,
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

            // GOLDEN STANDARD: Use Laravel transaction for database operations
            DB::beginTransaction();
            
            try {
                // GOLDEN STANDARD: Create quiz attempt with proper source for random quiz
                $quizAttempt = QuizAttempt::create([
                    'user_id' => $user->id,
                    'topic_id' => null, // Random quiz doesn't have a specific topic
                    'quiz_type' => 'random',
                    'study_mode' => null, // Random quiz doesn't have a study mode
                    'source' => 'random', // Set source as 'random' for random quizzes
                    'duration_seconds' => $timeTaken,
                    'percentage_grade' => round($percentageGrade, 2),
                    'score' => $score,
                    'total_questions' => $totalQuestions,
                    'question_data' => $questionData
                ]);
                
                Log::info('✅ Random quiz attempt created successfully', [
                    'request_id' => $requestId,
                    'user_id' => $user->id,
                    'quiz_attempt_id' => $quizAttempt->id,
                    'score' => $score,
                    'total_questions' => $totalQuestions,
                    'percentage' => round($percentageGrade, 2),
                    'source' => 'random'
                ]);
                
                // GOLDEN STANDARD: Update user stats after quiz attempt
                try {
                    $this->userStatsService->updateAfterQuizAttempt($user, $totalQuestions, $score, $timeTaken);
                } catch (\Exception $e) {
                    Log::warning('⚠️ Failed to update user stats', [
                        'request_id' => $requestId,
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                    // Continue - don't fail the entire grading process for stats update
                }
                
                // GOLDEN STANDARD: Create mistake records with robust error handling
                $mistakesProcessed = 0;
                $mistakesFailed = 0;
                
                foreach ($incorrectAnswers as $questionId => $mistakeData) {
                    try {
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
                            $mistakesProcessed++;
                        } else {
                            // Create new mistake
                            Mistake::create([
                                'user_id' => $user->id,
                                'question_id' => $questionId,
                                'last_attempt_date' => now(),
                                'incorrect_attempts' => 1,
                                'last_incorrect_answer' => $mistakeData['submitted_answer']
                            ]);
                            $mistakesProcessed++;
                        }
                    } catch (\Exception $e) {
                        $mistakesFailed++;
                        Log::error('❌ Failed to process mistake record', [
                            'request_id' => $requestId,
                            'user_id' => $user->id,
                            'question_id' => $questionId,
                            'error' => $e->getMessage()
                        ]);
                        // Continue processing other mistakes - don't fail the entire grading
                    }
                }
                
                Log::info('📊 Mistake processing completed', [
                    'request_id' => $requestId,
                    'user_id' => $user->id,
                    'mistakes_processed' => $mistakesProcessed,
                    'mistakes_failed' => $mistakesFailed,
                    'total_incorrect' => count($incorrectAnswers)
                ]);

                // Handle milestones
                $this->handleMilestones($user, $score);

                // Commit the transaction
                DB::commit();
                
                // Clear session data after successful grading
                session()->forget(['random_quiz_questions', 'random_quiz_time_limit']);

                // GOLDEN STANDARD: Return comprehensive success response
                $responseData = [
                    'success' => true,
                    'score' => $score,
                    'total' => $totalQuestions,
                    'total_questions' => $totalQuestions,
                    'percentage_grade' => round($percentageGrade, 2),
                    'incorrect_answers' => $incorrectAnswers,
                    'correct_answers' => $correctAnswersMap,
                    'message' => $this->getScoreMessage($percentageGrade),
                    'quiz_attempt_id' => $quizAttempt->id,
                    'mistakes_recorded' => $mistakesProcessed,
                    'time_taken' => $timeTaken,
                    'quiz_type' => 'random',
                    'source' => 'random'
                ];
                
                $executionTime = (microtime(true) - $startTime) * 1000;
                Log::info('🎉 Random quiz grading completed successfully', [
                    'request_id' => $requestId,
                    'user_id' => $user->id,
                    'quiz_attempt_id' => $quizAttempt->id,
                    'final_score' => "{$score}/{$totalQuestions}",
                    'percentage' => round($percentageGrade, 2),
                    'execution_time_ms' => round($executionTime, 2),
                    'source' => 'random'
                ]);
                
                return response()->json($responseData);
                
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::warning('❌ Random quiz grading failed - validation error', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'validation_errors' => $e->errors(),
                'user_id' => $userId,
                'execution_time_ms' => round($executionTime, 2)
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Invalid quiz data provided. Please check your answers and try again.',
                'validation_errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::error('💥 Critical error in random quiz grading', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'execution_time_ms' => round($executionTime, 2),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId,
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while grading the quiz. Please try again.',
                'debug' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
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