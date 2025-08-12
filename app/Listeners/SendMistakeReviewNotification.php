<?php

namespace App\Listeners;

use App\Events\MistakesNeedReview;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendMistakeReviewNotification implements ShouldQueue
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
    public function handle(MistakesNeedReview $event): void
    {
        try {
            \App\Services\NotificationTriggerService::onMistakesNeedReview(
                $event->user,
                $event->mistakeCount
            );
            
            Log::info('Mistake review notification sent', [
                'user_id' => $event->user->id,
                'mistake_count' => $event->mistakeCount,
                'topic_breakdown' => $event->topicBreakdown
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send mistake review notification', [
                'user_id' => $event->user->id,
                'mistake_count' => $event->mistakeCount,
                'error' => $e->getMessage()
            ]);
        }
    }
}