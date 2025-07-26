<?php

namespace App\Listeners;

use App\Events\AssignmentDeadlineChanged;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendAssignmentDeadlineNotification implements ShouldQueue
{
    use InteractsWithQueue;

    private NotificationService $notificationService;

    /**
     * Create the event listener.
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(AssignmentDeadlineChanged $event): void
    {
        try {
            // Check if assignment has required properties
            if (!isset($event->assignment->user_id) || !isset($event->assignment->title)) {
                Log::warning('Assignment missing required properties for notification', [
                    'assignment' => $event->assignment
                ]);
                return;
            }

            $message = "The deadline for '{$event->assignment->title}' has been changed from {$event->oldDeadline->format('M j, Y g:i A')} to {$event->newDeadline->format('M j, Y g:i A')}";
            
            if (!empty($event->reason)) {
                $message .= ". Reason: {$event->reason}";
            }
            
            $this->notificationService->createNotification(
                $event->assignment->user_id,
                'assignment_deadline_changed',
                'Assignment Deadline Updated',
                $message,
                [
                    'assignment_id' => $event->assignment->id ?? null,
                    'assignment_title' => $event->assignment->title,
                    'old_deadline' => $event->oldDeadline->toISOString(),
                    'new_deadline' => $event->newDeadline->toISOString(),
                    'changed_by' => $event->changedBy->name,
                    'reason' => $event->reason
                ]
            );
            
            Log::info('Assignment deadline change notification sent', [
                'student_id' => $event->assignment->user_id,
                'assignment_id' => $event->assignment->id ?? null,
                'old_deadline' => $event->oldDeadline->toISOString(),
                'new_deadline' => $event->newDeadline->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send assignment deadline notification', [
                'assignment_id' => $event->assignment->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }
}