<?php

namespace App\Events;

use App\Models\User;
use App\Models\Topic;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WeakTopicIdentified
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public Topic $topic;
    public float $averageScore;
    public int $attemptCount;
    public array $recentScores;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, Topic $topic, float $averageScore, int $attemptCount, array $recentScores = [])
    {
        $this->user = $user;
        $this->topic = $topic;
        $this->averageScore = $averageScore;
        $this->attemptCount = $attemptCount;
        $this->recentScores = $recentScores;
    }
}