<?php

namespace App\Services\Planner;

use App\Models\Mistake;
use App\Models\Topic;
use Illuminate\Support\Collection;

class PlannerTaskBuilderService
{
    public function __construct()
    {
    }

    public function bucketTopics(Collection $topics, PlannerContext $context): PlannerTopicBuckets
    {
        $orderedOpenTopics = $topics
            ->filter(fn (Topic $topic) => !$topic->is_locked)
            ->sortBy(fn (Topic $topic) => sprintf(
                '%010d-%010d',
                (int) ($topic->display_order ?? PHP_INT_MAX),
                (int) $topic->id
            ))
            ->values();
        [$openTopics, $frontierTopicId, $progressionLocked] = $this->scopeTopicsToProgressionFrontier($orderedOpenTopics, $context);

        $untouchedTopics = $openTopics
            ->filter(fn (Topic $topic) => data_get($context->topicSignals, 'profiles.' . $topic->id . '.progression_stage') === 'tutor')
            ->sortBy(fn (Topic $topic) => sprintf(
                '%010d-%010d',
                (int) ($topic->display_order ?? PHP_INT_MAX),
                (int) $topic->id
            ))
            ->values();
        $weakTopics = $openTopics
            ->filter(fn (Topic $topic) => (bool) data_get($context->topicSignals, 'profiles.' . $topic->id . '.is_weak', false))
            ->sortByDesc(fn (Topic $topic) => $this->topicPriorityScore($topic, $context))
            ->values();
        $nearMasteryTopics = $openTopics
            ->filter(fn (Topic $topic) => (bool) data_get($context->topicSignals, 'profiles.' . $topic->id . '.is_secure', false))
            ->sortByDesc(fn (Topic $topic) => $this->topicGrade($topic, $context))
            ->values();
        $dueMistakeTopics = collect($context->dueMistakeSignals['by_topic'] ?? [])
            ->filter(fn (array $signal) => $openTopics->contains('id', (int) ($signal['topic_id'] ?? 0)))
            ->sortByDesc(fn (array $signal) => ((int) ($signal['due_count'] ?? 0) * 1000)
                + ((int) ($signal['minutes_overdue'] ?? 0))
                + ((float) ($signal['average_risk'] ?? 0.0) * 500))
            ->values();

        return new PlannerTopicBuckets(
            openTopics: $openTopics,
            allOpenTopics: $orderedOpenTopics,
            untouchedTopics: $untouchedTopics,
            weakTopics: $weakTopics,
            nearMasteryTopics: $nearMasteryTopics,
            dueMistakeTopics: $dueMistakeTopics,
            masteredTopicsCount: $openTopics->filter(fn (Topic $topic) => $this->topicGrade($topic, $context) >= 80)->count(),
            attemptedTopicsCount: $openTopics->reject(fn (Topic $topic) => (bool) data_get($context->topicSignals, 'profiles.' . $topic->id . '.is_untouched', false))->count(),
            frontierTopicId: $frontierTopicId,
            progressionLocked: $progressionLocked,
        );
    }

    public function buildProtocolTasks(Collection $topics, PlannerContext $context): Collection
    {
        $buckets = $this->bucketTopics($topics, $context);
        $tasks = collect();

        $frontierTask = $this->buildFrontierProtocolTask($buckets, $context);
        if ($frontierTask !== null) {
            $slot1 = $this->withProtocolMetadata($frontierTask, 1, null, true);
            $previews = $this->buildProgressionPreviewTasks($topics, $context, $slot1);
            return collect([$slot1])->concat($previews)->values();
        }

        $backfillBuilders = [
            fn (int $remaining) => $this->buildClearedRepairBackfillTasks($topics, $buckets, $context, $remaining),
            fn (int $remaining) => $this->buildClearedSpacedBackfillTasks($topics, $buckets, $context, $remaining),
            fn (int $remaining) => $this->buildClearedQuizBackfillTasks($topics, $buckets, $context, $remaining),
            fn (int $remaining) => $this->buildClearedNeglectBackfillTasks($topics, $buckets, $context, $remaining),
            fn (int $remaining) => $this->buildSimulationBackfillTasks($topics, $buckets, $context, $remaining),
            fn (int $remaining) => $this->buildEarlyMistakeCleanupBackfillTasks($topics, $buckets, $context, $remaining),
        ];

        foreach ($backfillBuilders as $levelIndex => $builder) {
            $remaining = max(0, $context->tasksLimit - $tasks->count());
            if ($remaining <= 0) {
                break;
            }

            foreach ($builder($remaining) as $task) {
                if ($tasks->contains(fn (array $existing) => ($existing['id'] ?? null) === ($task['id'] ?? null))) {
                    continue;
                }

                $tasks->push($this->withProtocolMetadata(
                    $task,
                    min($context->tasksLimit, $tasks->count() + 1),
                    $levelIndex + 1,
                    false
                ));

                if ($tasks->count() >= $context->tasksLimit) {
                    break 2;
                }
            }
        }

        return $tasks->take($context->tasksLimit)->values();
    }

    public function buildProgressionPreviewTasks(Collection $topics, PlannerContext $context, array $actionableTask): Collection
    {
        if ((bool) ($actionableTask['is_preview'] ?? false)
            || (bool) ($actionableTask['completed'] ?? false)
            || !(bool) ($actionableTask['counts_toward_progression'] ?? false)) {
            return collect();
        }

        $topicId = (int) ($actionableTask['topic_id'] ?? 0);
        if ($topicId <= 0) {
            return collect();
        }

        $orderedTopics = $topics
            ->filter(fn (Topic $topic) => !$topic->is_locked)
            ->sortBy(fn (Topic $topic) => sprintf(
                '%010d-%010d',
                (int) ($topic->display_order ?? PHP_INT_MAX),
                (int) $topic->id
            ))
            ->values();
        $currentTopic = $orderedTopics->firstWhere('id', $topicId);
        if (!($currentTopic instanceof Topic)) {
            return collect();
        }

        $profile = data_get($context->topicSignals, 'profiles.' . $currentTopic->id, []);
        $nextTopic = $this->nextPreviewTopic($orderedTopics, $currentTopic);
        $previews = collect();

        if ($this->isStudyProgressionTask($actionableTask)) {
            $slotTwo = $this->buildSolvePreviewTask($currentTopic, $context);
            if ($slotTwo !== null) {
                $previews->push($this->withPreviewMetadata($slotTwo, 2, (string) $actionableTask['id']));
            }
            // No slot 3 — cannot know if mistakes will exist before quiz is taken
            return $previews->values();
        }

        if ($this->isSolveProgressionTask($actionableTask)) {
            // Only show "Revise Mistakes" preview if the topic actually has or is
            // likely to have a reviewable backlog. If the student answered everything
            // correctly, there's nothing to revise — show the next topic's Study instead.
            $hasBacklog = (bool) ($profile['has_reviewable_backlog'] ?? false)
                || (bool) ($profile['has_due_review'] ?? false);

            if ($hasBacklog) {
                $slotTwo = $this->buildRepairPreviewTask($currentTopic, $context);
                if ($slotTwo !== null) {
                    $previews->push($this->withPreviewMetadata($slotTwo, 2, (string) $actionableTask['id']));
                }

                if ($nextTopic !== null && $previews->isNotEmpty()) {
                    $previews->push($this->withPreviewMetadata(
                        $this->buildStudyPreviewTask($nextTopic, $context),
                        3,
                        (string) $previews->last()['id']
                    ));
                }
            } else {
                // No mistakes exist (or will exist) — skip repair, show next topic progression
                if ($nextTopic !== null) {
                    $slotTwo = $this->buildStudyPreviewTask($nextTopic, $context);
                    $previews->push($this->withPreviewMetadata($slotTwo, 2, (string) $actionableTask['id']));
                    $slotThree = $this->buildSolvePreviewTask($nextTopic, $context);
                    if ($slotThree !== null) {
                        $previews->push($this->withPreviewMetadata($slotThree, 3, (string) $previews->last()['id']));
                    }
                }
            }

            return $previews->filter()->values();
        }

        if ($this->isRepairProgressionTask($actionableTask)) {
            if ($nextTopic !== null) {
                $slotTwo = $this->buildStudyPreviewTask($nextTopic, $context);
                $previews->push($this->withPreviewMetadata($slotTwo, 2, (string) $actionableTask['id']));
                $slotThree = $this->buildSolvePreviewTask($nextTopic, $context);
                if ($slotThree !== null) {
                    $previews->push($this->withPreviewMetadata($slotThree, 3, (string) $previews->last()['id']));
                }
            }
            // if nextTopic is null (last topic in repair stage), previews stays empty — correct, nothing left to show
        }

        return $previews->filter()->values();
    }

    private function buildFrontierProtocolTask(PlannerTopicBuckets $buckets, PlannerContext $context): ?array
    {
        $topic = $buckets->allOpenTopics->firstWhere('id', $buckets->frontierTopicId ?? 0);
        if (!$topic) {
            return null;
        }

        $profile = data_get($context->topicSignals, 'profiles.' . $topic->id, []);
        $stage = (string) ($profile['progression_stage'] ?? '');

        $task = match ($stage) {
            'tutor' => $this->buildStartTopicTasks(collect([$topic]), $context, 1, 16)->first(),
            'quiz' => $this->buildFrontierQuizProtocolTask($buckets, $context, $topic, $profile),
            'mistakes' => $this->buildFrontierRepairProtocolTask($topic, $context, 16),
            default => null,
        };

        // If the frontier is in 'mistakes' stage but there are zero mistakes to review
        // (e.g. student got 100% on the quiz — no wrong answers), the repair builder
        // returns null. In this case the topic has effectively cleared — skip to the
        // next untouched topic's Study task instead of leaving the plan empty.
        if ($task === null && $stage === 'mistakes') {
            $nextTopic = $this->nextPreviewTopic($buckets->allOpenTopics, $topic);
            if ($nextTopic !== null) {
                $nextProfile = data_get($context->topicSignals, 'profiles.' . $nextTopic->id, []);
                $nextStage = (string) ($nextProfile['progression_stage'] ?? '');
                if ($nextStage === 'tutor') {
                    $task = $this->buildStartTopicTasks(collect([$nextTopic]), $context, 1, 16)->first();
                }
            }
        }

        return $task;
    }

    private function buildFrontierQuizProtocolTask(
        PlannerTopicBuckets $buckets,
        PlannerContext $context,
        Topic $topic,
        array $profile
    ): ?array {
        if ($context->daysToExam !== null && $context->daysToExam >= 0 && $context->daysToExam < 3) {
            return $this->buildSingleFinalExamSprintTask($context, 16, 'frontier_replace', [(int) $topic->id], $topic);
        }

        if ((bool) ($profile['quiz_failed_repeatedly'] ?? false) || (bool) ($profile['is_regressed'] ?? false)) {
            return $this->buildWeakTopicTutorTasks(collect([$topic]), $context, 1, 16)->first();
        }

        return $this->buildFrontierSolveTask($buckets, $context, 16);
    }

    private function buildFrontierRepairProtocolTask(Topic $topic, PlannerContext $context, int $priorityOffset = 0): ?array
    {
        if ($context->repairMode === 'spaced') {
            $signal = $this->resolveSpacedSignalForTopic((int) $topic->id, $context, false);

            return $signal !== null
                ? $this->buildSingleSpacedReviewTask($topic, $signal, $context, $priorityOffset, 'Revise Mistakes')
                : null;
        }

        return $this->buildSingleClassicRepairTask($topic, $context, $priorityOffset, 'Revise');
    }

    private function buildClearedRepairBackfillTasks(
        Collection $topics,
        PlannerTopicBuckets $buckets,
        PlannerContext $context,
        int $limit
    ): Collection {
        $clearedTopics = $this->clearedBackfillTopics($topics, $buckets, $context);

        if ($context->repairMode === 'spaced') {
            return $clearedTopics
                ->filter(fn (Topic $topic) => (bool) data_get($context->topicSignals, 'profiles.' . $topic->id . '.has_due_review', false))
                ->map(function (Topic $topic) use ($context) {
                    $signal = $this->resolveSpacedSignalForTopic((int) $topic->id, $context, false);

                    return $signal !== null
                        ? $this->buildSingleSpacedReviewTask($topic, $signal, $context, 10, 'Revise Mistakes')
                        : null;
                })
                ->filter()
                ->take($limit)
                ->values();
        }

        return $clearedTopics
            ->map(fn (Topic $topic) => $this->buildSingleClassicRepairTask($topic, $context, 10, 'Revise'))
            ->filter()
            ->take($limit)
            ->values();
    }

    private function buildClearedSpacedBackfillTasks(
        Collection $topics,
        PlannerTopicBuckets $buckets,
        PlannerContext $context,
        int $limit
    ): Collection {
        if ($context->repairMode !== 'spaced') {
            return collect();
        }

        return $this->clearedBackfillTopics($topics, $buckets, $context)
            ->filter(fn (Topic $topic) => (bool) data_get($context->topicSignals, 'profiles.' . $topic->id . '.has_due_review', false))
            ->map(function (Topic $topic) use ($context) {
                $signal = $this->resolveSpacedSignalForTopic((int) $topic->id, $context, true);

                return $signal !== null
                    ? $this->buildSingleSpacedReviewTask($topic, $signal, $context, 8, 'Revise Mistakes')
                    : null;
            })
            ->filter()
            ->take($limit)
            ->values();
    }

    private function buildClearedQuizBackfillTasks(
        Collection $topics,
        PlannerTopicBuckets $buckets,
        PlannerContext $context,
        int $limit
    ): Collection {
        return $this->clearedBackfillTopics($topics, $buckets, $context)
            ->reject(fn (Topic $topic) => $context->repairMode === 'spaced' 
                ? (bool) data_get($context->topicSignals, 'profiles.' . $topic->id . '.has_due_review', false)
                : (bool) data_get($context->topicSignals, 'profiles.' . $topic->id . '.has_reviewable_backlog', false))
            ->sortByDesc(fn (Topic $topic) => (float) data_get($context->topicSignals, 'profiles.' . $topic->id . '.priority_score', 0.0))
            ->map(fn (Topic $topic) => $this->buildSecureTopicTasks(collect([$topic]), $context, 1, 6)->first())
            ->filter()
            ->take($limit)
            ->values();
    }

    private function buildClearedNeglectBackfillTasks(
        Collection $topics,
        PlannerTopicBuckets $buckets,
        PlannerContext $context,
        int $limit
    ): Collection {
        return $this->clearedBackfillTopics($topics, $buckets, $context)
            ->reject(fn (Topic $topic) => $context->repairMode === 'spaced' 
                ? (bool) data_get($context->topicSignals, 'profiles.' . $topic->id . '.has_due_review', false)
                : (bool) data_get($context->topicSignals, 'profiles.' . $topic->id . '.has_reviewable_backlog', false))
            ->filter(fn (Topic $topic) => (int) data_get($context->topicSignals, 'profiles.' . $topic->id . '.hours_since_last_activity', 0) > 336)
            ->sortByDesc(fn (Topic $topic) => (int) data_get($context->topicSignals, 'profiles.' . $topic->id . '.hours_since_last_activity', 0))
            ->map(fn (Topic $topic) => $this->buildSecureTopicTasks(collect([$topic]), $context, 1, 4)->first())
            ->filter()
            ->take($limit)
            ->values();
    }

    private function buildSimulationBackfillTasks(
        Collection $topics,
        PlannerTopicBuckets $buckets,
        PlannerContext $context,
        int $limit
    ): Collection {
        if ($limit <= 0) {
            return collect();
        }

        if ($context->daysToExam === null || $context->daysToExam < 3 || $context->daysToExam > 7) {
            return collect();
        }

        $coveredTopicIds = $this->clearedBackfillTopics($topics, $buckets, $context)
            ->reject(fn (Topic $topic) => $context->repairMode === 'spaced' 
                ? (bool) data_get($context->topicSignals, 'profiles.' . $topic->id . '.has_due_review', false)
                : (bool) data_get($context->topicSignals, 'profiles.' . $topic->id . '.has_reviewable_backlog', false))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->take(6)
            ->values()
            ->all();

        if ($coveredTopicIds === []) {
            return collect();
        }

        $task = $this->buildSingleFinalExamSprintTask($context, 6, 'backfill_mixed', $coveredTopicIds);

        return $task ? collect([$task]) : collect();
    }

    private function buildEarlyMistakeCleanupBackfillTasks(
        Collection $topics,
        PlannerTopicBuckets $buckets,
        PlannerContext $context,
        int $limit
    ): Collection {
        $candidates = $topics
            ->sortBy(fn (Topic $topic) => sprintf(
                '%010d-%010d',
                (int) ($topic->display_order ?? PHP_INT_MAX),
                (int) $topic->id
            ))
            ->filter(function (Topic $topic) use ($buckets, $context) {
                if ((int) $topic->id === (int) $buckets->frontierTopicId) {
                    return false;
                }

                $profile = data_get($context->topicSignals, 'profiles.' . $topic->id, []);

                return (!(bool) ($profile['curriculum_unlocked'] ?? true) || (bool) $topic->is_locked)
                    && (bool) ($profile['has_reviewable_backlog'] ?? false);
            })
            ->values();

        if ($context->repairMode === 'spaced') {
            return $candidates
                ->map(function (Topic $topic) use ($context) {
                    $signal = $this->resolveSpacedSignalForTopic((int) $topic->id, $context, false);

                    return $signal !== null
                        ? $this->buildSingleSpacedReviewTask($topic, $signal, $context, 2, 'Clear Early Mistakes')
                        : null;
                })
                ->filter()
                ->take($limit)
                ->values();
        }

        return $candidates
            ->map(fn (Topic $topic) => $this->buildSingleClassicRepairTask($topic, $context, 2, 'Clear Early Mistakes'))
            ->filter()
            ->take($limit)
            ->values();
    }

    private function clearedBackfillTopics(
        Collection $topics,
        PlannerTopicBuckets $buckets,
        PlannerContext $context
    ): Collection {
        return $topics
            ->filter(fn (Topic $topic) => !$topic->is_locked)
            ->reject(fn (Topic $topic) => (int) $topic->id === (int) $buckets->frontierTopicId)
            ->filter(fn (Topic $topic) => data_get($context->topicSignals, 'profiles.' . $topic->id . '.progression_stage') === 'cleared')
            ->sortBy(fn (Topic $topic) => sprintf(
                '%010d-%010d',
                (int) ($topic->display_order ?? PHP_INT_MAX),
                (int) $topic->id
            ))
            ->values();
    }

    private function buildSingleClassicRepairTask(
        Topic $topic,
        PlannerContext $context,
        int $priorityOffset = 0,
        string $titlePrefix = 'Revise'
    ): ?array {
        $sourceLabel = $this->resolveTargetSourceForTopic($topic, $context->topicSignals, $context->preferredSource);
        $conceptSignal = $this->weakestConceptSignal((int) $topic->id, $context);
        $conceptLabel = $this->displayConceptLabel($conceptSignal);
        $assignedMistakeIds = $this->assignedClassicMistakeIds((int) $topic->id, $context);
        [$assignedMistakeIds, $sourceLabel] = $this->scopeAssignedMistakeIdsToSource($assignedMistakeIds, $sourceLabel);

        if (empty($assignedMistakeIds)) {
            return null;
        }

        $sizing = $this->applySizingProfile(
            $context,
            'repair_topic',
            'quiz',
            min($this->resolveTopicQuestionCount($context->topicSignals, (int) $topic->id, 12), count($assignedMistakeIds)),
            $this->resolveTopicDuration('repair', $context->daysToExam)
        );
        $taskId = 'repair_topic_' . $topic->id . '_' . $this->slugSource($sourceLabel) . '_' . $this->slugSource($titlePrefix);
        $score = $this->scoreTask([
            'base' => 92,
            'topic_priority' => $this->topicPriorityScore($topic, $context),
            'concept_risk_bonus' => min(10, (int) round(((float) ($conceptSignal['risk_score'] ?? 0.0)) * 4)),
            'priority_offset' => $priorityOffset,
        ]);
        $xpValue = $this->resolveXpValue('repair_topic', $score['priority'], $context);
        $repairDifficulty = $context->repairMode === 'hard' ? 'hard' : 'easy';

        return [
            'id' => $taskId,
            'type' => 'repair_topic',
            'topic_id' => $topic->id,
            'title' => $this->buildTopicTitle($titlePrefix, $topic->name, $conceptLabel),
            'meta' => $this->buildTaskMeta('Quiz', $sourceLabel, $sizing['question_target'], $sizing['duration_minutes']),
            'xp' => $xpValue,
            'xp_value' => $xpValue,
            'base_xp' => $this->baseXpForType('repair_topic'),
            'source' => $sourceLabel,
            'resolved_source' => $sourceLabel,
            'study_mode' => 'quiz',
            'mode_label' => 'Quiz',
            'repair_mode' => $repairDifficulty,
            'repair_difficulty' => $repairDifficulty,
            'difficulty_locked_at' => now()->toIso8601String(),
            'assigned_mistake_ids' => array_slice($assignedMistakeIds, 0, $sizing['question_target']),
            'assigned_due_count' => count($assignedMistakeIds),
            'target_concepts' => $this->resolveConceptTargets($topic, $context, $conceptLabel),
            'concept_tagging_complete' => false,
            'is_regression' => false,
            'spawned_from_task_id' => null,
            'question_target' => $sizing['question_target'],
            'duration_minutes' => $sizing['duration_minutes'],
            'priority' => $score['priority'],
            'priority_score' => $score['priority'],
            'score_components' => $score['components'],
            'sizing_basis' => $sizing['sizing_basis'],
            'counts_toward_progression' => true,
        ] + [];
    }

    private function buildSingleSpacedReviewTask(
        Topic $topic,
        array $topicDueSignal,
        PlannerContext $context,
        int $priorityOffset = 0,
        string $titlePrefix = 'Revise Mistakes'
    ): ?array {
        $conceptSignal = $this->weakestConceptSignal((int) $topic->id, $context);
        $conceptLabel = $this->displayConceptLabel($conceptSignal);
        $dueCount = (int) ($topicDueSignal['due_count'] ?? count($topicDueSignal['assigned_mistake_ids'] ?? []));
        $assignedMistakeIds = $this->assignedSpacedMistakeIds($topicDueSignal);
        [$assignedMistakeIds, $resolvedSourceLabel] = $this->scopeAssignedMistakeIdsToSource($assignedMistakeIds, $context->preferredSource);

        if (empty($assignedMistakeIds)) {
            return null;
        }

        $sizing = $this->applySizingProfile(
            $context,
            'topic_mistake_review',
            'mistake_review',
            min($this->resolveMistakeQuestionCount(max($dueCount, count($assignedMistakeIds))), count($assignedMistakeIds)),
            $this->resolveMistakeDuration(max($dueCount, count($assignedMistakeIds)), $context->dailyStudyMinutes)
        );
        $taskId = 'topic_mistake_review_' . $topic->id . '_' . $this->slugSource($titlePrefix);
        $score = $this->scoreTask([
            'base' => 97,
            'topic_priority' => $this->topicPriorityScore($topic, $context),
            'due_load_bonus' => ($dueCount >= 8 ? 8 : 0),
            'concept_risk_bonus' => min(8, (int) round(((float) ($conceptSignal['risk_score'] ?? 0.0)) * 3)),
            'priority_offset' => $priorityOffset,
        ]);
        $xpValue = $this->resolveXpValue('topic_mistake_review', $score['priority'], $context);

        return [
            'id' => $taskId,
            'type' => 'topic_mistake_review',
            'topic_id' => $topic->id,
            'title' => $this->buildTopicTitle($titlePrefix, $topic->name, $conceptLabel),
            'meta' => $this->buildTaskMeta(
                'Spaced review',
                $conceptLabel ? sprintf('%d due · %s', $dueCount, $conceptLabel) : $dueCount . ' due',
                $sizing['question_target'],
                $sizing['duration_minutes']
            ),
            'xp' => $xpValue,
            'xp_value' => $xpValue,
            'base_xp' => $this->baseXpForType('topic_mistake_review'),
            'source' => $resolvedSourceLabel,
            'resolved_source' => $resolvedSourceLabel,
            'study_mode' => 'mistake_review',
            'mode_label' => 'Spaced review',
            'repair_mode' => 'spaced',
            'assigned_mistake_ids' => array_slice($assignedMistakeIds, 0, $sizing['question_target']),
            'assigned_due_count' => count($assignedMistakeIds),
            'target_concepts' => $this->resolveConceptTargets($topic, $context, $conceptLabel),
            'concept_tagging_complete' => false,
            'is_regression' => false,
            'spawned_from_task_id' => null,
            'question_target' => $sizing['question_target'],
            'duration_minutes' => $sizing['duration_minutes'],
            'mistake_start_count' => $dueCount,
            'priority' => $score['priority'],
            'priority_score' => $score['priority'],
            'score_components' => $score['components'],
            'sizing_basis' => $sizing['sizing_basis'],
        ] + [];
    }

    private function buildSingleFinalExamSprintTask(
        PlannerContext $context,
        int $priorityOffset = 0,
        string $simulationScope = 'backfill_mixed',
        array $coveredTopicIds = [],
        ?Topic $topic = null
    ): ?array {
        $task = $this->buildFinalExamSprintTask($context, $priorityOffset);
        if (!$task) {
            return null;
        }

        $task['id'] = $simulationScope === 'frontier_replace' && $topic
            ? 'final_exam_sprint_frontier_' . (int) $topic->id
            : 'final_exam_sprint_backfill';
        $task['topic_id'] = $topic?->id;
        $task['covered_topic_ids'] = array_values(array_unique(array_map('intval', $coveredTopicIds)));
        $task['simulation_scope'] = $simulationScope;

        if ($simulationScope === 'frontier_replace' && $topic) {
            $task['title'] = 'Solve Exam Simulation: ' . $topic->name;
            $task['meta'] = $this->buildTaskMeta('Quiz', $task['resolved_source'] ?? $context->preferredSource, (int) ($task['question_target'] ?? 0), (int) ($task['duration_minutes'] ?? 0));
        }

        return $task;
    }

    private function resolveSpacedSignalForTopic(int $topicId, PlannerContext $context, bool $preferDueOnly): ?array
    {
        $dueSignal = collect($context->dueMistakeSignals['by_topic'] ?? [])
            ->first(fn (array $signal) => (int) ($signal['topic_id'] ?? 0) === $topicId);
        $backlogSignal = collect($context->backlogSignals['by_topic'] ?? [])
            ->first(fn (array $signal) => (int) ($signal['topic_id'] ?? 0) === $topicId);

        $assignedMistakeIds = $preferDueOnly
            ? array_values(array_map('intval', $dueSignal['assigned_mistake_ids'] ?? []))
            : array_values(array_map('intval', ($dueSignal['assigned_mistake_ids'] ?? []) !== []
                ? ($dueSignal['assigned_mistake_ids'] ?? [])
                : ($backlogSignal['reviewable_mistake_ids'] ?? [])));

        if ($assignedMistakeIds === []) {
            return null;
        }

        return [
            'topic_id' => $topicId,
            'due_count' => (int) ($dueSignal['due_count'] ?? count($assignedMistakeIds)),
            'assigned_mistake_ids' => $assignedMistakeIds,
            'minutes_overdue' => (int) ($dueSignal['minutes_overdue'] ?? 0),
            'average_risk' => (float) ($dueSignal['average_risk'] ?? 0.0),
        ];
    }

    private function withProtocolMetadata(
        array $task,
        int $slotIndex,
        ?int $backfillLevel,
        bool $countsTowardProgression
    ): array {
        $task['slot_index'] = $slotIndex;
        $task['backfill_level'] = $backfillLevel;
        $task['counts_toward_progression'] = $countsTowardProgression;
        $task['simulation_scope'] = $task['simulation_scope'] ?? null;
        $task['covered_topic_ids'] = array_values(array_unique(array_map('intval', $task['covered_topic_ids'] ?? [])));

        return $task;
    }

    private function withPreviewMetadata(array $task, int $slotIndex, string $unlocksAfterTaskId): array
    {
        $task['slot_index'] = $slotIndex;
        $task['backfill_level'] = null;
        // Preserve counts_toward_progression from the task type
        // frontier_topic_quiz and repair tasks count toward progression
        // even when shown as previews
        $task['counts_toward_progression'] = (bool) ($task['counts_toward_progression'] ?? in_array($task['type'] ?? '', ['frontier_topic_quiz', 'repair_topic', 'secure_topic'], true));
        $task['is_preview'] = true;
        $task['unlocks_after_task_id'] = $unlocksAfterTaskId;
        $task['completed'] = false;
        $task['completed_at'] = null;
        $task['completion_state'] = 'preview';
        $task['completion_progress'] = 0.0;
        $task['completion_meta'] = [];
        $task['xp'] = null;
        $task['xp_value'] = 0;
        $task['base_xp'] = 0;
        $task['xp_awarded'] = false;
        $task['xp_awarded_total'] = 0;
        $task['priority'] = 0.0;
        $task['priority_score'] = 0.0;
        $task['score_components'] = [];
        $task['trigger_reason'] = 'progression_preview';

        return $task;
    }

    private function isStudyProgressionTask(array $task): bool
    {
        return in_array((string) ($task['type'] ?? ''), ['start_topic', 'rapid_overview'], true);
    }

    private function isSolveProgressionTask(array $task): bool
    {
        return ((string) ($task['type'] ?? '') === 'frontier_topic_quiz')
            || (((string) ($task['type'] ?? '') === 'final_exam_sprint')
                && (string) ($task['simulation_scope'] ?? '') === 'frontier_replace');
    }

    private function isRepairProgressionTask(array $task): bool
    {
        return in_array((string) ($task['type'] ?? ''), ['repair_topic', 'topic_mistake_review'], true);
    }

    private function nextPreviewTopic(Collection $orderedTopics, Topic $currentTopic): ?Topic
    {
        return $orderedTopics
            ->filter(function (Topic $topic) use ($currentTopic) {
                $currentOrder = (int) ($currentTopic->display_order ?? PHP_INT_MAX);
                $topicOrder = (int) ($topic->display_order ?? PHP_INT_MAX);

                return $topicOrder > $currentOrder
                    || ($topicOrder === $currentOrder && (int) $topic->id > (int) $currentTopic->id);
            })
            ->first();
    }

    private function topicLikelyClearsCleanly(array $profile): bool
    {
        return (float) ($profile['progression_accuracy'] ?? 0.0) >= 85.0
            && !(bool) ($profile['has_reviewable_backlog'] ?? false)
            && !(bool) ($profile['is_weak'] ?? false);
    }

    private function buildStudyPreviewTask(Topic $topic, PlannerContext $context): array
    {
        $sourceLabel = $this->resolveTargetSourceForTopic($topic, $context->topicSignals, $context->preferredSource);
        $isRapidOverview = $this->shouldUseRapidOverview($context);
        $taskType = $isRapidOverview ? 'rapid_overview' : 'start_topic';
        $sizing = $this->applySizingProfile(
            $context,
            $taskType,
            'tutor',
            $this->resolveTopicQuestionCount($context->topicSignals, (int) $topic->id, 10),
            $isRapidOverview ? 10 : 20
        );

        return [
            'id' => sprintf('preview_%s_%d', $taskType, (int) $topic->id),
            'type' => $taskType,
            'topic_id' => $topic->id,
            'title' => $isRapidOverview ? 'Quick Study: ' . $topic->name : 'Study: ' . $topic->name,
            'meta' => $this->buildTaskMeta('Tutor', $sourceLabel, $sizing['question_target'], $sizing['duration_minutes']),
            'source' => $sourceLabel,
            'resolved_source' => $sourceLabel,
            'study_mode' => 'tutor',
            'mode_label' => 'Tutor',
            'target_concepts' => $this->resolveConceptTargets($topic, $context),
            'concept_tagging_complete' => false,
            'is_regression' => false,
            'spawned_from_task_id' => null,
            'question_target' => $sizing['question_target'],
            'duration_minutes' => $sizing['duration_minutes'],
            'sizing_basis' => $sizing['sizing_basis'],
            'simulation_scope' => null,
            'covered_topic_ids' => [],
        ];
    }

    private function buildSolvePreviewTask(Topic $topic, PlannerContext $context): array
    {
        $sourceLabel = $this->resolveTargetSourceForTopic($topic, $context->topicSignals, $context->preferredSource);
        $sizing = $this->applySizingProfile(
            $context,
            'frontier_topic_quiz',
            'quiz',
            $this->resolveTopicQuestionCount($context->topicSignals, (int) $topic->id, 8),
            15
        );

        return [
            'id' => sprintf('preview_frontier_topic_quiz_%d', (int) $topic->id),
            'type' => 'frontier_topic_quiz',
            'topic_id' => $topic->id,
            'title' => 'Solve: ' . $topic->name,
            'meta' => $this->buildTaskMeta('Quiz', $sourceLabel, $sizing['question_target'], $sizing['duration_minutes']),
            'source' => $sourceLabel,
            'resolved_source' => $sourceLabel,
            'study_mode' => 'quiz',
            'mode_label' => 'Quiz',
            'target_concepts' => $this->resolveConceptTargets($topic, $context),
            'concept_tagging_complete' => false,
            'is_regression' => false,
            'spawned_from_task_id' => null,
            'question_target' => $sizing['question_target'],
            'duration_minutes' => $sizing['duration_minutes'],
            'sizing_basis' => $sizing['sizing_basis'],
            'simulation_scope' => null,
            'covered_topic_ids' => [],
        ];
    }

    private function buildRepairPreviewTask(Topic $topic, PlannerContext $context): array
    {
        $sourceLabel = $this->resolveTargetSourceForTopic($topic, $context->topicSignals, $context->preferredSource);
        $questionTarget = $this->resolveMistakeQuestionCount(6);
        $durationMinutes = $this->resolveMistakeDuration(6, $context->dailyStudyMinutes);
        $sizing = $this->applySizingProfile(
            $context,
            $context->repairMode === 'spaced' ? 'topic_mistake_review' : 'repair_topic',
            $context->repairMode === 'spaced' ? 'mistake_review' : 'quiz',
            $questionTarget,
            $durationMinutes
        );
        $modeLabel = $context->repairMode === 'spaced' ? 'Spaced review' : 'Quiz';
        $studyMode = $context->repairMode === 'spaced' ? 'mistake_review' : 'quiz';

        return [
            'id' => sprintf('preview_repair_topic_%d', (int) $topic->id),
            'type' => $context->repairMode === 'spaced' ? 'topic_mistake_review' : 'repair_topic',
            'topic_id' => $topic->id,
            'title' => 'Revise Mistakes: ' . $topic->name,
            'meta' => $this->buildTaskMeta($modeLabel, $sourceLabel ?: 'after quiz', $sizing['question_target'], $sizing['duration_minutes']),
            'source' => $sourceLabel,
            'resolved_source' => $sourceLabel,
            'study_mode' => $studyMode,
            'mode_label' => $modeLabel,
            'repair_mode' => $context->repairMode,
            'repair_difficulty' => $context->classicRepairDifficulty,
            'target_concepts' => $this->resolveConceptTargets($topic, $context),
            'concept_tagging_complete' => false,
            'is_regression' => false,
            'spawned_from_task_id' => null,
            'assigned_mistake_ids' => [],
            'assigned_due_count' => null,
            'question_target' => $sizing['question_target'],
            'duration_minutes' => $sizing['duration_minutes'],
            'sizing_basis' => $sizing['sizing_basis'],
            'simulation_scope' => null,
            'covered_topic_ids' => [],
        ];
    }


    public function buildRepairTopicTasks(Collection $weakTopics, PlannerContext $context, int $limit = 2, int $priorityOffset = 0): Collection
    {
        if ($context->repairMode === 'spaced') {
            return collect();
        }

        $candidates = collect();

        foreach ($weakTopics->take($limit) as $topic) {
            $profile = data_get($context->topicSignals, 'profiles.' . $topic->id, []);
            if (($profile['progression_stage'] ?? '') !== 'mistakes' && ($profile['progression_stage'] ?? '') !== 'cleared') {
                continue;
            }

            $sourceLabel = $this->resolveTargetSourceForTopic($topic, $context->topicSignals, $context->preferredSource);
            $conceptSignal = $this->weakestConceptSignal((int) $topic->id, $context);
            $conceptLabel = $this->displayConceptLabel($conceptSignal);
            $assignedMistakeIds = $this->assignedClassicMistakeIds((int) $topic->id, $context);
            [$assignedMistakeIds, $sourceLabel] = $this->scopeAssignedMistakeIdsToSource($assignedMistakeIds, $sourceLabel);

            if (empty($assignedMistakeIds)) {
                continue;
            }
            $sizing = $this->applySizingProfile(
                $context,
                'repair_topic',
                'quiz',
                min($this->resolveTopicQuestionCount($context->topicSignals, (int) $topic->id, 12), count($assignedMistakeIds)),
                $this->resolveTopicDuration('repair', $context->daysToExam)
            );
            $taskId = 'repair_topic_' . $topic->id . '_' . $this->slugSource($sourceLabel);
            $score = $this->scoreTask([
                'base' => 92,
                'topic_priority' => $this->topicPriorityScore($topic, $context),
                'concept_risk_bonus' => min(10, (int) round(((float) ($conceptSignal['risk_score'] ?? 0.0)) * 4)),
                'priority_offset' => $priorityOffset,
            ]);
            $xpValue = $this->resolveXpValue('repair_topic', $score['priority'], $context);
            $repairDifficulty = $context->repairMode === 'hard' ? 'hard' : 'easy';
            $conceptTargets = $this->resolveConceptTargets($topic, $context, $conceptLabel);

            $candidates->push([
                'id' => $taskId,
                'type' => 'repair_topic',
                'topic_id' => $topic->id,
                'title' => $this->buildTopicTitle('Revise', $topic->name, $conceptLabel),
                'meta' => $this->buildTaskMeta('Quiz', $sourceLabel, $sizing['question_target'], $sizing['duration_minutes']),
                'xp' => $xpValue,
                'xp_value' => $xpValue,
                'base_xp' => $this->baseXpForType('repair_topic'),
                'source' => $sourceLabel,
                'resolved_source' => $sourceLabel,
                'study_mode' => 'quiz',
                'mode_label' => 'Quiz',
                'repair_mode' => $repairDifficulty,
                'repair_difficulty' => $repairDifficulty,
                'difficulty_locked_at' => now()->toIso8601String(),
                'assigned_mistake_ids' => array_slice($assignedMistakeIds, 0, $sizing['question_target']),
                'assigned_due_count' => count($assignedMistakeIds),
                'target_concepts' => $conceptTargets,
                'concept_tagging_complete' => false,
                'is_regression' => false,
                'spawned_from_task_id' => null,
                'question_target' => $sizing['question_target'],
                'duration_minutes' => $sizing['duration_minutes'],
                'priority' => $score['priority'],
                'priority_score' => $score['priority'],
                'score_components' => $score['components'],
                'sizing_basis' => $sizing['sizing_basis'],
            ] + []);
        }

        return $candidates;
    }

    public function buildWeakTopicTutorTasks(Collection $weakTopics, PlannerContext $context, int $limit = 2, int $priorityOffset = 0): Collection
    {
        if ($this->isTutorSuppressedByExamGate($context)) {
            return collect();
        }

        $candidates = collect();

        foreach ($weakTopics->take($limit) as $topic) {
            $profile = data_get($context->topicSignals, 'profiles.' . $topic->id, []);
            
            // "Re-study" trigger: only if already tutor cleared but failed quiz repeatedly OR regressed
            if (($profile['progression_stage'] ?? '') !== 'tutor' && !($profile['quiz_failed_repeatedly'] ?? false)) {
                continue;
            }

            // Guard: Re-study (Tutor mode) should only appear if they actually finished it once before
            // If they are in Stage 1 (!tutor_cleared), that's handled by buildStartTopicTasks
            // Allow if quiz_failed_repeatedly OR is_regressed OR tutor_cleared
            if (!($profile['tutor_cleared'] ?? false) && !($profile['quiz_failed_repeatedly'] ?? false) && !($profile['is_regressed'] ?? false)) {
                continue;
            }

            $topicGrade = $this->topicGrade($topic, $context);
            $sourceLabel = $this->resolveTargetSourceForTopic($topic, $context->topicSignals, $context->preferredSource);
            $conceptSignal = $this->weakestConceptSignal((int) $topic->id, $context);
            $conceptLabel = $this->displayConceptLabel($conceptSignal);
            $sizing = $this->applySizingProfile(
                $context,
                'start_topic',
                'tutor',
                $this->resolveTopicQuestionCount($context->topicSignals, (int) $topic->id, 8),
                18
            );
            $taskId = 'rebuild_topic_' . $topic->id . '_' . $this->slugSource($sourceLabel);
            $score = $this->scoreTask([
                'base' => 90,
                'topic_priority' => $this->topicPriorityScore($topic, $context),
                'concept_risk_bonus' => min(10, (int) round(((float) ($conceptSignal['risk_score'] ?? 0.0)) * 4)),
                'priority_offset' => $priorityOffset,
            ]);
            $xpValue = $this->resolveXpValue('start_topic', $score['priority'], $context);

            $candidates->push([
                'id' => $taskId,
                'type' => 'start_topic',
                'topic_id' => $topic->id,
                'title' => $this->buildTopicTitle($topicGrade <= 35 ? 'Study' : 'Revise', $topic->name, $conceptLabel),
                'meta' => $this->buildTaskMeta('Tutor', $sourceLabel, $sizing['question_target'], $sizing['duration_minutes']),
                'xp' => $xpValue,
                'xp_value' => $xpValue,
                'base_xp' => $this->baseXpForType('start_topic'),
                'source' => $sourceLabel,
                'resolved_source' => $sourceLabel,
                'study_mode' => 'tutor',
                'mode_label' => 'Tutor',
                'target_concepts' => $this->resolveConceptTargets($topic, $context, $conceptLabel),
                'concept_tagging_complete' => false,
                'is_regression' => false,
                'spawned_from_task_id' => null,
                'question_target' => $sizing['question_target'],
                'duration_minutes' => $sizing['duration_minutes'],
                'priority' => $score['priority'],
                'priority_score' => $score['priority'],
                'score_components' => $score['components'],
                'sizing_basis' => $sizing['sizing_basis'],
                'counts_toward_progression' => true,
            ] + []);
        }

        return $candidates;
    }

    public function buildConceptRepairTasks(PlannerTopicBuckets $buckets, PlannerContext $context, int $limit = 2, int $priorityOffset = 0): Collection
    {
        if ($this->isTutorSuppressedByExamGate($context)) {
            return collect();
        }

        $candidates = collect();

        $weakConcepts = collect($context->conceptSignals['weakest'] ?? [])
            ->filter(fn (array $signal) => empty($signal['is_generated']))
            ->filter(fn (array $signal) => ((float) ($signal['risk_score'] ?? 0.0)) >= 0.95)
            ->values();

        foreach ($weakConcepts as $conceptSignal) {
            $topicId = (int) ($conceptSignal['topic_id'] ?? 0);
            $topic = $buckets->openTopics->firstWhere('id', $topicId);
            if (!$topic) {
                continue;
            }

            if ($this->isUntouchedTopic($topicId, $context)) {
                continue;
            }

            $conceptLabel = $this->displayConceptLabel($conceptSignal);
            if ($conceptLabel === null) {
                continue;
            }

            $sizing = $this->applySizingProfile(
                $context,
                'concept_repair',
                'tutor',
                max(5, min(10, 4 + (int) round(((float) ($conceptSignal['risk_score'] ?? 1.0)) * 2))),
                max(12, min(20, 12 + (int) round(((float) ($conceptSignal['risk_score'] ?? 1.0)) * 3)))
            );
            $taskId = sprintf('concept_repair_%d_%d', $topicId, (int) ($conceptSignal['concept_id'] ?? 0));
            $score = $this->scoreTask([
                'base' => 96,
                'topic_priority' => $this->topicPriorityScore($topic, $context),
                'concept_risk_bonus' => min(12, (int) round(((float) ($conceptSignal['risk_score'] ?? 0.0)) * 5)),
                'priority_offset' => $priorityOffset,
            ]);
            $xpValue = $this->resolveXpValue('concept_repair', $score['priority'], $context);

            $candidates->push([
                'id' => $taskId,
                'type' => 'concept_repair',
                'topic_id' => $topicId,
                'concept_id' => (int) ($conceptSignal['concept_id'] ?? 0),
                'concept_slug' => (string) ($conceptSignal['slug'] ?? ''),
                'concept_name' => $conceptLabel,
                'concept_start_risk' => (float) ($conceptSignal['risk_score'] ?? 0.0),
                'title' => $this->buildTopicTitle('Revise', $topic->name, $conceptLabel),
                'meta' => $this->buildTaskMeta('Tutor', 'focus area', $sizing['question_target'], $sizing['duration_minutes']),
                'xp' => $xpValue,
                'xp_value' => $xpValue,
                'base_xp' => $this->baseXpForType('concept_repair'),
                'resolved_source' => null,
                'study_mode' => 'tutor',
                'mode_label' => 'Tutor',
                'target_concepts' => [$conceptLabel],
                'concept_tagging_complete' => false,
                'is_regression' => false,
                'spawned_from_task_id' => null,
                'question_target' => $sizing['question_target'],
                'duration_minutes' => $sizing['duration_minutes'],
                'priority' => $score['priority'],
                'priority_score' => $score['priority'],
                'score_components' => $score['components'],
                'sizing_basis' => $sizing['sizing_basis'],
            ] + []);
        }

        return $candidates
            ->sortByDesc('priority')
            ->unique('id')
            ->take($limit)
            ->values();
    }

    public function buildConceptClusterRepairTasks(PlannerTopicBuckets $buckets, PlannerContext $context, int $limit = 1, int $priorityOffset = 0): Collection
    {
        if ($this->isTutorSuppressedByExamGate($context)) {
            return collect();
        }

        return collect($context->conceptSignals['clusters'] ?? [])
            ->filter(fn (array $cluster) => ((int) ($cluster['concept_count'] ?? 0)) >= 2)
            ->filter(fn (array $cluster) => ((float) ($cluster['average_risk'] ?? 0.0)) >= 0.95)
            ->map(function (array $cluster) use ($buckets, $context, $priorityOffset) {
                $topicId = (int) ($cluster['topic_id'] ?? 0);
                $topic = $buckets->openTopics->firstWhere('id', $topicId);
                if (!$topic) {
                    return null;
                }

                if ($this->isUntouchedTopic($topicId, $context)) {
                    return null;
                }

                $clusterKey = (string) ($cluster['failure_cluster'] ?? 'balanced');
                $clusterLabel = (string) ($cluster['failure_cluster_label'] ?? 'Concept pattern');
                $conceptCount = (int) ($cluster['concept_count'] ?? 0);
                $averageRisk = (float) ($cluster['average_risk'] ?? 0.0);
                $sizing = $this->applySizingProfile(
                    $context,
                    'concept_cluster_repair',
                    'tutor',
                    max(6, min(12, 4 + ($conceptCount * 2))),
                    max(14, min(24, 12 + ($conceptCount * 3)))
                );
                $taskId = sprintf('concept_cluster_repair_%d_%s', $topicId, $clusterKey);
                $score = $this->scoreTask([
                    'base' => 99,
                    'topic_priority' => $this->topicPriorityScore($topic, $context),
                    'average_risk_bonus' => min(14, (int) round($averageRisk * 5)),
                    'cluster_bonus' => min(6, (int) round(((float) ($cluster['cluster_score'] ?? 0.0)) * 2)),
                    'priority_offset' => $priorityOffset,
                ]);
                $xpValue = $this->resolveXpValue('concept_repair', $score['priority'], $context);

                return [
                    'id' => $taskId,
                    'type' => 'concept_cluster_repair',
                    'topic_id' => $topicId,
                    'failure_cluster' => $clusterKey,
                    'failure_cluster_label' => $clusterLabel,
                    'cluster_concept_ids' => array_values(array_map('intval', $cluster['concept_ids'] ?? [])),
                    'cluster_concept_names' => array_values($cluster['concept_names'] ?? []),
                    'cluster_start_risk' => $averageRisk,
                    'title' => $this->buildTopicTitle('Revise', $topic->name, $clusterLabel),
                    'meta' => $this->buildTaskMeta('Tutor', $conceptCount . ' weak areas', $sizing['question_target'], $sizing['duration_minutes']),
                    'xp' => $xpValue,
                    'xp_value' => $xpValue,
                    'base_xp' => $this->baseXpForType('concept_repair'),
                    'resolved_source' => null,
                    'study_mode' => 'tutor',
                    'mode_label' => 'Tutor',
                    'target_concepts' => array_values($cluster['concept_names'] ?? []),
                    'concept_tagging_complete' => false,
                    'is_regression' => false,
                    'spawned_from_task_id' => null,
                    'question_target' => $sizing['question_target'],
                    'duration_minutes' => $sizing['duration_minutes'],
                    'priority' => $score['priority'],
                    'priority_score' => $score['priority'],
                    'score_components' => $score['components'],
                    'sizing_basis' => $sizing['sizing_basis'],
                ] + [];
            })
            ->filter()
            ->sortByDesc('priority')
            ->unique('id')
            ->take($limit)
            ->values();
    }

    public function buildSecureTopicTasks(Collection $nearMasteryTopics, PlannerContext $context, int $limit = 2, int $priorityOffset = 0): Collection
    {
        $candidates = collect();

        foreach ($nearMasteryTopics->take($limit) as $topic) {
            if ($this->isUntouchedTopic((int) $topic->id, $context)) {
                continue;
            }

            $sourceLabel = $this->resolveTargetSourceForTopic($topic, $context->topicSignals, $context->preferredSource);
            $sizing = $this->applySizingProfile(
                $context,
                'secure_topic',
                'quiz',
                $this->resolveTopicQuestionCount($context->topicSignals, (int) $topic->id, 10),
                $this->resolveTopicDuration('secure', $context->daysToExam)
            );
            $taskId = 'secure_topic_' . $topic->id . '_' . $this->slugSource($sourceLabel);
            $score = $this->scoreTask([
                'base' => 82,
                'topic_priority' => $this->topicPriorityScore($topic, $context),
                'priority_offset' => $priorityOffset,
            ]);
            $xpValue = $this->resolveXpValue('quiz', $score['priority'], $context);

            $candidates->push([
                'id' => $taskId,
                'type' => 'secure_topic',
                'topic_id' => $topic->id,
                'title' => 'Solve: ' . $topic->name,
                'meta' => $this->buildTaskMeta('Quiz', $sourceLabel, $sizing['question_target'], $sizing['duration_minutes']),
                'xp' => $xpValue,
                'xp_value' => $xpValue,
                'base_xp' => $this->baseXpForType('quiz'),
                'source' => $sourceLabel,
                'resolved_source' => $sourceLabel,
                'study_mode' => 'quiz',
                'mode_label' => 'Quiz',
                'target_concepts' => $this->resolveConceptTargets($topic, $context),
                'concept_tagging_complete' => false,
                'is_regression' => false,
                'spawned_from_task_id' => null,
                'question_target' => $sizing['question_target'],
                'duration_minutes' => $sizing['duration_minutes'],
                'priority' => $score['priority'],
                'priority_score' => $score['priority'],
                'score_components' => $score['components'],
                'sizing_basis' => $sizing['sizing_basis'],
                'counts_toward_progression' => true,
            ] + []);
        }

        return $candidates;
    }

    public function buildFrontierSolveTask(PlannerTopicBuckets $buckets, PlannerContext $context, int $priorityOffset = 0): ?array
    {
        $topic = $buckets->openTopics->firstWhere('id', $buckets->frontierTopicId ?? 0) ?? $buckets->openTopics->first();
        if (!$topic) {
            return null;
        }

        $profile = data_get($context->topicSignals, 'profiles.' . $topic->id, []);
        if (($profile['progression_stage'] ?? '') !== 'quiz') {
            return null;
        }

        $sourceLabel = $this->resolveTargetSourceForTopic($topic, $context->topicSignals, $context->preferredSource);
        $sizing = $this->applySizingProfile(
            $context,
            'frontier_topic_quiz',
            'quiz',
            $this->resolveTopicQuestionCount($context->topicSignals, (int) $topic->id, 8),
            15
        );
        $taskId = 'frontier_topic_quiz_' . $topic->id . '_' . $this->slugSource($sourceLabel);
        $score = $this->scoreTask([
            'base' => 78,
            'topic_priority' => $this->topicPriorityScore($topic, $context),
            'priority_offset' => $priorityOffset,
        ]);
        $xpValue = $this->resolveXpValue('frontier_topic_quiz', $score['priority'], $context);

        $isRegressed = (bool) ($profile['is_regressed'] ?? false);
        $titlePrefix = $isRegressed ? 'Re-study: ' : 'Solve: ';

        return [
            'id' => $taskId,
            'type' => 'frontier_topic_quiz',
            'topic_id' => $topic->id,
            'title' => $titlePrefix . $topic->name,
            'meta' => $this->buildTaskMeta('Quiz', $sourceLabel, $sizing['question_target'], $sizing['duration_minutes']),
            'xp' => $xpValue,
            'xp_value' => $xpValue,
            'base_xp' => $this->baseXpForType('frontier_topic_quiz'),
            'source' => $sourceLabel,
            'resolved_source' => $sourceLabel,
            'study_mode' => 'quiz',
            'mode_label' => 'Quiz',
            'target_concepts' => $this->resolveConceptTargets($topic, $context),
            'concept_tagging_complete' => false,
            'is_regression' => false,
            'spawned_from_task_id' => null,
            'question_target' => $sizing['question_target'],
            'duration_minutes' => $sizing['duration_minutes'],
            'priority' => $score['priority'],
            'priority_score' => $score['priority'],
            'score_components' => $score['components'],
            'sizing_basis' => $sizing['sizing_basis'],
            'counts_toward_progression' => true,
        ] + [];
    }

    public function buildStartTopicTasks(Collection $untouchedTopics, PlannerContext $context, int $limit = 3, int $priorityOffset = 0): Collection
    {
        $candidates = collect();

        foreach ($untouchedTopics->take($limit) as $index => $topic) {
            $profile = data_get($context->topicSignals, 'profiles.' . $topic->id, []);
            if (($profile['progression_stage'] ?? '') !== 'tutor') {
                continue;
            }

            $sourceLabel = $this->resolveTargetSourceForTopic($topic, $context->topicSignals, $context->preferredSource);
            $isRapidOverview = $this->shouldUseRapidOverview($context);
            $taskType = $isRapidOverview ? 'rapid_overview' : 'start_topic';
            $sizing = $this->applySizingProfile(
                $context,
                $taskType,
                'tutor',
                $this->resolveTopicQuestionCount($context->topicSignals, (int) $topic->id, $index === 0 ? 12 : 10),
                $isRapidOverview ? 10 : ($index === 0 ? 25 : 20)
            );
            $taskId = $taskType . '_' . $topic->id . '_' . $this->slugSource($sourceLabel);
            $score = $this->scoreTask([
                'base' => $isRapidOverview ? 76 : 68,
                'topic_priority' => $this->topicPriorityScore($topic, $context),
                'exam_penalty' => (($context->daysToExam !== null && $context->daysToExam <= 7) ? -12 : 0),
                'priority_offset' => $priorityOffset,
            ]);
            $xpValue = $this->resolveXpValue($isRapidOverview ? 'rapid_overview' : 'start_topic', $score['priority'], $context);

            $candidates->push([
                'id' => $taskId,
                'type' => $taskType,
                'topic_id' => $topic->id,
                'title' => $isRapidOverview ? 'Quick Study: ' . $topic->name : 'Study: ' . $topic->name,
                'meta' => $this->buildTaskMeta('Tutor', $sourceLabel, $sizing['question_target'], $sizing['duration_minutes']),
                'xp' => $xpValue,
                'xp_value' => $xpValue,
                'base_xp' => $this->baseXpForType($isRapidOverview ? 'rapid_overview' : 'start_topic'),
                'source' => $sourceLabel,
                'resolved_source' => $sourceLabel,
                'study_mode' => 'tutor',
                'mode_label' => 'Tutor',
                'target_concepts' => $this->resolveConceptTargets($topic, $context),
                'concept_tagging_complete' => false,
                'is_regression' => false,
                'spawned_from_task_id' => null,
                'question_target' => $sizing['question_target'],
                'duration_minutes' => $sizing['duration_minutes'],
                'priority' => $score['priority'],
                'priority_score' => $score['priority'],
                'score_components' => $score['components'],
                'sizing_basis' => $sizing['sizing_basis'],
                'counts_toward_progression' => true,
            ] + []);
        }

        return $candidates;
    }

    public function buildTopicMistakeReviewTasks(PlannerTopicBuckets $buckets, PlannerContext $context, int $limit = 2, int $priorityOffset = 0): Collection
    {
        if ($context->repairMode !== 'spaced') {
            return collect();
        }

        $candidates = collect();

        foreach ($buckets->dueMistakeTopics->take($limit) as $topicDueSignal) {
            $topic = $buckets->openTopics->firstWhere('id', (int) ($topicDueSignal['topic_id'] ?? 0));
            if (!$topic) {
                continue;
            }

            $profile = data_get($context->topicSignals, 'profiles.' . $topic->id, []);
            if (($profile['progression_stage'] ?? '') !== 'mistakes' && ($profile['progression_stage'] ?? '') !== 'cleared') {
                continue;
            }

            $conceptSignal = $this->weakestConceptSignal((int) $topic->id, $context);
            $conceptLabel = $this->displayConceptLabel($conceptSignal);
            $dueCount = (int) ($topicDueSignal['due_count'] ?? 0);
            $assignedMistakeIds = $this->assignedSpacedMistakeIds($topicDueSignal);
            [$assignedMistakeIds, $resolvedSourceLabel] = $this->scopeAssignedMistakeIdsToSource($assignedMistakeIds, $context->preferredSource);
            if (empty($assignedMistakeIds)) {
                continue;
            }
            $sizing = $this->applySizingProfile(
                $context,
                'topic_mistake_review',
                'mistake_review',
                min($this->resolveMistakeQuestionCount($dueCount), count($assignedMistakeIds)),
                $this->resolveMistakeDuration($dueCount, $context->dailyStudyMinutes)
            );
            $taskId = 'topic_mistake_review_' . $topic->id;
            $score = $this->scoreTask([
                'base' => 97,
                'topic_priority' => $this->topicPriorityScore($topic, $context),
                'due_load_bonus' => ($dueCount >= 8 ? 8 : 0),
                'concept_risk_bonus' => min(8, (int) round(((float) ($conceptSignal['risk_score'] ?? 0.0)) * 3)),
                'priority_offset' => $priorityOffset,
            ]);
            $xpValue = $this->resolveXpValue('topic_mistake_review', $score['priority'], $context);

            $candidates->push([
                'id' => $taskId,
                'type' => 'topic_mistake_review',
                'topic_id' => $topic->id,
                'title' => $this->buildTopicTitle('Revise Mistakes', $topic->name, $conceptLabel),
                'meta' => $this->buildTaskMeta(
                    'Spaced review',
                    $conceptLabel ? sprintf('%d due · %s', $dueCount, $conceptLabel) : $dueCount . ' due',
                    $sizing['question_target'],
                    $sizing['duration_minutes']
                ),
                'xp' => $xpValue,
                'xp_value' => $xpValue,
                'base_xp' => $this->baseXpForType('topic_mistake_review'),
                'source' => $resolvedSourceLabel,
                'resolved_source' => $resolvedSourceLabel,
                'study_mode' => 'mistake_review',
                'mode_label' => 'Spaced review',
                'repair_mode' => 'spaced',
                'assigned_mistake_ids' => array_slice($assignedMistakeIds, 0, $sizing['question_target']),
                'assigned_due_count' => count($assignedMistakeIds),
                'target_concepts' => $this->resolveConceptTargets($topic, $context, $conceptLabel),
                'concept_tagging_complete' => false,
                'is_regression' => false,
                'spawned_from_task_id' => null,
                'question_target' => $sizing['question_target'],
                'duration_minutes' => $sizing['duration_minutes'],
                'mistake_start_count' => $dueCount,
                'priority' => $score['priority'],
                'priority_score' => $score['priority'],
                'score_components' => $score['components'],
                'sizing_basis' => $sizing['sizing_basis'],
                'counts_toward_progression' => true,
            ] + []);
        }

        return $candidates;
    }

    public function buildMistakesCleanupTask(
        PlannerTopicBuckets $buckets,
        PlannerContext $context,
        int $priorityOffset = 0,
        bool $allowAlongsideDueReviews = false
    ): ?array {
        if ($context->openMistakesCount <= 0) {
            return null;
        }

        if (!$allowAlongsideDueReviews && $buckets->dueMistakeTopics->isNotEmpty()) {
            return null;
        }

        $courseConceptLabel = $this->displayConceptLabel($context->conceptSignals['weakest'][0] ?? null);
        $sizing = $this->applySizingProfile(
            $context,
            'mistakes_cleanup',
            'mistake_review',
            $this->resolveMistakeQuestionCount($context->openMistakesCount),
            $this->resolveMistakeDuration($context->openMistakesCount, $context->dailyStudyMinutes)
        );
        $score = $this->scoreTask([
            'base' => 94,
            'backlog_bonus' => ($context->openMistakesCount >= 8 ? 10 : 0),
            'priority_offset' => $priorityOffset,
        ]);

        return [
            'id' => 'mistakes_cleanup',
            'type' => 'mistakes_cleanup',
            'title' => $courseConceptLabel ? 'Revise Mistakes: ' . $courseConceptLabel : 'Revise Mistakes',
            'meta' => $this->buildTaskMeta('Review', 'current course', $sizing['question_target'], $sizing['duration_minutes']),
            'xp' => $this->resolveXpValue('mistakes_cleanup', $score['priority'], $context),
            'xp_value' => $this->resolveXpValue('mistakes_cleanup', $score['priority'], $context),
            'base_xp' => $this->baseXpForType('mistakes_cleanup'),
            'resolved_source' => null,
            'study_mode' => 'mistake_review',
            'mode_label' => 'Review',
            'repair_mode' => $context->repairMode,
            'target_concepts' => $courseConceptLabel ? [$courseConceptLabel] : [],
            'concept_tagging_complete' => false,
            'is_regression' => false,
            'spawned_from_task_id' => null,
            'question_target' => $sizing['question_target'],
            'duration_minutes' => $sizing['duration_minutes'],
            'mistake_start_count' => $context->openMistakesCount,
            'mistake_target_count' => $this->resolveMistakeTargetCount($context->openMistakesCount),
            'priority' => $score['priority'],
            'priority_score' => $score['priority'],
            'score_components' => $score['components'],
            'sizing_basis' => $sizing['sizing_basis'],
        ] + [];
    }

    public function buildFinalExamSprintTask(PlannerContext $context, int $priorityOffset = 0): ?array
    {
        if ($context->daysToExam === null || $context->daysToExam < 0 || $context->daysToExam > 7) {
            return null;
        }

        $simulationSource = $context->preferredSource ?: 'course questions';
        $sizing = $this->applySizingProfile(
            $context,
            'final_exam_sprint',
            'quiz',
            max(20, $context->mixedQuestionCount),
            max(30, $context->mixedDurationMinutes)
        );
        $score = $this->scoreTask([
            'base' => 96,
            'exam_pressure_bonus' => (10 - min($context->daysToExam, 10)),
            'priority_offset' => $priorityOffset,
        ]);
        $xpValue = $this->resolveXpValue('final_exam_sprint', $score['priority'], $context);

        return [
            'id' => 'final_exam_sprint',
            'type' => 'final_exam_sprint',
            'title' => 'Solve Exam Simulation',
            'meta' => $this->buildTaskMeta('Quiz', $simulationSource, $sizing['question_target'], $sizing['duration_minutes']),
            'xp' => $xpValue,
            'xp_value' => $xpValue,
            'base_xp' => $this->baseXpForType('final_exam_sprint'),
            'source' => $context->preferredSource,
            'resolved_source' => $context->preferredSource,
            'study_mode' => 'quiz',
            'mode_label' => 'Quiz',
            'target_concepts' => [],
            'concept_tagging_complete' => false,
            'is_regression' => false,
            'spawned_from_task_id' => null,
            'question_target' => $sizing['question_target'],
            'duration_minutes' => $sizing['duration_minutes'],
            'priority' => $score['priority'],
            'priority_score' => $score['priority'],
            'score_components' => $score['components'],
            'sizing_basis' => $sizing['sizing_basis'],
        ] + [];
    }

    private function topicGrade(Topic $topic, PlannerContext $context): float
    {
        return (float) data_get($context->topicSignals, 'profiles.' . $topic->id . '.accuracy', $topic->userProgress->first()?->percentage_grade ?? 0);
    }

    private function scopeTopicsToProgressionFrontier(Collection $orderedOpenTopics, PlannerContext $context): array
    {
        if ($orderedOpenTopics->isEmpty()) {
            return [$orderedOpenTopics, null, false];
        }

        $curriculumUnlockedTopics = $orderedOpenTopics
            ->filter(fn (Topic $topic) => (bool) data_get($context->topicSignals, 'profiles.' . $topic->id . '.curriculum_unlocked', true))
            ->values();

        $frontierTopic = $curriculumUnlockedTopics->first(function (Topic $topic) use ($context) {
            return data_get($context->topicSignals, 'profiles.' . $topic->id . '.progression_stage') !== 'cleared';
        });

        if (!$frontierTopic) {
            return [$curriculumUnlockedTopics, null, false];
        }

        return [$curriculumUnlockedTopics, (int) $frontierTopic->id, true];
    }

    private function topicPriorityScore(Topic $topic, PlannerContext $context): float
    {
        return (float) data_get($context->topicSignals, 'profiles.' . $topic->id . '.priority_score', 0.0);
    }

    private function isUntouchedTopic(int $topicId, PlannerContext $context): bool
    {
        return (bool) data_get($context->topicSignals, 'profiles.' . $topicId . '.is_untouched', false);
    }

    private function resolveTargetSourceForTopic(Topic $topic, array $topicSignals, ?string $preferredSource): ?string
    {
        $availableSources = array_keys(data_get($topicSignals, 'source_totals.' . $topic->id, []));
        if (empty($availableSources)) {
            return null;
        }

        if ($preferredSource !== null) {
            // Case-insensitive match — source labels in DB may differ in casing (e.g. 'Assuit' vs 'assiut')
            $matched = collect($availableSources)->first(
                fn ($s) => strcasecmp($s, $preferredSource) === 0
            );
            if ($matched !== null) {
                return $matched; // return the canonical case from source_totals, not the user preference string
            }
        }

        if (count($availableSources) === 1) {
            return $availableSources[0];
        }

        return null;
    }

    private function buildTaskMeta(string $modeLabel, ?string $sourceLabel, int $questionCount, int $durationMinutes): string
    {
        $questionLabel = $questionCount === 1 ? '1 question' : $questionCount . ' questions';

        return collect([$modeLabel, $sourceLabel, $questionLabel, $durationMinutes . ' min'])
            ->filter()
            ->implode(' · ');
    }

    private function resolveTopicQuestionCount(array $topicSignals, int $topicId, int $default): int
    {
        $sourceTotals = data_get($topicSignals, 'source_totals.' . $topicId, []);
        $available = array_sum(array_map('intval', $sourceTotals));

        if ($available <= 0) {
            return $default;
        }

        return max(6, min($default, $available));
    }

    private function resolveTopicDuration(string $taskType, ?int $daysToExam): int
    {
        return match ($taskType) {
            'repair' => ($daysToExam !== null && $daysToExam <= 7) ? 18 : 20,
            'secure' => 15,
            default => 20,
        };
    }

    private function resolveMistakeTargetCount(int $openMistakesCount): int
    {
        return max(2, min($openMistakesCount, (int) ceil($openMistakesCount / 2)));
    }

    private function resolveMistakeQuestionCount(int $openMistakesCount): int
    {
        return max(4, min(14, $openMistakesCount));
    }

    private function resolveMistakeDuration(int $openMistakesCount, int $dailyStudyMinutes): int
    {
        if ($openMistakesCount >= 10) {
            return min(25, max(18, (int) ceil($dailyStudyMinutes * 0.25)));
        }

        return 15;
    }

    private function shouldUseRapidOverview(PlannerContext $context): bool
    {
        return $this->isTutorSuppressedByExamGate($context);
    }

    private function isTutorSuppressedByExamGate(PlannerContext $context): bool
    {
        return $context->daysToExam !== null && $context->daysToExam >= 0 && $context->daysToExam < 3;
    }

    private function baseXpForType(string $taskType): int
    {
        return match ($taskType) {
            'final_exam_sprint' => 24,
            'repair_topic', 'concept_repair', 'topic_mistake_review', 'mistakes_cleanup' => 16,
            'start_topic' => 14,
            'rapid_overview' => 6,
            default => 12,
        };
    }

    private function resolveXpValue(string $taskType, float $priorityScore, PlannerContext $context, bool $isRegression = false): int
    {
        $baseXp = $this->baseXpForType($taskType);
        if ($isRegression) {
            return $baseXp;
        }

        $priorityFactor = max(0.0, min(1.0, $priorityScore / 100));
        $priorityMultiplier = 1 + ($priorityFactor * 0.5);
        $examMultiplier = match (true) {
            $context->daysToExam !== null && $context->daysToExam >= 0 && $context->daysToExam < 3 => 2.0,
            $context->daysToExam !== null && $context->daysToExam <= 7 => 1.5,
            $context->daysToExam !== null && $context->daysToExam <= 14 => 1.25,
            default => 1.0,
        };

        return (int) round($baseXp * min(2.5, $priorityMultiplier * $examMultiplier));
    }

    private function resolveConceptTargets(Topic $topic, PlannerContext $context, ?string $preferredConcept = null): array
    {
        $targets = collect();

        if ($preferredConcept !== null && trim($preferredConcept) !== '') {
            $targets->push(trim($preferredConcept));
        }

        $targets = $targets->concat(data_get($context->topicSignals, 'profiles.' . $topic->id . '.weak_concepts', []));
        $targets = $targets->filter(fn ($concept) => is_string($concept) && trim($concept) !== '');

        if ($targets->isEmpty()) {
            $targets->push($topic->name);
        }

        return $targets
            ->map(fn ($concept) => trim((string) $concept))
            ->unique()
            ->values()
            ->all();
    }

    private function assignedSpacedMistakeIds(array $topicDueSignal): array
    {
        return collect($topicDueSignal['assigned_mistake_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();
    }

    private function assignedClassicMistakeIds(int $topicId, PlannerContext $context): array
    {
        $allByTopic = $context->backlogSignals['by_topic'] ?? [];
        $topicSignal = $allByTopic[$topicId] ?? $allByTopic[(string)$topicId] ?? [];

        $classicIds = array_values(array_map('intval', $topicSignal['classic_reviewable_mistake_ids'] ?? []));
        if (!empty($classicIds)) {
            return $classicIds;
        }

        return array_values(array_map('intval', $topicSignal['_reviewable_mistake_ids'] ?? $topicSignal['reviewable_mistake_ids'] ?? []));
    }

    private function scopeAssignedMistakeIdsToSource(array $mistakeIds, ?string $sourceLabel): array
    {
        $mistakeIds = array_values(array_map('intval', array_filter($mistakeIds, fn ($id) => (int) $id > 0)));
        if (empty($mistakeIds)) {
            return [[], $sourceLabel];
        }

        if ($sourceLabel !== null) {
            $matchingIds = Mistake::query()
                ->whereIn('mistakes.id', $mistakeIds)
                ->whereHas('question', function ($query) use ($sourceLabel) {
                    $query->where('source', $sourceLabel);
                })
                ->pluck('mistakes.id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (!empty($matchingIds)) {
                $matchingLookup = array_flip($matchingIds);
                $filteredIds = array_values(array_filter($mistakeIds, fn ($id) => isset($matchingLookup[$id])));

                return [$filteredIds, $sourceLabel];
            }
        }

        return [$mistakeIds, $this->resolveSourceLabelFromAssignedMistakes($mistakeIds)];
    }

    private function resolveSourceLabelFromAssignedMistakes(array $mistakeIds): ?string
    {
        if (empty($mistakeIds)) {
            return null;
        }

        $sources = Mistake::query()
            ->whereIn('mistakes.id', $mistakeIds)
            ->join('questions', 'questions.id', '=', 'mistakes.question_id')
            ->pluck('questions.source')
            ->filter(fn ($source) => is_string($source) && trim($source) !== '')
            ->map(fn ($source) => trim((string) $source))
            ->unique()
            ->values();

        return $sources->count() === 1 ? $sources->first() : null;
    }

    private function slugSource(?string $source): string
    {
        if ($source === null || $source === '') {
            return 'general';
        }

        return strtolower(str_replace([' ', '/'], ['-', '-'], $source));
    }

    private function weakestConceptSignal(int $topicId, PlannerContext $context): ?array
    {
        $signals = collect($context->conceptSignals['by_topic'][$topicId] ?? []);

        if ($signals->isEmpty()) {
            return null;
        }

        return $signals
            ->sortByDesc(fn (array $signal) => (float) ($signal['risk_score'] ?? 0.0))
            ->first();
    }

    private function displayConceptLabel(?array $conceptSignal): ?string
    {
        if (!$conceptSignal || !empty($conceptSignal['is_generated'])) {
            return null;
        }

        $name = trim((string) ($conceptSignal['concept_name'] ?? ''));

        return $name !== '' ? $name : null;
    }

    private function buildTopicTitle(string $prefix, string $topicName, ?string $conceptLabel = null): string
    {
        $normalizedTopic = mb_strtolower(trim($topicName));
        $normalizedConcept = mb_strtolower(trim((string) $conceptLabel));

        if ($normalizedConcept === '' || $normalizedConcept === $normalizedTopic) {
            return sprintf('%s: %s', $prefix, $topicName);
        }

        return sprintf('%s: %s -> %s', $prefix, $topicName, $conceptLabel);
    }

    private function scoreTask(array $components): array
    {
        $normalized = collect($components)
            ->map(fn ($value) => is_numeric($value) ? round((float) $value, 2) : $value)
            ->all();

        return [
            'priority' => round(array_sum(array_map('floatval', $normalized)), 2),
            'components' => $normalized,
        ];
    }

    private function applySizingProfile(
        PlannerContext $context,
        string $taskType,
        string $studyMode,
        int $questionTarget,
        int $durationMinutes
    ): array {
        $taskBehavior = data_get($context->behaviorSignals, 'by_task_type.' . $taskType, []);
        $studyModeBehavior = data_get($context->behaviorSignals, 'by_study_mode.' . $studyMode, []);
        $completionRate = (float) ($taskBehavior['completion_rate'] ?? $studyModeBehavior['completion_rate'] ?? 0.0);
        $largeTaskAbandonmentRate = (float) ($context->behaviorSignals['large_task_abandonment_rate'] ?? 0.0);
        $pace = (float) ($studyModeBehavior['questions_per_minute'] ?? 0.0);

        $adjustments = [];

        if ($questionTarget >= 10 && $largeTaskAbandonmentRate >= 0.4) {
            $questionTarget = max(6, (int) floor($questionTarget * 0.8));
            $adjustments['large_task_abandonment'] = -0.2;
        }

        if ($completionRate > 0 && $completionRate < 0.5) {
            $questionTarget = max(4, (int) floor($questionTarget * 0.8));
            $durationMinutes = max($durationMinutes, (int) ceil($durationMinutes * 1.1));
            $adjustments['completion_rate'] = -0.2;
        } elseif ($completionRate >= 0.8) {
            $questionTarget = min(40, (int) ceil($questionTarget * 1.1));
            $adjustments['completion_rate'] = 0.1;
        }

        if ($pace > 0) {
            $durationMinutes = max($durationMinutes, (int) ceil(($questionTarget / max($pace, 0.35)) * 1.15));
        }

        return [
            'question_target' => $questionTarget,
            'duration_minutes' => $durationMinutes,
            'sizing_basis' => [
                'task_type_completion_rate' => round($completionRate, 4),
                'study_mode_questions_per_minute' => round($pace, 4),
                'large_task_abandonment_rate' => round($largeTaskAbandonmentRate, 4),
                'adjustments' => $adjustments,
            ],
        ];
    }

    private function chooseAdaptiveMixedMode(PlannerContext $context): string
    {
        if ($this->shouldUseRapidOverview($context) || ($context->repairMode === 'spaced' && $context->dueMistakeSignals['total_due'] > 0)) {
            return 'quiz';
        }

        $tutorPerformance = data_get($context->behaviorSignals, 'by_study_mode.tutor', []);
        $quizPerformance = data_get($context->behaviorSignals, 'by_study_mode.quiz', []);

        if (!empty($tutorPerformance) && !empty($quizPerformance)) {
            $tutorAccuracy = (float) ($tutorPerformance['average_accuracy'] ?? 0.0);
            $quizAccuracy = (float) ($quizPerformance['average_accuracy'] ?? 0.0);
            $tutorCompletion = (float) ($tutorPerformance['completion_rate'] ?? 0.0);
            $quizCompletion = (float) ($quizPerformance['completion_rate'] ?? 0.0);

            if ($tutorCompletion >= $quizCompletion && $tutorAccuracy >= ($quizAccuracy + 5)) {
                return 'tutor';
            }

            if ($quizCompletion >= 0.55 && $quizAccuracy >= $tutorAccuracy) {
                return 'quiz';
            }
        }

        return ($context->plannerFocus === 'revision' || $context->overallAccuracy < 68)
            ? 'tutor'
            : 'quiz';
    }
}
