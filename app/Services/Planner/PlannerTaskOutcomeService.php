<?php

namespace App\Services\Planner;

use App\Models\Course;
use App\Models\QuizAttempt;
use App\Models\PlannerTaskOutcome;
use App\Models\StudyPlan;
use App\Models\User;
use Illuminate\Support\Collection;

class PlannerTaskOutcomeService
{
    public function syncDailyOutcomes(
        User $user,
        StudyPlan $plan,
        Course $course,
        PlannerContext $context,
        Collection $tasks
    ): void {
        $planDate = $plan->plan_date?->toDateString() ?? now()->toDateString();
        $presentTaskIds = $tasks->pluck('id')->filter()->all();

        foreach ($tasks as $task) {
            if (!is_array($task) || empty($task['id'])) {
                continue;
            }

            if ((bool) ($task['is_preview'] ?? false)) {
                continue;
            }

            $outcome = PlannerTaskOutcome::query()
                ->where('user_id', $user->id)
                ->whereDate('plan_date', $planDate)
                ->where('course_id', $course->id)
                ->where('task_id', (string) $task['id'])
                ->first();

            if (!$outcome) {
                $outcome = new PlannerTaskOutcome([
                    'user_id' => $user->id,
                    'plan_date' => $planDate,
                    'course_id' => $course->id,
                    'task_id' => (string) $task['id'],
                ]);
            }

            $firstPresentedAt = $outcome->first_presented_at ?? now();
            $completedAt = $outcome->completed_at;
            $isCompleted = (bool) ($task['completed'] ?? false);

            if ($isCompleted && $completedAt === null) {
                $completedAt = !empty($task['completed_at'])
                    ? now()->parse((string) $task['completed_at'])
                    : now();
            }

            $completionLatencyMinutes = $outcome->completion_latency_minutes;
            if ($completionLatencyMinutes === null && $completedAt !== null && $firstPresentedAt !== null) {
                $completionLatencyMinutes = max(1, $firstPresentedAt->diffInMinutes($completedAt));
            }

            $outcome->fill([
                'study_plan_id' => $plan->id,
                'topic_id' => $task['topic_id'] ?? null,
                'task_type' => (string) ($task['type'] ?? 'unknown'),
                'title' => $task['title'] ?? null,
                'study_mode' => $task['study_mode'] ?? null,
                'source' => $task['source'] ?? null,
                'reason_code' => $task['reason_code'] ?? null,
                'planner_focus' => $context->plannerFocus,
                'allocated_minutes' => $context->dailyStudyMinutes,
                'total_daily_minutes' => $context->totalDailyStudyMinutes,
                'question_target' => $task['question_target'] ?? null,
                'duration_minutes' => $task['duration_minutes'] ?? null,
                'is_completed' => $isCompleted,
                'first_presented_at' => $firstPresentedAt,
                'last_presented_at' => now(),
                'completed_at' => $completedAt,
                'completion_latency_minutes' => $completionLatencyMinutes,
                'task_payload' => [
                    'meta' => $task['meta'] ?? null,
                    'xp' => $task['xp'] ?? null,
                    'xp_value' => $task['xp_value'] ?? null,
                    'xp_awarded_total' => $task['xp_awarded_total'] ?? 0,
                    'mistake_target_count' => $task['mistake_target_count'] ?? null,
                    'mistake_start_count' => $task['mistake_start_count'] ?? null,
                    'assigned_mistake_ids' => $task['assigned_mistake_ids'] ?? [],
                    'assigned_due_count' => $task['assigned_due_count'] ?? null,
                    'repair_mode' => $task['repair_mode'] ?? null,
                    'repair_difficulty' => $task['repair_difficulty'] ?? null,
                    'difficulty_locked_at' => $task['difficulty_locked_at'] ?? null,
                    'target_concepts' => $task['target_concepts'] ?? [],
                    'completion_state' => $task['completion_state'] ?? null,
                    'completion_progress' => $task['completion_progress'] ?? null,
                    'spawned_from_task_id' => $task['spawned_from_task_id'] ?? null,
                    'is_regression' => $task['is_regression'] ?? false,
                    'priority_score' => $task['priority_score'] ?? null,
                    'score_components' => $task['score_components'] ?? [],
                    'resolved_source' => $task['resolved_source'] ?? null,
                    'sizing_basis' => $task['sizing_basis'] ?? [],
                    'trigger_reason' => $task['trigger_reason'] ?? null,
                    'resolved_exam_date' => $task['resolved_exam_date'] ?? null,
                    'resolved_focus' => $task['resolved_focus'] ?? null,
                    'course_priority_score' => $context->coursePriorityScore,
                    'global_allocation' => data_get($context->globalAllocation, 'courses.' . $course->id),
                    'resolved_settings' => [
                        'exam_date' => $context->examDate?->format('Y-m-d'),
                        'daily_study_minutes' => $context->dailyStudyMinutes,
                        'planner_focus' => $context->plannerFocus,
                        'preferred_source' => $context->preferredSource,
                        'repair_mode' => $context->repairMode,
                        'mistake_mastery_mode' => $context->classicRepairDifficulty,
                    ],
                ],
            ]);

            $outcome->save();
        }

        if (!empty($presentTaskIds)) {
            PlannerTaskOutcome::query()
                ->where('user_id', $user->id)
                ->whereDate('plan_date', $planDate)
                ->where('course_id', $course->id)
                ->whereNotIn('task_id', $presentTaskIds)
                ->whereNull('completed_at')
                    ->delete();
        }
    }

    public function summarizeRecentBehavior(User $user, int $courseId, array $courseTopicIds): array
    {
        $windowStart = now()->subDays(14)->startOfDay();

        $outcomes = PlannerTaskOutcome::query()
            ->where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->whereDate('plan_date', '>=', $windowStart->toDateString())
            ->get([
                'task_type',
                'study_mode',
                'question_target',
                'duration_minutes',
                'completion_latency_minutes',
                'is_completed',
            ]);

        $byTaskType = $outcomes
            ->groupBy('task_type')
            ->map(function (Collection $group) {
                $total = max(1, $group->count());

                return [
                    'completion_rate' => round($group->where('is_completed', true)->count() / $total, 4),
                    'average_completion_latency_minutes' => round((float) $group->whereNotNull('completion_latency_minutes')->avg('completion_latency_minutes'), 2),
                ];
            })
            ->all();

        $byStudyModeCompletion = $outcomes
            ->filter(fn (PlannerTaskOutcome $outcome) => !empty($outcome->study_mode))
            ->groupBy('study_mode')
            ->map(function (Collection $group) {
                $total = max(1, $group->count());

                return round($group->where('is_completed', true)->count() / $total, 4);
            })
            ->all();

        $largeTasks = $outcomes->filter(fn (PlannerTaskOutcome $outcome) => (int) ($outcome->question_target ?? 0) >= 10);
        $largeTaskAbandonmentRate = $largeTasks->isEmpty()
            ? 0.0
            : round($largeTasks->where('is_completed', false)->count() / $largeTasks->count(), 4);

        $attempts = QuizAttempt::query()
            ->where('user_id', $user->id)
            ->whereDate('created_at', '>=', $windowStart->toDateString())
            ->when(!empty($courseTopicIds), function ($query) use ($courseTopicIds) {
                $query->whereIn('topic_id', $courseTopicIds);
            })
            ->where('total_questions', '>', 0)
            ->get([
                'study_mode',
                'quiz_type',
                'total_questions',
                'duration_seconds',
                'percentage_grade',
                'created_at',
            ]);

        $byStudyModePerformance = $attempts
            ->map(function (QuizAttempt $attempt) {
                $studyMode = $attempt->study_mode ?: match($attempt->quiz_type) {
                    'mistake_review' => 'mistake_review',
                    'random' => 'quiz',
                    default => 'quiz',
                };

                return [
                    'study_mode' => $studyMode,
                    'total_questions' => (int) ($attempt->total_questions ?? 0),
                    'duration_seconds' => max(0, (int) ($attempt->duration_seconds ?? 0)),
                    'percentage_grade' => (float) ($attempt->percentage_grade ?? 0.0),
                ];
            })
            ->groupBy('study_mode')
            ->map(function (Collection $group) use ($byStudyModeCompletion) {
                $sessionRates = $group
                    ->filter(fn ($row) => $row['duration_seconds'] > 30)
                    ->map(fn ($row) => $row['total_questions'] / max(0.01, $row['duration_seconds'] / 60))
                    ->values()
                    ->sort()
                    ->values();

                if ($sessionRates->isEmpty()) {
                    $totalQ = $group->sum('total_questions');
                    $totalM = max(0.01, $group->sum('duration_seconds') / 60);
                    $qpm = $totalQ / $totalM;
                } else {
                    $mid = (int) floor($sessionRates->count() / 2);
                    $qpm = $sessionRates->count() % 2 === 0
                        ? ($sessionRates[$mid - 1] + $sessionRates[$mid]) / 2.0
                        : $sessionRates[$mid];
                }

                return [
                    'questions_per_minute' => round((float) $qpm, 4),
                    'average_accuracy' => round((float) $group->avg('percentage_grade'), 2),
                    'completion_rate' => (float) ($byStudyModeCompletion[$group->first()['study_mode']] ?? 0.0),
                ];
            })
            ->all();

        return [
            'by_task_type' => $byTaskType,
            'large_task_abandonment_rate' => $largeTaskAbandonmentRate,
            'by_study_mode' => $byStudyModePerformance,
        ];
    }
}
