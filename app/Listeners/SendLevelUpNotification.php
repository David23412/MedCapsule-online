<?php

namespace App\Listeners;

use App\Events\UserLeveledUp;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendLevelUpNotification implements ShouldQueue
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
    public function handle(UserLeveledUp $event): void
    {
        try {
            $xpNeeded = $event->user->getXPForNextLevel();
            
            $this->notificationService->createLevelUpNotification(
                $event->user,
                $event->newLevel,
                $xpNeeded
            );
            
            Log::info('Level up notification sent', [
                'user_id' => $event->user->id,
                'new_level' => $event->newLevel,
                'previous_level' => $event->previousLevel,
                'total_xp' => $event->totalXP,
                'xp_gained' => $event->xpGained
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send level up notification', [
                'user_id' => $event->user->id,
                'new_level' => $event->newLevel,
                'error' => $e->getMessage()
            ]);
        }
    }
}