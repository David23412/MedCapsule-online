<?php

namespace App\Services;

use App\Models\User;
use App\Models\QuizAttempt;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * 🚀 PERFORMANCE BEAST: Profile Data Service
 * Handles: Data formatting and calculations for profile display
 * Extracted from ProfileController to follow SRP
 */
class ProfileDataService
{
    /**
     * Format study time from seconds to human-readable string
     */
    public function formatStudyTime($seconds): string
    {
        if (!$seconds) {
            return "0m";
        }
        
        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        if ($hours > 0) {
            return sprintf("%dh %dm", $hours, $remainingMinutes);
        }
        
        return sprintf("%dm", $minutes);
    }

    /**
     * Calculate study time breakdown by quiz type
     */
    public function calculateStudyTimeBreakdown(User $user): array
    {
        // PERFORMANCE: Single query with conditional aggregation
        $result = QuizAttempt::where('user_id', $user->id)
            ->selectRaw('
                SUM(CASE WHEN quiz_type = "normal" AND study_mode = "quiz" AND topic_id IS NOT NULL THEN duration_seconds ELSE 0 END) as quiz,
                SUM(CASE WHEN quiz_type = "normal" AND study_mode = "tutor" THEN duration_seconds ELSE 0 END) as tutor,
                SUM(CASE WHEN quiz_type = "normal" AND study_mode = "random" THEN duration_seconds ELSE 0 END) as random_quiz,
                SUM(CASE WHEN quiz_type = "mistake_review" OR study_mode = "mistake_review" THEN duration_seconds ELSE 0 END) as mistake_review,
                SUM(duration_seconds) as total
            ')
            ->first();
            
        return [
            'quiz' => $result->quiz ?? 0,
            'tutor' => $result->tutor ?? 0,
            'random_quiz' => $result->random_quiz ?? 0,
            'mistake_review' => $result->mistake_review ?? 0,
            'total' => $result->total ?? 0
        ];
    }

    /**
     * Get performance data for charts
     */
    public function getPerformanceData(User $user): array
    {
        return QuizAttempt::select('created_at as date', 'percentage_grade', 'quiz_type', 'topic_id')
            ->where('user_id', $user->id)
            ->where('quiz_type', '!=', 'mistake_review')
            ->orderBy('created_at', 'desc')
            ->take(7)
            ->get()
            ->sortBy('date')
            ->values()
            ->toArray();
    }

    /**
     * Format performance data for charts
     */
    public function formatChartData(array $performanceData): array
    {
        return collect($performanceData)->map(function($attempt) {
            return [
                'x' => Carbon::parse($attempt['date'])->timestamp * 1000,
                'y' => round($attempt['percentage_grade'] ?? 0, 1),
                'topic' => $attempt['topic'] ?? 'Unknown Topic',
                'date' => Carbon::parse($attempt['date'])->format('M j, Y g:i A')
            ];
        })->toArray();
    }

    /**
     * Get optimized performance data with caching
     */
    public function getOptimizedPerformanceData(User $user)
    {
        return Cache::remember("performance_data_optimized_{$user->id}", 1800, function() use ($user) { 
            return DB::table('quiz_attempts')
                ->leftJoin('topics', 'quiz_attempts.topic_id', '=', 'topics.id')
                ->where('quiz_attempts.user_id', $user->id)
                ->where('quiz_attempts.quiz_type', '!=', 'mistake_review')
                ->select(
                    'quiz_attempts.created_at as date',
                    DB::raw('COALESCE(quiz_attempts.percentage_grade, 0) as accuracy'),
                    DB::raw('CASE 
                        WHEN quiz_attempts.quiz_type = "random" THEN "Random Quiz"
                        WHEN quiz_attempts.topic_id IS NULL THEN "Unknown Topic"
                        ELSE COALESCE(topics.name, "Unknown Topic")
                    END as topic')
                )
                ->orderBy('quiz_attempts.created_at', 'desc')
                ->limit(7)
                ->get()
                ->sortBy('date')
                ->values();
        });
    }

    /**
     * Get weak topics data with optimized query
     */
    public function getWeakTopicsData(User $user)
    {
        $weakTopicsData = DB::table('topics')
            ->join('user_topic_progress', 'topics.id', '=', 'user_topic_progress.topic_id')
            ->join('courses', 'topics.course_id', '=', 'courses.id')
            ->where('user_topic_progress.user_id', $user->id)
            ->whereNotNull('user_topic_progress.percentage_grade')
            ->where('user_topic_progress.percentage_grade', '<', 100)
            ->select(
                'topics.id',
                'topics.name',
                'topics.course_id',
                'courses.name as course_name',
                'user_topic_progress.percentage_grade',
                DB::raw('
                    CASE 
                        WHEN user_topic_progress.percentage_grade <= 25 THEN 1
                        WHEN user_topic_progress.percentage_grade <= 49 THEN 2
                        WHEN user_topic_progress.percentage_grade <= 74 THEN 3
                        ELSE 4
                    END as priority
                ')
            )
            ->orderBy('priority')
            ->orderBy('user_topic_progress.percentage_grade')
            ->get();

        // Convert to collection with same structure as original
        return $weakTopicsData->map(function($topic) {
            return (object) [
                'id' => $topic->id,
                'name' => $topic->name,
                'course_id' => $topic->course_id,
                'course' => (object) [
                    'id' => $topic->course_id,
                    'name' => $topic->course_name
                ],
                'userProgress' => collect([(object) ['percentage_grade' => $topic->percentage_grade]])
            ];
        });
    }

    /**
     * Get leaderboard data with caching
     */
    public function getLeaderboardData()
    {
        return Cache::remember('leaderboard_students_optimized', 1800, function() { 
            return DB::table('users')
                ->select(
                    'users.id',
                    'users.name',
                    'users.total_questions_attempted',
                    'users.correct_answers_count',
                    'users.xp',
                    DB::raw('FLOOR(COALESCE(users.xp, 0) / 1000) + 1 as level')
                )
                ->where('users.total_questions_attempted', '>', 0)
                ->where('users.role', 'student')
                ->orderBy('xp', 'desc')
                ->limit(10)
                ->get();
        });
    }

    /**
     * Get user rank with caching
     */
    public function getUserRank(User $user, int $userXP): ?int
    {
        if ($userXP <= 0) {
            return null;
        }

        return Cache::remember("user_rank_optimized_{$user->id}", 1800, function() use ($userXP) { 
            return DB::table('users')
                ->where('total_questions_attempted', '>', 0)
                ->where('role', 'student')
                ->where('xp', '>', $userXP)
                ->count() + 1; 
        });
    }
}