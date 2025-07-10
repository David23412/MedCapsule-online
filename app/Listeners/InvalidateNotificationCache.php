<?php

namespace App\Listeners;

use App\Events\NotificationCreated;
use App\Services\CacheService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class InvalidateNotificationCache implements ShouldQueue
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
    public function handle(NotificationCreated $event): void
    {
        try {
            $notification = $event->notification;
            $userId = $event->userId;

            // Clear user notification caches
            $this->cacheService->clearUserNotificationCaches($userId);

            // Clear notification count caches
            $this->cacheService->clearNotificationCountCaches($userId);

            Log::info('Notification cache invalidated', [
                'notification_id' => $notification->id,
                'user_id' => $userId,
                'type' => $notification->type,
                'timestamp' => now()
            ]);
        } catch (\Exception $e) {
            Log::error('Error invalidating notification cache', [
                'notification_id' => $event->notification->id,
                'user_id' => $event->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
} 