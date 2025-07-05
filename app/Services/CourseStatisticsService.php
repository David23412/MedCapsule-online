<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CourseStatisticsService
{
    private const CACHE_TTL = 3600; // 1 hour cache

    public function getCourseStatistics(int $courseId): array
    {
        return Cache::remember("course_stats_{$courseId}", self::CACHE_TTL, function () use ($courseId) {
            return [
                'topics_count' => $this->getTopicsCount($courseId),
                'questions_count' => $this->getQuestionsCount($courseId),
                'enrolled_students_count' => $this->getEnrolledStudentsCount($courseId),
                'completion_percentage' => $this->getCompletionPercentage($courseId)
            ];
        });
    }

    private function getTopicsCount(int $courseId): int
    {
        return Topic::where('course_id', $courseId)->count();
    }

    private function getQuestionsCount(int $courseId): int
    {
        return Question::whereHas('topic', function($query) use ($courseId) {
            $query->where('course_id', $courseId);
        })->count();
    }

    private function getEnrolledStudentsCount(int $courseId): int
    {
        return DB::table('course_user')
            ->where('course_id', $courseId)
            ->count();
    }

    public function getCourseCompletionPercentage($courseId)
    {
        $totalQuestions = $this->getQuestionsCount($courseId);
        if ($totalQuestions === 0) {
            return 0;
        }

        // Fallback: Just return 0 or a placeholder, since spaced_repetitions table does not exist
        // You can implement your own logic here if needed
        return 0;
    }

    public function clearCourseCache(int $courseId): void
    {
        Cache::forget("course_stats_{$courseId}");
    }
} 