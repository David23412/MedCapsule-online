<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudyStreakUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public int $newStreak;
    public int $previousStreak;
    public bool $wasReset;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, int $newStreak, int $previousStreak = 0)
    {
        $this->user = $user;
        $this->newStreak = $newStreak;
        $this->previousStreak = $previousStreak;
        $this->wasReset = $previousStreak > 1 && $newStreak === 1;
    }
}