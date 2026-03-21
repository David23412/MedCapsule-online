<?php

namespace App\Services\Planner;

use App\Models\QuizAttempt;
use App\Models\Topic;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GlobalStudyBudgetAllocator
{
    public function __construct(
        private PlannerSignalService $plannerSignalService,
    ) {
    }

    public function allocate(User $user, int $currentCourseId): array
    {
        $totalDailyMinutes = max(30, (int) ($user->daily_study_minutes ?? 90));
        $courseIds = $this->resolveCourseIds($user, $currentCourseId);

        if (count($courseIds) === 1) {
            return [
                'total_daily_minutes' => $totalDailyMinutes,
                'courses' => [
                    $currentCourseId => [
                        'allocated_minutes' => $totalDailyMinutes,
                        'priority_score' => 100.0,
                        'signals' => [
                            'total_topics' => 0,
                            'attempted_topics' => 0,
                            'average_grade' => 0.0,
                            'coverage_gap' => 0,
                            'open_mistakes' => 0,
                            'due_mistakes' => 0,
                            'hours_since_last_activity' => null,
                        ],
                    ],
                ],
            ];
        }

        $courseSignals = $this->buildCourseSignals($user, $courseIds);
        $priorityScores = collect($courseSignals)
            ->mapWithKeys(fn (array $signals, int $courseId) => [(int) $courseId => $this->scoreCourse($signals)])
            ->all();

        $maintenanceFloor = $this->resolveMaintenanceFloor($totalDailyMinutes, count($courseIds));
        $baseAllocated = $maintenanceFloor * count($courseIds);
        $remainingMinutes = max(0, $totalDailyMinutes - $baseAllocated);
        $prioritySum = array_sum($priorityScores);

        $allocations = [];
        $remainders = [];

        foreach ($courseIds as $courseId) {
            $share = $prioritySum > 0 ? ($remainingMinutes * ($priorityScores[$courseId] / $prioritySum)) : 0.0;
            $extraMinutes = (int) floor($share);

            $allocations[$courseId] = [
                'allocated_minutes' => $maintenanceFloor + $extraMinutes,
                'priority_score' => round($priorityScores[$courseId], 2),
                'signals' => $courseSignals[$courseId],
            ];
            $remainders[$courseId] = $share - $extraMinutes;
        }

        $assigned = array_sum(array_column($allocations, 'allocated_minutes'));
        $leftover = max(0, $totalDailyMinutes - $assigned);

        if ($leftover > 0) {
            foreach (collect($remainders)->sortByDesc(fn (float $remainder) => $remainder)->keys() as $courseId) {
                if ($leftover <= 0) {
                    break;
                }

                $allocations[(int) $courseId]['allocated_minutes']++;
                $leftover--;
            }
        }

        return [
            'total_daily_minutes' => $totalDailyMinutes,
            'courses' => $allocations,
        ];
    }

    private function resolveCourseIds(User $user, int $currentCourseId): array
    {
        return collect(DB::table('course_user')
            ->where('user_id', $user->id)
            ->where('is_enrolled', true)
            ->pluck('course_id'))
            ->map(fn ($courseId) => (int) $courseId)
            ->push($currentCourseId)
            ->unique()
            ->values()
            ->all();
    }

    private function buildCourseSignals(User $user, array $courseIds): array
    {
        $topicStats = Topic::query()
            ->whereIn('course_id', $courseIds)
            ->selectRaw('course_id, COUNT(*) as total_topics')
            ->groupBy('course_id')
            ->get()
            ->keyBy('course_id');

        $progressStats = DB::table('topics')
            ->leftJoin('user_topic_progress', function ($join) use ($user) {
                $join->on('user_topic_progress.topic_id', '=', 'topics.id')
                    ->where('user_topic_progress.user_id', '=', $user->id);
            })
            ->whereIn('topics.course_id', $courseIds)
            ->selectRaw('topics.course_id, COUNT(user_topic_progress.id) as attempted_topics, AVG(COALESCE(user_topic_progress.percentage_grade, 0)) as average_grade')
            ->groupBy('topics.course_id')
            ->get()
            ->keyBy('course_id');

        $lastActivityStats = QuizAttempt::query()
            ->join('topics', 'topics.id', '=', 'quiz_attempts.topic_id')
            ->where('quiz_attempts.user_id', $user->id)
            ->whereIn('topics.course_id', $courseIds)
            ->selectRaw('topics.course_id, MAX(quiz_attempts.created_at) as last_activity_at')
            ->groupBy('topics.course_id')
            ->get()
            ->keyBy('course_id');

        $backlogStats = $this->plannerSignalService->buildCourseBacklogSignals($user->id, $courseIds);
        $assignmentSignals = $this->plannerSignalService->buildCourseAssignmentSignals($user->id, $courseIds);

        $signals = [];
        foreach ($courseIds as $courseId) {
            $totalTopics = (int) ($topicStats->get($courseId)->total_topics ?? 0);
            $attemptedTopics = (int) ($progressStats->get($courseId)->attempted_topics ?? 0);
            $averageGrade = round((float) ($progressStats->get($courseId)->average_grade ?? 0), 2);
            $lastActivityAt = $lastActivityStats->get($courseId)->last_activity_at ?? null;
            $courseBacklog = $backlogStats[$courseId] ?? [];
            $courseAssignments = $assignmentSignals[$courseId] ?? [];
            $hoursSinceLastActivity = $lastActivityAt
                ? Carbon::parse($lastActivityAt)->diffInHours(now())
                : null;

            $signals[$courseId] = [
                'total_topics' => $totalTopics,
                'attempted_topics' => $attemptedTopics,
                'average_grade' => $averageGrade,
                'coverage_gap' => max(0, $totalTopics - $attemptedTopics),
                'due_spaced_reviews' => (int) ($courseBacklog['due_spaced_reviews'] ?? 0),
                'active_open_backlog' => (int) ($courseBacklog['active_open_backlog'] ?? 0),
                'deferred_skipped_backlog' => (int) ($courseBacklog['deferred_skipped_backlog'] ?? 0),
                'reviewable_backlog' => (int) ($courseBacklog['reviewable_backlog'] ?? 0),
                'overdue_assignment_count' => (int) ($courseAssignments['overdue_assignment_count'] ?? 0),
                'due_within_48h_count' => (int) ($courseAssignments['due_within_48h_count'] ?? 0),
                'active_assignment_count' => (int) ($courseAssignments['active_assignment_count'] ?? 0),
                'nearest_assignment_hours' => $courseAssignments['nearest_assignment_hours'] ?? null,
                'hours_since_last_activity' => $hoursSinceLastActivity,
            ];
        }

        return $signals;
    }

    private function scoreCourse(array $signals): float
    {
        $totalTopics = max(1, (int) ($signals['total_topics'] ?? 0));
        $averageGrade = (float) ($signals['average_grade'] ?? 0);
        $hoursSinceLastActivity = $signals['hours_since_last_activity'];
        $coverageGapRatio = min(1, ((int) ($signals['coverage_gap'] ?? 0)) / $totalTopics);
        $persistentWeakness = max(0.0, min(1.0, 1 - ($averageGrade / 100)));
        $reviewableBacklog = (int) ($signals['reviewable_backlog'] ?? 0);
        $dueSpacedReviews = (int) ($signals['due_spaced_reviews'] ?? 0);
        $duePressure = min(1.0, (($dueSpacedReviews * 1.5) + $reviewableBacklog) / max(4, $totalTopics * 2));
        $assignmentPressure = match (true) {
            (int) ($signals['overdue_assignment_count'] ?? 0) > 0 => 1.0,
            (int) ($signals['due_within_48h_count'] ?? 0) > 0 => 0.9,
            ($signals['nearest_assignment_hours'] ?? null) !== null && (int) $signals['nearest_assignment_hours'] <= 168 => 0.45,
            default => 0.0,
        };
        $neglectScore = $hoursSinceLastActivity === null
            ? 1.0
            : min(1.0, $hoursSinceLastActivity / 96);

        return round(
            20
            + ($duePressure * 35)
            + ($assignmentPressure * 25)
            + ($coverageGapRatio * 20)
            + ($persistentWeakness * 10)
            + ($neglectScore * 10),
            2
        );
    }

    private function resolveMaintenanceFloor(int $totalDailyMinutes, int $courseCount): int
    {
        if ($courseCount <= 1) {
            return $totalDailyMinutes;
        }

        $preferredFloor = 15;
        if ($totalDailyMinutes >= ($courseCount * $preferredFloor)) {
            return $preferredFloor;
        }

        if ($totalDailyMinutes >= ($courseCount * 10)) {
            return 10;
        }

        return 0;
    }
}
