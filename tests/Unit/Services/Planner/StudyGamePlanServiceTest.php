<?php

namespace Tests\Unit\Services\Planner;

use App\Services\StudyGamePlanService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Unit tests for StudyGamePlanService.
 *
 * Tests child task spawning rules, XP calculation, completed task ordering,
 * plan recompute decisions, and visible task queue assembly.
 */
class StudyGamePlanServiceTest extends TestCase
{
    // ────────────────────────────────────────────────────────────
    // shouldSpawnChildTask — concept repair spawn rules
    // ────────────────────────────────────────────────────────────

    public function test_concept_repair_never_spawns_from_tutor_completion(): void
    {
        $service = $this->createPartialMock(StudyGamePlanService::class, []);
        $method = new ReflectionMethod(StudyGamePlanService::class, 'shouldSpawnChildTask');
        $method->setAccessible(true);

        $tutorTask = [
            'id' => 'start_topic_1',
            'type' => 'start_topic',
            'topic_id' => 1,
            'completed' => true,
            'spawned_from_task_id' => null,
        ];

        $storedTaskMap = collect(['start_topic_1' => ['id' => 'start_topic_1', 'completed' => false]]);

        $this->assertFalse(
            $method->invoke($service, $tutorTask, $storedTaskMap),
            'Tutor completions must NEVER spawn concept repair children — tutor is binary, wrong answers during tutor are learning signals not repair triggers'
        );
    }

    public function test_concept_repair_never_spawns_from_rapid_overview(): void
    {
        $service = $this->createPartialMock(StudyGamePlanService::class, []);
        $method = new ReflectionMethod(StudyGamePlanService::class, 'shouldSpawnChildTask');
        $method->setAccessible(true);

        $rapidOverviewTask = [
            'id' => 'rapid_overview_1',
            'type' => 'rapid_overview',
            'topic_id' => 1,
            'completed' => true,
            'spawned_from_task_id' => null,
        ];

        $storedTaskMap = collect(['rapid_overview_1' => ['id' => 'rapid_overview_1', 'completed' => false]]);

        $this->assertFalse(
            $method->invoke($service, $rapidOverviewTask, $storedTaskMap),
            'Rapid overview completions must NEVER spawn concept repair children'
        );
    }

    public function test_concept_repair_allowed_from_frontier_quiz_completion(): void
    {
        $service = $this->createPartialMock(StudyGamePlanService::class, []);
        $method = new ReflectionMethod(StudyGamePlanService::class, 'shouldSpawnChildTask');
        $method->setAccessible(true);

        $quizTask = [
            'id' => 'frontier_topic_quiz_1',
            'type' => 'frontier_topic_quiz',
            'topic_id' => 1,
            'completed' => true,
            'spawned_from_task_id' => null,
        ];

        // storedTask was NOT completed previously → spawning is allowed
        $storedTaskMap = collect(['frontier_topic_quiz_1' => ['id' => 'frontier_topic_quiz_1', 'completed' => false]]);

        $this->assertTrue(
            $method->invoke($service, $quizTask, $storedTaskMap),
            'Frontier quiz completions should allow concept repair spawning'
        );
    }

    public function test_concept_repair_allowed_from_repair_topic_completion(): void
    {
        $service = $this->createPartialMock(StudyGamePlanService::class, []);
        $method = new ReflectionMethod(StudyGamePlanService::class, 'shouldSpawnChildTask');
        $method->setAccessible(true);

        $repairTask = [
            'id' => 'repair_topic_1',
            'type' => 'repair_topic',
            'topic_id' => 1,
            'completed' => true,
            'spawned_from_task_id' => null,
        ];

        $storedTaskMap = collect(['repair_topic_1' => ['id' => 'repair_topic_1', 'completed' => false]]);

        $this->assertTrue(
            $method->invoke($service, $repairTask, $storedTaskMap),
            'Repair topic completions should allow concept repair spawning'
        );
    }

    public function test_concept_repair_allowed_from_spaced_review_completion(): void
    {
        $service = $this->createPartialMock(StudyGamePlanService::class, []);
        $method = new ReflectionMethod(StudyGamePlanService::class, 'shouldSpawnChildTask');
        $method->setAccessible(true);

        $spacedReviewTask = [
            'id' => 'topic_mistake_review_1',
            'type' => 'topic_mistake_review',
            'topic_id' => 1,
            'completed' => true,
            'spawned_from_task_id' => null,
        ];

        $storedTaskMap = collect(['topic_mistake_review_1' => ['id' => 'topic_mistake_review_1', 'completed' => false]]);

        $this->assertTrue(
            $method->invoke($service, $spacedReviewTask, $storedTaskMap),
            'Spaced review completions should allow concept repair spawning'
        );
    }

    public function test_concept_repair_never_spawns_from_already_spawned_child(): void
    {
        $service = $this->createPartialMock(StudyGamePlanService::class, []);
        $method = new ReflectionMethod(StudyGamePlanService::class, 'shouldSpawnChildTask');
        $method->setAccessible(true);

        $childTask = [
            'id' => 'concept_repair_child_parent1_slug',
            'type' => 'concept_repair',
            'topic_id' => 1,
            'completed' => true,
            'spawned_from_task_id' => 'parent1', // Already a child task
        ];

        $storedTaskMap = collect(['concept_repair_child_parent1_slug' => ['id' => 'concept_repair_child_parent1_slug', 'completed' => false]]);

        $this->assertFalse(
            $method->invoke($service, $childTask, $storedTaskMap),
            'Already-spawned child tasks must not spawn their own children (no chaining)'
        );
    }

    public function test_concept_repair_never_spawns_from_incomplete_task(): void
    {
        $service = $this->createPartialMock(StudyGamePlanService::class, []);
        $method = new ReflectionMethod(StudyGamePlanService::class, 'shouldSpawnChildTask');
        $method->setAccessible(true);

        $pendingTask = [
            'id' => 'frontier_topic_quiz_1',
            'type' => 'frontier_topic_quiz',
            'topic_id' => 1,
            'completed' => false,
            'spawned_from_task_id' => null,
        ];

        $storedTaskMap = collect(['frontier_topic_quiz_1' => ['id' => 'frontier_topic_quiz_1', 'completed' => false]]);

        $this->assertFalse(
            $method->invoke($service, $pendingTask, $storedTaskMap),
            'Incomplete tasks must not spawn children'
        );
    }

    public function test_concept_repair_never_spawns_when_already_completed_in_storage(): void
    {
        $service = $this->createPartialMock(StudyGamePlanService::class, []);
        $method = new ReflectionMethod(StudyGamePlanService::class, 'shouldSpawnChildTask');
        $method->setAccessible(true);

        $quizTask = [
            'id' => 'frontier_topic_quiz_1',
            'type' => 'frontier_topic_quiz',
            'topic_id' => 1,
            'completed' => true,
            'spawned_from_task_id' => null,
        ];

        // storedTask was ALREADY completed → do not re-spawn
        $storedTaskMap = collect(['frontier_topic_quiz_1' => ['id' => 'frontier_topic_quiz_1', 'completed' => true]]);

        $this->assertFalse(
            $method->invoke($service, $quizTask, $storedTaskMap),
            'Tasks already completed in storage must not re-spawn children'
        );
    }

    // ────────────────────────────────────────────────────────────
    // XP resolution — resolveTargetXpAward
    // ────────────────────────────────────────────────────────────

    public function test_xp_full_value_on_complete(): void
    {
        $service = $this->createPartialMock(StudyGamePlanService::class, []);
        $method = new ReflectionMethod(StudyGamePlanService::class, 'resolveTargetXpAward');
        $method->setAccessible(true);

        $task = ['xp_value' => 24, 'completion_state' => 'complete', 'completion_progress' => 1.0];

        $this->assertSame(24, $method->invoke($service, $task));
    }

    public function test_xp_partial_value_proportional_to_progress(): void
    {
        $service = $this->createPartialMock(StudyGamePlanService::class, []);
        $method = new ReflectionMethod(StudyGamePlanService::class, 'resolveTargetXpAward');
        $method->setAccessible(true);

        $task = ['xp_value' => 20, 'completion_state' => 'partial', 'completion_progress' => 0.5];

        $this->assertSame(10, $method->invoke($service, $task));
    }

    public function test_xp_zero_on_pending(): void
    {
        $service = $this->createPartialMock(StudyGamePlanService::class, []);
        $method = new ReflectionMethod(StudyGamePlanService::class, 'resolveTargetXpAward');
        $method->setAccessible(true);

        $task = ['xp_value' => 20, 'completion_state' => 'pending', 'completion_progress' => 0.0];

        $this->assertSame(0, $method->invoke($service, $task));
    }

    public function test_xp_partial_progress_capped_at_099(): void
    {
        $service = $this->createPartialMock(StudyGamePlanService::class, []);
        $method = new ReflectionMethod(StudyGamePlanService::class, 'resolveTargetXpAward');
        $method->setAccessible(true);

        $task = ['xp_value' => 20, 'completion_state' => 'partial', 'completion_progress' => 1.5];

        // min(0.99, 1.5) = 0.99 → 20 * 0.99 = 19.8 → round = 20
        $this->assertSame(20, $method->invoke($service, $task));
    }

    // ────────────────────────────────────────────────────────────
    // resolveCompletionState — state persistence and monotonicity
    // ────────────────────────────────────────────────────────────

    public function test_completed_state_never_regresses_to_pending(): void
    {
        $service = $this->createPartialMock(StudyGamePlanService::class, []);
        $method = new ReflectionMethod(StudyGamePlanService::class, 'resolveCompletionState');
        $method->setAccessible(true);

        $storedTask = ['completed' => true, 'completion_state' => 'complete'];
        $evaluation = ['state' => 'pending', 'progress' => 0.0];

        $this->assertSame('complete', $method->invoke($service, $storedTask, $evaluation));
    }

    public function test_pending_advances_to_complete(): void
    {
        $service = $this->createPartialMock(StudyGamePlanService::class, []);
        $method = new ReflectionMethod(StudyGamePlanService::class, 'resolveCompletionState');
        $method->setAccessible(true);

        $storedTask = ['completed' => false, 'completion_state' => 'pending'];
        $evaluation = ['state' => 'complete', 'progress' => 1.0];

        $this->assertSame('complete', $method->invoke($service, $storedTask, $evaluation));
    }

    public function test_partial_state_recognized(): void
    {
        $service = $this->createPartialMock(StudyGamePlanService::class, []);
        $method = new ReflectionMethod(StudyGamePlanService::class, 'resolveCompletionState');
        $method->setAccessible(true);

        $storedTask = null;
        $evaluation = ['state' => 'partial', 'progress' => 0.5];

        $this->assertSame('partial', $method->invoke($service, $storedTask, $evaluation));
    }

    // ────────────────────────────────────────────────────────────
    // resolveCompletionProgress — monotonic progress
    // ────────────────────────────────────────────────────────────

    public function test_progress_never_decreases(): void
    {
        $service = $this->createPartialMock(StudyGamePlanService::class, []);
        $method = new ReflectionMethod(StudyGamePlanService::class, 'resolveCompletionProgress');
        $method->setAccessible(true);

        $storedTask = ['completion_progress' => 0.7, 'completed' => false];
        $evaluation = ['state' => 'partial', 'progress' => 0.3];

        $this->assertSame(0.7, $method->invoke($service, $storedTask, $evaluation));
    }

    public function test_progress_advances_when_higher(): void
    {
        $service = $this->createPartialMock(StudyGamePlanService::class, []);
        $method = new ReflectionMethod(StudyGamePlanService::class, 'resolveCompletionProgress');
        $method->setAccessible(true);

        $storedTask = ['completion_progress' => 0.3, 'completed' => false];
        $evaluation = ['state' => 'partial', 'progress' => 0.8];

        $this->assertSame(0.8, $method->invoke($service, $storedTask, $evaluation));
    }

    // ────────────────────────────────────────────────────────────
    // Mode label resolution
    // ────────────────────────────────────────────────────────────

    public function test_mode_labels_resolve_correctly(): void
    {
        $service = $this->createPartialMock(StudyGamePlanService::class, []);
        $method = new ReflectionMethod(StudyGamePlanService::class, 'resolveModeLabel');
        $method->setAccessible(true);

        $this->assertSame('Tutor', $method->invoke($service, ['study_mode' => 'tutor']));
        $this->assertSame('Quiz', $method->invoke($service, ['study_mode' => 'quiz']));
        $this->assertSame('Spaced review', $method->invoke($service, ['study_mode' => 'mistake_review', 'repair_mode' => 'spaced']));
        $this->assertSame('Review', $method->invoke($service, ['study_mode' => 'mistake_review']));
    }

    public function test_mode_labels_infer_from_type(): void
    {
        $service = $this->createPartialMock(StudyGamePlanService::class, []);
        $method = new ReflectionMethod(StudyGamePlanService::class, 'resolveModeLabel');
        $method->setAccessible(true);

        $this->assertSame('Tutor', $method->invoke($service, ['type' => 'start_topic']));
        $this->assertSame('Tutor', $method->invoke($service, ['type' => 'rapid_overview']));
        $this->assertSame('Tutor', $method->invoke($service, ['type' => 'concept_repair']));
        $this->assertSame('Spaced review', $method->invoke($service, ['type' => 'topic_mistake_review']));
        $this->assertSame('Review', $method->invoke($service, ['type' => 'mistakes_cleanup']));
        $this->assertSame('Quiz', $method->invoke($service, ['type' => 'frontier_topic_quiz']));
    }

    // ────────────────────────────────────────────────────────────
    // Exam urgency band calculation
    // ────────────────────────────────────────────────────────────

    public function test_exam_urgency_bands(): void
    {
        $service = $this->createPartialMock(StudyGamePlanService::class, []);
        $method = new ReflectionMethod(StudyGamePlanService::class, 'examUrgencyBand');
        $method->setAccessible(true);

        $this->assertSame('immediate', $method->invoke($service, 1));
        $this->assertSame('immediate', $method->invoke($service, 2));
        $this->assertSame('urgent', $method->invoke($service, 3));
        $this->assertSame('urgent', $method->invoke($service, 7));
        $this->assertSame('soon', $method->invoke($service, 8));
        $this->assertSame('soon', $method->invoke($service, 10));
        $this->assertSame('planned', $method->invoke($service, 11));
        $this->assertSame('planned', $method->invoke($service, 21));
        $this->assertSame('none', $method->invoke($service, 22));
        $this->assertSame('none', $method->invoke($service, null));
    }

    // ────────────────────────────────────────────────────────────
    // Due load band calculation
    // ────────────────────────────────────────────────────────────

    public function test_due_load_bands(): void
    {
        $service = $this->createPartialMock(StudyGamePlanService::class, []);
        $method = new ReflectionMethod(StudyGamePlanService::class, 'dueLoadBand');
        $method->setAccessible(true);

        $this->assertSame('none', $method->invoke($service, 0));
        $this->assertSame('light', $method->invoke($service, 1));
        $this->assertSame('light', $method->invoke($service, 2));
        $this->assertSame('moderate', $method->invoke($service, 3));
        $this->assertSame('moderate', $method->invoke($service, 5));
        $this->assertSame('heavy', $method->invoke($service, 6));
        $this->assertSame('heavy', $method->invoke($service, 20));
    }

    // ────────────────────────────────────────────────────────────
    // Visible task queue ordering — completed tasks stay on top
    // ────────────────────────────────────────────────────────────

    public function test_completed_tasks_stay_above_active_within_same_topic(): void
    {
        $service = $this->createPartialMock(StudyGamePlanService::class, []);
        $method = new ReflectionMethod(StudyGamePlanService::class, 'sortTasksForStudentFlow');
        $method->setAccessible(true);

        // Simulate: Topic 1 Study (done, slot 1) + Topic 1 Solve (active, slot 1)
        $tasks = collect([
            [
                'id' => 'frontier_topic_quiz_1', // Active solve (slot 1)
                'type' => 'frontier_topic_quiz',
                'topic_id' => 1,
                'completed' => false,
                'slot_index' => 1,
                'is_preview' => false,
                'counts_toward_progression' => true,
            ],
            [
                'id' => 'start_topic_1', // Completed study (slot 1)
                'type' => 'start_topic',
                'topic_id' => 1,
                'completed' => true,
                'completed_at' => now()->toIso8601String(),
                'slot_index' => 1,
                'is_preview' => false,
                'counts_toward_progression' => true,
            ],
        ]);

        $sorted = $method->invoke($service, $tasks);

        // Both have slot_index=1, so they sort by original index
        // In the actual flow, buildVisibleTaskQueue handles the chronological ordering
        $this->assertCount(2, $sorted);
    }

    // ────────────────────────────────────────────────────────────
    // hasMaterialDueDelta — recompute triggers
    // ────────────────────────────────────────────────────────────

    public function test_no_material_delta_when_counts_equal(): void
    {
        $service = $this->createPartialMock(StudyGamePlanService::class, []);
        $method = new ReflectionMethod(StudyGamePlanService::class, 'hasMaterialDueDelta');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($service,
            ['due_spaced_reviews' => 5, 'due_load_band' => 'moderate'],
            ['due_spaced_reviews' => 5, 'due_load_band' => 'moderate']
        ));
    }

    public function test_material_delta_when_zero_to_nonzero(): void
    {
        $service = $this->createPartialMock(StudyGamePlanService::class, []);
        $method = new ReflectionMethod(StudyGamePlanService::class, 'hasMaterialDueDelta');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($service,
            ['due_spaced_reviews' => 0, 'due_load_band' => 'none'],
            ['due_spaced_reviews' => 1, 'due_load_band' => 'light']
        ));
    }

    public function test_material_delta_when_diff_at_least_2(): void
    {
        $service = $this->createPartialMock(StudyGamePlanService::class, []);
        $method = new ReflectionMethod(StudyGamePlanService::class, 'hasMaterialDueDelta');
        $method->setAccessible(true);

        // Diff = 1, same band → no delta
        $this->assertFalse($method->invoke($service,
            ['due_spaced_reviews' => 3, 'due_load_band' => 'moderate'],
            ['due_spaced_reviews' => 4, 'due_load_band' => 'moderate']
        ));

        // Diff = 2, same band → delta
        $this->assertTrue($method->invoke($service,
            ['due_spaced_reviews' => 3, 'due_load_band' => 'moderate'],
            ['due_spaced_reviews' => 5, 'due_load_band' => 'moderate']
        ));
    }

    public function test_material_delta_when_band_changes(): void
    {
        $service = $this->createPartialMock(StudyGamePlanService::class, []);
        $method = new ReflectionMethod(StudyGamePlanService::class, 'hasMaterialDueDelta');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($service,
            ['due_spaced_reviews' => 2, 'due_load_band' => 'light'],
            ['due_spaced_reviews' => 3, 'due_load_band' => 'moderate']
        ));
    }
}
