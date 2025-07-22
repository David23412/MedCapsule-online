<?php

namespace App\Listeners;

use App\Events\TopicUnlockedByTutor;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendTopicUnlockNotification implements ShouldQueue
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
    public function handle(TopicUnlockedByTutor $event): void
    {
        try {
            $message = "Your tutor {$event->tutor->name} has unlocked the topic '{$event->topic->name}' for you. You can now access new materials and quizzes!";
            
            if (!empty($event->unlockReason)) {
                $message .= " Reason: {$event->unlockReason}";
            }
            
            $this->notificationService->createNotification(
                $event->student->id,
                'topic_unlocked',
                'New Topic Unlocked!',
                $message,
                [
                    'topic_id' => $event->topic->id,
                    'topic_name' => $event->topic->name,
                    'tutor_id' => $event->tutor->id,
                    'tutor_name' => $event->tutor->name,
                    'unlock_reason' => $event->unlockReason,
                    'unlocked_at' => $event->unlockedAt->toISOString()
                ]
            );
            
            Log::info('Topic unlock notification sent', [
                'student_id' => $event->student->id,
                'tutor_id' => $event->tutor->id,
                'topic_id' => $event->topic->id,
                'topic_name' => $event->topic->name
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send topic unlock notification', [
                'student_id' => $event->student->id,
                'topic_id' => $event->topic->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}