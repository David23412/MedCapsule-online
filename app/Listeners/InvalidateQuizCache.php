<?php

namespace App\Listeners;

use App\Events\QuizAttemptCompleted;
use App\Services\Core\UltraConsolidatedCacheService;
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
    public function __construct(UltraConsolidatedCacheService $cacheService)
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
            $isRandomQuiz = $quizAttempt->quiz_type === 'random';

            // Clear quiz-specific caches
            $this->cacheService->clearQuizCaches($topicId, $userId);

            // Clear user progress caches
            $this->cacheService->invalidateUserCaches($userId, $topicId);

            // Clear user stats cache
            $this->cacheService->clearUserMasteryStats($userId);

            // Clear topic progress caches only for regular quizzes
            if (!$isRandomQuiz && $topicId) {
                $this->cacheService->clearTopicProgressCaches($topicId, $userId, 'quiz');
                // Clear course completion cache to reflect updated progress
                $this->clearCourseCompletionCache($userId, $topicId);
            }

            Log::info('Quiz cache invalidated', [
                'quiz_attempt_id' => $quizAttempt->id,
                'user_id' => $userId,
                'topic_id' => $topicId,
                'is_random_quiz' => $isRandomQuiz,
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

    /**
     * Clear course completion cache for the user
     */
    private function clearCourseCompletionCache(int $userId, int $topicId): void
    {
        try {
            // Get the course ID for this topic
            $topic = \App\Models\Topic::find($topicId);
            $course = $topic->course ?? null;
            
            if ($course) {
                // Use the Course model's method to clear completion cache
                $course->clearUserCompletionCache($userId);
                
                Log::info('Course completion cache cleared', [
                    'user_id' => $userId,
                    'course_id' => $course->id,
                    'topic_id' => $topicId
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error clearing course completion cache', [
                'user_id' => $userId,
                'topic_id' => $topicId,
                'error' => $e->getMessage()
            ]);
        }
    }
} 