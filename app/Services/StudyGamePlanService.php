<?php

namespace App\Services;

use App\Models\Course;
use App\Models\QuizAttempt;
use App\Models\StudyPlan;
use App\Models\Topic;
use App\Models\User;
use App\Services\Planner\PlannerContextFactory;
use App\Services\Planner\PlannerTaskBuilderService;
use App\Services\Planner\PlannerTaskOutcomeService;
use App\Services\Planner\TaskCompletionService;
use App\Services\Planner\TaskGeneratorService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StudyGamePlanService
{
    private const PLAN_VERSION = 37;
    private const STABILITY_WINDOW_MINUTES = 180;
    private const TASK_LIMIT_DEFAULT = 5;
    private const RECOMPUTE_WINDOW_MINUTES = 15;
    private const COMPLETED_PLAN_FREEZE_MINUTES = 360; // 6 hours

    public function __construct(
        private PlannerContextFactory $plannerContextFactory,
        private TaskGeneratorService $taskGeneratorService,
        private TaskCompletionService $taskCompletionService,
        private PlannerTaskOutcomeService $plannerTaskOutcomeService,
        private PlannerTaskBuilderService $plannerTaskBuilderService,
    ) {
    }

    public function syncAfterSession(User $user, Course $course): array
    {
        $plan = $this->resolveTodayPlan($user);
        $topics = $course->topics()
            ->with(['userProgress' => fn ($query) => $query->where('user_id', $user->id)])
            ->get();

        $storedTasks = collect(data_get($plan->tasks, 'courses.' . $course->id, []))
            ->filter(fn ($task) => is_array($task) && !empty($task['id']))
            ->values();

        if ($storedTasks->isNotEmpty()) {
            $context = $this->plannerContextFactory->buildForCourse($user, $course, $topics, $plan);
            $markersChanged = $this->taskCompletionService->applyPlannerStageTransitions(
                $user,
                $course,
                $storedTasks,
                $topics,
                $context
            );

            if ($markersChanged) {
                $courseKey = (string) $course->id;
                // Invalidate stored plan meta to force recompute
                $tasksPayload = $plan->tasks ?? [];
                if (isset($tasksPayload['planner_meta'][$courseKey])) {
                    unset($tasksPayload['planner_meta'][$courseKey]['generated_at']);
                    $plan->tasks = $tasksPayload;
                    $plan->save();
                }

                $user = $user->fresh();
                $course = $course->fresh();
                $topics = $course->topics()
                    ->with(['userProgress' => fn ($query) => $query->where('user_id', $user->id)])
                    ->get();
            }
        }

        return $this->buildForCourse($user->fresh(), $course->fresh(), $topics);
    }

    public function buildForCourse(User $user, Course $course, Collection $topics): array
    {
        $plan = $this->resolveTodayPlan($user);

        $courseKey = (string) $course->id;
        $storedTasks = collect(data_get($plan->tasks, 'courses.' . $courseKey, []))
            ->filter(fn ($task) => is_array($task) && !empty($task['id']))
            ->values();
        $storedTaskMap = $storedTasks->keyBy('id');
        $storedPlanMeta = data_get($plan->tasks, 'planner_meta.' . $courseKey, []);

        $context = $this->plannerContextFactory->buildForCourse($user, $course, $topics, $plan);
        $evaluatedStoredTasks = $storedTasks
            ->map(fn (array $storedTask) => $this->normalizeStoredTask(
                $storedTask,
                $topics->firstWhere('id', (int) ($storedTask['topic_id'] ?? 0)),
                $context,
                'stable_reuse'
            ))
            ->values();

        $recomputeDecision = $this->resolveRecomputeDecision($storedPlanMeta, $context, $storedTasks, $topics);
        $responseTriggerReason = $recomputeDecision['reason'];
        $shouldResetStoredTasks = $recomputeDecision['reason'] === 'planner_version_upgrade';
        $storedTaskMapForNormalization = $shouldResetStoredTasks ? collect() : $storedTaskMap;
        $evaluatedStoredTasksForMerge = $shouldResetStoredTasks ? collect() : $evaluatedStoredTasks;
        $storedTaskMapForChildTasks = $shouldResetStoredTasks ? collect() : $storedTaskMap;
        $planMeta = $this->refreshPersistedMeta(
            $storedPlanMeta,
            $context,
            $evaluatedStoredTasks,
            $recomputeDecision['reason'],
            false
        );

        if ($recomputeDecision['recompute']) {
            $candidateTasks = $this->taskGeneratorService->buildCandidates($topics, $context)
                ->unique('id')
                ->values()
                ->map(function (array $candidate) use ($storedTaskMapForNormalization, $topics, $context, $responseTriggerReason) {
                    $storedTask = $storedTaskMapForNormalization->get($candidate['id']);

                    return $this->normalizeCandidateTask(
                        $candidate,
                        $storedTask,
                        $topics->firstWhere('id', (int) ($candidate['topic_id'] ?? 0)),
                        $context,
                        $responseTriggerReason
                    );
                })
                ->values();

            $normalizedTasks = $this->mergeRecomputedTasks(
                $candidateTasks,
                $evaluatedStoredTasksForMerge,
                $context,
                $recomputeDecision
            );

            $planMeta = $this->refreshPersistedMeta(
                $storedPlanMeta,
                $context,
                $normalizedTasks,
                $responseTriggerReason,
                true
            );
        } else {
            $normalizedTasks = $evaluatedStoredTasks
                ->map(fn (array $task) => array_merge($task, [
                    'trigger_reason' => $responseTriggerReason,
                    'resolved_exam_date' => $context->examDate?->format('Y-m-d'),
                    'resolved_focus' => $context->plannerFocus,
                ]))
                ->take($context->tasksLimit)
                ->values();
        }

        $normalizedTasks = $this->injectImmediateChildTasks(
            $normalizedTasks,
            $storedTaskMapForChildTasks,
            $topics,
            $context
        );

        [$normalizedTasks, $justCompletedTasks, $xpAwardedNow] = $this->applyXpTransitions(
            $normalizedTasks,
            $storedTaskMap
        );

        $persistedTasks = $this->sortTasksForStudentFlow(
            $normalizedTasks->filter(fn (array $t) => !(bool) ($t['is_preview'] ?? false))
        )->values();
        $visibleTasks = $this->buildVisibleTaskQueue(
            $persistedTasks,
            $topics,
            $context,
            $storedTaskMapForNormalization,
            $responseTriggerReason
        )->values();

        $tasksPayload = $plan->tasks ?? [];
        $tasksPayload['courses'] = $tasksPayload['courses'] ?? [];
        $tasksPayload['planner_meta'] = $tasksPayload['planner_meta'] ?? [];
        $tasksPayload['courses'][$courseKey] = $persistedTasks->all();
        $tasksPayload['planner_meta'][$courseKey] = $planMeta;
        $tasksPayload['allocations'] = data_get($context->globalAllocation, 'courses', []);

        $plan->tasks = $tasksPayload;
        $plan->topics = $persistedTasks
            ->pluck('topic_id')
            ->filter()
            ->map(fn ($topicId) => (int) $topicId)
            ->values()
            ->all();
        $plan->daily_goal = max($persistedTasks->count(), 1);
        $plan->exam_date = $context->examDate;
        $plan->is_committed = true;
        $plan->is_completed = $persistedTasks->isNotEmpty() && $persistedTasks->every(fn ($task) => (bool) $task['completed']);
        $plan->save();

        $this->plannerTaskOutcomeService->syncDailyOutcomes(
            $user,
            $plan,
            $course,
            $context,
            $persistedTasks
        );

        if ($xpAwardedNow > 0) {
            $user->increment('xp', $xpAwardedNow);
            $user->refresh();
        }

        [$trackedMinutes, $trackedQuestions] = $this->resolveTodayStudyProgress($user);
        $targetMinutes = max(1, (int) $context->totalDailyStudyMinutes);

        $roadmapTasks = $this->roadmapTasks($visibleTasks, $context);

        return [
            'tasks' => $visibleTasks->all(),
            'completed_count' => $roadmapTasks->where('completed', true)->count(),
            'total_count' => $roadmapTasks->count(),
            'earned_xp' => (int) $persistedTasks
                ->sum(fn (array $task) => (int) ($task['xp_awarded_total'] ?? 0)),
            'awarded_now' => $xpAwardedNow,
            'just_completed_tasks' => $justCompletedTasks->all(),
            'planned_minutes' => (int) $persistedTasks->sum(fn (array $task) => (int) ($task['duration_minutes'] ?? 0)),
            'planned_questions' => (int) $persistedTasks->sum(fn (array $task) => (int) ($task['question_target'] ?? 0)),
            'daily_progress' => [
                'tracked_minutes' => $trackedMinutes,
                'target_minutes' => $targetMinutes,
                'tracked_questions' => $trackedQuestions,
                'progress_percent' => min(100, (int) round(($trackedMinutes / max($targetMinutes, 1)) * 100)),
            ],
            'generated_at' => $planMeta['generated_at'] ?? now()->toIso8601String(),
            'version' => (int) ($planMeta['version'] ?? self::PLAN_VERSION),
            'trigger_reason' => $responseTriggerReason,
            'settings' => [
                'exam_date' => $context->examDate?->format('Y-m-d'),
                'daily_study_minutes' => $context->dailyStudyMinutes,
                'total_daily_study_minutes' => $context->totalDailyStudyMinutes,
                'course_priority_score' => $context->coursePriorityScore,
                'global_allocation' => $context->globalAllocation,
                'planner_focus' => $context->plannerFocus,
                'resolved_settings' => $this->serializeResolvedSettings($context->resolvedSettings),
            ],
        ] + array_filter([
            'debug' => $this->buildDebugPayload($user, $visibleTasks, $topics, $context),
        ], fn ($value) => $value !== null);
    }

    private function resolveTodayStudyProgress(User $user): array
    {
        $todayAttempts = QuizAttempt::query()
            ->where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->where('duration_seconds', '>', 0)
            ->where('total_questions', '>', 0)
            ->get(['duration_seconds', 'total_questions']);

        $trackedMinutes = (int) round(((int) $todayAttempts->sum('duration_seconds')) / 60);
        $trackedQuestions = (int) $todayAttempts->sum('total_questions');

        return [$trackedMinutes, $trackedQuestions];
    }

    private function normalizeCandidateTask(
        array $candidate,
        ?array $storedTask,
        mixed $topic,
        object $context,
        string $triggerReason
    ): array {
        $evaluation = $this->taskCompletionService->evaluateTask($candidate, $topic, $context);

        return [
            'id' => $candidate['id'],
            'type' => $candidate['type'],
            'topic_id' => $candidate['topic_id'] ?? null,
            'concept_id' => $candidate['concept_id'] ?? null,
            'concept_slug' => $candidate['concept_slug'] ?? null,
            'concept_name' => $candidate['concept_name'] ?? null,
            'failure_cluster' => $candidate['failure_cluster'] ?? null,
            'failure_cluster_label' => $candidate['failure_cluster_label'] ?? null,
            'cluster_concept_ids' => $candidate['cluster_concept_ids'] ?? null,
            'cluster_concept_names' => $candidate['cluster_concept_names'] ?? null,
            'title' => $candidate['title'],
            'meta' => $candidate['meta'],
            'xp' => (int) $candidate['xp'],
            'xp_value' => (int) ($candidate['xp_value'] ?? $candidate['xp'] ?? 0),
            'base_xp' => (int) ($candidate['base_xp'] ?? $candidate['xp'] ?? 0),
            'source' => $candidate['source'] ?? null,
            'resolved_source' => $candidate['resolved_source'] ?? ($candidate['source'] ?? null),
            'study_mode' => $candidate['study_mode'] ?? null,
            'mode_label' => $candidate['mode_label'] ?? $this->resolveModeLabel($candidate),
            'repair_mode' => $candidate['repair_mode'] ?? null,
            'repair_difficulty' => $candidate['repair_difficulty'] ?? null,
            'difficulty_locked_at' => $candidate['difficulty_locked_at'] ?? null,
            'question_target' => $candidate['question_target'] ?? null,
            'duration_minutes' => $candidate['duration_minutes'] ?? null,
            'mistake_start_count' => $candidate['mistake_start_count'] ?? null,
            'mistake_target_count' => $candidate['mistake_target_count'] ?? null,
            'assigned_mistake_ids' => $candidate['assigned_mistake_ids'] ?? [],
            'assigned_due_count' => $candidate['assigned_due_count'] ?? null,
            'concept_start_risk' => $candidate['concept_start_risk'] ?? null,
            'cluster_start_risk' => $candidate['cluster_start_risk'] ?? null,
            'target_concepts' => $candidate['target_concepts'] ?? [],
            'concept_tagging_complete' => (bool) ($candidate['concept_tagging_complete'] ?? false),
            'is_regression' => (bool) ($candidate['is_regression'] ?? false),
            'spawned_from_task_id' => $candidate['spawned_from_task_id'] ?? null,
            'reason_code' => $candidate['reason_code'] ?? null,
            'reason_text' => $candidate['reason_text'] ?? null,
            'priority' => (float) ($candidate['priority'] ?? $candidate['priority_score'] ?? 0.0),
            'priority_score' => (float) ($candidate['priority_score'] ?? $candidate['priority'] ?? 0.0),
            'score_components' => $candidate['score_components'] ?? [],
            'sizing_basis' => $candidate['sizing_basis'] ?? [],
            'slot_index' => isset($candidate['slot_index']) ? (int) $candidate['slot_index'] : null,
            'backfill_level' => isset($candidate['backfill_level']) ? (int) $candidate['backfill_level'] : null,
            'counts_toward_progression' => (bool) ($candidate['counts_toward_progression'] ?? false),
            'simulation_scope' => $candidate['simulation_scope'] ?? null,
            'is_preview' => (bool) ($candidate['is_preview'] ?? false),
            'unlocks_after_task_id' => $candidate['unlocks_after_task_id'] ?? null,
            'covered_topic_ids' => collect($candidate['covered_topic_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->values()
                ->all(),
            'url' => $this->resolveTaskUrl($candidate, $context),
            'trigger_reason' => $triggerReason,
            'resolved_exam_date' => $context->examDate?->format('Y-m-d'),
            'resolved_focus' => $context->plannerFocus,
            'completion_state' => $this->resolveCompletionState($storedTask, $evaluation),
            'completion_progress' => $this->resolveCompletionProgress($storedTask, $evaluation),
            'completion_meta' => $evaluation['meta'] ?? [],
            'completed' => $this->resolveCompletionState($storedTask, $evaluation) === 'complete',
            'completed_at' => $this->resolveCompletionState($storedTask, $evaluation) === 'complete'
                ? ($storedTask['completed_at'] ?? now()->toIso8601String())
                : null,
            'xp_awarded' => (bool) ($storedTask['xp_awarded'] ?? false),
            'xp_awarded_total' => (int) ($storedTask['xp_awarded_total'] ?? 0),
        ];
    }

    private function normalizeStoredTask(array $storedTask, mixed $topic, object $context, string $triggerReason): array
    {
        $evaluation = $this->taskCompletionService->evaluateTask($storedTask, $topic, $context);

        return [
            'id' => $storedTask['id'],
            'type' => $storedTask['type'],
            'topic_id' => $storedTask['topic_id'] ?? null,
            'concept_id' => $storedTask['concept_id'] ?? null,
            'concept_slug' => $storedTask['concept_slug'] ?? null,
            'concept_name' => $storedTask['concept_name'] ?? null,
            'failure_cluster' => $storedTask['failure_cluster'] ?? null,
            'failure_cluster_label' => $storedTask['failure_cluster_label'] ?? null,
            'cluster_concept_ids' => $storedTask['cluster_concept_ids'] ?? null,
            'cluster_concept_names' => $storedTask['cluster_concept_names'] ?? null,
            'title' => $storedTask['title'] ?? 'Study task',
            'meta' => $storedTask['meta'] ?? '',
            'xp' => (int) ($storedTask['xp'] ?? 0),
            'xp_value' => (int) ($storedTask['xp_value'] ?? $storedTask['xp'] ?? 0),
            'base_xp' => (int) ($storedTask['base_xp'] ?? $storedTask['xp'] ?? 0),
            'source' => $storedTask['source'] ?? null,
            'resolved_source' => $storedTask['resolved_source'] ?? ($storedTask['source'] ?? null),
            'study_mode' => $storedTask['study_mode'] ?? null,
            'mode_label' => $storedTask['mode_label'] ?? $this->resolveModeLabel($storedTask),
            'repair_mode' => $storedTask['repair_mode'] ?? null,
            'repair_difficulty' => $storedTask['repair_difficulty'] ?? null,
            'difficulty_locked_at' => $storedTask['difficulty_locked_at'] ?? null,
            'question_target' => $storedTask['question_target'] ?? null,
            'duration_minutes' => $storedTask['duration_minutes'] ?? null,
            'mistake_start_count' => $storedTask['mistake_start_count'] ?? null,
            'mistake_target_count' => $storedTask['mistake_target_count'] ?? null,
            'assigned_mistake_ids' => $storedTask['assigned_mistake_ids'] ?? [],
            'assigned_due_count' => $storedTask['assigned_due_count'] ?? null,
            'concept_start_risk' => $storedTask['concept_start_risk'] ?? null,
            'cluster_start_risk' => $storedTask['cluster_start_risk'] ?? null,
            'target_concepts' => $storedTask['target_concepts'] ?? [],
            'concept_tagging_complete' => (bool) ($storedTask['concept_tagging_complete'] ?? false),
            'is_regression' => (bool) ($storedTask['is_regression'] ?? false),
            'spawned_from_task_id' => $storedTask['spawned_from_task_id'] ?? null,
            'reason_code' => $storedTask['reason_code'] ?? null,
            'reason_text' => $storedTask['reason_text'] ?? null,
            'priority' => (float) ($storedTask['priority'] ?? $storedTask['priority_score'] ?? 0.0),
            'priority_score' => (float) ($storedTask['priority_score'] ?? $storedTask['priority'] ?? 0.0),
            'score_components' => $storedTask['score_components'] ?? [],
            'sizing_basis' => $storedTask['sizing_basis'] ?? [],
            'slot_index' => isset($storedTask['slot_index']) ? (int) $storedTask['slot_index'] : null,
            'backfill_level' => isset($storedTask['backfill_level']) ? (int) $storedTask['backfill_level'] : null,
            'counts_toward_progression' => (bool) ($storedTask['counts_toward_progression'] ?? false),
            'simulation_scope' => $storedTask['simulation_scope'] ?? null,
            'is_preview' => (bool) ($storedTask['is_preview'] ?? false),
            'unlocks_after_task_id' => $storedTask['unlocks_after_task_id'] ?? null,
            'covered_topic_ids' => collect($storedTask['covered_topic_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->values()
                ->all(),
            'url' => $this->resolveTaskUrl($storedTask, $context),
            'trigger_reason' => $triggerReason,
            'resolved_exam_date' => $storedTask['resolved_exam_date'] ?? $context->examDate?->format('Y-m-d'),
            'resolved_focus' => $storedTask['resolved_focus'] ?? $context->plannerFocus,
            'completion_state' => $this->resolveCompletionState($storedTask, $evaluation),
            'completion_progress' => $this->resolveCompletionProgress($storedTask, $evaluation),
            'completion_meta' => $evaluation['meta'] ?? [],
            'completed' => $this->resolveCompletionState($storedTask, $evaluation) === 'complete',
            'completed_at' => $this->resolveCompletionState($storedTask, $evaluation) === 'complete'
                ? ($storedTask['completed_at'] ?? now()->toIso8601String())
                : null,
            'xp_awarded' => (bool) ($storedTask['xp_awarded'] ?? false),
            'xp_awarded_total' => (int) ($storedTask['xp_awarded_total'] ?? (($storedTask['xp_awarded'] ?? false) ? (int) ($storedTask['xp_value'] ?? $storedTask['xp'] ?? 0) : 0)),
        ];
    }

    private function resolveCompletionState(?array $storedTask, array $evaluation): string
    {
        // Persistence guard: A task once completed must never regress to pending
        // unless explicitly handled (e.g. by regression logic elsewhere).
        if ((bool) ($storedTask['completed'] ?? false)) {
            return 'complete';
        }

        if (($evaluation['state'] ?? 'pending') === 'complete') {
            return 'complete';
        }

        if (($storedTask['completion_state'] ?? null) === 'partial' || ($evaluation['state'] ?? 'pending') === 'partial') {
            return 'partial';
        }

        return 'pending';
    }

    private function resolveCompletionProgress(?array $storedTask, array $evaluation): float
    {
        $storedProgress = (float) ($storedTask['completion_progress'] ?? (($storedTask['completed'] ?? false) ? 1.0 : 0.0));
        $evaluationProgress = (float) ($evaluation['progress'] ?? 0.0);

        if ($this->resolveCompletionState($storedTask, $evaluation) === 'complete') {
            return 1.0;
        }

        return round(max($storedProgress, $evaluationProgress), 4);
    }

    private function injectImmediateChildTasks(
        Collection $tasks,
        Collection $storedTaskMap,
        Collection $topics,
        object $context
    ): Collection {
        $result = collect();

        foreach ($tasks as $task) {
            $result->push($task);

            if (!$this->shouldSpawnChildTask($task, $storedTaskMap)) {
                continue;
            }

            $topic = $topics->firstWhere('id', (int) ($task['topic_id'] ?? 0));
            if (!($topic instanceof Topic)) {
                continue;
            }

            $childTask = $this->buildImmediateChildConceptRepairTask($task, $topic, $context);
            if ($childTask === null) {
                continue;
            }

            if ($result->contains(fn (array $candidate) => ($candidate['id'] ?? null) === $childTask['id'])) {
                continue;
            }

            $result->push($childTask);
        }

        // Previews and spawned children must not be truncated by tasksLimit —
        // doing so would drop slot 2/3 previews when a child task is injected after slot 1.
        // Progression tasks (Slot 1 frontier, etc) must never be demoted by child injections.
        // Concept repair children must never appear at Slot 1.
        $progressionTasks = $result
            ->filter(fn (array $t) => !(bool) ($t['is_preview'] ?? false) && empty($t['spawned_from_task_id']))
            ->sortBy(function (array $t) {
                // Frontier tasks (actionable) first, then completed progression tasks.
                return sprintf('%01d-%01d', $t['completed'] ? 1 : 0, (int) ($t['slot_index'] ?? 99));
            })
            ->take($context->tasksLimit);

        $previewAndChildTasks = $result
            ->filter(fn (array $t) => (bool) ($t['is_preview'] ?? false) || !empty($t['spawned_from_task_id']));

        return $progressionTasks
            ->concat($previewAndChildTasks)
            ->unique('id')
            ->values();
    }

    private function shouldSpawnChildTask(array $task, Collection $storedTaskMap): bool
    {
        if (!($task['completed'] ?? false) || empty($task['topic_id'])) {
            return false;
        }

        if (!empty($task['spawned_from_task_id'])) {
            return false;
        }

        // Tutor and rapid overview completions never spawn concept repair
        // Concept repair is only meaningful after scored sessions
        $type = (string) ($task['type'] ?? '');
        if (in_array($type, ['start_topic', 'rapid_overview'], true)) {
            return false;
        }

        $storedTask = $storedTaskMap->get($task['id']);
        if ($storedTask === null) {
            return false;
        }

        return !(bool) ($storedTask['completed'] ?? false);
    }

    private function buildImmediateChildConceptRepairTask(array $parentTask, Topic $topic, object $context): ?array
    {
        if ($context->daysToExam !== null && $context->daysToExam >= 0 && $context->daysToExam < 3) {
            return null;
        }

        $conceptSignal = collect($context->conceptSignals['by_topic'][(int) $topic->id] ?? [])
            ->sortByDesc(fn (array $signal) => (float) ($signal['risk_score'] ?? 0.0))
            ->first();

        if (!is_array($conceptSignal) || (float) ($conceptSignal['risk_score'] ?? 0.0) < 0.95) {
            return null;
        }

        $childTask = $this->plannerTaskBuilderService
            ->buildConceptRepairTasks(
                $this->plannerTaskBuilderService->bucketTopics(collect([$topic]), $context),
                $context,
                1
            )
            ->first(function (array $candidate) use ($topic, $conceptSignal) {
                return (int) ($candidate['topic_id'] ?? 0) === (int) $topic->id
                    && (int) ($candidate['concept_id'] ?? 0) === (int) ($conceptSignal['concept_id'] ?? 0);
            });

        if (!is_array($childTask)) {
            return null;
        }

        $conceptKey = (string) ($childTask['concept_slug'] ?? ('concept-' . (int) ($childTask['concept_id'] ?? 0)));
        $childTask['id'] = 'concept_repair_child_' . (string) $parentTask['id'] . '_' . $conceptKey;
        $childTask['spawned_from_task_id'] = (string) $parentTask['id'];
        $childTask['trigger_reason'] = 'child_spawn_after_completion';
        $childTask['resolved_exam_date'] = $context->examDate?->format('Y-m-d');
        $childTask['resolved_focus'] = $context->plannerFocus;
        $childTask['completed'] = false;
        $childTask['completion_state'] = 'pending';
        $childTask['completion_progress'] = 0.0;
        $childTask['completion_meta'] = [];
        $childTask['completed_at'] = null;
        $childTask['xp_awarded'] = false;
        $childTask['xp_awarded_total'] = 0;
        $childTask['slot_index'] = $parentTask['slot_index'] ?? null;
        $childTask['backfill_level'] = $parentTask['backfill_level'] ?? null;
        // Preserve counts_toward_progression from the task type
        // frontier_topic_quiz and repair tasks count toward progression
        // even when shown as previews
        $childTask['counts_toward_progression'] = (bool) ($childTask['counts_toward_progression'] ?? false);
        $childTask['simulation_scope'] = null;
        $childTask['covered_topic_ids'] = [];
        $childTask['is_preview'] = false;
        $childTask['unlocks_after_task_id'] = null;

        return $childTask;
    }

    private function applyXpTransitions(Collection $tasks, Collection $storedTaskMap): array
    {
        $justCompletedTasks = collect();
        $xpAwardedNow = 0;

        $normalizedTasks = $tasks->map(function (array $task) use ($storedTaskMap, &$justCompletedTasks, &$xpAwardedNow) {
            $storedTask = $storedTaskMap->get($task['id'], []);
            $currentAwarded = max(
                (int) ($task['xp_awarded_total'] ?? 0),
                (int) ($storedTask['xp_awarded_total'] ?? (($storedTask['xp_awarded'] ?? false)
                    ? (int) ($storedTask['xp_value'] ?? $storedTask['xp'] ?? 0)
                    : 0))
            );
            $targetAward = $this->resolveTargetXpAward($task);
            $awardDelta = max(0, $targetAward - $currentAwarded);

            if ($awardDelta > 0) {
                $xpAwardedNow += $awardDelta;
                $justCompletedTasks->push([
                    'title' => $task['title'],
                    'xp' => $awardDelta,
                ]);
            }

            $task['xp_awarded_total'] = $currentAwarded + $awardDelta;
            $task['xp_awarded'] = $task['completed']
                && $task['xp_awarded_total'] >= (int) ($task['xp_value'] ?? $task['xp'] ?? 0);
            $task['award_delta'] = $awardDelta;
            $task['was_previously_completed'] = (bool) ($storedTask['completed'] ?? false);

            return $task;
        })->values();

        return [$normalizedTasks, $justCompletedTasks->values(), $xpAwardedNow];
    }

    private function resolveTargetXpAward(array $task): int
    {
        $xpValue = (int) ($task['xp_value'] ?? $task['xp'] ?? 0);
        $state = (string) ($task['completion_state'] ?? 'pending');
        $progress = (float) ($task['completion_progress'] ?? 0.0);

        return match ($state) {
            'complete' => $xpValue,
            'partial' => (int) round($xpValue * max(0.0, min(0.99, $progress))),
            default => 0,
        };
    }

    private function resolveRecomputeDecision(
        array $storedPlanMeta,
        object $context,
        Collection $storedTasks,
        Collection $topics
    ): array {
        if ($storedTasks->isEmpty()) {
            return ['recompute' => true, 'reason' => 'initial_generation', 'critical' => true];
        }

        // Remove immediate recompute for completed tasks to allow 6-hour freeze
        // if ($storedTasks->every(fn (array $task) => (bool) ($task['completed'] ?? false))) {
        //     return ['recompute' => true, 'reason' => 'task_queue_advanced', 'critical' => true];
        // }

        $generatedAt = !empty($storedPlanMeta['generated_at'])
            ? Carbon::parse((string) $storedPlanMeta['generated_at'])
            : null;
        if ($generatedAt === null) {
            return ['recompute' => true, 'reason' => 'missing_generation_metadata', 'critical' => true];
        }

        if ((int) ($storedPlanMeta['version'] ?? 0) !== self::PLAN_VERSION) {
            return ['recompute' => true, 'reason' => 'planner_version_upgrade', 'critical' => true];
        }

        $currentSettingsHash = $this->settingsHash($context);
        if (($storedPlanMeta['signal_snapshot']['settings_hash'] ?? null) !== $currentSettingsHash) {
            return ['recompute' => true, 'reason' => 'planner_settings_changed', 'critical' => true];
        }

        if ($this->hasNewlyCompletedTask($storedTasks, $topics, $context)) {
            return ['recompute' => true, 'reason' => 'task_completion_changed', 'critical' => true];
        }

        $storedSignals = $storedPlanMeta['signal_snapshot'] ?? [];
        $currentSignals = $this->buildSignalSnapshot($context);

        if ($this->hasMaterialDueDelta($storedSignals, $currentSignals)) {
            return ['recompute' => true, 'reason' => 'due_spaced_load_changed', 'critical' => true];
        }

        if (($storedSignals['assignment_urgency_band'] ?? null) !== ($currentSignals['assignment_urgency_band'] ?? null)) {
            return ['recompute' => true, 'reason' => 'assignment_urgency_changed', 'critical' => true];
        }

        if (($storedSignals['exam_urgency_band'] ?? null) !== ($currentSignals['exam_urgency_band'] ?? null)) {
            return ['recompute' => true, 'reason' => 'exam_urgency_changed', 'critical' => true];
        }

        $allTasksCompleted = $storedTasks->every(fn (array $task) => (bool) ($task['completed'] ?? false));
        $stabilityWindow = $allTasksCompleted ? self::COMPLETED_PLAN_FREEZE_MINUTES : self::RECOMPUTE_WINDOW_MINUTES;

        if ($generatedAt->lte(now()->subMinutes($stabilityWindow))) {
            return ['recompute' => true, 'reason' => 'stability_window_expired', 'critical' => false];
        }

        return ['recompute' => false, 'reason' => 'stable_reuse', 'critical' => false];
    }

    private function hasNewlyCompletedTask(Collection $storedTasks, Collection $topics, object $context): bool
    {
        return $storedTasks->contains(function (array $task) use ($topics, $context) {
            if (!empty($task['completed'])) {
                return false;
            }

            /* Preview tasks CAN complete during stable reuse if a student solves 
               material ahead of the active slot (e.g. solving a preview quiz).
               We MUST detect this to trigger a recompute and unlock the plan. */

            $topic = isset($task['topic_id'])
                ? $topics->firstWhere('id', (int) $task['topic_id'])
                : null;

            return $this->taskCompletionService->isTaskCompleted($task, $topic, $context);
        });
    }

    private function hasMaterialDueDelta(array $storedSignals, array $currentSignals): bool
    {
        $previousDue = (int) ($storedSignals['due_spaced_reviews'] ?? 0);
        $currentDue = (int) ($currentSignals['due_spaced_reviews'] ?? 0);

        if ($previousDue === $currentDue) {
            return false;
        }

        if (($previousDue === 0) !== ($currentDue === 0)) {
            return true;
        }

        if (($storedSignals['due_load_band'] ?? null) !== ($currentSignals['due_load_band'] ?? null)) {
            return true;
        }

        return abs($previousDue - $currentDue) >= 2;
    }

    private function mergeRecomputedTasks(
        Collection $candidateTasks,
        Collection $storedTasks,
        object $context,
        array $recomputeDecision
    ): Collection {
        $completedStoredTasks = $storedTasks
            ->filter(fn (array $task) => (bool) ($task['completed'] ?? false))
            ->values();

        $currentOpenTask = $storedTasks
            ->first(fn (array $task) => !(bool) ($task['completed'] ?? false));

        $candidateTasks = $candidateTasks->values();

        if (!$recomputeDecision['critical'] && $currentOpenTask) {
            $matchingCurrentTask = $candidateTasks->firstWhere('id', $currentOpenTask['id']);
            if ($matchingCurrentTask) {
                $candidateTasks = collect([$matchingCurrentTask])
                    ->concat($candidateTasks->reject(fn (array $task) => $task['id'] === $matchingCurrentTask['id']))
                    ->values();
            } else {
                $currentOpenTask['trigger_reason'] = 'preserved_current_task';
                $candidateTasks = collect([$currentOpenTask])->concat($candidateTasks)->values();
            }
        }

        // Append completed tasks AFTER new candidates so they never consume slots
        // that new tasks need (prepending caused slot starvation when 3+ tasks were completed).
        return $candidateTasks
            ->concat($completedStoredTasks)
            ->unique('id')
            ->values(); // no take() here — sortTasksForStudentFlow + buildVisibleTaskQueue handle limits
    }

    private function refreshPersistedMeta(
        array $storedPlanMeta,
        object $context,
        Collection $tasks,
        string $triggerReason,
        bool $didRecompute
    ): array {
        $resolvedSettings = $this->serializeResolvedSettings($context->resolvedSettings);

        if (!$didRecompute) {
            return array_merge($storedPlanMeta, [
                'resolved_settings' => $resolvedSettings,
                'task_goal' => $tasks->count(),
                'planned_minutes' => (int) $tasks->sum(fn (array $task) => (int) ($task['duration_minutes'] ?? 0)),
                'planned_questions' => (int) $tasks->sum(fn (array $task) => (int) ($task['question_target'] ?? 0)),
            ]);
        }

        return [
            'generated_at' => now()->toIso8601String(),
            'version' => self::PLAN_VERSION,
            'trigger_reason' => $triggerReason,
            'resolved_settings' => $resolvedSettings,
            'signal_snapshot' => $this->buildSignalSnapshot($context),
            'task_goal' => $tasks->count(),
            'planned_minutes' => (int) $tasks->sum(fn (array $task) => (int) ($task['duration_minutes'] ?? 0)),
            'planned_questions' => (int) $tasks->sum(fn (array $task) => (int) ($task['question_target'] ?? 0)),
        ];
    }

    private function sortTasksForStudentFlow(Collection $tasks): Collection
    {
        return $tasks
            ->values()
            ->map(fn (array $task, int $index) => $task + ['_sort_index' => $index])
            ->sortBy(function (array $task, int $index): string {
                $slotBucket = isset($task['slot_index']) && $task['slot_index'] !== null
                    ? (int) $task['slot_index']
                    : 99;

                return sprintf('%02d-%04d', $slotBucket, $index);
            })
            ->map(function (array $task): array {
                unset($task['_sort_index']);

                return $task;
            })
            ->values();
    }

    private function buildVisibleTaskQueue(
        Collection $tasks,
        Collection $topics,
        object $context,
        Collection $storedTaskMap,
        string $triggerReason
    ): Collection {
        $activeTasks = $tasks
            ->filter(function (array $task) {
                if (!($task['completed'] ?? false)) {
                    return true;
                }

                $completedAt = $task['completed_at'] ?? null;
                if (!$completedAt) {
                    return false;
                }

                return Carbon::parse($completedAt)->isAfter(now()->subHours(24));
            })
            ->values();

        $currentActionableTask = $activeTasks->first(function (array $task) {
            return !(bool) ($task['completed'] ?? false)
                && !(bool) ($task['is_preview'] ?? false)
                && (bool) ($task['counts_toward_progression'] ?? false);
        });

        if ($currentActionableTask !== null) {
            $previewTasks = $this->plannerTaskBuilderService
                ->buildProgressionPreviewTasks($topics, $context, $currentActionableTask)
                ->map(function (array $task) use ($context) {
                    return array_merge($task, [
                        'resolved_exam_date' => $context->examDate?->format('Y-m-d'),
                        'resolved_focus' => $context->plannerFocus,
                    ]);
                })
                ->values();

            // Critical Fix: Group completed tasks for the same topic together with the actionable task
            // so the "Study (Done) -> Solve (Active)" flow stays intact at the top.
            $actionableTopicId = (int) ($currentActionableTask['topic_id'] ?? 0);
            $actionableType = (string) ($currentActionableTask['type'] ?? '');

            $topicGroup = $activeTasks->filter(function (array $task) use ($actionableTopicId) {
                return (int) ($task['topic_id'] ?? 0) === $actionableTopicId
                    && !($task['is_preview'] ?? false);
            })->values();

            $previewTasks = $previewTasks->reject(function (array $preview) use ($actionableTopicId, $actionableType) {
                return (int) ($preview['topic_id'] ?? 0) === $actionableTopicId
                    && (string) ($preview['type'] ?? '') === $actionableType;
            });

            $groupAndPreviewIds = $topicGroup->pluck('id')->concat($previewTasks->pluck('id'))->all();

            $extraTasks = $activeTasks
                ->reject(fn (array $task) => in_array($task['id'] ?? null, $groupAndPreviewIds, true))
                ->values();

            return $topicGroup
                ->concat($previewTasks)
                ->concat($extraTasks)
                ->values();
        }

        return $activeTasks->take($context->tasksLimit)->values();
    }

    private function buildSignalSnapshot(object $context): array
    {
        return [
            'due_spaced_reviews' => (int) data_get($context->backlogSignals, 'due_spaced_reviews', 0),
            'reviewable_backlog' => (int) data_get($context->backlogSignals, 'reviewable_backlog', 0),
            'active_open_backlog' => (int) data_get($context->backlogSignals, 'active_open_backlog', 0),
            'deferred_skipped_backlog' => (int) data_get($context->backlogSignals, 'deferred_skipped_backlog', 0),
            'due_load_band' => $this->dueLoadBand((int) data_get($context->backlogSignals, 'due_spaced_reviews', 0)),
            'assignment_urgency_band' => $this->assignmentUrgencyBand($context->assignmentSignals),
            'exam_urgency_band' => $this->examUrgencyBand($context->daysToExam),
            'settings_hash' => $this->settingsHash($context),
        ];
    }

    private function dueLoadBand(int $dueCount): string
    {
        return match (true) {
            $dueCount <= 0 => 'none',
            $dueCount <= 2 => 'light',
            $dueCount <= 5 => 'moderate',
            default => 'heavy',
        };
    }

    private function assignmentUrgencyBand(array $assignmentSignals): string
    {
        if ((int) ($assignmentSignals['overdue_assignment_count'] ?? 0) > 0) {
            return 'overdue';
        }

        if ((int) ($assignmentSignals['due_within_48h_count'] ?? 0) > 0) {
            return 'due_48h';
        }

        $nearestHours = $assignmentSignals['nearest_assignment_hours'] ?? null;
        if ($nearestHours !== null && (int) $nearestHours <= 168) {
            return 'due_week';
        }

        return 'stable';
    }

    private function examUrgencyBand(?int $daysToExam): string
    {
        if ($daysToExam === null || $daysToExam > 21) {
            return 'none';
        }

        return match (true) {
            $daysToExam < 3 => 'immediate',
            $daysToExam <= 7 => 'urgent',
            $daysToExam <= 10 => 'soon',
            default => 'planned',
        };
    }

    private function settingsHash(object $context): string
    {
        return md5(json_encode($this->serializeResolvedSettings($context->resolvedSettings)));
    }

    private function resolveModeLabel(array $task): string
    {
        $configuredMode = (string) ($task['study_mode'] ?? '');
        if ($configuredMode !== '') {
            return match ($configuredMode) {
                'tutor' => 'Tutor',
                'mistake_review' => (($task['repair_mode'] ?? null) === 'spaced') ? 'Spaced review' : 'Review',
                default => 'Quiz',
            };
        }

        return match ((string) ($task['type'] ?? '')) {
            'start_topic', 'rapid_overview', 'concept_repair', 'concept_cluster_repair' => 'Tutor',
            'topic_mistake_review' => 'Spaced review',
            'mistakes_cleanup' => 'Review',
            default => 'Quiz',
        };
    }

    private function serializeResolvedSettings(array $resolvedSettings): array
    {
        $examDate = $resolvedSettings['exam_date'] ?? null;
        if ($examDate instanceof Carbon) {
            $examDate = $examDate->format('Y-m-d');
        }

        return [
            'exam_date' => $examDate,
            'exam_date_iso' => $resolvedSettings['exam_date_iso'] ?? $examDate,
            'configured_daily_study_minutes' => (int) ($resolvedSettings['configured_daily_study_minutes'] ?? 0),
            'effective_daily_study_minutes' => (int) ($resolvedSettings['effective_daily_study_minutes'] ?? 0),
            'allocated_minutes' => (int) ($resolvedSettings['allocated_minutes'] ?? 0),
            'planner_focus' => (string) ($resolvedSettings['planner_focus'] ?? 'balanced'),
            'preferred_source' => $resolvedSettings['preferred_source'] ?? null,
            'repair_mode' => (string) ($resolvedSettings['repair_mode'] ?? 'easy'),
            'mistake_mastery_mode' => (string) ($resolvedSettings['mistake_mastery_mode'] ?? 'easy'),
            'course_overrides' => [
                'planner_exam_date' => data_get($resolvedSettings, 'course_overrides.planner_exam_date'),
                'planner_daily_study_minutes' => data_get($resolvedSettings, 'course_overrides.planner_daily_study_minutes'),
                'planner_focus' => data_get($resolvedSettings, 'course_overrides.planner_focus'),
                'planner_preferred_source' => data_get($resolvedSettings, 'course_overrides.planner_preferred_source'),
                'planner_repair_mode' => data_get($resolvedSettings, 'course_overrides.planner_repair_mode'),
                'planner_mistake_mastery_mode' => data_get($resolvedSettings, 'course_overrides.planner_mistake_mastery_mode'),
            ],
        ];
    }

    private function roadmapTasks(Collection $tasks, object $context): Collection
    {
        return $tasks
            ->filter(fn (array $task) =>
                !isset($task['slot_index'])
                || $task['slot_index'] === null
                || (int) $task['slot_index'] <= $context->tasksLimit
            )
            ->sortBy(fn (array $task) => (int) ($task['slot_index'] ?? 999))
            ->take($context->tasksLimit)
            ->values();
    }

    private function resolveTaskUrl(array $task, object $context): ?string
    {
        $topicId = (int) ($task['topic_id'] ?? 0);
        if ($topicId <= 0) {
            if (($task['type'] ?? '') === 'mistakes_cleanup') {
                return route('review.index');
            }
            return null;
        }

        $courseId = $context->course->id;
        $mode = $task['study_mode'] ?? 'quiz';
        $source = (string) ($task['resolved_source'] ?? $task['source'] ?? '');
        $sourceParams = $source !== '' ? ['sources' => [$source]] : [];

        if ($mode === 'tutor') {
            return route('quiz.tutor', array_merge(['course' => $courseId, 'topic' => $topicId], $sourceParams));
        }

        return route('quiz.start', array_merge(['course' => $courseId, 'topic' => $topicId], $sourceParams));
    }

    private function resolveTodayPlan(User $user): StudyPlan
    {
        $plan = StudyPlan::query()
            ->where('user_id', $user->id)
            ->whereDate('plan_date', now()->toDateString())
            ->first();

        if ($plan instanceof StudyPlan) {
            return $plan;
        }

        return StudyPlan::query()->create([
            'user_id' => $user->id,
            'plan_date' => now()->toDateString(),
            'daily_goal' => 4,
            'questions_completed' => 0,
            'is_committed' => true,
            'tasks' => [],
            'topics' => [],
        ]);
    }

    private function buildDebugPayload(User $user, Collection $tasks, Collection $topics, object $context): ?array
    {
        if (!$user->isAdmin() && !$user->isTutor()) {
            return null;
        }

        $orderedTopics = $topics
            ->sortBy(fn (Topic $topic) => sprintf(
                '%010d-%010d',
                (int) ($topic->display_order ?? PHP_INT_MAX),
                (int) $topic->id
            ))
            ->values();

        $frontierTopic = $orderedTopics->first(function (Topic $topic) use ($context) {
            return (bool) data_get($context->topicSignals, 'profiles.' . $topic->id . '.curriculum_unlocked', true)
                && data_get($context->topicSignals, 'profiles.' . $topic->id . '.progression_stage') !== 'cleared';
        });

        return [
            'frontier_topic_id' => $frontierTopic?->id,
            'frontier_stage' => $frontierTopic
                ? data_get($context->topicSignals, 'profiles.' . $frontierTopic->id . '.progression_stage')
                : null,
            'topics' => $orderedTopics->map(function (Topic $topic) use ($context): array {
                $profile = data_get($context->topicSignals, 'profiles.' . $topic->id, []);
                $lockedReason = $topic->lock_reason ?? null;

                if ($lockedReason === null && !(bool) ($profile['curriculum_unlocked'] ?? true)) {
                    $lockedReason = 'curriculum_locked';
                } elseif ($lockedReason === null && (bool) $topic->is_locked) {
                    $lockedReason = 'topic_locked';
                }

                return [
                    'topic_id' => (int) $topic->id,
                    'topic_name' => $topic->name,
                    'display_order' => (int) ($topic->display_order ?? 0),
                    'progression_stage' => (string) ($profile['progression_stage'] ?? 'unknown'),
                    'curriculum_unlocked' => (bool) ($profile['curriculum_unlocked'] ?? false),
                    'priority_score' => (float) ($profile['priority_score'] ?? 0.0),
                    'locked_reason' => $lockedReason,
                ];
            })->all(),
            'tasks' => $tasks->map(fn (array $task): array => [
                'id' => $task['id'],
                'type' => $task['type'],
                'title' => $task['title'],
                'topic_id' => $task['topic_id'] ?? null,
                'slot_index' => $task['slot_index'] ?? null,
                'backfill_level' => $task['backfill_level'] ?? null,
                'trigger_reason' => $task['trigger_reason'] ?? null,
                'score_components' => $task['score_components'] ?? [],
                'counts_toward_progression' => (bool) ($task['counts_toward_progression'] ?? false),
                'is_preview' => (bool) ($task['is_preview'] ?? false),
                'unlocks_after_task_id' => $task['unlocks_after_task_id'] ?? null,
            ])->all(),
        ];
    }
}
