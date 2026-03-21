<?php

namespace App\Services\Planner;

use App\Models\Mistake;
use App\Models\UserMistakeMastery;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlannerSignalService
{
    public function buildTopicBacklogSignals(int $userId, array $topicIds, ?int $classicMasteryThreshold = null): array
    {
        $topicIds = array_values(array_unique(array_map('intval', $topicIds)));
        if (empty($topicIds)) {
            return $this->emptyBacklogSignals();
        }

        $mistakes = Mistake::query()
            ->where('user_id', $userId)
            ->whereHas('question', function ($query) use ($topicIds) {
                $query->whereIn('topic_id', $topicIds);
            })
            ->with('question:id,topic_id,source')
            ->get(array_merge([
                'id',
                'question_id',
                'skipped',
            ], Mistake::availableColumns([
                'skip_until',
                'next_review_at',
                'retrievability',
                'stability',
                'last_reviewed_at',
                'last_attempt_date',
            ])));

        $masteryByQuestionId = $classicMasteryThreshold !== null
            ? $this->masteryByQuestionId($userId, $mistakes->pluck('question_id')->all())
            : [];

        return $this->summarizeBacklog(
            $mistakes,
            fn (Mistake $mistake) => (int) ($mistake->question?->topic_id ?? 0),
            'topic_id',
            $masteryByQuestionId,
            $classicMasteryThreshold
        );
    }

    public function buildCourseBacklogSignals(int $userId, array $courseIds): array
    {
        $courseIds = array_values(array_unique(array_map('intval', $courseIds)));
        if (empty($courseIds)) {
            return [];
        }

        $mistakes = Mistake::query()
            ->where('user_id', $userId)
            ->whereHas('question.topic', function ($query) use ($courseIds) {
                $query->whereIn('course_id', $courseIds);
            })
            ->with(['question:id,topic_id,source', 'question.topic:id,course_id'])
            ->get(array_merge([
                'id',
                'question_id',
                'skipped',
            ], Mistake::availableColumns([
                'skip_until',
                'next_review_at',
                'retrievability',
                'stability',
                'last_reviewed_at',
                'last_attempt_date',
            ])));

        $grouped = $this->summarizeBacklog(
            $mistakes,
            fn (Mistake $mistake) => (int) ($mistake->question?->topic?->course_id ?? 0),
            'course_id'
        );

        return collect($grouped['by_topic'] ?? [])
            ->keyBy('course_id')
            ->map(fn (array $signal) => $signal)
            ->all();
    }

    public function buildCourseAssignmentSignals(int $userId, array $courseIds): array
    {
        $courseIds = array_values(array_unique(array_map('intval', $courseIds)));
        if (empty($courseIds) || !Schema::hasTable('assignments') || !Schema::hasTable('assignment_user')) {
            return [];
        }

        $attemptStats = DB::table('quiz_attempts')
            ->where('user_id', $userId)
            ->whereNotNull('assignment_id')
            ->selectRaw('assignment_id, COUNT(*) as attempts_count, SUM(total_questions) as total_questions, SUM(score) as total_correct')
            ->groupBy('assignment_id');

        $rows = DB::table('assignment_user')
            ->join('assignments', 'assignments.id', '=', 'assignment_user.assignment_id')
            ->leftJoinSub($attemptStats, 'attempt_stats', function ($join) {
                $join->on('attempt_stats.assignment_id', '=', 'assignments.id');
            })
            ->where('assignment_user.user_id', $userId)
            ->whereIn('assignments.course_id', $courseIds)
            ->selectRaw('
                assignments.id as assignment_id,
                assignments.course_id,
                assignments.topic_id,
                COALESCE(assignment_user.extended_deadline, assignments.deadline) as effective_deadline,
                COALESCE(attempt_stats.attempts_count, 0) as attempts_count
            ')
            ->get();

        return collect($courseIds)->mapWithKeys(function (int $courseId) use ($rows) {
            $courseRows = $rows->where('course_id', $courseId)->values();
            $signals = [
                'nearest_assignment_deadline' => null,
                'nearest_assignment_hours' => null,
                'overdue_assignment_count' => 0,
                'due_within_48h_count' => 0,
                'active_assignment_count' => 0,
                'by_topic' => [],
            ];

            foreach ($courseRows as $row) {
                $hasAttempt = (int) ($row->attempts_count ?? 0) > 0;
                $deadline = $row->effective_deadline ? Carbon::parse($row->effective_deadline) : null;
                $topicId = (int) ($row->topic_id ?? 0);

                if ($hasAttempt || !$deadline) {
                    continue;
                }

                $signals['active_assignment_count']++;
                $signals['nearest_assignment_deadline'] = $signals['nearest_assignment_deadline'] === null
                    ? $deadline
                    : ($deadline->lt($signals['nearest_assignment_deadline']) ? $deadline : $signals['nearest_assignment_deadline']);

                if ($deadline->isPast()) {
                    $signals['overdue_assignment_count']++;
                } elseif ($deadline->lte(now()->addHours(48))) {
                    $signals['due_within_48h_count']++;
                }

                if ($topicId > 0) {
                    $signals['by_topic'][$topicId] = $signals['by_topic'][$topicId] ?? [
                        'topic_id' => $topicId,
                        'overdue_assignment_count' => 0,
                        'due_within_48h_count' => 0,
                        'active_assignment_count' => 0,
                        'nearest_assignment_deadline' => null,
                    ];
                    $signals['by_topic'][$topicId]['active_assignment_count']++;

                    if ($deadline->isPast()) {
                        $signals['by_topic'][$topicId]['overdue_assignment_count']++;
                    } elseif ($deadline->lte(now()->addHours(48))) {
                        $signals['by_topic'][$topicId]['due_within_48h_count']++;
                    }

                    $currentNearest = $signals['by_topic'][$topicId]['nearest_assignment_deadline'];
                    if ($currentNearest === null || $deadline->lt($currentNearest)) {
                        $signals['by_topic'][$topicId]['nearest_assignment_deadline'] = $deadline;
                    }
                }
            }

            if ($signals['nearest_assignment_deadline'] instanceof Carbon) {
                $signals['nearest_assignment_hours'] = max(
                    0,
                    now()->diffInHours($signals['nearest_assignment_deadline'], false) * -1
                );
                $signals['nearest_assignment_deadline'] = $signals['nearest_assignment_deadline']->toIso8601String();
            }

            $signals['by_topic'] = collect($signals['by_topic'])
                ->map(function (array $signal) {
                    if ($signal['nearest_assignment_deadline'] instanceof Carbon) {
                        $signal['nearest_assignment_deadline'] = $signal['nearest_assignment_deadline']->toIso8601String();
                    }

                    return $signal;
                })
                ->values()
                ->all();

            return [$courseId => $signals];
        })->all();
    }

    private function summarizeBacklog(
        Collection $mistakes,
        callable $groupResolver,
        string $groupKey,
        array $masteryByQuestionId = [],
        ?int $classicMasteryThreshold = null
    ): array
    {
        $byGroup = [];
        $totals = [
            'due_spaced_reviews' => 0,
            'active_open_backlog' => 0,
            'deferred_skipped_backlog' => 0,
            'reviewable_backlog' => 0,
            'classic_reviewable_backlog' => 0,
            'total_backlog' => 0,
            'recent_unresolved_count' => 0,
            'stale_skipped_count' => 0,
            'by_topic' => [],
        ];

        foreach ($mistakes as $mistake) {
            $groupId = (int) $groupResolver($mistake);
            if ($groupId <= 0) {
                continue;
            }

            $classification = $this->classifyBacklogState($mistake);
            $classificationUrgency = $classification === 'due_spaced_reviews' ? 'due' : 'open';
            $classificationRisk = max(0.0, min(1.0, 1 - $mistake->currentRetrievability()));
            $nextReviewAt = $mistake->next_review_at instanceof Carbon
                ? $mistake->next_review_at
                : ($mistake->next_review_at ? Carbon::parse($mistake->next_review_at) : null);

            $byGroup[$groupId] = $byGroup[$groupId] ?? [
                $groupKey => $groupId,
                '_id' => $groupId,
                'due_spaced_reviews' => 0,
                'active_open_backlog' => 0,
                'deferred_skipped_backlog' => 0,
                'reviewable_backlog' => 0,
                'classic_reviewable_backlog' => 0,
                'total_backlog' => 0,
                'recent_unresolved_count' => 0,
                'stale_skipped_count' => 0,
                'minutes_overdue' => 0,
                'average_risk' => 0.0,
                '_due_risks' => [],
                '_oldest_due_at' => null,
                '_due_mistakes' => [],
                '_source_backlog' => [],
                '_reviewable_mistake_ids' => [],
                '_classic_reviewable_mistake_ids' => [],
            ];

            $byGroup[$groupId][$classification]++;
            $byGroup[$groupId]['total_backlog']++;
            $totals[$classification]++;
            $totals['total_backlog']++;

            if ($classificationUrgency === 'due') {
                $byGroup[$groupId]['_due_mistakes'][] = [
                    'id' => (int) $mistake->id,
                    'risk' => (float) $classificationRisk,
                    'next_review_at' => $nextReviewAt,
                ];
            }

            if ($classification !== 'deferred_skipped_backlog') {
                $byGroup[$groupId]['reviewable_backlog']++;
                $totals['reviewable_backlog']++;
                $byGroup[$groupId]['_reviewable_mistake_ids'][] = (int) $mistake->id;

                $source = (string) ($mistake->question?->source ?? 'general');
                $byGroup[$groupId]['_source_backlog'][$source] = ($byGroup[$groupId]['_source_backlog'][$source] ?? 0) + 1;

                if ($classicMasteryThreshold !== null
                    && $this->isClassicRepairCandidate(
                        $masteryByQuestionId[(int) $mistake->question_id] ?? null,
                        $classicMasteryThreshold
                    )) {
                    $byGroup[$groupId]['classic_reviewable_backlog']++;
                    $totals['classic_reviewable_backlog']++;
                    $byGroup[$groupId]['_classic_reviewable_mistake_ids'][] = (int) $mistake->id;
                }
            }

            if ($classification === 'due_spaced_reviews') {
                $byGroup[$groupId]['_due_risks'][] = $classificationRisk;
                if ($nextReviewAt && ($byGroup[$groupId]['_oldest_due_at'] === null || $nextReviewAt->lt($byGroup[$groupId]['_oldest_due_at']))) {
                    $byGroup[$groupId]['_oldest_due_at'] = $nextReviewAt;
                }
            }

            $lastAttemptDate = $mistake->last_attempt_date instanceof Carbon
                ? $mistake->last_attempt_date
                : ($mistake->last_attempt_date ? Carbon::parse($mistake->last_attempt_date) : null);

            if (!((bool) ($mistake->skipped ?? false)) && $lastAttemptDate && $lastAttemptDate->gte(now()->subDays(7))) {
                $byGroup[$groupId]['recent_unresolved_count']++;
                $totals['recent_unresolved_count']++;
            }

            if ((bool) ($mistake->skipped ?? false) && $lastAttemptDate && $lastAttemptDate->lte(now()->subDays(14))) {
                $byGroup[$groupId]['stale_skipped_count']++;
                $totals['stale_skipped_count']++;
            }
        }

        $totals['by_topic'] = collect($byGroup)
            ->map(function (array $signal) {
                $signal['topic_id'] = (int) ($signal['_id'] ?? 0);
                $oldestDueAt = $signal['_oldest_due_at'];
                $signal['minutes_overdue'] = $oldestDueAt instanceof Carbon
                    ? max(0, now()->diffInMinutes($oldestDueAt, false) * -1)
                    : 0;
                $signal['average_risk'] = !empty($signal['_due_risks'])
                    ? round(array_sum($signal['_due_risks']) / count($signal['_due_risks']), 4)
                    : 0.0;
                $signal['due_mistake_ids'] = collect($signal['_due_mistakes'])
                    ->sort(function (array $left, array $right) {
                        $leftDue = $left['next_review_at'] instanceof Carbon ? $left['next_review_at']->timestamp : PHP_INT_MAX;
                        $rightDue = $right['next_review_at'] instanceof Carbon ? $right['next_review_at']->timestamp : PHP_INT_MAX;

                        if ($leftDue === $rightDue) {
                            return ($right['risk'] <=> $left['risk']) ?: ($left['id'] <=> $right['id']);
                        }

                        return $leftDue <=> $rightDue;
                    })
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all();
                $signal['reviewable_mistake_ids'] = array_values(array_map('intval', $signal['_reviewable_mistake_ids']));
                $signal['source_backlog'] = $signal['_source_backlog'] ?? [];
                $signal['classic_reviewable_mistake_ids'] = array_values(array_map('intval', $signal['_classic_reviewable_mistake_ids']));

                return $signal;
            })
            ->all();

        return $totals;
    }

    private function classifyBacklogState(Mistake $mistake): string
    {
        $skipUntil = $mistake->skip_until instanceof Carbon
            ? $mistake->skip_until
            : ($mistake->skip_until ? Carbon::parse($mistake->skip_until) : null);
        $nextReviewAt = $mistake->next_review_at instanceof Carbon
            ? $mistake->next_review_at
            : ($mistake->next_review_at ? Carbon::parse($mistake->next_review_at) : null);

        if ($mistake->skipped && ($skipUntil === null || $skipUntil->isFuture())) {
            return 'deferred_skipped_backlog';
        }

        if ($nextReviewAt === null || $nextReviewAt->lte(now())) {
            return 'due_spaced_reviews';
        }

        return 'active_open_backlog';
    }

    private function emptyBacklogSignals(): array
    {
        return [
            'due_spaced_reviews' => 0,
            'active_open_backlog' => 0,
            'deferred_skipped_backlog' => 0,
            'reviewable_backlog' => 0,
            'classic_reviewable_backlog' => 0,
            'total_backlog' => 0,
            'recent_unresolved_count' => 0,
            'stale_skipped_count' => 0,
            'by_topic' => [],
        ];
    }

    private function masteryByQuestionId(int $userId, array $questionIds): array
    {
        $questionIds = array_values(array_unique(array_map('intval', $questionIds)));
        if (empty($questionIds) || !Schema::hasTable('user_mistake_mastery')) {
            return [];
        }

        return UserMistakeMastery::query()
            ->where('user_id', $userId)
            ->whereIn('question_id', $questionIds)
            ->get(['question_id', 'consecutive_correct', 'graduated_at'])
            ->keyBy(fn (UserMistakeMastery $mastery) => (int) $mastery->question_id)
            ->all();
    }

    private function isClassicRepairCandidate(?UserMistakeMastery $mastery, int $threshold): bool
    {
        if ($threshold <= 0) {
            return true;
        }

        if (!$mastery) {
            return true;
        }

        return (int) ($mastery->consecutive_correct ?? 0) < $threshold;
    }
}
