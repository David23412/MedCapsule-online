<?php

namespace App\Listeners;

use App\Events\SkippedQuestionsDetected;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendSkippedQuestionNotification implements ShouldQueue
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
    public function handle(SkippedQuestionsDetected $event): void
    {
        try {
            $this->notificationService->createNotification(
                $event->user->id,
                'skipped_questions_reminder',
                'Skipped Questions Reminder',
                "You have {$event->skippedCount} skipped questions. Consider reviewing them to improve your understanding and complete your learning journey.",
                [
                    'skipped_count' => $event->skippedCount,
                    'topic_breakdown' => $event->topicBreakdown,
                    'detected_at' => $event->detectedAt->toISOString()
                ]
            );
            
            Log::info('Skipped questions notification sent', [
                'user_id' => $event->user->id,
                'skipped_count' => $event->skippedCount,
                'topic_breakdown' => $event->topicBreakdown
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send skipped questions notification', [
                'user_id' => $event->user->id,
                'skipped_count' => $event->skippedCount,
                'error' => $e->getMessage()
            ]);
        }
    }
}