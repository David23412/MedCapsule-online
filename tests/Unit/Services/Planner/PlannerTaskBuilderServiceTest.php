<?php

namespace Tests\Unit\Services\Planner;

use App\Services\Planner\PlannerContext;
use App\Services\Planner\PlannerTaskBuilderService;
use App\Models\Topic;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Illuminate\Support\Collection;

/**
 * Tests for slot assembly, preview generation, and progression flow.
 *
 * Validates the three-stage progression system:
 *   Study (tutor) → Solve (quiz) → Revise Mistakes (repair) → Cleared
 *
 * Slot rules:
 *   Slot 1 = active frontier task (always)
 *   Slot 2 = next progression step preview
 *   Slot 3 = step after slot 2 (empty when slot 1 is Study)
 */
class PlannerTaskBuilderServiceTest extends TestCase
{
    private PlannerTaskBuilderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PlannerTaskBuilderService();
    }

    // ────────────────────────────────────────────────────────────
    // buildProgressionPreviewTasks — Study (Slot 1) → Solve (Slot 2) → Slot 3 EMPTY
    // ────────────────────────────────────────────────────────────

    public function test_study_slot1_shows_solve_preview_in_slot2_and_empty_slot3(): void
    {
        $topic1 = $this->createTopic(1, 'Topic 1', 1);
        $topic2 = $this->createTopic(2, 'Topic 2', 2);
        $topics = collect([$topic1, $topic2]);

        $actionableTask = [
            'id' => 'start_topic_1',
            'type' => 'start_topic',
            'topic_id' => 1,
            'is_preview' => false,
            'completed' => false,
            'counts_toward_progression' => true,
            'slot_index' => 1,
        ];

        $context = $this->buildContext(['profiles' => [
            1 => ['progression_stage' => 'tutor', 'curriculum_unlocked' => true],
            2 => ['progression_stage' => 'tutor', 'curriculum_unlocked' => false],
        ]]);

        $previews = $this->service->buildProgressionPreviewTasks($topics, $context, $actionableTask);

        // Slot 2 = Solve preview for Topic 1
        $this->assertCount(1, $previews, 'Study in slot 1 should generate exactly 1 preview (Solve)');
        $this->assertSame('frontier_topic_quiz', $previews->first()['type']);
        $this->assertSame(1, (int) $previews->first()['topic_id']);
        $this->assertSame(2, $previews->first()['slot_index']);
        $this->assertTrue($previews->first()['is_preview']);
        // Slot 3 is NOT generated — cannot know if mistakes will exist before quiz
    }

    // ────────────────────────────────────────────────────────────
    // buildProgressionPreviewTasks — Solve (Slot 1) → Repair (Slot 2) → Study T2 (Slot 3)
    // ────────────────────────────────────────────────────────────

    public function test_solve_slot1_shows_repair_preview_slot2_and_study_next_topic_slot3(): void
    {
        $topic1 = $this->createTopic(1, 'Topic 1', 1);
        $topic2 = $this->createTopic(2, 'Topic 2', 2);
        $topics = collect([$topic1, $topic2]);

        $actionableTask = [
            'id' => 'frontier_topic_quiz_1',
            'type' => 'frontier_topic_quiz',
            'topic_id' => 1,
            'is_preview' => false,
            'completed' => false,
            'counts_toward_progression' => true,
            'slot_index' => 1,
        ];

        $context = $this->buildContext([
            'profiles' => [
                1 => ['progression_stage' => 'quiz', 'curriculum_unlocked' => true],
                2 => ['progression_stage' => 'tutor', 'curriculum_unlocked' => false],
            ],
        ]);

        $previews = $this->service->buildProgressionPreviewTasks($topics, $context, $actionableTask);

        // Should have 2 previews: Repair (slot 2) + Study T2 (slot 3)
        $this->assertCount(2, $previews, 'Solve in slot 1 should generate 2 previews (Repair + Study next)');

        $slot2 = $previews->first();
        $slot3 = $previews->last();

        // Slot 2 = Repair preview for Topic 1
        $this->assertContains($slot2['type'], ['repair_topic', 'topic_mistake_review']);
        $this->assertSame(1, (int) $slot2['topic_id']);
        $this->assertSame(2, $slot2['slot_index']);

        // Slot 3 = Study preview for Topic 2
        $this->assertContains($slot3['type'], ['start_topic', 'rapid_overview']);
        $this->assertSame(2, (int) $slot3['topic_id']);
        $this->assertSame(3, $slot3['slot_index']);
    }

    // ────────────────────────────────────────────────────────────
    // buildProgressionPreviewTasks — Repair (Slot 1) → Study T2 (Slot 2) → Solve T2 (Slot 3)
    // ────────────────────────────────────────────────────────────

    public function test_repair_slot1_shows_study_next_topic_slot2_and_solve_next_topic_slot3(): void
    {
        $topic1 = $this->createTopic(1, 'Topic 1', 1);
        $topic2 = $this->createTopic(2, 'Topic 2', 2);
        $topics = collect([$topic1, $topic2]);

        $actionableTask = [
            'id' => 'repair_topic_1',
            'type' => 'repair_topic',
            'topic_id' => 1,
            'is_preview' => false,
            'completed' => false,
            'counts_toward_progression' => true,
            'slot_index' => 1,
        ];

        $context = $this->buildContext([
            'profiles' => [
                1 => ['progression_stage' => 'mistakes', 'curriculum_unlocked' => true],
                2 => ['progression_stage' => 'tutor', 'curriculum_unlocked' => false],
            ],
        ]);

        $previews = $this->service->buildProgressionPreviewTasks($topics, $context, $actionableTask);

        $this->assertCount(2, $previews, 'Repair in slot 1 should generate 2 previews (Study T2 + Solve T2)');

        $slot2 = $previews->first();
        $slot3 = $previews->last();

        // Slot 2 = Study preview for Topic 2
        $this->assertContains($slot2['type'], ['start_topic', 'rapid_overview']);
        $this->assertSame(2, (int) $slot2['topic_id']);
        $this->assertSame(2, $slot2['slot_index']);

        // Slot 3 = Solve preview for Topic 2
        $this->assertSame('frontier_topic_quiz', $slot3['type']);
        $this->assertSame(2, (int) $slot3['topic_id']);
        $this->assertSame(3, $slot3['slot_index']);
    }

    // ────────────────────────────────────────────────────────────
    // Preview tasks never generated for already-completed tasks
    // ────────────────────────────────────────────────────────────

    public function test_no_previews_for_completed_actionable_task(): void
    {
        $topic1 = $this->createTopic(1, 'Topic 1', 1);
        $topics = collect([$topic1]);

        $actionableTask = [
            'id' => 'start_topic_1',
            'type' => 'start_topic',
            'topic_id' => 1,
            'is_preview' => false,
            'completed' => true,
            'counts_toward_progression' => true,
            'slot_index' => 1,
        ];

        $context = $this->buildContext();

        $previews = $this->service->buildProgressionPreviewTasks($topics, $context, $actionableTask);

        $this->assertCount(0, $previews, 'Completed actionable tasks should not generate previews');
    }

    public function test_no_previews_for_preview_task(): void
    {
        $topic1 = $this->createTopic(1, 'Topic 1', 1);
        $topics = collect([$topic1]);

        $actionableTask = [
            'id' => 'preview_start_topic_1',
            'type' => 'start_topic',
            'topic_id' => 1,
            'is_preview' => true,
            'completed' => false,
            'counts_toward_progression' => false,
            'slot_index' => 2,
        ];

        $context = $this->buildContext();

        $previews = $this->service->buildProgressionPreviewTasks($topics, $context, $actionableTask);

        $this->assertCount(0, $previews, 'Preview tasks should not generate their own previews');
    }

    // ────────────────────────────────────────────────────────────
    // Preview metadata — is_preview flag, XP zeroed, etc
    // ────────────────────────────────────────────────────────────

    public function test_preview_tasks_have_correct_metadata(): void
    {
        $topic1 = $this->createTopic(1, 'Topic 1', 1);
        $topic2 = $this->createTopic(2, 'Topic 2', 2);
        $topics = collect([$topic1, $topic2]);

        $actionableTask = [
            'id' => 'start_topic_1',
            'type' => 'start_topic',
            'topic_id' => 1,
            'is_preview' => false,
            'completed' => false,
            'counts_toward_progression' => true,
            'slot_index' => 1,
        ];

        $context = $this->buildContext(['profiles' => [
            1 => ['progression_stage' => 'tutor', 'curriculum_unlocked' => true],
            2 => ['progression_stage' => 'tutor', 'curriculum_unlocked' => false],
        ]]);

        $previews = $this->service->buildProgressionPreviewTasks($topics, $context, $actionableTask);

        $this->assertNotEmpty($previews);
        $preview = $previews->first();

        $this->assertTrue($preview['is_preview'], 'Preview task must have is_preview=true');
        $this->assertSame(false, $preview['completed'], 'Preview task must have completed=false');
        $this->assertSame('preview', $preview['completion_state'], 'Preview task must have completion_state=preview');
        $this->assertSame(0.0, $preview['completion_progress']);
        $this->assertSame(0, $preview['xp_value'], 'Preview tasks have zero XP');
        $this->assertSame(0, $preview['xp_awarded_total']);
        $this->assertFalse($preview['xp_awarded']);
        $this->assertNotEmpty($preview['unlocks_after_task_id'], 'Preview must reference parent task');
    }

    // ────────────────────────────────────────────────────────────
    // isStudyProgressionTask / isSolveProgressionTask / isRepairProgressionTask
    // ────────────────────────────────────────────────────────────

    public function test_task_type_classification(): void
    {
        $isStudy = new ReflectionMethod(PlannerTaskBuilderService::class, 'isStudyProgressionTask');
        $isStudy->setAccessible(true);
        $isSolve = new ReflectionMethod(PlannerTaskBuilderService::class, 'isSolveProgressionTask');
        $isSolve->setAccessible(true);
        $isRepair = new ReflectionMethod(PlannerTaskBuilderService::class, 'isRepairProgressionTask');
        $isRepair->setAccessible(true);

        $this->assertTrue($isStudy->invoke($this->service, ['type' => 'start_topic']));
        $this->assertTrue($isStudy->invoke($this->service, ['type' => 'rapid_overview']));
        $this->assertFalse($isStudy->invoke($this->service, ['type' => 'frontier_topic_quiz']));

        $this->assertTrue($isSolve->invoke($this->service, ['type' => 'frontier_topic_quiz']));
        $this->assertTrue($isSolve->invoke($this->service, ['type' => 'final_exam_sprint', 'simulation_scope' => 'frontier_replace']));
        $this->assertFalse($isSolve->invoke($this->service, ['type' => 'final_exam_sprint', 'simulation_scope' => 'backfill_mixed']));
        $this->assertFalse($isSolve->invoke($this->service, ['type' => 'start_topic']));

        $this->assertTrue($isRepair->invoke($this->service, ['type' => 'repair_topic']));
        $this->assertTrue($isRepair->invoke($this->service, ['type' => 'topic_mistake_review']));
        $this->assertFalse($isRepair->invoke($this->service, ['type' => 'concept_repair']));
    }

    // ────────────────────────────────────────────────────────────
    // Repair slot 1 → no previews when no next topic (last topic)
    // ────────────────────────────────────────────────────────────

    public function test_repair_last_topic_generates_no_previews(): void
    {
        $topic1 = $this->createTopic(1, 'Topic 1', 1);
        $topics = collect([$topic1]); // Only one topic

        $actionableTask = [
            'id' => 'repair_topic_1',
            'type' => 'repair_topic',
            'topic_id' => 1,
            'is_preview' => false,
            'completed' => false,
            'counts_toward_progression' => true,
            'slot_index' => 1,
        ];

        $context = $this->buildContext(['profiles' => [
            1 => ['progression_stage' => 'mistakes', 'curriculum_unlocked' => true],
        ]]);

        $previews = $this->service->buildProgressionPreviewTasks($topics, $context, $actionableTask);

        $this->assertCount(0, $previews, 'Last topic in repair stage should have no previews');
    }

    // ────────────────────────────────────────────────────────────
    // Preview frontier_topic_quiz retains counts_toward_progression
    // ────────────────────────────────────────────────────────────

    public function test_preview_frontier_quiz_retains_counts_toward_progression(): void
    {
        $topic1 = $this->createTopic(1, 'Topic 1', 1);
        $topic2 = $this->createTopic(2, 'Topic 2', 2);
        $topics = collect([$topic1, $topic2]);

        $actionableTask = [
            'id' => 'start_topic_1',
            'type' => 'start_topic',
            'topic_id' => 1,
            'is_preview' => false,
            'completed' => false,
            'counts_toward_progression' => true,
            'slot_index' => 1,
        ];

        $context = $this->buildContext(['profiles' => [
            1 => ['progression_stage' => 'tutor', 'curriculum_unlocked' => true],
            2 => ['progression_stage' => 'tutor', 'curriculum_unlocked' => false],
        ]]);

        $previews = $this->service->buildProgressionPreviewTasks($topics, $context, $actionableTask);

        $this->assertNotEmpty($previews);
        $previewQuiz = $previews->first();
        $this->assertSame('frontier_topic_quiz', $previewQuiz['type']);

        // Critical: frontier_topic_quiz preview MUST retain counts_toward_progression
        // so applyPlannerStageTransitions can evaluate it for stage transitions
        $this->assertTrue(
            (bool) ($previewQuiz['counts_toward_progression'] ?? false),
            'Preview frontier_topic_quiz must retain counts_toward_progression=true for stage transition evaluation'
        );
    }

    // ────────────────────────────────────────────────────────────
    // Spaced repair task (topic_mistake_review) appears in slot 2
    // when repair mode is spaced
    // ────────────────────────────────────────────────────────────

    public function test_spaced_mode_shows_spaced_review_preview_in_slot2(): void
    {
        $topic1 = $this->createTopic(1, 'Topic 1', 1);
        $topic2 = $this->createTopic(2, 'Topic 2', 2);
        $topics = collect([$topic1, $topic2]);

        $actionableTask = [
            'id' => 'frontier_topic_quiz_1',
            'type' => 'frontier_topic_quiz',
            'topic_id' => 1,
            'is_preview' => false,
            'completed' => false,
            'counts_toward_progression' => true,
            'slot_index' => 1,
        ];

        $context = $this->buildContext([
            'profiles' => [
                1 => ['progression_stage' => 'quiz', 'curriculum_unlocked' => true],
                2 => ['progression_stage' => 'tutor', 'curriculum_unlocked' => false],
            ],
            'repairMode' => 'spaced',
        ]);

        $previews = $this->service->buildProgressionPreviewTasks($topics, $context, $actionableTask);

        $this->assertGreaterThanOrEqual(1, $previews->count());
        $slot2 = $previews->first();
        $this->assertSame('topic_mistake_review', $slot2['type'],
            'In spaced mode, repair preview should be topic_mistake_review');
    }

    // ────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────

    private function createTopic(int $id, string $name, int $displayOrder): Topic
    {
        $topic = new Topic();
        $topic->id = $id;
        $topic->name = $name;
        $topic->display_order = $displayOrder;
        $topic->is_locked = false;
        $topic->setRelation('userProgress', collect());

        return $topic;
    }

    private function buildContext(array $overrides = []): PlannerContext
    {
        $profiles = $overrides['profiles'] ?? [];
        $repairMode = $overrides['repairMode'] ?? 'easy';

        return new PlannerContext(
            examDate: null,
            daysToExam: null,
            dailyStudyMinutes: 90,
            totalDailyStudyMinutes: 90,
            plannerFocus: 'balanced',
            tasksLimit: 3,
            mixedQuestionCount: 15,
            mixedDurationMinutes: 27,
            carryOverTaskIds: [],
            courseTopicIds: array_keys($profiles) ?: [1, 2, 3],
            openMistakesCount: 0,
            overallAccuracy: 75.0,
            topicSignals: ['profiles' => $profiles],
            attemptSignals: ['topic_attempts_today' => [], 'random_attempts_today' => [], 'course_topic_ids' => [1, 2, 3]],
            dueMistakeSignals: ['total_due' => 0, 'by_topic' => []],
            backlogSignals: ['due_spaced_reviews' => 0, 'reviewable_backlog' => 0, 'by_topic' => []],
            assignmentSignals: [],
            conceptSignals: ['by_topic' => [], 'weakest' => [], 'topics' => []],
            behaviorSignals: [],
            globalAllocation: [],
            coursePriorityScore: 100.0,
            preferredSource: null,
            repairMode: $repairMode,
            classicRepairDifficulty: 'easy',
            resolvedSettings: [],
            course: $this->createMockCourse(),
        );
    }

    private function createMockCourse(): object
    {
        return new class {
            public int $id = 1;
        };
    }
}
