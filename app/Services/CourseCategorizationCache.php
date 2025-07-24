<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Support\Facades\Cache;

class CourseCategorizationCache
{
    const CACHE_TTL = 3600; // 1 hour
    const CACHE_PREFIX = 'course_categorizations_';

    private CourseTypeService $courseTypeService;

    public function __construct(CourseTypeService $courseTypeService)
    {
        $this->courseTypeService = $courseTypeService;
    }

    /**
     * Get categorizations with caching
     */
    public function getCategorizations(Course $course): array
    {
        return Cache::remember(
            $this->getCacheKey($course),
            self::CACHE_TTL,
            fn() => $this->courseTypeService->getCategorizations($course)
        );
    }

    /**
     * Invalidate cache for a specific course
     */
    public function invalidate(Course $course): void
    {
        Cache::forget($this->getCacheKey($course));
        
        // Also invalidate related caches
        $this->courseTypeService->invalidateCache($course);
    }

    /**
     * Warm cache for a course
     */
    public function warmCache(Course $course): void
    {
        $this->getCategorizations($course);
    }

    /**
     * Bulk invalidate cache for multiple courses
     */
    public function bulkInvalidate(array $courseIds): void
    {
        foreach ($courseIds as $courseId) {
            Cache::forget(self::CACHE_PREFIX . $courseId);
        }
    }

    /**
     * Get cache key for a course
     */
    private function getCacheKey(Course $course): string
    {
        return self::CACHE_PREFIX . $course->id;
    }

    /**
     * Clear all categorization caches
     */
    public function clearAll(): void
    {
        // Get all course IDs and clear their caches
        $courseIds = Course::pluck('id')->toArray();
        $this->bulkInvalidate($courseIds);
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        $courseIds = Course::pluck('id')->toArray();
        $cached = 0;
        $total = count($courseIds);

        foreach ($courseIds as $courseId) {
            if (Cache::has(self::CACHE_PREFIX . $courseId)) {
                $cached++;
            }
        }

        return [
            'total_courses' => $total,
            'cached_courses' => $cached,
            'cache_hit_rate' => $total > 0 ? round(($cached / $total) * 100, 2) : 0,
            'ttl_seconds' => self::CACHE_TTL
        ];
    }
}