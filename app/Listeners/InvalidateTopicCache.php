<?php

namespace App\Listeners;

use App\Events\TopicCompleted;
use App\Services\CacheService;
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
    public function __construct(CacheService $cacheService)
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
} 