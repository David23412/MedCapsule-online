<?php

namespace App\Http\Controllers\Learn;

use App\Http\Controllers\Controller;
use App\Models\Mistake;
use Illuminate\Http\Request;
use App\Services\QuizCacheService;
use App\Services\UserStatsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * LETHAL OPTIMIZED LEARN VIEW CONTROLLER
 * Handles mistake display and review interface
 */
class LearnViewController extends Controller
{
    protected $userStatsService;

    public function __construct(UserStatsService $userStatsService)
    {
        $this->middleware(['auth']);
        $this->userStatsService = $userStatsService;
    }

    /**
     * Show the mistakes that need to be reviewed - LETHAL CACHED
     */
    public function learnMistakes(Request $request)
    {
        try {
            $userId = auth()->id();
            $mode = session('learn_mode', 'quiz');
            $courseFilter = $request->input('course');
            
            // LETHAL CACHING: Ultra-aggressive caching for mistakes
            $cacheKey = QuizUltraConsolidatedCacheService::generateCacheKey('learn_mistakes', [
                'user_id' => $userId,
                'mode' => $mode,
                'course_filter' => $courseFilter,
                'timestamp' => now()->format('Y-m-d-H')
            ]);

            return Cache::remember($cacheKey, 1800, function () use ($userId, $mode, $courseFilter) {
                // Get all mistakes for the user (including mastered ones)
                $mistakes = Mistake::select('mistakes.*', 'questions.question', 'questions.correct_answer', 'questions.explanation', 'topics.name as topic_name', 'courses.name as course_name')
                    ->join('questions', 'mistakes.question_id', '=', 'questions.id')
                    ->join('topics', 'questions.topic_id', '=', 'topics.id')
                    ->join('courses', 'topics.course_id', '=', 'courses.id')
                    ->where('mistakes.user_id', $userId)
                    ->when($courseFilter, function ($query, $courseFilter) {
                        return $query->where('courses.id', $courseFilter);
                    })
                    ->orderBy('mistakes.created_at', 'desc')
                    ->get();

                return view('mistakes.learn', compact('mistakes', 'mode', 'courseFilter'));
            });
            
        } catch (\Exception $e) {
            Log::error('Learn mistakes failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            
            return view('mistakes.learn')->with('error', 'Failed to load mistakes');
        }
    }

    /**
     * Get updated list of mistakes after a review
     */
    public function getUpdatedMistakes(Request $request)
    {
        try {
            $userId = auth()->id();
            $courseFilter = $request->input('course');
            
            $cacheKey = QuizUltraConsolidatedCacheService::generateCacheKey('updated_mistakes', [
                'user_id' => $userId,
                'course_filter' => $courseFilter,
                'timestamp' => now()->format('Y-m-d-H-i')
            ]);

            $mistakes = Cache::remember($cacheKey, 300, function () use ($userId, $courseFilter) {
                return Mistake::select('mistakes.*', 'questions.question', 'questions.correct_answer', 'questions.explanation', 'topics.name as topic_name', 'courses.name as course_name')
                    ->join('questions', 'mistakes.question_id', '=', 'questions.id')
                    ->join('topics', 'questions.topic_id', '=', 'topics.id')
                    ->join('courses', 'topics.course_id', '=', 'courses.id')
                    ->where('mistakes.user_id', $userId)
                    ->when($courseFilter, function ($query, $courseFilter) {
                        return $query->where('courses.id', $courseFilter);
                    })
                    ->orderBy('mistakes.created_at', 'desc')
                    ->get();
            });

            return response()->json([
                'success' => true,
                'mistakes' => $mistakes,
                'total_count' => $mistakes->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get updated mistakes failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get updated mistakes'
            ], 500);
        }
    }
}
