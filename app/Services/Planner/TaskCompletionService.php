<?php

namespace App\Services\Planner;

use App\Models\Course;
use App\Models\Mistake;
use App\Models\QuizAttempt;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserTopicProgress;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TaskCompletionService
{
    public function isTaskCompleted(array $task, ?Topic $topic, PlannerContext $context): bool
    {
        return $this->evaluateTask($task, $topic, $context)['state'] === 'complete';
    }

    public function evaluateTask(array $task, ?Topic $topic, PlannerContext $context): array
    {
        $type = (string) ($task['type'] ?? '');

        return match ($type) {
            'start_topic' => $this->evaluateTutorTask($task, $topic, $context),
            'rapid_overview' => $this->evaluateRapidOverviewTask($task, $topic, $context),
            'repair_topic' => $this->evaluateClassicRepairTask($task, $topic, $context),
            'frontier_topic_quiz' => $this->evaluateProgressionQuizTask($task, $topic, $context),
            'secure_topic' => $this->evaluateScoredTopicTask($task, $topic, $context, 70.0),
            'concept_repair' => $this->evaluateConceptRepairTask($task, $context),
            'concept_cluster_repair' => $this->evaluateConceptClusterRepairTask($task, $context),
            'final_exam_sprint' => $this->evaluateFinalExamSprintTask($task, $topic, $context, 60.0),
            'mistakes_cleanup' => $this->evaluateMistakesCleanupTask($task, $context),
            'topic_mistake_review' => $this->evaluateSpacedReviewTask($task, $context),
            default => $this->result('pending', 0.0, false),
        };
    }

    public function applyPlannerStageTransitions(
        User $user,
        Course $course,
        Collection $storedTasks,
        Collection $topics,
        PlannerContext $context
    ): bool {
        $changed = false;

        foreach ($storedTasks as $storedTask) {
            if (!is_array($storedTask) || (bool) ($storedTask['completed'] ?? false)) {
                continue;
            }

            if (!(bool) ($storedTask['counts_toward_progression'] ?? false)) {
                continue;
            }

            $topicId = (int) ($storedTask['topic_id'] ?? 0);
            if ($topicId <= 0) {
                continue;
            }

            $topic = $topics->firstWhere('id', $topicId);
            if (!($topic instanceof Topic)) {
                continue;
            }

            $evaluation = $this->evaluateTask($storedTask, $topic, $context);
            if (($evaluation['state'] ?? 'pending') !== 'complete') {
                continue;
            }

            $progress = UserTopicProgress::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'topic_id' => $topicId,
                ],
                [
                    'percentage_grade' => $topic->userProgress->first()?->percentage_grade ?? 0.0,
                    'last_attempt_date' => $topic->userProgress->first()?->last_attempt_date,
                ]
            );

            $now = now();
            $type = (string) ($storedTask['type'] ?? '');

            if (in_array($type, ['start_topic', 'rapid_overview'], true)) {
                if ($progress->planner_tutor_cleared_at === null) {
                    $progress->planner_tutor_cleared_at = $now;
                }
            }

            if (in_array($type, ['frontier_topic_quiz', 'final_exam_sprint'], true)) {
                if ($progress->planner_tutor_cleared_at === null) {
                    $progress->planner_tutor_cleared_at = $now;
                }

                if ($progress->planner_quiz_cleared_at === null) {
                    $progress->planner_quiz_cleared_at = $now;
                }

                if (!$this->topicHasReviewableBacklog($topicId, $context) && $progress->planner_cleared_at === null) {
                    $progress->planner_cleared_at = $now;
                }
            }

            if (in_array($type, ['repair_topic', 'topic_mistake_review'], true)) {
                if ($progress->planner_tutor_cleared_at === null) {
                    $progress->planner_tutor_cleared_at = $now;
                }
                if ($progress->planner_quiz_cleared_at === null) {
                    $progress->planner_quiz_cleared_at = $now;
                }
                if ($progress->planner_cleared_at === null) {
                    $progress->planner_cleared_at = $now;
                }
            }

            if ($progress->isDirty([
                'planner_tutor_cleared_at',
                'planner_quiz_cleared_at',
                'planner_cleared_at',
            ])) {
                $progress->save();
                $changed = true;
            }
        }

        $changed = $this->applyRegressionTransitions($user, $topics, $context) || $changed;

        return $changed;
    }

    private function evaluateTutorTask(array $task, ?Topic $topic, PlannerContext $context): array
    {
        $matchingAttempts = $this->matchingTopicAttempts($task, $context->attemptSignals);
        if ($matchingAttempts->isEmpty()) {
            return $this->result('pending', 0.0, false);
        }

        return $this->result('complete', 1.0, true);
    }

    private function evaluateRapidOverviewTask(array $task, ?Topic $topic, PlannerContext $context): array
    {
        $matchingAttempts = $this->matchingTopicAttempts($task, $context->attemptSignals);
        if ($matchingAttempts->isEmpty()) {
            return $this->result('pending', 0.0, false);
        }

        $withinCap = $matchingAttempts->contains(function (array $attempt) {
            $durationSeconds = max(0, (int) ($attempt['duration_seconds'] ?? 0));

            return $durationSeconds > 0 && $durationSeconds <= 600;
        });

        return $withinCap
            ? $this->result('complete', 1.0, true)
            : $this->result('pending', 0.0, true);
    }

    private function evaluateClassicRepairTask(array $task, ?Topic $topic, PlannerContext $context): array
    {
        $matchingAttempts = $this->matchingTopicAttempts($task, $context->attemptSignals);
        if ($matchingAttempts->isEmpty()) {
            return $this->result('pending', 0.0, false);
        }

        $assignedMistakeIds = collect($task['assigned_mistake_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();

        if ($assignedMistakeIds->isEmpty()) {
            return $this->evaluateScoredTopicTask($task, $topic, $context, 70.0);
        }

        $difficulty = (string) ($task['repair_difficulty'] ?? $context->classicRepairDifficulty ?? 'easy');
        $requiredStreak = $difficulty === 'hard' ? 3 : 2;

        $masteredCount = $this->resolvedClassicMasteryCount($assignedMistakeIds->all(), $requiredStreak);
        $progress = $assignedMistakeIds->count() > 0
            ? round($masteredCount / $assignedMistakeIds->count(), 4)
            : 0.0;

        if ($progress >= 1.0) {
            return $this->result('complete', 1.0, true, [
                'assigned_count' => $assignedMistakeIds->count(),
                'mastered_count' => $masteredCount,
            ]);
        }

        if ($masteredCount > 0) {
            return $this->result('partial', $progress, true, [
                'assigned_count' => $assignedMistakeIds->count(),
                'mastered_count' => $masteredCount,
            ]);
        }

        return $this->result('pending', 0.0, true);
    }

    private function evaluateScoredTopicTask(array $task, ?Topic $topic, PlannerContext $context, float $passThreshold): array
    {
        if (!($topic instanceof Topic)) {
            return $this->result('pending', 0.0, false);
        }

        $matchingAttempts = $this->matchingTopicAttempts($task, $context->attemptSignals);
        if ($matchingAttempts->isEmpty()) {
            return $this->result('pending', 0.0, false);
        }

        $bestPercentage = (float) $matchingAttempts->max(fn (array $attempt) => (float) ($attempt['percentage_grade'] ?? 0.0));
        if ($bestPercentage >= $passThreshold) {
            return $this->result('complete', 1.0, true, [
                'best_percentage_grade' => round($bestPercentage, 2),
            ]);
        }

        return $this->result(
            'partial',
            round(max(0.0, min(0.99, $bestPercentage / max($passThreshold, 1.0))), 4),
            true,
            ['best_percentage_grade' => round($bestPercentage, 2)]
        );
    }

    private function evaluateProgressionQuizTask(array $task, ?Topic $topic, PlannerContext $context): array
    {
        if (!($topic instanceof Topic)) {
            return $this->result('pending', 0.0, false);
        }

        $matchingAttempts = $this->matchingTopicAttempts($task, $context->attemptSignals);
        if ($matchingAttempts->isEmpty()) {
            return $this->result('pending', 0.0, false);
        }

        $profile = data_get($context->topicSignals, 'profiles.' . $topic->id, []);
        $questionCount = (int) ($profile['stage_two_question_count'] ?? 0);
        $accuracy = (float) ($profile['stage_two_accuracy'] ?? 0.0);
        $passReady = (bool) ($profile['stage_two_pass_ready'] ?? false);
        $fallbackUsed = (bool) ($profile['stage_two_fallback_used'] ?? false);

        if ($passReady) {
            return $this->result('complete', 1.0, true, [
                'best_percentage_grade' => round($accuracy, 2),
                'stage_two_question_count' => $questionCount,
                'stage_two_fallback_used' => $fallbackUsed,
            ]);
        }

        if ($questionCount > 0) {
            $progress = $fallbackUsed
                ? round(max(0.0, min(0.99, $accuracy / 70.0)), 4)
                : round(max(0.0, min(0.99, ($accuracy / 70.0) * min(1.0, $questionCount / 2))), 4);

            return $this->result('partial', $progress, true, [
                'best_percentage_grade' => round($accuracy, 2),
                'stage_two_question_count' => $questionCount,
                'stage_two_fallback_used' => $fallbackUsed,
            ]);
        }

        return $this->result('pending', 0.0, true);
    }

    private function evaluateFinalExamSprintTask(array $task, ?Topic $topic, PlannerContext $context, float $passThreshold): array
    {
        if ((string) ($task['simulation_scope'] ?? '') === 'frontier_replace' && $topic instanceof Topic) {
            return $this->evaluateScoredTopicTask($task, $topic, $context, $passThreshold);
        }

        return $this->evaluateRandomSprintTask($task, $context, $passThreshold);
    }

    private function evaluateRandomSprintTask(array $task, PlannerContext $context, float $passThreshold): array
    {
        $matchingAttempts = $this->matchingRandomAttempts($task, $context->attemptSignals);
        if ($matchingAttempts->isEmpty()) {
            return $this->result('pending', 0.0, false);
        }

        $bestPercentage = (float) $matchingAttempts->max(fn (array $attempt) => (float) ($attempt['percentage_grade'] ?? 0.0));
        if ($bestPercentage >= $passThreshold) {
            return $this->result('complete', 1.0, true, [
                'best_percentage_grade' => round($bestPercentage, 2),
            ]);
        }

        return $this->result(
            'partial',
            round(max(0.0, min(0.99, $bestPercentage / max($passThreshold, 1.0))), 4),
            true,
            ['best_percentage_grade' => round($bestPercentage, 2)]
        );
    }

    private function evaluateSpacedReviewTask(array $task, PlannerContext $context): array
    {
        $assignedMistakeIds = collect($task['assigned_mistake_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();

        if ($assignedMistakeIds->isEmpty()) {
            return $this->result('pending', 0.0, false);
        }

        $matchingAttempts = $this->matchingTopicAttempts($task, $context->attemptSignals);
        if ($matchingAttempts->isEmpty()) {
            return $this->result('pending', 0.0, false);
        }

        $stillDueIds = Mistake::query()
            ->whereIn('id', $assignedMistakeIds->all())
            ->dueForReview('spaced')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $clearedCount = $assignedMistakeIds->count() - count($stillDueIds);
        $progress = round($clearedCount / max(1, $assignedMistakeIds->count()), 4);

        if (empty($stillDueIds)) {
            return $this->result('complete', 1.0, true, [
                'assigned_due_count' => $assignedMistakeIds->count(),
                'cleared_count' => $clearedCount,
            ]);
        }

        if ($clearedCount > 0) {
            return $this->result('partial', $progress, true, [
                'assigned_due_count' => $assignedMistakeIds->count(),
                'cleared_count' => $clearedCount,
                'remaining_due_ids' => array_values($stillDueIds),
            ]);
        }

        return $this->result('pending', 0.0, true);
    }

    private function evaluateMistakesCleanupTask(array $task, PlannerContext $context): array
    {
        $startingCount = max(0, (int) ($task['mistake_start_count'] ?? 0));
        $currentCount = max(0, $context->openMistakesCount);
        if ($startingCount <= 0) {
            return $this->result('pending', 0.0, false);
        }

        $clearedCount = max(0, $startingCount - $currentCount);
        $progress = round($clearedCount / max(1, $startingCount), 4);

        if ($currentCount <= 0) {
            return $this->result('complete', 1.0, true, [
                'starting_count' => $startingCount,
                'cleared_count' => $clearedCount,
            ]);
        }

        if ($clearedCount > 0) {
            return $this->result('partial', $progress, true, [
                'starting_count' => $startingCount,
                'cleared_count' => $clearedCount,
            ]);
        }

        return $this->result('pending', 0.0, false);
    }

    private function evaluateConceptRepairTask(array $task, PlannerContext $context): array
    {
        $topicId = (int) ($task['topic_id'] ?? 0);
        $conceptId = (int) ($task['concept_id'] ?? 0);
        if ($topicId <= 0 || $conceptId <= 0) {
            return $this->result('pending', 0.0, false);
        }

        $currentSignal = collect($context->conceptSignals['by_topic'][$topicId] ?? [])
            ->first(fn (array $signal) => (int) ($signal['concept_id'] ?? 0) === $conceptId);

        if ($currentSignal === null) {
            return $this->result('complete', 1.0, true);
        }

        $matchingAttempts = $this->matchingTopicAttempts($task, $context->attemptSignals);
        $startingRisk = (float) ($task['concept_start_risk'] ?? 0.0);
        $currentRisk = (float) ($currentSignal['risk_score'] ?? 0.0);
        $currentRetrievability = (float) ($currentSignal['retrievability'] ?? 0.9);

        if ($currentRetrievability >= 0.72
            && $currentRisk <= max(0.85, $startingRisk - 0.35)
            && $matchingAttempts->isNotEmpty()) {
            return $this->result('complete', 1.0, true);
        }

        return $matchingAttempts->isNotEmpty()
            ? $this->result('partial', 0.5, true)
            : $this->result('pending', 0.0, false);
    }

    private function evaluateConceptClusterRepairTask(array $task, PlannerContext $context): array
    {
        $topicId = (int) ($task['topic_id'] ?? 0);
        $conceptIds = collect($task['cluster_concept_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();

        if ($topicId <= 0 || $conceptIds->isEmpty()) {
            return $this->result('pending', 0.0, false);
        }

        $currentSignals = collect($context->conceptSignals['by_topic'][$topicId] ?? [])
            ->filter(fn (array $signal) => $conceptIds->contains((int) ($signal['concept_id'] ?? 0)))
            ->values();

        if ($currentSignals->isEmpty()) {
            return $this->result('complete', 1.0, true);
        }

        $matchingAttempts = $this->matchingTopicAttempts($task, $context->attemptSignals);
        $startingRisk = (float) ($task['cluster_start_risk'] ?? 0.0);
        $currentAverageRisk = (float) $currentSignals->avg(fn (array $signal) => (float) ($signal['risk_score'] ?? 0.0));
        $currentAverageRetrievability = (float) $currentSignals->avg(fn (array $signal) => (float) ($signal['retrievability'] ?? 0.9));

        if ($currentAverageRetrievability >= 0.74
            && $currentAverageRisk <= max(0.90, $startingRisk - 0.35)
            && $matchingAttempts->isNotEmpty()) {
            return $this->result('complete', 1.0, true);
        }

        return $matchingAttempts->isNotEmpty()
            ? $this->result('partial', 0.5, true)
            : $this->result('pending', 0.0, false);
    }

    private function resolvedClassicMasteryCount(array $assignedMistakeIds, int $requiredStreak): int
    {
        if ($requiredStreak <= 0 || empty($assignedMistakeIds)) {
            return 0;
        }

        if (!Schema::hasTable('user_mistake_mastery')) {
            return Mistake::query()
                ->whereIn('id', $assignedMistakeIds)
                ->get(['id', 'correct_streak', 'mastered'])
                ->filter(fn (Mistake $mistake) => ((bool) $mistake->mastered) || ((int) $mistake->correct_streak >= $requiredStreak))
                ->count();
        }

        return DB::table('mistakes')
            ->leftJoin('user_mistake_mastery as mastery', function ($join) {
                $join->on('mastery.user_id', '=', 'mistakes.user_id')
                    ->on('mastery.question_id', '=', 'mistakes.question_id');
            })
            ->whereIn('mistakes.id', $assignedMistakeIds)
            ->where(function ($query) use ($requiredStreak) {
                $query->where('mastery.consecutive_correct', '>=', $requiredStreak)
                    ->orWhere(function ($fallback) use ($requiredStreak) {
                        $fallback->whereNull('mastery.id')
                            ->where(function ($legacy) use ($requiredStreak) {
                                $legacy->where('mistakes.mastered', true)
                                    ->orWhere('mistakes.correct_streak', '>=', $requiredStreak);
                            });
                    });
            })
            ->count();
    }

    private function topicHasAttempt(Topic $topic, array $topicSignals = []): bool
    {
        $attemptedTopicIds = $topicSignals['attempted_topic_ids'] ?? [];
        if (in_array((int) $topic->id, $attemptedTopicIds, true)) {
            return true;
        }

        $progress = $topic->userProgress->first();

        return $topic->last_attempt_date !== null || $progress?->last_attempt_date !== null;
    }

    private function matchingTopicAttempts(array $task, array $attemptSignals)
    {
        $topicId = (int) ($task['topic_id'] ?? 0);
        if ($topicId <= 0) {
            return collect();
        }

        $requiredMode = $this->resolveRequiredStudyMode($task);
        $requiredSource = $task['source'] ?? null;

        return collect($attemptSignals['topic_attempts_today'] ?? [])->filter(function (array $attempt) use ($topicId, $requiredMode, $requiredSource) {
            $attemptMode = (string) ($attempt['study_mode'] ?? 'quiz');
            $attemptTopicId = isset($attempt['topic_id']) ? (int) $attempt['topic_id'] : null;
            $isRandomAttempt = (string) ($attempt['session_type'] ?? '') === 'random_quiz'
                || (string) ($attempt['quiz_type'] ?? '') === 'random';
            $modeMatches = $attemptMode === $requiredMode;
            $sourceMatches = $requiredSource === null
                || strcasecmp((string) ($attempt['source'] ?? ''), (string) $requiredSource) === 0;

            return $attemptTopicId === $topicId
                && $modeMatches
                && !$isRandomAttempt
                && $sourceMatches;
        })->values();
    }

    private function matchingRandomAttempts(array $task, array $attemptSignals)
    {
        $requiredMode = $this->resolveRequiredStudyMode($task);
        $requiredSource = $task['source'] ?? null;
        $requiredQuestions = max(1, (int) ($task['question_target'] ?? 1));
        $coveredTopicIds = array_values(array_unique(array_map('intval', $task['covered_topic_ids'] ?? [])));
        $courseTopicIds = $coveredTopicIds !== []
            ? $coveredTopicIds
            : array_map('intval', $attemptSignals['course_topic_ids'] ?? []);

        return collect($attemptSignals['random_attempts_today'] ?? [])->filter(function (array $attempt) use ($requiredMode, $requiredSource, $requiredQuestions, $courseTopicIds) {
            $attemptMode = (string) ($attempt['study_mode'] ?? 'quiz');
            $attemptTopicId = isset($attempt['topic_id']) ? (int) $attempt['topic_id'] : null;
            $modeMatches = $attemptMode === $requiredMode;
            $topicMatches = $attemptTopicId !== null && in_array($attemptTopicId, $courseTopicIds, true);
            $sourceMatches = $requiredSource === null
                || trim((string) ($attempt['source'] ?? '')) === ''
                || strcasecmp((string) ($attempt['source'] ?? ''), (string) $requiredSource) === 0;

            return $modeMatches
                && $topicMatches
                && $sourceMatches
                && (int) ($attempt['total_questions'] ?? 0) >= max(5, (int) floor($requiredQuestions * 0.8));
        })->values();
    }

    private function topicHasReviewableBacklog(int $topicId, PlannerContext $context): bool
    {
        $topicSignal = collect($context->backlogSignals['by_topic'] ?? [])
            ->first(fn (array $signal) => (int) ($signal['topic_id'] ?? 0) === $topicId);

        return (int) ($topicSignal['reviewable_backlog'] ?? 0) > 0;
    }

    private function applyRegressionTransitions(User $user, Collection $topics, PlannerContext $context): bool
    {
        $progressRows = UserTopicProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('topic_id', $topics->pluck('id')->map(fn ($id) => (int) $id)->all())
            ->whereNotNull('planner_cleared_at')
            ->get();

        $changed = false;

        foreach ($progressRows as $progress) {
            $topicId = (int) $progress->topic_id;
            $profile = data_get($context->topicSignals, 'profiles.' . $topicId, []);
            $preferredSource = ((bool) ($profile['has_preferred_source_material'] ?? false))
                ? $context->preferredSource
                : null;
            $latestAttempt = $this->resolveLatestStructuredQuizAttemptAfterClear(
                $user->id,
                $topicId,
                $progress->planner_cleared_at,
                $preferredSource
            );

            if (!$latestAttempt) {
                continue;
            }

            if ((float) ($latestAttempt->percentage_grade ?? 0.0) >= 70.0) {
                continue;
            }

            $progress->planner_quiz_cleared_at = null;
            $progress->planner_cleared_at = null;

            if ($progress->isDirty(['planner_quiz_cleared_at', 'planner_cleared_at'])) {
                $progress->save();
                $changed = true;
            }
        }

        return $changed;
    }

    private function resolveLatestStructuredQuizAttemptAfterClear(
        int $userId,
        int $topicId,
        ?Carbon $plannerClearedAt,
        ?string $preferredSource = null
    ): ?QuizAttempt {
        if (!$plannerClearedAt instanceof Carbon) {
            return null;
        }

        return QuizAttempt::query()
            ->where('user_id', $userId)
            ->where('topic_id', $topicId)
            ->where('study_mode', 'quiz')
            ->where(function ($query) {
                $query->whereNull('session_type')
                    ->orWhere('session_type', '!=', 'random_quiz');
            })
            ->when($preferredSource !== null, function ($query) use ($preferredSource) {
                $query->where('source', $preferredSource);
            })
            ->where('created_at', '>', $plannerClearedAt->copy()->addHours(48))
            ->orderByDesc('created_at')
            ->first(['percentage_grade', 'created_at', 'study_mode', 'session_type', 'source']);
    }

    private function result(string $state, float $progress, bool $matchedMode, array $meta = []): array
    {
        return [
            'state' => $state,
            'progress' => round(max(0.0, min(1.0, $progress)), 4),
            'matched_mode' => $matchedMode,
            'meta' => $meta,
        ];
    }

    private function resolveRequiredStudyMode(array $task): string
    {
        $configuredMode = (string) ($task['study_mode'] ?? '');
        if ($configuredMode !== '') {
            return $configuredMode;
        }

        return match ((string) ($task['type'] ?? '')) {
            'start_topic', 'rapid_overview', 'concept_repair', 'concept_cluster_repair' => 'tutor',
            'topic_mistake_review', 'mistakes_cleanup' => 'mistake_review',
            default => 'quiz',
        };
    }
}
