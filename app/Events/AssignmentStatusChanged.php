<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssignmentStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $assignmentData;

    /**
     * Create a new event instance.
     */
    public function __construct(array $assignmentData)
    {
        $this->assignmentData = $assignmentData;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->assignmentData['user_id']),
            new PrivateChannel('tutor.' . $this->assignmentData['tutor_id']),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'assignment.status.changed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'assignment' => $this->assignmentData,
            'message' => $this->getStatusChangeMessage(),
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Get a human-readable message for the status change
     */
    private function getStatusChangeMessage(): string
    {
        $oldStatus = $this->assignmentData['old_status'];
        $newStatus = $this->assignmentData['new_status'];
        $title = $this->assignmentData['title'];

        return match ([$oldStatus, $newStatus]) {
            ['active', 'overdue'] => "Assignment '{$title}' is now overdue",
            ['overdue', 'active'] => "Assignment '{$title}' deadline has been extended",
            ['active', 'expired'] => "Assignment '{$title}' time limit has expired",
            ['active', 'completed'] => "Assignment '{$title}' has been completed",
            default => "Assignment '{$title}' status changed from {$oldStatus} to {$newStatus}"
        };
    }
}