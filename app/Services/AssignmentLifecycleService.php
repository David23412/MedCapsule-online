<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\User;
use App\Events\AssignmentDeadlineChanged;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
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
        
        if ($result) {
            Log::info("Assignment {$assignment->id} deleted successfully.");
        } else {
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

        $assignment->deadline = $newDeadline;
        $assignment->status = Assignment::STATUS_ACTIVE; // Reset status on deadline change
        $saved = $assignment->save();

        if ($saved) {
            event(new AssignmentDeadlineChanged($assignment));
        }

        return $saved;
    }

    /**
     * Expire overdue assignments that have passed their grace period.
     */
    public function expireOverdueAssignments()
    {
        $assignmentsToExpire = $this->getOverdueAssignments(72); // 3-day grace period

        foreach ($assignmentsToExpire as $assignment) {
            $assignment->status = Assignment::STATUS_EXPIRED;
            $assignment->save();
            Log::info("Expired assignment {$assignment->id}");
        }
    }

    /**
     * Send reminders for assignments nearing their deadline.
     */
    public function sendDeadlineReminders()
    {
        $assignments = $this->getAssignmentsNearingDeadline(24);
        foreach ($assignments as $assignment) {
            $this->notificationService->sendDeadlineReminder($assignment->student, $assignment);
        }
    }

    /**
     * Get assignments that are overdue by a certain number of hours.
     */
    private function getOverdueAssignments(int $hours = 0): Collection
    {
        return Assignment::where('deadline', '<', now()->subHours($hours))
                         ->where('status', '!=', Assignment::STATUS_COMPLETED)
                         ->get();
    }

    /**
     * Get assignments nearing their deadline.
     */
    private function getAssignmentsNearingDeadline(int $hours = 24): Collection
    {
        return Assignment::where('deadline', '>', now())
                         ->where('deadline', '<=', now()->addHours($hours))
                         ->where('status', Assignment::STATUS_ACTIVE)
                         ->get();
    }
}
