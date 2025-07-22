<?php

namespace App\Listeners;

use App\Events\CorrectAnswersMilestoneReached;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendCorrectAnswersMilestoneNotification implements ShouldQueue
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
    public function handle(CorrectAnswersMilestoneReached $event): void
    {
        try {
            $this->notificationService->createCorrectAnswersMilestoneNotification(
                $event->user,
                $event->milestone
            );
            
            Log::info('Correct answers milestone notification sent', [
                'user_id' => $event->user->id,
                'milestone' => $event->milestone,
                'total_correct_answers' => $event->totalCorrectAnswers,
                'overall_accuracy' => $event->overallAccuracy
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send correct answers milestone notification', [
                'user_id' => $event->user->id,
                'milestone' => $event->milestone,
                'error' => $e->getMessage()
            ]);
        }
    }
}