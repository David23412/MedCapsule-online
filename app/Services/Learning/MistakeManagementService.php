<?php

namespace App\Services\Learning;

use App\Models\Mistake;
use App\Services\QuizCacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MistakeManagementService
{
    /**
     * Get mistakes for learning with caching
     */
    public function getMistakesForLearning(int $userId, string $mode, ?string $courseFilter = null): array
    {
        $cacheKey = QuizCacheService::generateCacheKey('learn_mistakes', [
            'user_id' => $userId,
            'mode' => $mode,
            'course_filter' => $courseFilter,
            'timestamp' => now()->format('Y-m-d-H')
        ]);

        return Cache::remember($cacheKey, 1800, function () use ($userId, $mode, $courseFilter) {
            $mistakes = Mistake::where('user_id', $userId)
                ->select([
                    'mistakes.id',
                    'mistakes.question_id',
                    'mistakes.submitted_answer',
                    'mistakes.last_attempt_date',
                    'mistakes.correct_streak',
                    'mistakes.skipped'
                ])
                ->with(['question' => function($query) {
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
                ->with('question.topic:id,name,course_id')
                ->with('question.topic.course:id,name')
                ->when($courseFilter, function($query) use ($courseFilter) {
                    if ($courseFilter === 'skipped') {
                        return $query->where('mistakes.skipped', true);
                    } else {
                        return $query->whereHas('question.topic.course', function($q) use ($courseFilter) {
                            $q->where('id', $courseFilter);
                        });
                    }
                })
                ->orderBy('correct_streak', 'desc')
                ->get();

            $courses = $mistakes->map(function($mistake) {
                return $mistake->question->topic->course ?? null;
            })->filter()->unique('id')->values();

            if ($mistakes->isEmpty()) {
                return ['mistakes' => collect(), 'courses' => collect()];
            }

            if ($mistakes->every(function($mistake) { return $mistake->question === null; })) {
                return null;
            }

            return compact('mistakes', 'courses');
        });
    }

    /**
     * Get updated mistakes count
     */
    public function getUpdatedMistakesCount(int $userId): array
    {
        $mistakes = Mistake::where('user_id', $userId)
            ->where('correct_streak', '<', 3)
            ->with('question')
            ->get();

        return [
            'success' => true,
            'mistakes_count' => $mistakes->count(),
            'has_mistakes' => $mistakes->isNotEmpty()
        ];
    }

    /**
     * Process mistake review
     */
    public function reviewMistake(int $userId, int $questionId, string $selectedAnswer, string $mode): array
    {
        return DB::transaction(function () use ($userId, $questionId, $selectedAnswer, $mode) {
            $mistake = Mistake::select('id', 'user_id', 'question_id', 'correct_streak', 'last_attempt_date')
                ->where('user_id', $userId)
                ->where('question_id', $questionId)
                ->first();

            if (!$mistake) {
                return [
                    'success' => false,
                    'message' => 'Mistake not found'
                ];
            }

            $isCorrect = false;
            
            if (isset($mistake->question->question_type) && $mistake->question->question_type === 'written') {
                $isCorrect = $mistake->question->isAnswerCorrect($selectedAnswer);
            } else {
                $isCorrect = strtolower(trim($selectedAnswer)) === strtolower(trim($mistake->question->correct_answer));
            }
            
            $mistake->updateTracking($isCorrect, $mode);

            return [
                'success' => true,
                'is_correct' => $isCorrect,
                'correct_answer' => $mistake->question->correct_answer,
                'explanation' => $mistake->question->explanation,
                'new_streak' => $mistake->correct_streak,
                'is_mastered' => $mistake->isMastered($mode),
                'message' => $isCorrect ? 'Correct!' : 'Incorrect. Try again!'
            ];
        });
    }

    /**
     * Get mistakes for review session
     */
    public function getMistakesForReview(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return Mistake::where('user_id', $userId)
            ->where('correct_streak', '<', 3)
            ->with('question.topic.course')
            ->orderBy('last_attempt_date', 'asc')
            ->limit(10)
            ->get();
    }

    /**
     * Remove a mistake
     */
    public function removeMistake(int $userId, int $questionId): array
    {
        $mistake = Mistake::select('id', 'user_id', 'question_id')
                        ->where('user_id', $userId)
                        ->where('question_id', $questionId)
                        ->first();

        if (!$mistake) {
            return [
                'success' => false,
                'message' => 'Mistake not found'
            ];
        }

        $mistake->delete();

        return [
            'success' => true,
            'message' => 'Mistake removed successfully'
        ];
    }
}