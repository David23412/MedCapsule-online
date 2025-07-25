<?php

namespace App\Observers;

use App\Models\Course;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CourseObserver
{
    /**
     * Handle the Course "created" event.
     */
    public function created(Course $course): void
    {
        $this->clearAllCourseCaches();
        Log::info('Course created, caches cleared', ['course_id' => $course->id]);
    }

    /**
     * Handle the Course "updated" event.
     */
    public function updated(Course $course): void
    {
        $this->clearAllCourseCaches();
        Log::info('Course updated, caches cleared', [
            'course_id' => $course->id,
            'changes' => $course->getChanges()
        ]);
    }

    /**
     * Handle the Course "deleted" event.
     */
    public function deleted(Course $course): void
    {
        $this->clearAllCourseCaches();
        Log::info('Course deleted, caches cleared', ['course_id' => $course->id]);
    }

    /**
     * Handle the Course "restored" event.
     */
    public function restored(Course $course): void
    {
        $this->clearAllCourseCaches();
        Log::info('Course restored, caches cleared', ['course_id' => $course->id]);
    }

    /**
     * Handle the Course "force deleted" event.
     */
    public function forceDeleted(Course $course): void
    {
        $this->clearAllCourseCaches();
        Log::info('Course force deleted, caches cleared', ['course_id' => $course->id]);
    }

    /**
     * Clear all course-related caches AGGRESSIVELY
     */
    private function clearAllCourseCaches(): void
    {
        // Increment cache version to invalidate ALL versioned caches
        $currentVersion = Cache::get('courses_cache_version', 1);
        Cache::put('courses_cache_version', $currentVersion + 1, 86400);
        
        // Clear specific cache keys
        $cacheKeys = [
            'courses_list',
            'courses_with_stats',
            'courses_metadata_guest_all',
        ];
        
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
        
        // For extreme cases, clear everything (nuclear option)
        if (config('app.env') === 'local') {
            Cache::flush();
        }
    }
}