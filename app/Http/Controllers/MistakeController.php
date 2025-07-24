<?php

namespace App\Http\Controllers;

use App\Models\Mistake;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MistakeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show mistakes for the logged-in user.
     */
    public function index(Request $request)
    {
        $userId = auth()->id();
        $courseFilter = $request->input('course');

        Log::info('📌 Fetching mistakes for user', ['user_id' => $userId, 'course_filter' => $courseFilter]);

        // Get all courses for the filter dropdown
        $courses = DB::table('courses')
            ->join('topics', 'courses.id', '=', 'topics.course_id')
            ->join('questions', 'topics.id', '=', 'questions.topic_id')
            ->join('mistakes', 'questions.id', '=', 'mistakes.question_id')
            ->where('mistakes.user_id', $userId)
            ->where('mistakes.mastered', false)
            ->select('courses.id', 'courses.name')
            ->distinct()
            ->get();

        // Fetch all mistakes along with the related questions that are not mastered
        $mistakesQuery = Mistake::where('user_id', $userId)
            ->where('mastered', false)  // Only show non-mastered mistakes
            ->select('*')  // Explicitly select all fields including 'skipped'
            ->with(['question' => function($query) {
                $query->select('id', 'question', 'topic_id', 'image_url', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer', 'explanation');
                $query->with(['topic' => function($query) {
                    $query->select('id', 'name', 'course_id');
                    $query->with(['course' => function($query) {
                        $query->select('id', 'name');
                    }]);
                }]);
            }]);
        
        // Apply course filter if specified
        if ($courseFilter) {
            if ($courseFilter === 'skipped') {
                // Filter for skipped questions only
                $mistakesQuery->where('skipped', true);
            } else {
                // Filter by course ID
                $mistakesQuery->whereHas('question.topic.course', function($query) use ($courseFilter) {
                    $query->where('id', $courseFilter);
                });
            }
        }

        $mistakes = $mistakesQuery->orderBy('last_attempt_date', 'desc')->get();

        // Ensure all skipped values are properly set
        foreach ($mistakes as $mistake) {
            // Force skipped to be a boolean if it's null or not a boolean
            if ($mistake->skipped === null) {
                $mistake->skipped = false;
                $mistake->save();
                
                Log::info('Fixed null skipped value', [
                    'question_id' => $mistake->question_id,
                    'new_value' => false
                ]);
            }
            
            Log::info('Mistake skipped value', [
                'question_id' => $mistake->question_id,
                'skipped' => var_export($mistake->skipped, true),
                'type' => gettype($mistake->skipped)
            ]);
        }

        Log::info('✅ Mistakes fetched:', [
            'count' => $mistakes->count(),
            'courses_count' => $courses->count(),
            'skipped_mistakes' => $mistakes->where('skipped', true)->count(),
            'sample_mistake_data' => $mistakes->first() ? [
                'id' => $mistakes->first()->id,
                'question_id' => $mistakes->first()->question_id,
                'skipped' => $mistakes->first()->skipped,
                'skipped_type' => gettype($mistakes->first()->skipped)
            ] : null
        ]);

        return view('mistakes', compact('mistakes', 'courses'))
            ->with('selectedCourse', $courseFilter);
    }

    /**
     * Store mistake if answer is incorrect.
     */
    public function store(Request $request)
    {
        $userId = auth()->id();
        $questionId = $request->input('question_id');
        $submittedAnswer = $request->input('submitted_answer');
        
        // Find the question by its ID
        $question = Question::findOrFail($questionId);
        Log::info('Attempting to store mistake for question_id', ['question_id' => $questionId]);
        Log::info('📌 Checking answer for mistake tracking', [
            'user_id' => $userId,
            'question_id' => $questionId,
            'submitted' => $submittedAnswer,
            'correct' => $question->correct_answer,
        ]);

        // If the answer is correct, do nothing
        if ($submittedAnswer === $question->correct_answer) {
            Log::info('✅ Correct answer, no mistake recorded.');
            return response()->json(['message' => 'Correct answer, no mistake recorded.']);
        }

        // If the answer is incorrect, store the mistake
        DB::beginTransaction();
        try {
            $mistake = Mistake::where('user_id', $userId)
                  ->where('question_id', $questionId)
                  ->first();

            if ($mistake) {
                // Update existing mistake
                $mistake->submitted_answer = $submittedAnswer;
                $mistake->last_attempt_date = now();
                $mistake->save();
            } else {
                // Create a new mistake entry
                $mistake = Mistake::create([
                    'user_id' => $userId,
                    'question_id', $questionId,
                    'submitted_answer' => $submittedAnswer,
                    'last_attempt_date' => now(),
                ]);

                // Initialize Anki parameters for new mistake
                $mistake->initializeAnki();
                $mistake->save();
            }

            DB::commit();
            Log::info('✅ Mistake recorded', ['question_id' => $questionId]);

            return response()->json(['message' => 'Mistake recorded.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error storing mistake', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to record mistake.'], 500);
        }
    }

    /**
     * Remove mistake from the list.
     */
    public function destroy($questionId)
    {
        $userId = auth()->id();

        Log::info('📌 Deleting mistake', [
            'user_id' => $userId,
            'question_id' => $questionId
        ]);

        // Delete the mistake record for the given user and question
        $deleted = Mistake::where('user_id', $userId)
            ->where('question_id', $questionId)
            ->delete();

        if ($deleted) {
            Log::info('✅ Mistake deleted');
        } else {
            Log::warning('⚠️ No mistake found to delete');
        }

        return redirect()->route('mistakes.index')->with('success', 'Mistake removed.');
    }

    /**
     * Remove mistake via AJAX request.
     */
    public function removeMistake($question_id)
    {
        $userId = auth()->id();

        Log::info("📌 Removing mistake", [
            'user_id' => $userId,
            'question_id' => $question_id
        ]);

        // Delete the mistake record for the given user and question
        $deleted = Mistake::where('user_id', $userId)
                          ->where('question_id', $question_id)
                          ->delete();

        if ($deleted) {
            Log::info("✅ Mistake successfully removed", [
                'user_id' => $userId,
                'question_id' => $question_id
            ]);
            return response()->json(['success' => true]);
        } else {
            Log::warning("⚠️ No mistake found or already removed", [
                'user_id' => $userId,
                'question_id' => $question_id
            ]);
            return response()->json([
                'success' => false,
                'message' => "No mistake found or already removed."
            ]);
        }
    }

    /**
     * Skip a mistake question.
     */
    public function skipMistake($question_id)
    {
        $startTime = microtime(true);
        $requestId = uniqid('skip_mistake_');
        $userId = auth()->id();

        Log::info("📌 Skipping mistake", [
            'request_id' => $requestId,
            'user_id' => $userId,
            'question_id' => $question_id,
            'ip_address' => request()->ip()
        ]);

        try {
            DB::beginTransaction();

            // Find the mistake record for the given user and question
            $mistake = Mistake::where('user_id', $userId)
                              ->where('question_id', $question_id)
                              ->first();

            if ($mistake) {
                // Mark the mistake as skipped and update last attempt date
                $mistake->skipped = true;
                $mistake->last_attempt_date = now();
                $mistake->save();

                DB::commit();

                $executionTime = (microtime(true) - $startTime) * 1000;
                Log::info("✅ Mistake successfully skipped", [
                    'request_id' => $requestId,
                    'user_id' => $userId,
                    'question_id' => $question_id,
                    'execution_time_ms' => round($executionTime, 2)
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Question skipped successfully'
                ]);
            } else {
                DB::rollBack();
                
                $executionTime = (microtime(true) - $startTime) * 1000;
                Log::warning("⚠️ No mistake found to skip", [
                    'request_id' => $requestId,
                    'user_id' => $userId,
                    'question_id' => $question_id,
                    'execution_time_ms' => round($executionTime, 2)
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => "No mistake found to skip."
                ], 404);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::error('❌ Error skipping mistake', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => $userId,
                'question_id' => $question_id,
                'execution_time_ms' => round($executionTime, 2),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to skip mistake'
            ], 500);
        }
    }

    /**
     * Handle Anki rating for a mistake.
     */
    public function rateMistake(Request $request, $questionId)
    {
        $userId = auth()->id();
        $rating = $request->input('rating');

        Log::info('📌 Rating mistake', [
            'user_id' => $userId,
            'question_id' => $questionId,
            'rating' => $rating
        ]);

        try {
            $mistake = Mistake::where('user_id', $userId)
                ->where('question_id', $questionId)
                ->firstOrFail();

            // Update Anki parameters based on rating
            $mistake->updateAnki($rating);
            $mistake->save();

            Log::info('✅ Mistake rated successfully', [
                'user_id' => $userId,
                'question_id' => $questionId,
                'rating' => $rating,
                'next_review_date' => $mistake->next_review_date
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mistake rated successfully',
                'next_review_date' => $mistake->next_review_date
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error rating mistake', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'question_id' => $questionId
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to rate mistake'
            ], 500);
        }
    }
}