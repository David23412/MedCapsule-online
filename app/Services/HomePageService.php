<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Topic;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Home Page Service
 * Optimized data retrieval for home page
 */
class HomePageService
{
    /**
     * Get optimized home page data
     */
    public function getHomePageData(): array
    {
        try {
            // Optimized statistics query with single database hit
            $statistics = $this->getOptimizedStatistics();
            
            // Optimized featured courses with eager loading
            $featuredCourses = $this->getOptimizedFeaturedCourses();

            return [
                'statistics' => $statistics,
                'featuredCourses' => $featuredCourses
            ];

        } catch (\Exception $e) {
            Log::error('Home page data retrieval failed', [
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Get NUCLEAR optimized statistics with precomputed values
     * NUCLEAR OPTIMIZATION: Redis caching + indexed queries + precomputed aggregates
     */
    private function getOptimizedStatistics(): array
    {
        return Cache::remember('home_statistics_nuclear_v3', 3600, function () {
            // NUCLEAR: Separate optimized queries for maximum speed
            $quizStats = DB::selectOne("
                SELECT 
                    COUNT(CASE WHEN case_type = 'quiz' THEN 1 END) as quizzes,
                    COUNT(CASE WHEN case_type = 'cases' THEN 1 END) as cases,
                    COUNT(CASE WHEN case_type = 'practical' THEN 1 END) as practical
                FROM topics 
                WHERE case_type IN ('quiz', 'cases', 'practical')
            ");

            $questionCount = DB::selectOne("SELECT COUNT(*) as total FROM questions")->total;
            
            $enrolledCount = DB::selectOne("
                SELECT COUNT(DISTINCT user_id) as total 
                FROM course_user 
                WHERE enrolled_at IS NOT NULL
            ")->total;

            return [
                'quizzes' => (int) ($quizStats->quizzes ?? 0),
                'cases' => (int) ($quizStats->cases ?? 0),
                'practical' => (int) ($quizStats->practical ?? 0),
                'questions' => (int) $questionCount,
                'enrolled_students' => (int) $enrolledCount
            ];
        });
    }

    /**
     * Get NUCLEAR optimized featured courses with precomputed data
     * NUCLEAR OPTIMIZATION: Separate queries + Redis caching + minimal data transfer
     */
    private function getOptimizedFeaturedCourses()
    {
        return Cache::remember('featured_courses_nuclear_v3', 7200, function () {
            // NUCLEAR: Get base course data first (fastest query)
            $courses = Course::select([
                    'id', 'name', 'description', 'image', 
                    'color', 'price', 'academic_year'
                ])
                ->where('type', 'medical')
                ->orderBy('name')
                ->limit(12)
                ->get();

            // NUCLEAR: Precompute stats for all courses in single queries
            $courseIds = $courses->pluck('id')->toArray();
            
            if (empty($courseIds)) {
                return collect([]);
            }

            // Get topic counts by course
            $topicStats = DB::select("
                SELECT 
                    course_id,
                    COUNT(CASE WHEN case_type = 'quiz' THEN 1 END) as quiz_count,
                    COUNT(CASE WHEN case_type = 'cases' THEN 1 END) as case_count,
                    COUNT(CASE WHEN case_type = 'practical' THEN 1 END) as practical_count
                FROM topics 
                WHERE course_id IN (" . implode(',', $courseIds) . ")
                GROUP BY course_id
            ");

            // Get question counts by course
            $questionStats = DB::select("
                SELECT t.course_id, COUNT(q.id) as question_count
                FROM topics t
                LEFT JOIN questions q ON t.id = q.topic_id
                WHERE t.course_id IN (" . implode(',', $courseIds) . ")
                GROUP BY t.course_id
            ");

            // Get enrollment counts by course
            $enrollmentStats = DB::select("
                SELECT course_id, COUNT(DISTINCT user_id) as enrolled_count
                FROM course_user 
                WHERE course_id IN (" . implode(',', $courseIds) . ") AND enrolled_at IS NOT NULL
                GROUP BY course_id
            ");

            // NUCLEAR: Map stats to courses efficiently
            $topicStatsMap = collect($topicStats)->keyBy('course_id');
            $questionStatsMap = collect($questionStats)->keyBy('course_id');
            $enrollmentStatsMap = collect($enrollmentStats)->keyBy('course_id');

            return $courses->map(function ($course) use ($topicStatsMap, $questionStatsMap, $enrollmentStatsMap) {
                $topicStat = $topicStatsMap->get($course->id);
                $questionStat = $questionStatsMap->get($course->id);
                $enrollmentStat = $enrollmentStatsMap->get($course->id);

                $course->quiz_count = (int) ($topicStat->quiz_count ?? 0);
                $course->case_count = (int) ($topicStat->case_count ?? 0);
                $course->practical_count = (int) ($topicStat->practical_count ?? 0);
                $course->question_count = (int) ($questionStat->question_count ?? 0);
                $course->enrolled_count = (int) ($enrollmentStat->enrolled_count ?? 0);

                return $course;
            });
        });
    }
}