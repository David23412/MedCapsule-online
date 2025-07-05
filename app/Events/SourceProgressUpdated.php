<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SourceProgressUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $topicId;
    public $sourceProgress;
    public $allSourcesMastered;

    /**
     * Create a new event instance.
     */
    public function __construct($topicId, $sourceProgress, $allSourcesMastered)
    {
        $this->topicId = $topicId;
        $this->sourceProgress = $sourceProgress;
        $this->allSourcesMastered = $allSourcesMastered;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('source.progress'),
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
            'topic_id' => $this->topicId,
            'source_progress' => $this->sourceProgress,
            'all_sources_mastered' => $this->allSourcesMastered,
        ];
    }
} 