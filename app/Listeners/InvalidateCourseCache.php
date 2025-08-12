<?php

namespace App\Listeners;

use App\Events\CourseUpdated;
use App\Services\Core\UltraConsolidatedCacheService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class InvalidateCourseCache implements ShouldQueue
{
    use InteractsWithQueue;

    protected $cacheService;

    /**
     * Create the event listener.
     */
    public function __construct(UltraConsolidatedCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the event.
     */
    public function handle(CourseUpdated $event): void
    {
        try {
            $course = $event->course;
            $userId = $event->userId;

            // Clear course-specific caches
            $this->cacheService->clearCourseCaches($course->id, $userId);

            // Clear related topic caches
            $this->cacheService->clearTopicCachesByCourse($course->id);

            Log::info('Course cache invalidated', [
                'course_id' => $course->id,
                'user_id' => $userId,
                'changes' => $event->changes,
                'timestamp' => now()
            ]);
        } catch (\Exception $e) {
            Log::error('Error invalidating course cache', [
                'course_id' => $event->course->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
} 