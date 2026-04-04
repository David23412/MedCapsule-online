<?php

namespace Tests\Unit\Services\Planner;

use App\Models\Topic;
use App\Services\Planner\PlannerContext;
use App\Services\Planner\PlannerTaskBuilderService;
use App\Services\Planner\Strategies\BalancedPlannerStrategy;
use App\Services\Planner\Strategies\ExamPrepPlannerStrategy;
use App\Services\Planner\Strategies\RevisionPlannerStrategy;
use App\Services\Planner\TaskGeneratorService;
use PHPUnit\Framework\TestCase;

/**
 * Tests for planner strategy differentiation (focus modes).
 *
 * Validates:
 *   - ExamPrep mode prioritises is_exam topics
 *   - Revision mode prioritises mistake repair over new topics
 *   - Balanced mode is the default
 *   - TaskGeneratorService resolves strategies correctly
 */
class PlannerStrategyTest extends TestCase
{
    // ────────────────────────────────────────────────────────────
    // ExamPrepPlannerStrategy — exam topics prioritised
    // ────────────────────────────────────────────────────────────

    public function test_exam_prep_supports_only_exam_prep(): void
    {
        $strategy = new ExamPrepPlannerStrategy(new PlannerTaskBuilderService());

        $this->assertTrue($strategy->supports('exam_prep'));
        $this->assertFalse($strategy->supports('balanced'));
        $this->assertFalse($strategy->supports('revision'));
    }

    public function test_exam_prep_reorders_exam_topics_first(): void
    {
        $builder = new PlannerTaskBuilderService();
        $strategy = new ExamPrepPlannerStrategy($builder);

        // Create topics: lecture display_order=1, exam display_order=2
        $lectureA = $this->createTopic(1, 'Lecture A', 1, false);
        $examTopic = $this->createTopic(2, 'Past Exam 2022', 2, true);
        $lectureB = $this->createTopic(3, 'Lecture B', 3, false);
        $topics = collect([$lectureA, $examTopic, $lectureB]);

        $context = $this->buildContext([
            'plannerFocus' => 'exam_prep',
            'profiles' => [
                1 => ['progression_stage' => 'tutor', 'curriculum_unlocked' => true],
                2 => ['progression_stage' => 'tutor', 'curriculum_unlocked' => true],
                3 => ['progression_stage' => 'tutor', 'curriculum_unlocked' => true],
            ],
        ]);

        $tasks = $strategy->buildCandidates($topics, $context);

        // The first task should be for the exam topic (id=2), not lecture A (id=1)
        $this->assertNotEmpty($tasks, 'Exam prep should generate tasks');
        $firstTask = $tasks->first();
        $this->assertSame(2, (int) $firstTask['topic_id'],
            'Exam prep mode should prioritise is_exam topic (id=2) over lecture topic (id=1)');
    }

    // ────────────────────────────────────────────────────────────
    // RevisionPlannerStrategy — mistake repair prioritised
    // ────────────────────────────────────────────────────────────

    public function test_revision_supports_only_revision(): void
    {
        $strategy = new RevisionPlannerStrategy(new PlannerTaskBuilderService());

        $this->assertTrue($strategy->supports('revision'));
        $this->assertFalse($strategy->supports('balanced'));
        $this->assertFalse($strategy->supports('exam_prep'));
    }

    public function test_revision_falls_back_to_protocol_when_no_backlog(): void
    {
        $builder = new PlannerTaskBuilderService();
        $strategy = new RevisionPlannerStrategy($builder);

        $topic1 = $this->createTopic(1, 'Topic 1', 1, false);
        $topics = collect([$topic1]);

        $context = $this->buildContext([
            'plannerFocus' => 'revision',
            'profiles' => [
                1 => [
                    'progression_stage' => 'tutor',
                    'curriculum_unlocked' => true,
                    'has_reviewable_backlog' => false,
                    'has_due_review' => false,
                    'is_weak' => false,
                ],
            ],
        ]);

        $tasks = $strategy->buildCandidates($topics, $context);

        // With no backlog and no weak topics, revision should fall back to normal protocol
        $this->assertNotEmpty($tasks, 'Revision should fall back to normal protocol when no backlog exists');
    }

    // ────────────────────────────────────────────────────────────
    // BalancedPlannerStrategy — default mode
    // ────────────────────────────────────────────────────────────

    public function test_balanced_supports_only_balanced(): void
    {
        $strategy = new BalancedPlannerStrategy(new PlannerTaskBuilderService());

        $this->assertTrue($strategy->supports('balanced'));
        $this->assertFalse($strategy->supports('revision'));
        $this->assertFalse($strategy->supports('exam_prep'));
    }

    // ────────────────────────────────────────────────────────────
    // TaskGeneratorService — strategy resolution
    // ────────────────────────────────────────────────────────────

    public function test_task_generator_resolves_balanced_strategy(): void
    {
        $builder = new PlannerTaskBuilderService();
        $service = new TaskGeneratorService(
            new BalancedPlannerStrategy($builder),
            new RevisionPlannerStrategy($builder),
            new ExamPrepPlannerStrategy($builder),
        );

        $topic = $this->createTopic(1, 'Topic 1', 1, false);
        $context = $this->buildContext([
            'plannerFocus' => 'balanced',
            'profiles' => [
                1 => ['progression_stage' => 'tutor', 'curriculum_unlocked' => true],
            ],
        ]);

        $tasks = $service->buildCandidates(collect([$topic]), $context);
        $this->assertNotEmpty($tasks, 'Balanced strategy should generate tasks');
    }

    public function test_task_generator_resolves_exam_prep_strategy(): void
    {
        $builder = new PlannerTaskBuilderService();
        $service = new TaskGeneratorService(
            new BalancedPlannerStrategy($builder),
            new RevisionPlannerStrategy($builder),
            new ExamPrepPlannerStrategy($builder),
        );

        $lectureA = $this->createTopic(1, 'Lecture A', 1, false);
        $examTopic = $this->createTopic(2, 'Past Exam 2022', 2, true);
        $context = $this->buildContext([
            'plannerFocus' => 'exam_prep',
            'profiles' => [
                1 => ['progression_stage' => 'tutor', 'curriculum_unlocked' => true],
                2 => ['progression_stage' => 'tutor', 'curriculum_unlocked' => true],
            ],
        ]);

        $tasks = $service->buildCandidates(collect([$lectureA, $examTopic]), $context);
        $this->assertNotEmpty($tasks, 'Exam prep strategy should generate tasks');
        $this->assertSame(2, (int) $tasks->first()['topic_id'],
            'Exam prep should prioritise exam topic');
    }

    public function test_task_generator_falls_back_to_balanced_for_unknown(): void
    {
        $builder = new PlannerTaskBuilderService();
        $service = new TaskGeneratorService(
            new BalancedPlannerStrategy($builder),
            new RevisionPlannerStrategy($builder),
            new ExamPrepPlannerStrategy($builder),
        );

        $topic = $this->createTopic(1, 'Topic 1', 1, false);
        $context = $this->buildContext([
            'plannerFocus' => 'unknown_mode',
            'profiles' => [
                1 => ['progression_stage' => 'tutor', 'curriculum_unlocked' => true],
            ],
        ]);

        $tasks = $service->buildCandidates(collect([$topic]), $context);
        $this->assertNotEmpty($tasks, 'Unknown mode should fall back to balanced and generate tasks');
    }

    // ────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────

    private function createTopic(int $id, string $name, int $displayOrder, bool $isExam): Topic
    {
        $topic = new Topic();
        $topic->id = $id;
        $topic->name = $name;
        $topic->display_order = $displayOrder;
        $topic->is_locked = false;
        $topic->is_exam = $isExam;
        $topic->setRelation('userProgress', collect());

        return $topic;
    }

    private function buildContext(array $overrides = []): PlannerContext
    {
        $profiles = $overrides['profiles'] ?? [];
        $plannerFocus = $overrides['plannerFocus'] ?? 'balanced';

        return new PlannerContext(
            examDate: null,
            daysToExam: null,
            dailyStudyMinutes: 90,
            totalDailyStudyMinutes: 90,
            plannerFocus: $plannerFocus,
            tasksLimit: 3,
            mixedQuestionCount: 15,
            mixedDurationMinutes: 27,
            carryOverTaskIds: [],
            courseTopicIds: array_keys($profiles) ?: [1, 2, 3],
            openMistakesCount: 0,
            overallAccuracy: 75.0,
            topicSignals: ['profiles' => $profiles],
            attemptSignals: ['topic_attempts_today' => [], 'random_attempts_today' => [], 'course_topic_ids' => array_keys($profiles) ?: [1, 2, 3]],
            dueMistakeSignals: ['total_due' => 0, 'by_topic' => []],
            backlogSignals: ['due_spaced_reviews' => 0, 'reviewable_backlog' => 0, 'by_topic' => []],
            assignmentSignals: [],
            conceptSignals: ['by_topic' => [], 'weakest' => [], 'topics' => []],
            behaviorSignals: [],
            globalAllocation: [],
            coursePriorityScore: 100.0,
            preferredSource: null,
            repairMode: 'easy',
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
