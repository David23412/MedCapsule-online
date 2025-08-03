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
     * Get optimized statistics with single query
     * RUTHLESS OPTIMIZATION: Aggressive caching + optimized query
     */
    private function getOptimizedStatistics(): array
    {
        return Cache::remember('home_statistics_optimized_v2', 1800, function () {
            // RUTHLESS: Single optimized query for all statistics
            $stats = DB::select("
                SELECT 
                    COALESCE(COUNT(DISTINCT CASE WHEN t.case_type = 'quiz' THEN t.id END), 0) as quizzes,
                    COALESCE(COUNT(DISTINCT CASE WHEN t.case_type = 'cases' THEN t.id END), 0) as cases,
                    COALESCE(COUNT(DISTINCT CASE WHEN t.case_type = 'practical' THEN t.id END), 0) as practical,
                    COALESCE(COUNT(DISTINCT q.id), 0) as questions,
                    COALESCE(COUNT(DISTINCT CASE WHEN cu.is_enrolled = 1 THEN cu.user_id END), 0) as enrolled_students
                FROM topics t
                LEFT JOIN questions q ON t.id = q.topic_id
                LEFT JOIN course_user cu ON t.course_id = cu.course_id AND cu.is_enrolled = 1
                WHERE t.id IS NOT NULL
            ");

            return [
                'quizzes' => (int) ($stats[0]->quizzes ?? 0),
                'cases' => (int) ($stats[0]->cases ?? 0),
                'practical' => (int) ($stats[0]->practical ?? 0),
                'questions' => (int) ($stats[0]->questions ?? 0),
                'enrolled_students' => (int) ($stats[0]->enrolled_students ?? 0)
            ];
        });
    }

    /**
     * Get optimized featured courses with minimal queries
     * RUTHLESS OPTIMIZATION: Single query with aggressive caching
     */
    private function getOptimizedFeaturedCourses()
    {
        return Cache::remember('featured_courses_optimized_v2', 1800, function () {
            return Course::select([
                    'courses.id',
                    'courses.name', 
                    'courses.description',
                    'courses.image',
                    'courses.color',
                    'courses.price',
                    'courses.academic_year'
                ])
                ->selectRaw('
                    COALESCE(COUNT(DISTINCT CASE WHEN topics.case_type = "quiz" THEN topics.id END), 0) as quiz_count,
                    COALESCE(COUNT(DISTINCT CASE WHEN topics.case_type = "cases" THEN topics.id END), 0) as case_count,
                    COALESCE(COUNT(DISTINCT CASE WHEN topics.case_type = "practical" THEN topics.id END), 0) as practical_count,
                    COALESCE(COUNT(DISTINCT questions.id), 0) as question_count,
                    COALESCE(COUNT(DISTINCT CASE WHEN course_user.is_enrolled = 1 THEN course_user.user_id END), 0) as enrolled_count
                ')
                ->leftJoin('topics', function($join) {
                    $join->on('courses.id', '=', 'topics.course_id');
                })
                ->leftJoin('questions', function($join) {
                    $join->on('topics.id', '=', 'questions.topic_id');
                })
                ->leftJoin('course_user', function($join) {
                    $join->on('courses.id', '=', 'course_user.course_id')
                         ->where('course_user.is_enrolled', '=', 1);
                })
                ->where('courses.type', 'medical')
                ->groupBy([
                    'courses.id', 'courses.name', 'courses.description', 
                    'courses.image', 'courses.color', 'courses.price', 'courses.academic_year'
                ])
                ->orderBy('courses.name')
                ->limit(12)
                ->get();
        });
    }
}