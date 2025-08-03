<?php

namespace App\Events;

use App\Models\User;
use App\Models\Topic;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizStarted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public Topic $topic;
    public string $mode;
    public int $questionCount;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, Topic $topic, string $mode, int $questionCount)
    {
        $this->user = $user;
        $this->topic = $topic;
        $this->mode = $mode;
        $this->questionCount = $questionCount;
    }
}