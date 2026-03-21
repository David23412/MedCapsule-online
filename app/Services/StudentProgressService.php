<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentProgressService
{
    public function getComprehensiveProgress(int $studentId, array $params): array
    {
        try {
            $aggregate = $this->baseAttemptQuery($studentId, $params)
                ->selectRaw('
                    COUNT(qa.id) as total_quizzes,
                    AVG(qa.percentage_grade) as average_score,
                    SUM(CASE WHEN qa.percentage_grade >= 70 THEN 1 ELSE 0 END) as passed_quizzes,
                    COUNT(DISTINCT t.course_id) as courses_attempted,
                    COALESCE(SUM(qa.duration_seconds), 0) as total_time_spent,
                    MAX(qa.created_at) as last_activity
                ')
                ->first();

            if (!$aggregate || (int) $aggregate->total_quizzes === 0) {
                return $this->getEmptyProgressData($studentId);
            }

            $student = User::find($studentId);

            return [
                'student_info' => [
                    'id' => $studentId,
                    'name' => $student?->name ?? 'Unknown',
                    'last_activity' => $aggregate->last_activity,
                ],
                'performance_metrics' => [
                    'total_quizzes' => (int) $aggregate->total_quizzes,
                    'average_score' => round((float) $aggregate->average_score, 2),
                    'passed_quizzes' => (int) $aggregate->passed_quizzes,
                    'pass_rate' => (int) $aggregate->total_quizzes > 0
                        ? round(((int) $aggregate->passed_quizzes / (int) $aggregate->total_quizzes) * 100, 2)
                        : 0,
                    'courses_attempted' => (int) $aggregate->courses_attempted,
                    'total_time_spent' => (int) $aggregate->total_time_spent,
                ],
                'detailed_analytics' => !empty($params['include_details'])
                    ? $this->getDetailedAnalytics($studentId, $params)
                    : [],
            ];
        } catch (\Throwable $e) {
            Log::error('Comprehensive progress retrieval failed', [
                'student_id' => $studentId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function getRealtimeUpdates(int $studentId, array $params): array
    {
        try {
            $recentActivity = $this->baseAttemptQuery($studentId, $params)
                ->where('qa.created_at', '>=', now()->subDay())
                ->orderByDesc('qa.created_at')
                ->limit(10)
                ->get([
                    'qa.id',
                    'qa.percentage_grade as score',
                    'qa.duration_seconds',
                    'qa.created_at',
                    'c.name as course_name',
                    't.name as topic_name',
                ]);

            return [
                'recent_activity' => $recentActivity,
                'last_updated' => now()->toISOString(),
                'activity_count' => $recentActivity->count(),
            ];
        } catch (\Throwable $e) {
            Log::error('Real-time updates failed', [
                'student_id' => $studentId,
                'error' => $e->getMessage(),
            ]);

            return [
                'recent_activity' => [],
                'last_updated' => now()->toISOString(),
                'activity_count' => 0,
            ];
        }
    }

    public function getPerformanceAnalytics(int $studentId, array $params): array
    {
        try {
            $analytics = $this->baseAttemptQuery($studentId, $params)
                ->selectRaw('
                    DATE(qa.created_at) as date,
                    COUNT(qa.id) as quiz_count,
                    AVG(qa.percentage_grade) as avg_score,
                    COALESCE(SUM(qa.duration_seconds), 0) as total_time
                ')
                ->groupByRaw('DATE(qa.created_at)')
                ->orderByDesc('date')
                ->limit(30)
                ->get();

            return [
                'daily_analytics' => $analytics,
                'trends' => $this->calculateTrends($analytics->all()),
                'generated_at' => now()->toISOString(),
            ];
        } catch (\Throwable $e) {
            Log::error('Performance analytics failed', [
                'student_id' => $studentId,
                'error' => $e->getMessage(),
            ]);

            return [
                'daily_analytics' => [],
                'trends' => [],
                'generated_at' => now()->toISOString(),
            ];
        }
    }

    public function getComparisonAnalytics(int $studentId, array $params): array
    {
        try {
            $studentAverage = (float) ($this->baseAttemptQuery($studentId, $params)->avg('qa.percentage_grade') ?? 0);
            $courseIds = $this->getAttemptCourseIds($studentId, $params);

            if ($courseIds === []) {
                return [
                    'student_average' => round($studentAverage, 2),
                    'peer_average' => 0,
                    'peer_count' => 0,
                    'median_score' => 0,
                    'performance_ranking' => 'N/A',
                ];
            }

            $peerAverages = $this->peerAverageScores($studentId, $params, $courseIds);
            $medianScore = $this->calculateMedian($peerAverages);
            $peerAverage = $peerAverages === []
                ? 0
                : array_sum($peerAverages) / count($peerAverages);

            return [
                'student_average' => round($studentAverage, 2),
                'peer_average' => round($peerAverage, 2),
                'peer_count' => count($peerAverages),
                'median_score' => round($medianScore, 2),
                'performance_ranking' => $this->calculatePerformanceRanking($studentAverage, $peerAverages),
            ];
        } catch (\Throwable $e) {
            Log::error('Comparison analytics failed', [
                'student_id' => $studentId,
                'error' => $e->getMessage(),
            ]);

            return [
                'student_average' => 0,
                'peer_average' => 0,
                'peer_count' => 0,
                'median_score' => 0,
                'performance_ranking' => 'N/A',
            ];
        }
    }

    public function generateExportData(int $studentId, array $params): array
    {
        try {
            $exportData = $this->baseAttemptQuery($studentId, $params)
                ->orderByDesc('qa.created_at')
                ->get([
                    'qa.id',
                    'qa.percentage_grade as score',
                    'qa.duration_seconds',
                    'qa.created_at',
                    'qa.quiz_type',
                    'qa.study_mode',
                    'qa.source',
                    'c.name as course_name',
                    't.name as topic_name',
                ]);

            return [
                'student_id' => $studentId,
                'export_date' => now()->toISOString(),
                'date_range' => $params['date_range'] ?? 'month',
                'total_records' => $exportData->count(),
                'quiz_attempts' => $exportData,
                'summary' => $this->generateExportSummary($exportData->all()),
            ];
        } catch (\Throwable $e) {
            Log::error('Export data generation failed', [
                'student_id' => $studentId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function bulkUpdateProgress(array $studentIds, array $params): array
    {
        $results = [];

        foreach ($studentIds as $studentId) {
            try {
                $results[$studentId] = [
                    'success' => true,
                    'data' => $this->getComprehensiveProgress((int) $studentId, $params),
                ];
            } catch (\Throwable $e) {
                $results[$studentId] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    public function getProgressSummary(int $tutorId, array $params): array
    {
        try {
            $tutor = User::find($tutorId);
            $tutorCourseIds = $tutor?->courses()->pluck('courses.id')->map(fn ($id) => (int) $id)->all() ?? [];

            if (!empty($params['course_ids'])) {
                $requestedIds = array_map('intval', $params['course_ids']);
                $tutorCourseIds = array_values(array_intersect($tutorCourseIds, $requestedIds));
            }

            if ($tutorCourseIds === []) {
                return [
                    'total_students' => 0,
                    'students' => [],
                    'overall_stats' => $this->calculateOverallStats([]),
                    'generated_at' => now()->toISOString(),
                ];
            }

            $studentIds = DB::table('course_user')
                ->whereIn('course_id', $tutorCourseIds)
                ->where('is_enrolled', true)
                ->distinct()
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($studentIds === []) {
                return [
                    'total_students' => 0,
                    'students' => [],
                    'overall_stats' => $this->calculateOverallStats([]),
                    'generated_at' => now()->toISOString(),
                ];
            }

            $summarySubquery = DB::table('quiz_attempts as qa')
                ->join('topics as t', 'qa.topic_id', '=', 't.id')
                ->where('qa.created_at', '>=', $this->getDateRangeStart($params['date_range'] ?? 'month'))
                ->whereIn('qa.user_id', $studentIds)
                ->whereIn('t.course_id', $tutorCourseIds)
                ->groupBy('qa.user_id')
                ->selectRaw('
                    qa.user_id,
                    COUNT(qa.id) as total_attempts,
                    AVG(qa.percentage_grade) as average_score,
                    MAX(qa.created_at) as last_activity,
                    COUNT(DISTINCT t.course_id) as courses_attempted
                ');

            $summaryData = DB::table('users as u')
                ->leftJoinSub($summarySubquery, 'progress', function ($join) {
                    $join->on('u.id', '=', 'progress.user_id');
                })
                ->whereIn('u.id', $studentIds)
                ->where('u.role', User::ROLE_STUDENT)
                ->orderByDesc('progress.average_score')
                ->get([
                    'u.id',
                    'u.name',
                    DB::raw('COALESCE(progress.total_attempts, 0) as total_attempts'),
                    DB::raw('COALESCE(progress.average_score, 0) as average_score'),
                    'progress.last_activity',
                    DB::raw('COALESCE(progress.courses_attempted, 0) as courses_attempted'),
                ]);

            return [
                'total_students' => $summaryData->count(),
                'students' => $summaryData,
                'overall_stats' => $this->calculateOverallStats($summaryData->all()),
                'generated_at' => now()->toISOString(),
            ];
        } catch (\Throwable $e) {
            Log::error('Progress summary failed', [
                'tutor_id' => $tutorId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function getDetailedAnalytics(int $studentId, array $params): array
    {
        $coursePerformance = $this->baseAttemptQuery($studentId, $params)
            ->groupBy('t.course_id', 'c.name')
            ->orderByDesc('avg_score')
            ->get([
                't.course_id as id',
                'c.name',
                DB::raw('COUNT(qa.id) as attempts'),
                DB::raw('AVG(qa.percentage_grade) as avg_score'),
                DB::raw('MAX(qa.percentage_grade) as best_score'),
            ]);

        return [
            'course_performance' => $coursePerformance,
            'strengths_weaknesses' => $this->identifyStrengthsWeaknesses($coursePerformance->all()),
        ];
    }

    private function baseAttemptQuery(int $studentId, array $params): Builder
    {
        $query = DB::table('quiz_attempts as qa')
            ->leftJoin('topics as t', 'qa.topic_id', '=', 't.id')
            ->leftJoin('courses as c', 't.course_id', '=', 'c.id')
            ->where('qa.user_id', $studentId)
            ->where('qa.created_at', '>=', $this->getDateRangeStart($params['date_range'] ?? 'month'));

        if (!empty($params['course_ids'])) {
            $query->whereIn('t.course_id', array_map('intval', $params['course_ids']));
        }

        return $query;
    }

    private function peerAverageScores(int $studentId, array $params, array $courseIds): array
    {
        return DB::table('quiz_attempts as qa')
            ->join('topics as t', 'qa.topic_id', '=', 't.id')
            ->where('qa.user_id', '!=', $studentId)
            ->where('qa.created_at', '>=', $this->getDateRangeStart($params['date_range'] ?? 'month'))
            ->whereIn('t.course_id', $courseIds)
            ->groupBy('qa.user_id')
            ->selectRaw('AVG(qa.percentage_grade) as average_score')
            ->pluck('average_score')
            ->map(fn ($score) => (float) $score)
            ->all();
    }

    private function getAttemptCourseIds(int $studentId, array $params): array
    {
        return $this->baseAttemptQuery($studentId, $params)
            ->whereNotNull('t.course_id')
            ->distinct()
            ->pluck('t.course_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function getDateRangeStart(string $range): Carbon
    {
        return match ($range) {
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'quarter' => now()->subMonths(3),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };
    }

    private function calculateTrends(array $analytics): array
    {
        if (count($analytics) < 2) {
            return ['trend' => 'insufficient_data'];
        }

        $recent = array_slice($analytics, 0, 7);
        $previous = array_slice($analytics, 7, 7);

        $recentAvg = collect($recent)->avg('avg_score') ?? 0;
        $previousAvg = collect($previous)->avg('avg_score') ?? 0;

        return [
            'trend' => $recentAvg > $previousAvg ? 'improving' : ($recentAvg < $previousAvg ? 'declining' : 'stable'),
            'recent_average' => round($recentAvg, 2),
            'previous_average' => round($previousAvg, 2),
            'change_percentage' => $previousAvg > 0
                ? round((($recentAvg - $previousAvg) / $previousAvg) * 100, 2)
                : 0,
        ];
    }

    private function identifyStrengthsWeaknesses(array $coursePerformance): array
    {
        $courses = collect($coursePerformance);

        return [
            'strengths' => $courses->where('avg_score', '>=', 80)->pluck('name')->values()->all(),
            'needs_improvement' => $courses->where('avg_score', '<', 70)->pluck('name')->values()->all(),
            'most_attempted' => $courses->sortByDesc('attempts')->first()->name ?? 'N/A',
        ];
    }

    private function calculatePerformanceRanking(float $studentAverage, array $peerAverages): string
    {
        if ($studentAverage <= 0 && $peerAverages === []) {
            return 'N/A';
        }

        $betterPeers = count(array_filter($peerAverages, fn (float $average) => $average > $studentAverage));
        $totalStudents = count($peerAverages) + 1;

        return '#' . ($betterPeers + 1) . ' of ' . $totalStudents;
    }

    private function calculateMedian(array $scores): float
    {
        if ($scores === []) {
            return 0;
        }

        sort($scores);
        $count = count($scores);
        $middle = intdiv($count, 2);

        if ($count % 2 === 0) {
            return ($scores[$middle - 1] + $scores[$middle]) / 2;
        }

        return $scores[$middle];
    }

    private function generateExportSummary(array $exportData): array
    {
        $data = collect($exportData);

        return [
            'total_attempts' => $data->count(),
            'average_score' => round($data->avg('score') ?? 0, 2),
            'highest_score' => $data->max('score'),
            'lowest_score' => $data->min('score'),
            'total_time_spent' => (int) $data->sum('duration_seconds'),
            'courses_covered' => $data->pluck('course_name')->filter()->unique()->count(),
            'topics_covered' => $data->pluck('topic_name')->filter()->unique()->count(),
        ];
    }

    private function calculateOverallStats(array $summaryData): array
    {
        $data = collect($summaryData);

        return [
            'average_score_across_students' => round($data->avg('average_score') ?? 0, 2),
            'total_attempts_all_students' => (int) $data->sum('total_attempts'),
            'most_active_student' => $data->sortByDesc('total_attempts')->first()->name ?? 'N/A',
            'highest_performer' => $data->sortByDesc('average_score')->first()->name ?? 'N/A',
        ];
    }

    private function getEmptyProgressData(int $studentId): array
    {
        $student = User::find($studentId);

        return [
            'student_info' => [
                'id' => $studentId,
                'name' => $student?->name ?? 'Unknown',
                'last_activity' => null,
            ],
            'performance_metrics' => [
                'total_quizzes' => 0,
                'average_score' => 0,
                'passed_quizzes' => 0,
                'pass_rate' => 0,
                'courses_attempted' => 0,
                'total_time_spent' => 0,
            ],
            'detailed_analytics' => [],
        ];
    }
}
