<?php

namespace App\Events;

use App\Models\Topic;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TopicCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $topic;
    public $userId;
    public $percentage;
    public $sourceProgress;

    /**
     * Create a new event instance.
     */
    public function __construct(Topic $topic, int $userId, float $percentage, array $sourceProgress = [])
    {
        $this->topic = $topic;
        $this->userId = $userId;
        $this->percentage = $percentage;
        $this->sourceProgress = $sourceProgress;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('topic.completion'),
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
            'topic_id' => $this->topic->id,
            'user_id' => $this->userId,
            'percentage' => $this->percentage,
            'source_progress' => $this->sourceProgress,
            'timestamp' => now()->toISOString(),
        ];
    }
} 