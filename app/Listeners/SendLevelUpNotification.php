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
            
        } catch (\Exception $e) {
            Log::error('Failed to send level up notification', [
                'user_id' => $event->user->id,
                'new_level' => $event->newLevel,
                'error' => $e->getMessage()
            ]);
        }
    }
}