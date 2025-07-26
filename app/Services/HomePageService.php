<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Topic;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
     */
    private function getOptimizedStatistics(): array
    {
        // Single optimized query for all statistics
        $stats = DB::select("
            SELECT 
                COUNT(DISTINCT CASE WHEN t.case_type = 'quiz' THEN t.id END) as quizzes,
                COUNT(DISTINCT CASE WHEN t.case_type = 'cases' THEN t.id END) as cases,
                COUNT(DISTINCT CASE WHEN t.case_type = 'practical' THEN t.id END) as practical,
                COUNT(DISTINCT q.id) as questions,
                COUNT(DISTINCT CASE WHEN cu.enrollment_status = 'active' THEN cu.user_id END) as enrolled_students
            FROM topics t
            LEFT JOIN questions q ON t.id = q.topic_id
            LEFT JOIN course_user cu ON t.course_id = cu.course_id AND cu.enrollment_status = 'active'
        ");

        return [
            'quizzes' => (int) $stats[0]->quizzes,
            'cases' => (int) $stats[0]->cases,
            'practical' => (int) $stats[0]->practical,
            'questions' => (int) $stats[0]->questions,
            'enrolled_students' => (int) $stats[0]->enrolled_students
        ];
    }

    /**
     * Get optimized featured courses with minimal queries
     */
    private function getOptimizedFeaturedCourses()
    {
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
                COUNT(DISTINCT CASE WHEN topics.case_type = "quiz" THEN topics.id END) as quiz_count,
                COUNT(DISTINCT CASE WHEN topics.case_type = "cases" THEN topics.id END) as case_count,
                COUNT(DISTINCT CASE WHEN topics.case_type = "practical" THEN topics.id END) as practical_count,
                COUNT(DISTINCT questions.id) as question_count,
                COUNT(DISTINCT CASE WHEN course_user.enrollment_status = "active" THEN course_user.user_id END) as enrolled_count
            ')
            ->leftJoin('topics', 'courses.id', '=', 'topics.course_id')
            ->leftJoin('questions', 'topics.id', '=', 'questions.topic_id')
            ->leftJoin('course_user', function($join) {
                $join->on('courses.id', '=', 'course_user.course_id')
                     ->where('course_user.enrollment_status', '=', 'active');
            })
            ->groupBy([
                'courses.id',
                'courses.name',
                'courses.description', 
                'courses.image',
                'courses.color',
                'courses.price',
                'courses.academic_year'
            ])
            ->orderBy('courses.name')
            ->limit(12) // Limit to reduce data transfer
            ->get();
    }
}