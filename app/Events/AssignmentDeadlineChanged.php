<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class AssignmentDeadlineChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $assignment;
    public Carbon $oldDeadline;
    public Carbon $newDeadline;
    public User $changedBy;
    public string $reason;

    /**
     * Create a new event instance.
     */
    public function __construct($assignment, Carbon $oldDeadline, Carbon $newDeadline, User $changedBy, string $reason = '')
    {
        $this->assignment = $assignment;
        $this->oldDeadline = $oldDeadline;
        $this->newDeadline = $newDeadline;
        $this->changedBy = $changedBy;
        $this->reason = $reason;
    }
}