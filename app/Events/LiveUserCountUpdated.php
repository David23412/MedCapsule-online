<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LiveUserCountUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The number of active users.
     *
     * @var int
     */
    public $count;

    /**
     * Create a new event instance.
     */
    public function __construct(int $count)
    { 
        $this->count = $count;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    { 
        return [
            new Channel('live-users'),
        ];
    }

    /**
     * The name of the event's broadcast.
     */
    public function broadcastAs(): string
    {
        return 'UserCountUpdated';
    }
}
