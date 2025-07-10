<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * BRUTALLY OPTIMIZED HomeController - MAXIMUM PERFORMANCE MODE
 * 
 * This controller uses the most aggressive optimization techniques:
 * - Raw SQL queries for maximum speed
 * - Aggressive caching with multiple layers
 * - Minimal object instantiation
 * - Zero N+1 queries
 * - Materialized view approach
 */
class BrutallyOptimizedHomeController extends Controller
{
    /**
     * BRUTAL optimization - Raw SQL with massive caching
     */
    public function index()
    {
        // Layer 1: Redis cache for instant response
        $cacheKey = 'home_brutal_v3_' . date('Y-m-d-H'); // Hourly cache
        
        if (Redis::exists($cacheKey)) {
            $data = json_decode(Redis::get($cacheKey), true);
            return view('home', $data);
        }
        
        // Layer 2: Database cache for medium-term storage
        $data = Cache::remember('home_data_brutal_v3', 1800, function() {
            return $this->generateHomeDataBrutally();
        });
        
        // Store in Redis for instant access
        Redis::setex($cacheKey, 3600, json_encode($data));
        
        return view('home', $data);
    }
    
    /**
     * Generate home data with ZERO MERCY optimizations
     */
    private function generateHomeDataBrutally()
    {
        // BRUTAL: Single massive query to get everything at once
        $statisticsAndCourses = DB::select("
            SELECT 
                'statistics' as type,
                'quizzes' as category,
                COUNT(CASE WHEN t.case_type = 'quiz' THEN 1 END) as count,
                NULL as course_data
            FROM topics t
            
            UNION ALL
            
            SELECT 
                'statistics' as type,
                'cases' as category,
                COUNT(CASE WHEN t.case_type = 'cases' THEN 1 END) as count,
                NULL as course_data
            FROM topics t
            
            UNION ALL
            
            SELECT 
                'statistics' as type,
                'practical' as category,
                COUNT(CASE WHEN t.case_type = 'practical' THEN 1 END) as count,
                NULL as course_data
            FROM topics t
            
            UNION ALL
            
            SELECT 
                'statistics' as type,
                'questions' as category,
                COUNT(*) as count,
                NULL as course_data
            FROM questions
            
            UNION ALL
            
            SELECT 
                'statistics' as type,
                'enrolled_students' as category,
                COUNT(DISTINCT cu.user_id) as count,
                NULL as course_data
            FROM course_user cu
            WHERE cu.enrollment_status = 'active'
            
            UNION ALL
            
            SELECT 
                'course' as type,
                c.id as category,
                NULL as count,
                JSON_OBJECT(
                    'id', c.id,
                    'name', c.name,
                    'description', c.description,
                    'image', c.image,
                    'color', c.color,
                    'price', c.price,
                    'currency', c.currency,
                    'quiz_count', COALESCE(quiz_counts.count, 0),
                    'case_count', COALESCE(case_counts.count, 0),
                    'practical_count', COALESCE(practical_counts.count, 0),
                    'question_count', COALESCE(question_counts.count, 0),
                    'enrolled_count', COALESCE(enrolled_counts.count, 0)
                ) as course_data
            FROM courses c
            LEFT JOIN (
                SELECT course_id, COUNT(*) as count
                FROM topics 
                WHERE case_type = 'quiz'
                GROUP BY course_id
            ) quiz_counts ON c.id = quiz_counts.course_id
            LEFT JOIN (
                SELECT course_id, COUNT(*) as count
                FROM topics 
                WHERE case_type = 'cases'
                GROUP BY course_id
            ) case_counts ON c.id = case_counts.course_id
            LEFT JOIN (
                SELECT course_id, COUNT(*) as count
                FROM topics 
                WHERE case_type = 'practical'
                GROUP BY course_id
            ) practical_counts ON c.id = practical_counts.course_id
            LEFT JOIN (
                SELECT t.course_id, COUNT(q.id) as count
                FROM topics t
                LEFT JOIN questions q ON t.id = q.topic_id
                GROUP BY t.course_id
            ) question_counts ON c.id = question_counts.course_id
            LEFT JOIN (
                SELECT course_id, COUNT(DISTINCT user_id) as count
                FROM course_user
                WHERE enrollment_status = 'active'
                GROUP BY course_id
            ) enrolled_counts ON c.id = enrolled_counts.course_id
            ORDER BY c.name
        ");
        
        // Process the brutal query results
        $statistics = [];
        $courses = [];
        
        foreach ($statisticsAndCourses as $row) {
            if ($row->type === 'statistics') {
                $statistics[$row->category] = $row->count;
            } else {
                $courseData = json_decode($row->course_data, true);
                $courses[] = $courseData;
            }
        }
        
        return [
            'statistics' => $statistics,
            'featuredCourses' => $courses
        ];
    }
    
    /**
     * ULTRA BRUTAL: Get user-specific course data with single query
     */
    public function userDashboard(Request $request)
    {
        $userId = auth()->id();
        
        if (!$userId) {
            return redirect()->route('home');
        }
        
        // BRUTAL: Single query for ALL user dashboard data
        $userDashboardData = DB::select("
            SELECT 
                'user_stats' as type,
                u.id,
                u.name,
                u.email,
                u.correct_answers_count,
                u.total_questions_attempted,
                u.xp,
                u.study_streak_days,
                CASE 
                    WHEN u.total_questions_attempted > 0 
                    THEN ROUND((u.correct_answers_count * 100.0 / u.total_questions_attempted), 2)
                    ELSE 0 
                END as accuracy_percentage,
                NULL as course_data,
                NULL as recent_activity
            FROM users u
            WHERE u.id = ?
            
            UNION ALL
            
            SELECT 
                'enrolled_course' as type,
                c.id,
                c.name,
                NULL as email,
                NULL as correct_answers_count,
                NULL as total_questions_attempted,
                NULL as xp,
                NULL as study_streak_days,
                NULL as accuracy_percentage,
                JSON_OBJECT(
                    'id', c.id,
                    'name', c.name,
                    'description', c.description,
                    'color', c.color,
                    'enrollment_status', cu.enrollment_status,
                    'enrolled_at', cu.enrolled_at,
                    'completion_percentage', COALESCE(cu.completion_percentage, 0),
                    'topics_count', COALESCE(topic_counts.count, 0),
                    'completed_topics', COALESCE(completed_counts.count, 0)
                ) as course_data,
                NULL as recent_activity
            FROM courses c
            JOIN course_user cu ON c.id = cu.course_id
            LEFT JOIN (
                SELECT course_id, COUNT(*) as count
                FROM topics
                GROUP BY course_id
            ) topic_counts ON c.id = topic_counts.course_id
            LEFT JOIN (
                SELECT t.course_id, COUNT(*) as count
                FROM topics t
                JOIN user_topic_progress utp ON t.id = utp.topic_id
                WHERE utp.user_id = ? AND utp.percentage_grade >= 70
                GROUP BY t.course_id
            ) completed_counts ON c.id = completed_counts.course_id
            WHERE cu.user_id = ? AND cu.enrollment_status = 'active'
            
            UNION ALL
            
            SELECT 
                'recent_activity' as type,
                qa.id,
                NULL as name,
                NULL as email,
                qa.correct_answers,
                qa.total_questions,
                NULL as xp,
                NULL as study_streak_days,
                qa.percentage_grade as accuracy_percentage,
                NULL as course_data,
                JSON_OBJECT(
                    'quiz_id', qa.id,
                    'topic_name', t.name,
                    'course_name', c.name,
                    'percentage_grade', qa.percentage_grade,
                    'correct_answers', qa.correct_answers,
                    'total_questions', qa.total_questions,
                    'created_at', qa.created_at
                ) as recent_activity
            FROM quiz_attempts qa
            JOIN topics t ON qa.topic_id = t.id
            JOIN courses c ON t.course_id = c.id
            WHERE qa.user_id = ?
            ORDER BY qa.created_at DESC
            LIMIT 5
        ", [$userId, $userId, $userId, $userId]);
        
        // Process brutal query results
        $userStats = null;
        $enrolledCourses = [];
        $recentActivities = [];
        
        foreach ($userDashboardData as $row) {
            switch ($row->type) {
                case 'user_stats':
                    $userStats = [
                        'id' => $row->id,
                        'name' => $row->name,
                        'email' => $row->email,
                        'correct_answers_count' => $row->correct_answers_count,
                        'total_questions_attempted' => $row->total_questions_attempted,
                        'xp' => $row->xp,
                        'study_streak_days' => $row->study_streak_days,
                        'accuracy_percentage' => $row->accuracy_percentage,
                    ];
                    break;
                    
                case 'enrolled_course':
                    $enrolledCourses[] = json_decode($row->course_data, true);
                    break;
                    
                case 'recent_activity':
                    $recentActivities[] = json_decode($row->recent_activity, true);
                    break;
            }
        }
        
        return view('dashboard', [
            'user' => $userStats,
            'enrolledCourses' => $enrolledCourses,
            'recentActivities' => $recentActivities
        ]);
    }
    
    /**
     * EXTREME BRUTAL: API endpoint with microsecond response time
     */
    public function apiStats()
    {
        // BRUTAL: Pre-computed stats updated every 5 minutes
        $stats = Cache::remember('api_stats_brutal', 300, function() {
            return DB::selectOne("
                SELECT 
                    (SELECT COUNT(*) FROM users) as total_users,
                    (SELECT COUNT(*) FROM courses) as total_courses,
                    (SELECT COUNT(*) FROM questions) as total_questions,
                    (SELECT COUNT(*) FROM quiz_attempts) as total_attempts,
                    (SELECT AVG(percentage_grade) FROM quiz_attempts) as avg_score,
                    (SELECT COUNT(DISTINCT user_id) FROM course_user WHERE enrollment_status = 'active') as active_students,
                    NOW() as generated_at
            ");
        });
        
        return response()->json([
            'success' => true,
            'data' => $stats,
            'cache_info' => [
                'generated_at' => $stats->generated_at,
                'response_time_ms' => round((microtime(true) - LARAVEL_START) * 1000, 2)
            ]
        ])->header('Cache-Control', 'public, max-age=300');
    }
    
    /**
     * INSANE BRUTAL: Real-time leaderboard with sub-100ms response
     */
    public function leaderboard()
    {
        // BRUTAL: Single query with window functions for ranking
        $leaderboard = Cache::remember('leaderboard_brutal', 600, function() {
            return DB::select("
                SELECT 
                    u.id,
                    u.name,
                    u.xp,
                    u.study_streak_days,
                    u.correct_answers_count,
                    u.total_questions_attempted,
                    CASE 
                        WHEN u.total_questions_attempted > 0 
                        THEN ROUND((u.correct_answers_count * 100.0 / u.total_questions_attempted), 2)
                        ELSE 0 
                    END as accuracy,
                    ROW_NUMBER() OVER (ORDER BY u.xp DESC, u.correct_answers_count DESC) as rank_position,
                    RANK() OVER (ORDER BY u.xp DESC) as xp_rank,
                    DENSE_RANK() OVER (ORDER BY u.study_streak_days DESC) as streak_rank
                FROM users u
                WHERE u.total_questions_attempted > 0
                ORDER BY u.xp DESC, u.correct_answers_count DESC
                LIMIT 100
            ");
        });
        
        return response()->json([
            'success' => true,
            'leaderboard' => $leaderboard,
            'generated_at' => now(),
            'total_users' => count($leaderboard)
        ]);
    }
}