<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserProgressUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $topicId;
    public $percentage;
    public $studyMode;

    /**
     * Create a new event instance.
     */
    public function __construct(int $userId, int $topicId, float $percentage, string $studyMode = 'quiz')
    {
        $this->userId = $userId;
        $this->topicId = $topicId;
        $this->percentage = $percentage;
        $this->studyMode = $studyMode;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.progress'),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'topic_id' => $this->topicId,
            'percentage' => $this->percentage,
            'study_mode' => $this->studyMode,
            'timestamp' => now()->toISOString(),
        ];
    }
} 