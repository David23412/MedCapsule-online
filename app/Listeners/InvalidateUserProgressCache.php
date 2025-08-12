<?php

namespace App\Listeners;

use App\Events\UserProgressUpdated;
use App\Services\Core\UltraConsolidatedCacheService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class InvalidateUserProgressCache implements ShouldQueue
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
    public function handle(UserProgressUpdated $event): void
    {
        try {
            $userId = $event->userId;
            $topicId = $event->topicId;
            $studyMode = $event->studyMode;

            // Clear user progress caches for this topic and study mode
            $this->cacheService->invalidateUserCaches($userId, $topicId);

            // Clear user mastery stats cache
            $this->cacheService->clearUserMasteryStats($userId);

            // Clear topic-specific progress caches
            $this->cacheService->clearTopicProgressCaches($topicId, $userId, $studyMode);

            // Clear course completion cache to reflect updated progress
            $this->clearCourseCompletionCache($userId, $topicId);

            Log::info('User progress cache invalidated', [
                'user_id' => $userId,
                'topic_id' => $topicId,
                'study_mode' => $studyMode,
                'percentage' => $event->percentage,
                'timestamp' => now()
            ]);
        } catch (\Exception $e) {
            Log::error('Error invalidating user progress cache', [
                'user_id' => $event->userId,
                'topic_id' => $event->topicId,
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
                
                Log::info('Course completion cache cleared from user progress update', [
                    'user_id' => $userId,
                    'course_id' => $course->id,
                    'topic_id' => $topicId
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error clearing course completion cache from user progress update', [
                'user_id' => $userId,
                'topic_id' => $topicId,
                'error' => $e->getMessage()
            ]);
        }
    }
} 