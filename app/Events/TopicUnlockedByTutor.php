<?php

namespace App\Events;

use App\Models\User;
use App\Models\Topic;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TopicUnlockedByTutor
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $student;
    public User $tutor;
    public Topic $topic;
    public string $unlockReason;
    public \Carbon\Carbon $unlockedAt;

    /**
     * Create a new event instance.
     */
    public function __construct(User $student, User $tutor, Topic $topic, string $unlockReason = '')
    {
        $this->student = $student;
        $this->tutor = $tutor;
        $this->topic = $topic;
        $this->unlockReason = $unlockReason;
        $this->unlockedAt = now();
    }
}