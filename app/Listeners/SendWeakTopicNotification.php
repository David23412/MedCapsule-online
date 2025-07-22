<?php

namespace App\Listeners;

use App\Events\WeakTopicIdentified;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendWeakTopicNotification implements ShouldQueue
{
    use InteractsWithQueue;

    private NotificationService $notificationService;

    /**
     * Create the event listener.
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(WeakTopicIdentified $event): void
    {
        try {
            $this->notificationService->createWeakTopicAddedNotification(
                $event->user,
                $event->topic->name
            );
            
            Log::info('Weak topic notification sent', [
                'user_id' => $event->user->id,
                'topic_id' => $event->topic->id,
                'topic_name' => $event->topic->name,
                'average_score' => $event->averageScore,
                'attempt_count' => $event->attemptCount
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send weak topic notification', [
                'user_id' => $event->user->id,
                'topic_id' => $event->topic->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}