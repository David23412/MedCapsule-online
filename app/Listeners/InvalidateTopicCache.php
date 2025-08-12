<?php

namespace App\Listeners;

use App\Events\TopicCompleted;
use App\Services\Core\UltraConsolidatedCacheService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class InvalidateTopicCache implements ShouldQueue
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
    public function handle(TopicCompleted $event): void
    {
        try {
            $topic = $event->topic;
            $userId = $event->userId;

            // Clear user progress caches for this topic
            $this->cacheService->invalidateUserCaches($userId, $topic->id);

            // Clear topic-specific caches
            $this->cacheService->clearTopicCaches($topic->id, $userId);

            // Clear user mastery stats cache
            $this->cacheService->clearUserMasteryStats($userId);

            // Clear course completion cache to reflect updated progress
            $this->clearCourseCompletionCache($userId, $topic->id);

            Log::info('Topic cache invalidated', [
                'topic_id' => $topic->id,
                'user_id' => $userId,
                'percentage' => $event->percentage,
                'timestamp' => now()
            ]);
        } catch (\Exception $e) {
            Log::error('Error invalidating topic cache', [
                'topic_id' => $event->topic->id,
                'user_id' => $event->userId,
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
                
                Log::info('Course completion cache cleared from topic completion', [
                    'user_id' => $userId,
                    'course_id' => $course->id,
                    'topic_id' => $topicId
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error clearing course completion cache from topic completion', [
                'user_id' => $userId,
                'topic_id' => $topicId,
                'error' => $e->getMessage()
            ]);
        }
    }
} 