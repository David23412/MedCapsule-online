<?php

namespace App\Listeners;

use App\Events\StudyStreakUpdated;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendStudyStreakNotification implements ShouldQueue
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
    public function handle(StudyStreakUpdated $event): void
    {
        try {
            if ($event->wasReset) {
                // Send streak reset notification
                $this->notificationService->createStreakResetNotification($event->user);
                
                Log::info('Streak reset notification sent', [
                    'user_id' => $event->user->id,
                    'previous_streak' => $event->previousStreak,
                    'new_streak' => $event->newStreak
                ]);
            } else {
                // Send streak milestone notification for significant milestones
                $milestones = [1, 2, 3, 7, 14, 30, 60, 100];
                
                if (in_array($event->newStreak, $milestones) && $event->newStreak > $event->previousStreak) {
                    $this->notificationService->createStudyStreakNotification($event->user, $event->newStreak);
                    
                    Log::info('Streak milestone notification sent', [
                        'user_id' => $event->user->id,
                        'streak' => $event->newStreak
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send streak notification', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
                'streak' => $event->newStreak
            ]);
        }
    }
}