<?php

namespace App\Services;

use App\Models\Course;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class QuizMistakeCacheService
{
    /**
     * Get supporting data with smart caching
     * FIXED: Extracted from fat controller for better separation of concerns
     */
    public function getSupportingData(int $userId): array
    {
        $cacheKey = "mistakes_support_smart_{$userId}";
        
        return Cache::remember($cacheKey, 600, function() use ($userId) {
            return $this->getSupportingDataFromDatabase($userId);
        });
    }

    /**
     * FIXED: Extract supporting data query logic to eliminate duplication
     */
    private function getSupportingDataFromDatabase(int $userId): array
    {
        $coursesWithCounts = $this->getCoursesWithCounts($userId);
        $totalCount = $this->getTotalMistakeCount($userId);
        $skippedCount = $this->getSkippedMistakeCount($userId);
        $recentCourse = $this->getRecentCourse($userId);

        return [
            'courses' => $coursesWithCounts,
            'total_count' => $totalCount,
            'skipped_count' => $skippedCount,
            'recent_course' => $recentCourse
        ];
    }

    /**
     * FIXED: Extract courses with counts query to eliminate duplication
     */
    private function getCoursesWithCounts(int $userId): Collection
    {
        return DB::table('mistakes')
            ->join('questions', 'mistakes.question_id', '=', 'questions.id')
            ->join('topics', 'questions.topic_id', '=', 'topics.id')
            ->join('courses', 'topics.course_id', '=', 'courses.id')
            ->where('mistakes.user_id', $userId)
            ->select('courses.id', 'courses.name', DB::raw('count(*) as mistake_count'))
            ->groupBy('courses.id', 'courses.name')
            ->orderBy('courses.name')
            ->get();
    }

    /**
     * FIXED: Extract total count query to eliminate duplication
     */
    private function getTotalMistakeCount(int $userId): int
    {
        return DB::table('mistakes')
            ->where('user_id', $userId)
            ->count();
    }

    /**
     * FIXED: Extract skipped count query to eliminate duplication
     */
    private function getSkippedMistakeCount(int $userId): int
    {
        return DB::table('mistakes')
            ->where('user_id', $userId)
            ->where(function($query) {
                $query->where('skipped', true)
                      ->orWhere('submitted_answer', 'unanswered')
                      ->orWhere('submitted_answer', '')
                      ->orWhereNull('submitted_answer');
            })
            ->count();
    }

    /**
     * FIXED: Extract recent course query to eliminate duplication
     */
    private function getRecentCourse(int $userId)
    {
        return DB::table('quiz_attempts')
            ->join('topics', 'quiz_attempts.topic_id', '=', 'topics.id')
            ->join('courses', 'topics.course_id', '=', 'courses.id')
            ->where('quiz_attempts.user_id', $userId)
            ->whereNotNull('quiz_attempts.topic_id')
            ->select('courses.id', 'courses.name')
            ->orderByDesc('quiz_attempts.created_at')
            ->first();
    }

    /**
     * Clear user caches
     * FIXED: Extracted from fat controller for better separation of concerns
     */
    public function clearUserCaches(int $userId): void
    {
        $cacheKeys = $this->generateCacheKeys($userId);
        
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * FIXED: Extract cache key generation to eliminate duplication
     */
    private function generateCacheKeys(int $userId): array
    {
        $cacheKeys = [
            "mistakes_smart_{$userId}__v3",           // No course filter
            "mistakes_smart_{$userId}_skipped_v3",    // Skipped filter
            "mistakes_support_smart_{$userId}",       // Supporting data cache
        ];
        
        // Get all course IDs and clear their specific caches
        $courseIds = Course::pluck('id')->toArray();
        foreach ($courseIds as $courseId) {
            $cacheKeys[] = "mistakes_smart_{$userId}_{$courseId}_v3";
        }
        
        return $cacheKeys;
    }
}
