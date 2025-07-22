<?php

namespace App\Events;

use App\Models\User;
use App\Models\Topic;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TopicMastered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public Topic $topic;
    public float $masteryScore;
    public int $attemptsToMastery;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, Topic $topic, float $masteryScore, int $attemptsToMastery = 1)
    {
        $this->user = $user;
        $this->topic = $topic;
        $this->masteryScore = $masteryScore;
        $this->attemptsToMastery = $attemptsToMastery;
    }
}