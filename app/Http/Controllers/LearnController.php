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

class LearnController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the mistakes that need to be reviewed.
     */
    public function learnMistakes()
    {
        try {
            $userId = auth()->id();
            $mode = session('learn_mode', 'quiz');
            Log::info('Starting learnMistakes for user', ['user_id' => $userId, 'mode' => $mode]);

            // Get all mistakes for the user (including mastered ones)
            $mistakes = Mistake::where('user_id', $userId)
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
                ->orderBy('correct_streak', 'desc')  // Show ones closer to mastery first
                ->get();

            // Get unique courses from the mistakes
            $courses = $mistakes->map(function($mistake) {
                return $mistake->question->topic->course ?? null;
            })->filter()->unique('id')->values();

            Log::info('Fetched mistakes for user', [
                'user_id' => $userId,
                'mistake_count' => $mistakes->count(),
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
                return redirect()->route('review.index')
                    ->with('info', 'No mistakes to review! Add some mistakes first.');
            }

            // If mistakes exist but no valid questions found
            if ($mistakes->every(function($mistake) { return $mistake->question === null; })) {
                Log::error('No valid questions found for mistakes', [
                    'user_id' => $userId,
                    'mistake_ids' => $mistakes->pluck('id')
                ]);
                return redirect()->route('review.index')
                    ->with('error', 'Error loading questions. Please try again.');
            }

            return view('mistakes.learn', compact('mistakes', 'courses'));
        } catch (\Exception $e) {
            Log::error('Error in learnMistakes', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
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
        $userId = auth()->id();

        $mistakes = Mistake::where('user_id', $userId)
            ->where('correct_streak', '<', 3) // Changed from mastered to correct_streak
            ->with('question')
            ->get();

        return response()->json([
            'success' => true,
            'mistakes_count' => $mistakes->count(),
            'has_mistakes' => $mistakes->isNotEmpty()
        ]);
    }

    /**
     * Handle the review (rating) for a mistake.
     */
    public function reviewMistake(Request $request, $questionId)
    {
        try {
            $userId = auth()->id();
            $mode = $request->input('mode', 'quiz');
            
            Log::info('📌 Starting review process', [
                'question_id' => $questionId,
                'user_id' => $userId,
                'selected_answer' => $request->input('selected_answer'),
                'mode' => $mode
            ]);
            
            DB::beginTransaction();

            // Find the mistake by user_id and question_id
            $mistake = Mistake::where('user_id', $userId)
                ->where('question_id', $questionId)
                ->first();

            if (!$mistake) {
                Log::error('❌ Mistake not found', [
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
                // Manual override for very short answers - single letter answers should never be correct for written questions
                if (strlen(trim($selectedAnswer)) <= 1) {
                    $isCorrect = false;
                    Log::warning('Single letter answer rejected forcefully', [
                        'question_id' => $questionId,
                        'submitted' => $selectedAnswer,
                        'correct' => $mistake->question->correct_answer
                    ]);
                } else {
                    // For written questions, use the proper grading method
                    $isCorrect = $mistake->question->isAnswerCorrect($selectedAnswer);
                }
                
                // Double-check: Even if somehow the grading marked a very short answer as correct,
                // we'll override it here for safety since the UI showed it was a problem
                if ($isCorrect && strlen(trim($selectedAnswer)) <= 2 && strlen($mistake->question->correct_answer) > 4) {
                    $isCorrect = false;
                    Log::warning('Very short answer incorrectly marked as correct - overriding', [
                        'question_id' => $questionId,
                        'submitted' => $selectedAnswer,
                        'correct' => $mistake->question->correct_answer
                    ]);
                }
                
                // Log additional info for written answers
                Log::info('Written answer grading result', [
                    'question_id' => $questionId,
                    'submitted' => $selectedAnswer,
                    'correct' => $mistake->question->correct_answer,
                    'is_correct' => $isCorrect,
                    'submitted_length' => strlen($selectedAnswer),
                    'correct_length' => strlen($mistake->question->correct_answer)
                ]);
            } else {
                // For multiple choice questions, exact match is fine
                $isCorrect = $selectedAnswer === $mistake->question->correct_answer;
            }
            
            $mistake->updateTracking($isCorrect, $mode);

            Log::info('✅ Review processed', [
                'mistake_id' => $mistake->id,
                'is_correct' => $isCorrect,
                'new_streak' => $mistake->correct_streak,
                'is_mastered' => $mistake->isMastered($mode)
            ]);

            DB::commit();

            // Prepare the response message
            $message = '';
            if ($isCorrect) {
                $message = 'Correct!';
            } else {
                // For written questions, provide more detailed feedback
                if (isset($mistake->question->question_type) && $mistake->question->question_type === 'written') {
                    // If answer is very short, provide specific feedback
                    if (strlen($selectedAnswer) <= 1 && strlen($mistake->question->correct_answer) > 3) {
                        $message = 'Incorrect. Your answer is too short. Please provide a complete answer.';
                    } 
                    // If answer length is significantly different, mention it
                    elseif (strlen($selectedAnswer) < (strlen($mistake->question->correct_answer) * 0.5)) {
                        $message = 'Incorrect. Your answer seems incomplete. Try to be more specific.';
                    }
                    else {
                        $message = 'Incorrect. Try again!';
                    }
                } else {
                    $message = 'Incorrect. Try again!';
                }
            }

            return response()->json([
                'success' => true,
                'mastered' => $mistake->isMastered($mode),
                'progress' => $mistake->correct_streak,
                'message' => $message,
                'is_correct' => $isCorrect,
                'correct_answer' => !$isCorrect ? $mistake->question->correct_answer : null
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error in reviewMistake', [
                'question_id' => $questionId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your review: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start a review session for mistakes
     */
    public function startReview(Request $request)
    {
        try {
            $mode = $request->query('mode', 'quiz');
            $mistakes = Mistake::where('user_id', auth()->id())
                ->where('correct_streak', '<', 3) // Changed from mastered to correct_streak
                ->join('questions', 'mistakes.question_id', '=', 'questions.id')
                ->select(
                    'mistakes.id as mistake_id',
                    'mistakes.submitted_answer',
                    'questions.id as question_id',
                    'questions.question',
                    'questions.image_url',
                    'questions.option_a',
                    'questions.option_b',
                    'questions.option_c',
                    'questions.option_d',
                    'questions.correct_answer'
                )
                ->inRandomOrder()
                ->take(10)
                ->get();

            if ($mistakes->isEmpty()) {
                return redirect()->route('review.index')
                    ->with('info', 'No mistakes found to review!');
            }

            $questions = $mistakes->map(function ($mistake) {
                return [
                    'id' => $mistake->mistake_id,
                    'question' => $mistake->question,
                    'image_url' => $mistake->image_url,
                    'options' => [
                        'A' => $mistake->option_a,
                        'B' => $mistake->option_b,
                        'C' => $mistake->option_c,
                        'D' => $mistake->option_d
                    ],
                    'correct_answer' => $mistake->correct_answer,
                    'your_answer' => $mistake->submitted_answer,
                    'progress' => $mistake->quick_correct_count // For quick mode progress tracking
                ];
            });

            session(['review_questions' => $questions, 'review_mode' => $mode]);
            return view('mistakes.review', compact('questions', 'mode'));
        } catch (\Exception $e) {
            Log::error('Error in startReview', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('review.index')
                ->with('error', 'An error occurred while starting the review.');
        }
    }

    public function removeMistake($questionId)
    {
        try {
            $userId = auth()->id();
            $mistake = Mistake::where('user_id', $userId)
                            ->where('question_id', $questionId)
                            ->first();

            if (!$mistake) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mistake not found'
                ], 404);
            }

            $mistake->delete();

            return response()->json([
                'success' => true,
                'message' => 'Question removed from mistakes list'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error removing mistake: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error removing question'
            ], 500);
        }
    }

    /**
     * Save the study time from a mistake learning session
     */
    public function saveStudyTime(Request $request)
    {
        try {
            $request->validate([
                'duration_seconds' => 'required|integer|min:1',
                'total_questions' => 'required|integer|min:1',
                'completed_questions' => 'required|integer|min:0'
            ]);

            $user = auth()->user();
            
            // Get the first mistake's topic_id to associate with this attempt
            $firstMistake = Mistake::where('user_id', $user->id)
                ->with('question:id,topic_id')
                ->first();
                
            // If no topic_id is found, use a default topic for mistake reviews
            $topicId = $firstMistake && $firstMistake->question 
                ? $firstMistake->question->topic_id 
                : Topic::firstOrCreate(
                    ['name' => 'Mistake Reviews'],
                    [
                        'description' => 'Auto-generated topic for tracking mistake reviews',
                        'user_id' => $user->id,
                        'course_id' => Course::firstOrCreate([
                            'name' => 'Review Sessions'
                        ], [
                            'description' => 'Auto-generated course for tracking review sessions',
                            'user_id' => $user->id
                        ])->id
                    ]
                )->id;
            
            // Create a quiz attempt record for the mistake review session
            QuizAttempt::create([
                'user_id' => $user->id,
                'topic_id' => $topicId,
                'quiz_type' => 'mistake_review',
                'study_mode' => 'review',
                'source' => 'mistakes',
                'score' => $request->completed_questions,
                'total_questions' => $request->total_questions,
                'percentage_grade' => ($request->completed_questions / $request->total_questions) * 100,
                'duration_seconds' => $request->duration_seconds,
                'missed_questions' => [], // No need to track missed questions in review mode
                'question_data' => [] // No need to track individual question data in review mode
            ]);

            // Update user's total study time
            $user->increment('study_time_seconds', $request->duration_seconds);

            // Update study streak
            $user->updateStudyStreak();

            return response()->json([
                'success' => true,
                'message' => 'Study time saved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving study time', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save study time: ' . $e->getMessage()
            ], 500);
        }
    }
}