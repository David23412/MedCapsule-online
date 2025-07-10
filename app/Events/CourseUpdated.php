<?php

namespace App\Events;

use App\Models\Course;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourseUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $course;
    public $userId;
    public $changes;

    /**
     * Create a new event instance.
     */
    public function __construct(Course $course, ?int $userId = null, array $changes = [])
    {
        $this->course = $course;
        $this->userId = $userId;
        $this->changes = $changes;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('course.updates'),
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
            'course_id' => $this->course->id,
            'user_id' => $this->userId,
            'changes' => $this->changes,
            'timestamp' => now()->toISOString(),
        ];
    }
} 