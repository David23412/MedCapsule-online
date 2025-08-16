<?php

namespace App\Http\Controllers\Mistake;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mistake;
use App\Models\Question;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * 🚀 PERFORMANCE BEAST: Focused Mistake Creation Controller
 * Handles: Mistake creation and processing
 * Lines: ~150 (under 200 limit)
 */
class MistakeCreationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Store a new mistake
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'question_id' => 'required|exists:questions,id',
                'submitted_answer' => 'required|string'
            ]);

            $userId = auth()->id();
            $questionId = $validated['question_id'];
            $submittedAnswer = $validated['submitted_answer'];

            // Check if answer is correct
            if ($this->isCorrectAnswer($questionId, $submittedAnswer)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This is actually a correct answer!'
                ]);
            }

            // Process the mistake
            $mistake = $this->processMistake($userId, $questionId, $submittedAnswer);

            return response()->json([
                'success' => true,
                'message' => 'Mistake recorded successfully',
                'mistake_id' => $mistake->id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to store mistake: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to record mistake'
            ], 500);
        }
    }

    /**
     * Check if submitted answer is correct
     */
    private function isCorrectAnswer($questionId, $submittedAnswer): bool
    {
        $question = Question::find($questionId);
        if (!$question) {
            return false;
        }

        // Normalize answers for comparison
        $correctAnswer = strtolower(trim($question->correct_answer));
        $submittedAnswer = strtolower(trim($submittedAnswer));

        return $correctAnswer === $submittedAnswer;
    }

    /**
     * Process mistake creation/update
     */
    private function processMistake($userId, $questionId, $submittedAnswer)
    {
        // Find or create mistake
        $mistake = $this->findOrCreateMistake($userId, $questionId, $submittedAnswer);

        // Update mistake details
        $mistake->increment('attempt_count');
        $mistake->last_attempt_date = now();
        $mistake->submitted_answer = $submittedAnswer;
        $mistake->save();

        // Clear user caches
        $this->clearMistakeCaches($userId);

        Log::info('Mistake processed', [
            'user_id' => $userId,
            'question_id' => $questionId,
            'mistake_id' => $mistake->id,
            'attempt_count' => $mistake->attempt_count
        ]);

        return $mistake;
    }

    /**
     * Find existing mistake or create new one
     */
    private function findOrCreateMistake($userId, $questionId, $submittedAnswer)
    {
        $mistake = Mistake::where('user_id', $userId)
            ->where('question_id', $questionId)
            ->first();

        if (!$mistake) {
            $mistake = Mistake::create([
                'user_id' => $userId,
                'question_id' => $questionId,
                'submitted_answer' => $submittedAnswer,
                'attempt_count' => 0,
                'last_attempt_date' => now(),
                'skipped' => false
            ]);

            Log::info('New mistake created', [
                'user_id' => $userId,
                'question_id' => $questionId,
                'mistake_id' => $mistake->id
            ]);
        }

        return $mistake;
    }

    /**
     * Clear mistake-related caches
     */
    private function clearMistakeCaches($userId)
    {
        $cacheKeys = [
            "user_mistakes_{$userId}",
            "user_mistake_count_{$userId}",
            "user_progress_{$userId}",
            "user_stats_{$userId}"
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        // Clear course-specific caches
        Cache::tags(["user_{$userId}", 'mistakes'])->flush();
    }
}