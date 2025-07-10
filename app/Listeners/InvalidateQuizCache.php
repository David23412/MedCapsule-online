<?php

namespace App\Listeners;

use App\Events\QuizAttemptCompleted;
use App\Services\CacheService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class InvalidateQuizCache implements ShouldQueue
{
    use InteractsWithQueue;

    protected $cacheService;

    /**
     * Create the event listener.
     */
    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the event.
     */
    public function handle(QuizAttemptCompleted $event): void
    {
        try {
            $quizAttempt = $event->quizAttempt;
            $userId = $quizAttempt->user_id;
            $topicId = $quizAttempt->topic_id;

            // Clear quiz-specific caches
            $this->cacheService->clearQuizCaches($topicId, $userId);

            // Clear user progress caches
            $this->cacheService->invalidateUserCaches($userId, $topicId);

            // Clear user stats cache
            $this->cacheService->clearUserMasteryStats($userId);

            // Clear topic progress caches
            $this->cacheService->clearTopicProgressCaches($topicId, $userId, 'quiz');

            Log::info('Quiz cache invalidated', [
                'quiz_attempt_id' => $quizAttempt->id,
                'user_id' => $userId,
                'topic_id' => $topicId,
                'score' => $event->score,
                'total_questions' => $event->totalQuestions,
                'percentage' => $event->percentage,
                'timestamp' => now()
            ]);
        } catch (\Exception $e) {
            Log::error('Error invalidating quiz cache', [
                'quiz_attempt_id' => $event->quizAttempt->id,
                'user_id' => $event->quizAttempt->user_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
} 