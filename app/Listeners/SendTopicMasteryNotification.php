<?php

namespace App\Listeners;

use App\Events\TopicMastered;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendTopicMasteryNotification implements ShouldQueue
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
    public function handle(TopicMastered $event): void
    {
        try {
            $this->notificationService->createTopicMasteryNotification(
                $event->user,
                $event->topic->name
            );
            
        } catch (\Exception $e) {
            Log::error('Failed to send topic mastery notification', [
                'user_id' => $event->user->id,
                'topic_id' => $event->topic->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}