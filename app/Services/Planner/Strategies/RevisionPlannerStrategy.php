<?php

namespace App\Services\Planner\Strategies;

use App\Models\Topic;
use App\Services\Planner\PlannerContext;
use App\Services\Planner\PlannerTaskBuilderService;
use Illuminate\Support\Collection;

class RevisionPlannerStrategy implements PlannerModeStrategy
{
    public function __construct(
        private PlannerTaskBuilderService $plannerTaskBuilderService,
    ) {
    }

    public function supports(string $plannerFocus): bool
    {
        return $plannerFocus === 'revision';
    }

    /**
     * Revision mode focuses on clearing mistake backlogs across all attempted
     * topics instead of advancing to new topics.
     *
     * Task priority:
     * 1. Repair / revise mistakes for topics with backlog (spaced or classic).
     * 2. Strengthen weak topics via quiz re-takes.
     * 3. Only when no backlog remains anywhere, fall back to normal protocol.
     */
    public function buildCandidates(Collection $topics, PlannerContext $context): Collection
    {
        $buckets = $this->plannerTaskBuilderService->bucketTopics($topics, $context);
        $tasks = collect();
        $limit = $context->tasksLimit;

        // 1. Spaced review tasks for topics with due mistakes (highest priority).
        if ($context->repairMode === 'spaced') {
            $dueTopics = collect($context->dueMistakeSignals['by_topic'] ?? [])
                ->filter(fn (array $signal) => $buckets->openTopics->contains('id', (int) ($signal['topic_id'] ?? 0)))
                ->sortByDesc(fn (array $signal) => ((int) ($signal['due_count'] ?? 0) * 1000)
                    + ((int) ($signal['minutes_overdue'] ?? 0))
                    + ((float) ($signal['average_risk'] ?? 0.0) * 500))
                ->values();

            foreach ($dueTopics as $dueSignal) {
                if ($tasks->count() >= $limit) {
                    break;
                }
                $topic = $buckets->openTopics->firstWhere('id', (int) $dueSignal['topic_id']);
                if (!$topic) {
                    continue;
                }
                $task = $this->plannerTaskBuilderService->buildTopicMistakeReviewTasks($buckets, $context, 1, 16)->first();
                if ($task && !$tasks->contains(fn (array $t) => ($t['id'] ?? null) === ($task['id'] ?? null))) {
                    $tasks->push($this->withSlot($task, $tasks->count() + 1));
                }
            }
        }

        // 2. Classic repair tasks for topics with reviewable backlog.
        $topicsWithBacklog = $buckets->openTopics
            ->filter(fn (Topic $topic) => (bool) data_get($context->topicSignals, 'profiles.' . $topic->id . '.has_reviewable_backlog', false))
            ->sortByDesc(fn (Topic $topic) => (float) data_get($context->topicSignals, 'profiles.' . $topic->id . '.priority_score', 0.0))
            ->values();

        foreach ($topicsWithBacklog as $topic) {
            if ($tasks->count() >= $limit) {
                break;
            }

            $repairTasks = $this->plannerTaskBuilderService->buildRepairTopicTasks(collect([$topic]), $context, 1, 14);
            foreach ($repairTasks as $task) {
                if ($tasks->count() >= $limit && $tasks->contains(fn (array $t) => ($t['id'] ?? null) === ($task['id'] ?? null))) {
                    continue;
                }
                if (!$tasks->contains(fn (array $t) => ($t['id'] ?? null) === ($task['id'] ?? null))) {
                    $tasks->push($this->withSlot($task, $tasks->count() + 1));
                }
                if ($tasks->count() >= $limit) {
                    break;
                }
            }
        }

        // 3. Strengthen weak topics that have no backlog but low grades.
        foreach ($buckets->weakTopics as $weakTopic) {
            if ($tasks->count() >= $limit) {
                break;
            }
            $secureTasks = $this->plannerTaskBuilderService->buildSecureTopicTasks(collect([$weakTopic]), $context, 1, 8);
            foreach ($secureTasks as $task) {
                if (!$tasks->contains(fn (array $t) => ($t['id'] ?? null) === ($task['id'] ?? null))) {
                    $tasks->push($this->withSlot($task, $tasks->count() + 1));
                }
                if ($tasks->count() >= $limit) {
                    break;
                }
            }
        }

        // 4. If nothing generated (all topics clean), fall back to normal protocol.
        if ($tasks->isEmpty()) {
            return $this->plannerTaskBuilderService->buildProtocolTasks($topics, $context);
        }

        return $tasks->take($limit)->values();
    }

    private function withSlot(array $task, int $slotIndex): array
    {
        $task['slot_index'] = $slotIndex;
        $task['backfill_level'] = null;
        $task['counts_toward_progression'] = true;
        $task['simulation_scope'] = $task['simulation_scope'] ?? null;
        $task['covered_topic_ids'] = array_values(array_unique(array_map('intval', $task['covered_topic_ids'] ?? [])));

        return $task;
    }
}
