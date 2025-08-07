<?php

namespace App\Services;

use App\Models\User;
use App\Models\QuizAttempt;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Ultra-Optimized Student Progress Service
 * Lightning-speed student progress analytics
 */
class StudentProgressService
{
    /**
     * Get comprehensive student progress with optimized queries
     */
    public function getComprehensiveProgress(int $studentId, array $params): array
    {
        try {
            // Single optimized query for all progress data
            $progressData = DB::select("
                SELECT 
                    u.id as student_id,
                    u.name as student_name,
                    COUNT(DISTINCT qa.id) as total_quizzes,
                    AVG(qa.score) as average_score,
                    COUNT(DISTINCT CASE WHEN qa.score >= 70 THEN qa.id END) as passed_quizzes,
                    COUNT(DISTINCT qa.course_id) as courses_attempted,
                    SUM(qa.time_taken) as total_time_spent,
                    MAX(qa.created_at) as last_activity
                FROM users u
                LEFT JOIN quiz_attempts qa ON u.id = qa.user_id
                WHERE u.id = ? AND qa.created_at >= ?
                GROUP BY u.id, u.name
            ", [$studentId, $this->getDateRangeStart($params['date_range'])]);

            if (empty($progressData)) {
                return $this->getEmptyProgressData($studentId);
            }

            $data = $progressData[0];

            return [
                'student_info' => [
                    'id' => $data->student_id,
                    'name' => $data->student_name,
                    'last_activity' => $data->last_activity
                ],
                'performance_metrics' => [
                    'total_quizzes' => (int) $data->total_quizzes,
                    'average_score' => round((float) $data->average_score, 2),
                    'passed_quizzes' => (int) $data->passed_quizzes,
                    'pass_rate' => $data->total_quizzes > 0 ? round(($data->passed_quizzes / $data->total_quizzes) * 100, 2) : 0,
                    'courses_attempted' => (int) $data->courses_attempted,
                    'total_time_spent' => (int) $data->total_time_spent
                ],
                'detailed_analytics' => $params['include_details'] ? $this->getDetailedAnalytics($studentId, $params) : []
            ];

        } catch (\Exception $e) {
            Log::error('Comprehensive progress retrieval failed', [
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Get real-time updates with minimal database hits
     */
    public function getRealtimeUpdates(int $studentId, array $params): array
    {
        try {
            // Ultra-fast query for recent activity
            $recentActivity = DB::select("
                SELECT 
                    qa.id,
                    qa.score,
                    qa.time_taken,
                    qa.created_at,
                    c.name as course_name,
                    t.name as topic_name
                FROM quiz_attempts qa
                JOIN courses c ON qa.course_id = c.id
                JOIN topics t ON qa.topic_id = t.id
                WHERE qa.user_id = ? AND qa.created_at >= ?
                ORDER BY qa.created_at DESC
                LIMIT 10
            ", [$studentId, now()->subHours(24)]);

            return [
                'recent_activity' => $recentActivity,
                'last_updated' => now()->toISOString(),
                'activity_count' => count($recentActivity)
            ];

        } catch (\Exception $e) {
            Log::error('Real-time updates failed', [
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'recent_activity' => [],
                'last_updated' => now()->toISOString(),
                'activity_count' => 0
            ];
        }
    }

    /**
     * Get performance analytics with optimized aggregations
     */
    public function getPerformanceAnalytics(int $studentId, array $params): array
    {
        try {
            // Optimized analytics query
            $analytics = DB::select("
                SELECT 
                    DATE(qa.created_at) as date,
                    COUNT(qa.id) as quiz_count,
                    AVG(qa.score) as avg_score,
                    SUM(qa.time_taken) as total_time
                FROM quiz_attempts qa
                WHERE qa.user_id = ? AND qa.created_at >= ?
                GROUP BY DATE(qa.created_at)
                ORDER BY date DESC
                LIMIT 30
            ", [$studentId, $this->getDateRangeStart($params['date_range'])]);

            return [
                'daily_analytics' => $analytics,
                'trends' => $this->calculateTrends($analytics),
                'generated_at' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            Log::error('Performance analytics failed', [
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'daily_analytics' => [],
                'trends' => [],
                'generated_at' => now()->toISOString()
            ];
        }
    }

    /**
     * Get detailed analytics with eager loading
     */
    private function getDetailedAnalytics(int $studentId, array $params): array
    {
        // Course-specific performance
        $coursePerformance = DB::select("
            SELECT 
                c.id,
                c.name,
                COUNT(qa.id) as attempts,
                AVG(qa.score) as avg_score,
                MAX(qa.score) as best_score
            FROM courses c
            LEFT JOIN quiz_attempts qa ON c.id = qa.course_id AND qa.user_id = ?
            WHERE qa.created_at >= ?
            GROUP BY c.id, c.name
            HAVING attempts > 0
            ORDER BY avg_score DESC
        ", [$studentId, $this->getDateRangeStart($params['date_range'])]);

        return [
            'course_performance' => $coursePerformance,
            'strengths_weaknesses' => $this->identifyStrengthsWeaknesses($coursePerformance)
        ];
    }

    /**
     * Get date range start based on parameter
     */
    private function getDateRangeStart(string $range): Carbon
    {
        return match($range) {
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'quarter' => now()->subMonths(3),
            'year' => now()->subYear(),
            default => now()->subMonth()
        };
    }

    /**
     * Calculate performance trends
     */
    private function calculateTrends(array $analytics): array
    {
        if (count($analytics) < 2) {
            return ['trend' => 'insufficient_data'];
        }

        $recent = array_slice($analytics, 0, 7);
        $previous = array_slice($analytics, 7, 7);

        $recentAvg = collect($recent)->avg('avg_score') ?? 0;
        $previousAvg = collect($previous)->avg('avg_score') ?? 0;

        $trend = $recentAvg > $previousAvg ? 'improving' : ($recentAvg < $previousAvg ? 'declining' : 'stable');

        return [
            'trend' => $trend,
            'recent_average' => round($recentAvg, 2),
            'previous_average' => round($previousAvg, 2),
            'change_percentage' => $previousAvg > 0 ? round((($recentAvg - $previousAvg) / $previousAvg) * 100, 2) : 0
        ];
    }

    /**
     * Identify strengths and weaknesses
     */
    private function identifyStrengthsWeaknesses(array $coursePerformance): array
    {
        $courses = collect($coursePerformance);
        
        return [
            'strengths' => $courses->where('avg_score', '>=', 80)->pluck('name')->toArray(),
            'needs_improvement' => $courses->where('avg_score', '<', 70)->pluck('name')->toArray(),
            'most_attempted' => $courses->sortByDesc('attempts')->first()->name ?? 'N/A'
        ];
    }

    /**
     * Get comparison analytics for student performance
     */
    public function getComparisonAnalytics(int $studentId, array $params): array
    {
        try {
            // Get peer comparison data with optimized query
            $peerComparison = DB::select("
                SELECT 
                    AVG(qa.score) as peer_average,
                    COUNT(DISTINCT qa.user_id) as peer_count,
                    PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY qa.score) as median_score
                FROM quiz_attempts qa
                JOIN users u ON qa.user_id = u.id
                WHERE qa.created_at >= ? AND u.id != ?
                AND EXISTS (
                    SELECT 1 FROM quiz_attempts qa2 
                    WHERE qa2.user_id = ? AND qa2.course_id = qa.course_id
                )
            ", [$this->getDateRangeStart($params['date_range']), $studentId, $studentId]);

            $studentData = DB::select("
                SELECT AVG(score) as student_average
                FROM quiz_attempts 
                WHERE user_id = ? AND created_at >= ?
            ", [$studentId, $this->getDateRangeStart($params['date_range'])]);

            return [
                'student_average' => round((float) ($studentData[0]->student_average ?? 0), 2),
                'peer_average' => round((float) ($peerComparison[0]->peer_average ?? 0), 2),
                'peer_count' => (int) ($peerComparison[0]->peer_count ?? 0),
                'median_score' => round((float) ($peerComparison[0]->median_score ?? 0), 2),
                'performance_ranking' => $this->calculatePerformanceRanking($studentId, $params)
            ];

        } catch (\Exception $e) {
            Log::error('Comparison analytics failed', [
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'student_average' => 0,
                'peer_average' => 0,
                'peer_count' => 0,
                'median_score' => 0,
                'performance_ranking' => 'N/A'
            ];
        }
    }

    /**
     * Generate export data for student progress
     */
    public function generateExportData(int $studentId, array $params): array
    {
        try {
            // Comprehensive export query
            $exportData = DB::select("
                SELECT 
                    qa.id,
                    qa.score,
                    qa.time_taken,
                    qa.created_at,
                    c.name as course_name,
                    t.name as topic_name,
                    q.name as quiz_name,
                    q.difficulty_level
                FROM quiz_attempts qa
                JOIN courses c ON qa.course_id = c.id
                JOIN topics t ON qa.topic_id = t.id
                JOIN quizzes q ON qa.quiz_id = q.id
                WHERE qa.user_id = ? AND qa.created_at >= ?
                ORDER BY qa.created_at DESC
            ", [$studentId, $this->getDateRangeStart($params['date_range'])]);

            return [
                'student_id' => $studentId,
                'export_date' => now()->toISOString(),
                'date_range' => $params['date_range'],
                'total_records' => count($exportData),
                'quiz_attempts' => $exportData,
                'summary' => $this->generateExportSummary($exportData)
            ];

        } catch (\Exception $e) {
            Log::error('Export data generation failed', [
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Bulk update progress for multiple students
     */
    public function bulkUpdateProgress(array $studentIds, array $params): array
    {
        try {
            $results = [];
            
            foreach ($studentIds as $studentId) {
                try {
                    $progressData = $this->getComprehensiveProgress($studentId, $params);
                    $results[$studentId] = [
                        'success' => true,
                        'data' => $progressData
                    ];
                } catch (\Exception $e) {
                    $results[$studentId] = [
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }

            return $results;

        } catch (\Exception $e) {
            Log::error('Bulk progress update failed', [
                'student_ids' => $studentIds,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Get progress summary for tutor's students
     */
    public function getProgressSummary(int $tutorId, array $params): array
    {
        try {
            // Get tutor's students with course-based filtering
            $tutor = User::find($tutorId);
            $tutorCourseIds = $tutor->courses()->pluck('courses.id')->toArray();
            
            if (empty($tutorCourseIds)) {
                return [
                    'total_students' => 0,
                    'students' => [],
                    'overall_stats' => $this->calculateOverallStats([]),
                    'generated_at' => now()->toISOString()
                ];
            }
            
            $coursePlaceholders = str_repeat('?,', count($tutorCourseIds) - 1) . '?';
            
            $summaryData = DB::select("
                SELECT 
                    u.id,
                    u.name,
                    COUNT(DISTINCT qa.id) as total_attempts,
                    AVG(qa.score) as average_score,
                    MAX(qa.created_at) as last_activity,
                    COUNT(DISTINCT qa.course_id) as courses_attempted
                FROM users u
                JOIN course_user cu ON u.id = cu.user_id
                LEFT JOIN quiz_attempts qa ON u.id = qa.user_id AND qa.created_at >= ?
                WHERE u.role = 'student' 
                AND cu.course_id IN ({$coursePlaceholders})
                AND cu.is_enrolled = 1
                GROUP BY u.id, u.name
                ORDER BY average_score DESC
            ", array_merge([$this->getDateRangeStart($params['date_range'])], $tutorCourseIds));

            return [
                'total_students' => count($summaryData),
                'students' => $summaryData,
                'overall_stats' => $this->calculateOverallStats($summaryData),
                'generated_at' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            Log::error('Progress summary failed', [
                'tutor_id' => $tutorId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Calculate performance ranking
     */
    private function calculatePerformanceRanking(int $studentId, array $params): string
    {
        $ranking = DB::select("
            SELECT 
                COUNT(*) + 1 as rank,
                (SELECT COUNT(DISTINCT user_id) FROM quiz_attempts WHERE created_at >= ?) as total_students
            FROM quiz_attempts qa1
            WHERE qa1.created_at >= ?
            AND (
                SELECT AVG(score) FROM quiz_attempts qa2 
                WHERE qa2.user_id = qa1.user_id AND qa2.created_at >= ?
            ) > (
                SELECT AVG(score) FROM quiz_attempts qa3 
                WHERE qa3.user_id = ? AND qa3.created_at >= ?
            )
        ", [
            $this->getDateRangeStart($params['date_range']),
            $this->getDateRangeStart($params['date_range']),
            $this->getDateRangeStart($params['date_range']),
            $studentId,
            $this->getDateRangeStart($params['date_range'])
        ]);

        if (empty($ranking)) {
            return 'N/A';
        }

        $rank = $ranking[0]->rank;
        $total = $ranking[0]->total_students;
        
        return "#{$rank} of {$total}";
    }

    /**
     * Generate export summary
     */
    private function generateExportSummary(array $exportData): array
    {
        $data = collect($exportData);
        
        return [
            'total_attempts' => $data->count(),
            'average_score' => round($data->avg('score'), 2),
            'highest_score' => $data->max('score'),
            'lowest_score' => $data->min('score'),
            'total_time_spent' => $data->sum('time_taken'),
            'courses_covered' => $data->pluck('course_name')->unique()->count(),
            'topics_covered' => $data->pluck('topic_name')->unique()->count()
        ];
    }

    /**
     * Calculate overall statistics
     */
    private function calculateOverallStats(array $summaryData): array
    {
        $data = collect($summaryData);
        
        return [
            'average_score_across_students' => round($data->avg('average_score'), 2),
            'total_attempts_all_students' => $data->sum('total_attempts'),
            'most_active_student' => $data->sortByDesc('total_attempts')->first()->name ?? 'N/A',
            'highest_performer' => $data->sortByDesc('average_score')->first()->name ?? 'N/A'
        ];
    }

    /**
     * Get empty progress data structure
     */
    private function getEmptyProgressData(int $studentId): array
    {
        $student = User::find($studentId);
        
        return [
            'student_info' => [
                'id' => $studentId,
                'name' => $student->name ?? 'Unknown',
                'last_activity' => null
            ],
            'performance_metrics' => [
                'total_quizzes' => 0,
                'average_score' => 0,
                'passed_quizzes' => 0,
                'pass_rate' => 0,
                'courses_attempted' => 0,
                'total_time_spent' => 0
            ],
            'detailed_analytics' => []
        ];
    }
}