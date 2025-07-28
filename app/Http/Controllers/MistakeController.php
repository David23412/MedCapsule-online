<?php

namespace App\Http\Controllers;

use App\Models\Mistake;
use App\Models\Question;
use Illuminate\Http\Request;
use App\Services\QuizCacheService;
use Illuminate\Http\JsonResponse;
use App\Services\QuizPerformanceService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class MistakeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * Show mistakes for the logged-in user.
     */
    public function index(Request $request)
    {
        $userId = auth()->id();
        $courseFilter = $request->input('course');

        Log::info('📌 Fetching mistakes for user', ['user_id' => $userId, 'course_filter' => $courseFilter]);

        // Ultra-aggressive caching for entire mistakes data
        $cacheKey = QuizCacheService::generateCacheKey('mistakes_full', [
            'user_id' => $userId, 
            'course_filter' => $courseFilter ?? 'all'
        ]);
        
        $data = Cache::remember($cacheKey, 900, function () use ($userId, $courseFilter) {
            // Cache courses for filter dropdown with extended TTL
            $courses = Cache::remember("mistake_courses_{$userId}", 1800, function () use ($userId) {
                return \App\Models\Course::select('courses.id', 'courses.name')
                    ->join('topics', 'courses.id', '=', 'topics.course_id')
                    ->join('questions', 'topics.id', '=', 'questions.topic_id')
                    ->join('mistakes', 'questions.id', '=', 'mistakes.question_id')
                    ->where('mistakes.user_id', $userId)
                    ->where('mistakes.mastered', false)
                    ->distinct()
                    ->get();
            });

            // Fetch all mistakes along with the related questions that are not mastered
            $mistakesQuery = Mistake::where('user_id', $userId)
                ->where('mastered', false)  // Only show non-mastered mistakes
            ->select('*')  // Explicitly select all fields including 'skipped'
            ->with(['question' => function($query) {
                $query->select('id', 'question', 'topic_id', 'image_url', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer', 'explanation')->with(['relatedModel']); // TODO: Add actual relationships
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

            return compact('mistakes', 'courses');
        });

        // Extract cached data
        extract($data);

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
     * Store mistake if answer is incorrect - COMPLEXITY REDUCED
     */
    public function store(Request $request)
    {
        $userId = auth()->id();
        $questionId = $request->input('question_id');
        $submittedAnswer = $request->input('submitted_answer');
        
        // COMPLEXITY REDUCTION: Extract answer validation
        if ($this->isCorrectAnswer($questionId, $submittedAnswer)) {
            Log::info('✅ Correct answer, no mistake recorded.');
            return response()->json(['message' => 'Correct answer, no mistake recorded.']);
        }

        // COMPLEXITY REDUCTION: Extract mistake processing
        return $this->processMistake($userId, $questionId, $submittedAnswer);
    }

    /**
     * COMPLEXITY REDUCTION: Check if answer is correct
     */
    private function isCorrectAnswer($questionId, $submittedAnswer): bool
    {
        $question = Question::findOrFail($questionId);
        
        Log::info('📌 Checking answer for mistake tracking', [
            'question_id' => $questionId,
            'submitted' => $submittedAnswer,
            'correct' => $question->correct_answer]);

        return $submittedAnswer === $question->correct_answer;
    }

    /**
     * COMPLEXITY REDUCTION: Process mistake creation/update
     */
    private function processMistake($userId, $questionId, $submittedAnswer)
    {
        DB::beginTransaction();
        try {
            $mistake = $this->findOrCreateMistake($userId, $questionId, $submittedAnswer);
            
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
     * COMPLEXITY REDUCTION: Find existing mistake or create new one
     */
    private function findOrCreateMistake($userId, $questionId, $submittedAnswer)
    {
        $mistake = Mistake::select('id', 'user_id', 'question_id', 'submitted_answer', 'correct_answer', 'mastered', 'skipped')
            ->with([
                'question:id,question,topic_id,difficulty',
                'question.topic:id,name,course_id',
                'question.topic.course:id,name'
            ])
            ->where('user_id', $userId)
            ->where('question_id', $questionId)
            ->first();

        if ($mistake) {
            // Update existing mistake
            $mistake->submitted_answer = $submittedAnswer;
            $mistake->last_attempt_date = now();
            $mistake->save();
        } else {
            // Get question details for mistake creation
            $question = Question::findOrFail($questionId);
            
            // Create a new mistake entry
            $mistake = Mistake::create([
                'user_id' => $userId,
                'question_id' => $questionId,
                'submitted_answer' => $submittedAnswer,
                'question_text' => $question->question,
                'correct_answer' => $question->correct_answer,
                'image_path' => $question->image_url,
                'last_attempt_date' => now()
            ]);

            // Mistake created successfully
        }

        return $mistake;
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
            // 💀 LETHAL CACHE INVALIDATION 💀
            $this->clearMistakeCaches($userId);
            Log::info('✅ Mistake deleted');
        } else {
            Log::warning('⚠️ No mistake found to delete');
        }

        return redirect()->route('review.index')->with('success', 'Mistake removed.');
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
            // 💀 LETHAL CACHE INVALIDATION - CLEAR ALL MISTAKE CACHES 💀
            $this->clearMistakeCaches($userId);
            
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
     * 💀 LETHAL CACHE INVALIDATION METHOD 💀
     */
    private function clearMistakeCaches($userId)
    {
        try {
            // Clear all possible mistake cache keys
            $cacheKeys = [
                QuizCacheService::generateCacheKey('mistakes_full', ['user_id' => $userId, 'course_filter' => 'all']),
                QuizCacheService::generateCacheKey('mistakes_full', ['user_id' => $userId, 'course_filter' => 'skipped']),
                "mistake_courses_{$userId}",
                "mistakes_user_{$userId}",
                "mistakes_count_{$userId}"
            ];
            
            // Also clear any course-specific caches
            $courses = \App\Models\Course::all();
            foreach ($courses as $course) {
                $cacheKeys[] = QuizCacheService::generateCacheKey('mistakes_full', [
                    'user_id' => $userId, 
                    'course_filter' => $course->id
                ]);
            }
            
            // Clear all cache keys
            foreach ($cacheKeys as $key) {
                Cache::forget($key);
            }
            
            Log::info("💀 CACHE CLEARED for user", [
                'user_id' => $userId,
                'cache_keys_cleared' => count($cacheKeys)
            ]);
            
        } catch (\Exception $e) {
            Log::error("❌ Error clearing mistake caches", [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Skip a mistake question.
     */
    public function skipMistake($question_id)
    {
        $userId = auth()->id();

        try {
            // Simple query to find and update the mistake
            $mistake = Mistake::where('user_id', $userId)
                ->where('question_id', $question_id)
                ->first();

            if ($mistake) {
                // Mark as skipped
                $mistake->skipped = true;
                $mistake->last_attempt_date = now();
                $mistake->save();

                // 💀 LETHAL CACHE INVALIDATION 💀
                $this->clearMistakeCaches($userId);

                return response()->json([
                    'success' => true,
                    'message' => 'Question skipped successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No mistake found to skip.'
                ], 404);
            }
        } catch (\Exception $e) {
            Log::error('Error skipping mistake', [
                'user_id' => $userId,
                'question_id' => $question_id,
                'error' => $e->getMessage()
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