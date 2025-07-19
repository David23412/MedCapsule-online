<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentProgressUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $student;
    public $analytics;
    private $tutorId;

    /**
     * Create a new event instance.
     */
    public function __construct(User $student, array $analytics, int $tutorId)
    {
        $this->student = $student;
        $this->analytics = $analytics;
        $this->tutorId = $tutorId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Broadcast to specific tutor's channel and admin channel
        return [
            new PrivateChannel('tutor.' . $this->tutorId),
            new PrivateChannel('admin.progress'),
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
            'student_id' => $this->student->id,
            'name' => $this->student->name,
            'analytics' => $this->analytics,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'student.progress.updated';
    }
} 