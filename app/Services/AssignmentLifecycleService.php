<?php

namespace App\Services;

use App\Models\Assignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AssignmentLifecycleService
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Delete an assignment (soft delete with validation)
     */
    public function deleteAssignment(Assignment $assignment): bool
    {
        // Since status column was removed, allow deletion
        $result = $assignment->delete();
        
        if (!$result) {
            Log::error("Failed to delete assignment {$assignment->id}.");
        }

        return $result;
    }

    /**
     * Extend the deadline for an assignment
     */
    public function extendDeadline(Assignment $assignment, Carbon $newDeadline): bool
    {
        if ($newDeadline->isPast()) {
            throw new \InvalidArgumentException('New deadline must be in the future.');
        }

        $oldDeadline = $assignment->deadline->copy();
        $assignment->deadline = $newDeadline;
        $saved = $assignment->save();

        if ($saved) {
            $this->notificationService->notifyDeadlineChanged($assignment, $oldDeadline);
        }

        return $saved;
    }

    /**
     * Expire overdue assignments that have passed their grace period.
     */
    public function expireOverdueAssignments()
    {
        // Assignment status is derived from deadlines; there is no status column.
        // Keep this compatibility entry point without writing obsolete state.
    }

    /**
     * Send reminders for assignments nearing their deadline.
     */
    public function sendDeadlineReminders()
    {
        $this->notificationService->sendDeadlineReminders();
    }
}
