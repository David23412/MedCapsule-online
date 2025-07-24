<?php

namespace App\Http\Controllers;

use App\Models\Mistake;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\QuizAttempt;
use App\Models\Topic;
use App\Models\Course;
use App\Services\UserStatsService;

class LearnController extends Controller
{
    protected $userStatsService;

    public function __construct(UserStatsService $userStatsService)
    {
        $this->middleware('auth');
        $this->userStatsService = $userStatsService;
    }

    /**
     * Show the mistakes that need to be reviewed.
     */
    public function learnMistakes(Request $request)
    {
        $startTime = microtime(true);
        $requestId = uniqid('learn_mistakes_');
        
        try {
            $userId = auth()->id();
            $mode = session('learn_mode', 'quiz');
            $courseFilter = $request->input('course');
            
            Log::info('Starting learnMistakes for user', [
                'request_id' => $requestId,
                'user_id' => $userId, 
                'mode' => $mode,
                'course_filter' => $courseFilter,
                'ip_address' => request()->ip()
            ]);

            // Get all mistakes for the user (including mastered ones)
            $mistakes = Mistake::where('user_id', $userId)
                ->select([
                    'mistakes.id',
                    'mistakes.question_id',
                    'mistakes.submitted_answer',
                    'mistakes.last_attempt_date',
                    'mistakes.correct_streak',
                    'mistakes.skipped'
                ])
                ->with(['question' => function($query) {  // Eager load question with specific fields
                    $query->select(
                        'id',
                        'question',
                        'image_url',
                        'correct_answer',
                        'option_a',
                        'option_b',
                        'option_c',
                        'option_d',
                        'topic_id',
                        'explanation'
                    );
                }])
                ->with('question.topic:id,name,course_id')  // Eager load topic with course_id
                ->with('question.topic.course:id,name')     // Eager load course
                ->when($courseFilter, function($query) use ($courseFilter) {
                    if ($courseFilter === 'skipped') {
                        return $query->where('mistakes.skipped', true);
                    } else {
                        return $query->whereHas('question.topic.course', function($q) use ($courseFilter) {
                            $q->where('id', $courseFilter);
                        });
                    }
                })
                ->orderBy('correct_streak', 'desc')  // Show ones closer to mastery first
                ->get();

            // Get unique courses from the mistakes
            $courses = $mistakes->map(function($mistake) {
                return $mistake->question->topic->course ?? null;
            })->filter()->unique('id')->values();

            Log::info('Fetched mistakes for user', [
                'request_id' => $requestId,
                'user_id' => $userId,
                'mistake_count' => $mistakes->count(),
                'courses_count' => $courses->count(),
                'mistakes' => $mistakes->map(function($mistake) use ($mode) {
                    return [
                        'id' => $mistake->id,
                        'question_id' => $mistake->question_id,
                        'has_question' => $mistake->question !== null,
                        'correct_streak' => $mistake->correct_streak,
                        'is_mastered' => $mistake->isMastered($mode)
                    ];
                })
            ]);

            // If no mistakes found, redirect with a message
            if ($mistakes->isEmpty()) {
                Log::info('No mistakes found for user', [
                    'request_id' => $requestId,
                    'user_id' => $userId
                ]);
                return redirect()->route('review.index')
                    ->with('info', 'No mistakes to review! Add some mistakes first.');
            }

            // If mistakes exist but no valid questions found
            if ($mistakes->every(function($mistake) { return $mistake->question === null; })) {
                Log::error('No valid questions found for mistakes', [
                    'request_id' => $requestId,
                    'user_id' => $userId,
                    'mistake_ids' => $mistakes->pluck('id')
                ]);
                return redirect()->route('review.index')
                    ->with('error', 'Error loading questions. Please try again.');
            }

            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::info('✅ Learn mistakes loaded successfully', [
                'request_id' => $requestId,
                'execution_time_ms' => round($executionTime, 2),
                'mistakes_count' => $mistakes->count(),
                'courses_count' => $courses->count()
            ]);

            return view('mistakes.learn', compact('mistakes', 'courses', 'courseFilter'));
        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::error('❌ Error in learnMistakes', [
                'request_id' => $requestId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'execution_time_ms' => round($executionTime, 2),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('review.index')
                ->with('error', 'An error occurred while loading the learn page.');
        }
    }

    /**
     * Get updated list of mistakes after a review
     */
    public function getUpdatedMistakes(Request $request)
    {
        $startTime = microtime(true);
        $requestId = uniqid('get_mistakes_');
        
        try {
        $userId = auth()->id();

            Log::info('Getting updated mistakes for user', [
                'request_id' => $requestId,
                'user_id' => $userId,
                'ip_address' => request()->ip()
            ]);

        $mistakes = Mistake::where('user_id', $userId)
            ->where('correct_streak', '<', 3) // Changed from mastered to correct_streak
            ->with('question')
            ->get();

            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::info('✅ Updated mistakes retrieved successfully', [
                'request_id' => $requestId,
                'execution_time_ms' => round($executionTime, 2),
                'mistakes_count' => $mistakes->count()
            ]);

        return response()->json([
            'success' => true,
            'mistakes_count' => $mistakes->count(),
            'has_mistakes' => $mistakes->isNotEmpty()
        ]);
        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::error('❌ Error getting updated mistakes', [
                'request_id' => $requestId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'execution_time_ms' => round($executionTime, 2)
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve mistakes'
            ], 500);
        }
    }

    /**
     * Handle the review (rating) for a mistake.
     */
    public function reviewMistake(Request $request, $questionId)
    {
        $startTime = microtime(true);
        $requestId = uniqid('review_mistake_');
        
        try {
            $userId = auth()->id();
            $mode = $request->input('mode', 'quiz');
            
            Log::info('📌 Starting review process', [
                'request_id' => $requestId,
                'question_id' => $questionId,
                'user_id' => $userId,
                'selected_answer' => $request->input('selected_answer'),
                'mode' => $mode,
                'ip_address' => request()->ip()
            ]);
            
            DB::beginTransaction();

            // Find the mistake by user_id and question_id
            $mistake = Mistake::where('user_id', $userId)
                ->where('question_id', $questionId)
                ->first();

            if (!$mistake) {
                Log::error('❌ Mistake not found', [
                    'request_id' => $requestId,
                    'question_id' => $questionId,
                    'user_id' => $userId
                ]);
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Mistake not found'
                ], 404);
            }

            $selectedAnswer = $request->input('selected_answer');

            if (!$selectedAnswer) {
                Log::error('❌ Missing required data', [
                    'request_id' => $requestId,
                    'selected_answer' => $selectedAnswer,
                    'mode' => $mode
                ]);
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required data: selected_answer is required'
                ], 400);
            }

            Log::info('📝 Processing review', [
                'request_id' => $requestId,
                'mistake_id' => $mistake->id,
                'question_id' => $questionId,
                'selected_answer' => $selectedAnswer,
                'correct_answer' => $mistake->question->correct_answer,
                'question_type' => $mistake->question->question_type ?? 'unknown',
                'mode' => $mode,
                'current_streak' => $mistake->correct_streak,
                'submitted_length' => strlen($selectedAnswer),
                'expected_length' => strlen($mistake->question->correct_answer)
            ]);

            // Use the Question model's isAnswerCorrect method to properly handle different question types
            $isCorrect = false;
            
            if (isset($mistake->question->question_type) && $mistake->question->question_type === 'written') {
                // For written questions, use the proper grading method
                $isCorrect = $mistake->question->isAnswerCorrect($selectedAnswer);
            } else {
                // For multiple choice questions, use direct comparison
                $isCorrect = strtolower(trim($selectedAnswer)) === strtolower(trim($mistake->question->correct_answer));
            }
            
            $mistake->updateTracking($isCorrect, $mode);

            Log::info('✅ Review processed', [
                'request_id' => $requestId,
                'mistake_id' => $mistake->id,
                'is_correct' => $isCorrect,
                'new_streak' => $mistake->correct_streak,
                'is_mastered' => $mistake->isMastered($mode)
            ]);

            DB::commit();

            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::info('✅ Review completed successfully', [
                'request_id' => $requestId,
                'execution_time_ms' => round($executionTime, 2),
                'is_correct' => $isCorrect,
                'new_streak' => $mistake->correct_streak
            ]);

            return response()->json([
                'success' => true,
                'is_correct' => $isCorrect,
                'correct_answer' => $mistake->question->correct_answer,
                'explanation' => $mistake->question->explanation,
                'new_streak' => $mistake->correct_streak,
                'is_mastered' => $mistake->isMastered($mode),
                'message' => $isCorrect ? 'Correct!' : 'Incorrect. Try again!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::error('❌ Error in reviewMistake:', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'question_id' => $questionId,
                'user_id' => auth()->id(),
                'execution_time_ms' => round($executionTime, 2),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your review.'
            ], 500);
        }
    }

    /**
     * Start a review session
     */
    public function startReview(Request $request)
    {
        $startTime = microtime(true);
        $requestId = uniqid('start_review_');
        
        try {
            $userId = auth()->id();
            $mode = $request->input('mode', 'quiz');
            
            Log::info('Starting review session', [
                'request_id' => $requestId,
                'user_id' => $userId,
                'mode' => $mode,
                'ip_address' => request()->ip()
            ]);

            // Store the mode in session
            session(['learn_mode' => $mode]);

            // Get mistakes that need review
            $mistakes = Mistake::where('user_id', $userId)
                ->where('correct_streak', '<', 3)
                ->with('question.topic.course')
                ->orderBy('last_attempt_date', 'asc')
                ->limit(10)
                ->get();

            if ($mistakes->isEmpty()) {
                Log::info('No mistakes available for review', [
                    'request_id' => $requestId,
                    'user_id' => $userId
                ]);
                return redirect()->route('review.index')
                    ->with('info', 'No mistakes available for review!');
            }

            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::info('✅ Review session started successfully', [
                'request_id' => $requestId,
                'execution_time_ms' => round($executionTime, 2),
                'mistakes_count' => $mistakes->count(),
                'mode' => $mode
            ]);

            return view('mistakes.review', compact('mistakes', 'mode'));

        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::error('❌ Error starting review session:', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id(),
                'execution_time_ms' => round($executionTime, 2),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('review.index')
                ->with('error', 'An error occurred while starting the review session.');
        }
    }

    /**
     * Remove a mistake from the user's list
     */
    public function removeMistake($questionId)
    {
        $startTime = microtime(true);
        $requestId = uniqid('remove_mistake_');
        
        try {
            $userId = auth()->id();
            
            Log::info('Removing mistake', [
                'request_id' => $requestId,
                'question_id' => $questionId,
                'user_id' => $userId,
                'ip_address' => request()->ip()
            ]);

            $mistake = Mistake::where('user_id', $userId)
                            ->where('question_id', $questionId)
                            ->first();

            if (!$mistake) {
                Log::warning('Mistake not found for removal', [
                    'request_id' => $requestId,
                    'question_id' => $questionId,
                    'user_id' => $userId
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Mistake not found'
                ], 404);
            }

            $mistake->delete();

            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::info('✅ Mistake removed successfully', [
                'request_id' => $requestId,
                'execution_time_ms' => round($executionTime, 2),
                'question_id' => $questionId,
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mistake removed successfully'
            ]);

        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::error('❌ Error removing mistake:', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'question_id' => $questionId,
                'user_id' => auth()->id(),
                'execution_time_ms' => round($executionTime, 2),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while removing the mistake.'
            ], 500);
        }
    }

    /**
     * Save study time for analytics
     */
    public function saveStudyTime(Request $request)
    {
        $startTime = microtime(true);
        $requestId = uniqid('save_study_time_');
        
        try {
            $userId = auth()->id();
            $studyTime = $request->input('study_time', 0);
            $totalQuestions = $request->input('total_questions', 0);
            $completedQuestions = $request->input('completed_questions', 0);
            
            Log::info('Saving mistake review study time', [
                'request_id' => $requestId,
                'user_id' => $userId,
                'study_time_seconds' => $studyTime,
                'total_questions' => $totalQuestions,
                'completed_questions' => $completedQuestions,
                'ip_address' => request()->ip()
            ]);

            // Create a quiz attempt record for the mistake review session
            $quizAttempt = \App\Models\QuizAttempt::create([
                'user_id' => $userId,
                'topic_id' => null, // Mistake review doesn't have a specific topic
                'quiz_type' => 'mistake_review',
                'study_mode' => 'mistake_review',
                'source' => 'mistake_review',
                'score' => $completedQuestions, // Number of mistakes mastered
                'total_questions' => $totalQuestions,
                'percentage_grade' => $totalQuestions > 0 ? ($completedQuestions / $totalQuestions) * 100 : 0,
                'duration_seconds' => $studyTime,
                'question_data' => [
                    [
                        'question_id' => 1, // Placeholder for mistake review
                        'user_answer' => null,
                        'is_correct' => true // Placeholder for mistake review
                    ]
                ], // Minimal valid structure for mistake review
                'correct_answers' => $completedQuestions
            ]);

            // Also increment user's study_time_seconds for quick total lookup
            $user = auth()->user();
            $user->increment('study_time_seconds', $studyTime);

            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::info('✅ Mistake review study time saved successfully', [
                'request_id' => $requestId,
                'execution_time_ms' => round($executionTime, 2),
                'study_time_seconds' => $studyTime,
                'user_id' => $userId,
                'quiz_attempt_id' => $quizAttempt->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Study time recorded'
            ]);

        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::error('❌ Error saving mistake review study time:', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id(),
                'execution_time_ms' => round($executionTime, 2),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to record study time'
            ], 500);
        }
    }
}