<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserLeveledUp
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public int $newLevel;
    public int $previousLevel;
    public int $totalXP;
    public int $xpGained;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, int $newLevel, int $previousLevel, int $totalXP, int $xpGained)
    {
        $this->user = $user;
        $this->newLevel = $newLevel;
        $this->previousLevel = $previousLevel;
        $this->totalXP = $totalXP;
        $this->xpGained = $xpGained;
    }
}