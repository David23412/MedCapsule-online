<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CorrectAnswersMilestoneReached
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public int $milestone;
    public int $totalCorrectAnswers;
    public float $overallAccuracy;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, int $milestone, int $totalCorrectAnswers, float $overallAccuracy)
    {
        $this->user = $user;
        $this->milestone = $milestone;
        $this->totalCorrectAnswers = $totalCorrectAnswers;
        $this->overallAccuracy = $overallAccuracy;
    }
}