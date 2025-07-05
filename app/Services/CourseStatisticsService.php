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

    private function getCompletionPercentage(int $courseId): float
    {
        $totalQuestions = $this->getQuestionsCount($courseId);
        if ($totalQuestions === 0) {
            return 0;
        }

        $completedQuestions = DB::table('spaced_repetitions')
            ->join('questions', 'spaced_repetitions.question_id', '=', 'questions.id')
            ->join('topics', 'questions.topic_id', '=', 'topics.id')
            ->where('topics.course_id', $courseId)
            ->whereColumn('spaced_repetitions.submitted_answer', 'questions.correct_answer')
            ->count();

        return ($completedQuestions / $totalQuestions) * 100;
    }

    public function clearCourseCache(int $courseId): void
    {
        Cache::forget("course_stats_{$courseId}");
    }
} 