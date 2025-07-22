<?php

namespace App\Listeners;

use App\Events\CourseProgressMilestone;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendCourseProgressNotification implements ShouldQueue
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
    public function handle(CourseProgressMilestone $event): void
    {
        try {
            if ($event->isCompletion) {
                // Course completion notification
                $this->notificationService->createNotification(
                    $event->user->id,
                    'course_mastery',
                    'Course Completed!',
                    "Congratulations! You have completed '{$event->course->name}'. You've mastered all topics and achieved excellent performance!",
                    [
                        'course_id' => $event->course->id,
                        'course_name' => $event->course->name,
                        'completion_date' => now()->toISOString(),
                        'progress_percentage' => $event->progressPercentage
                    ]
                );
            } else {
                // Progress milestone notification
                $this->notificationService->createCourseProgressNotification(
                    $event->user,
                    $event->course->name,
                    $event->progressPercentage
                );
            }
            
            Log::info('Course progress notification sent', [
                'user_id' => $event->user->id,
                'course_id' => $event->course->id,
                'progress_percentage' => $event->progressPercentage,
                'is_completion' => $event->isCompletion
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send course progress notification', [
                'user_id' => $event->user->id,
                'course_id' => $event->course->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}