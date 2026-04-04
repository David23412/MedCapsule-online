<?php

namespace Tests\Unit\Services\Planner;

use App\Models\Topic;
use App\Services\Planner\TaskCompletionService;
use App\Services\Planner\PlannerContext;
use PHPUnit\Framework\TestCase;
use Carbon\Carbon;
use ReflectionMethod;

/**
 * Comprehensive unit tests for TaskCompletionService.
 *
 * Tests task evaluation, stage transition logic, and attempt matching
 * without requiring a database. Uses constructed PlannerContext data
 * to simulate real planner signals.
 */
class TaskCompletionServiceTest extends TestCase
{
    private TaskCompletionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TaskCompletionService();
    }

    // ────────────────────────────────────────────────────────────
    // evaluateTask() — Tutor (binary completion)
    // ────────────────────────────────────────────────────────────

    public function test_tutor_task_completes_when_matching_attempt_exists(): void
    {
        $task = $this->buildTask('start_topic', 1, ['study_mode' => 'tutor']);
        $context = $this->buildContext([
            'topic_attempts_today' => [
                $this->buildAttempt(1, 'tutor'),
            ],
        ]);

        $result = $this->service->evaluateTask($task, null, $context);

        $this->assertSame('complete', $result['state']);
        $this->assertSame(1.0, $result['progress']);
    }

    public function test_tutor_task_stays_pending_when_no_matching_attempt(): void
    {
        $task = $this->buildTask('start_topic', 1, ['study_mode' => 'tutor']);
        $context = $this->buildContext([
            'topic_attempts_today' => [],
        ]);

        $result = $this->service->evaluateTask($task, null, $context);

        $this->assertSame('pending', $result['state']);
        $this->assertSame(0.0, $result['progress']);
    }

    public function test_tutor_task_ignores_quiz_mode_attempt(): void
    {
        $task = $this->buildTask('start_topic', 1, ['study_mode' => 'tutor']);
        $context = $this->buildContext([
            'topic_attempts_today' => [
                $this->buildAttempt(1, 'quiz'),
            ],
        ]);

        $result = $this->service->evaluateTask($task, null, $context);

        $this->assertSame('pending', $result['state'], 'Wrong mode (quiz instead of tutor) must not complete a tutor task');
    }

    // ────────────────────────────────────────────────────────────
    // evaluateTask() — Rapid Overview (binary, same as tutor)
    // ────────────────────────────────────────────────────────────

    public function test_rapid_overview_completes_with_matching_attempt(): void
    {
        $task = $this->buildTask('rapid_overview', 1, ['study_mode' => 'tutor']);
        $context = $this->buildContext([
            'topic_attempts_today' => [
                $this->buildAttempt(1, 'tutor'),
            ],
        ]);

        $result = $this->service->evaluateTask($task, null, $context);

        $this->assertSame('complete', $result['state']);
    }

    // ────────────────────────────────────────────────────────────
    // evaluateTask() — Frontier Quiz (≥70% on preferred source)
    // ────────────────────────────────────────────────────────────

    public function test_frontier_quiz_completes_when_pass_ready(): void
    {
        $task = $this->buildTask('frontier_topic_quiz', 1, ['study_mode' => 'quiz']);
        $topic = $this->createMockTopic(1);
        $context = $this->buildContext([
            'topic_attempts_today' => [
                $this->buildAttempt(1, 'quiz', 80.0),
            ],
            'profiles' => [
                1 => [
                    'stage_two_pass_ready' => true,
                    'stage_two_question_count' => 5,
                    'stage_two_accuracy' => 80.0,
                    'stage_two_fallback_used' => false,
                ],
            ],
        ]);

        $result = $this->service->evaluateTask($task, $topic, $context);

        $this->assertSame('complete', $result['state']);
        $this->assertSame(1.0, $result['progress']);
    }

    public function test_frontier_quiz_partial_when_below_70_percent(): void
    {
        $task = $this->buildTask('frontier_topic_quiz', 1, ['study_mode' => 'quiz']);
        $topic = $this->createMockTopic(1);
        $context = $this->buildContext([
            'topic_attempts_today' => [
                $this->buildAttempt(1, 'quiz', 50.0),
            ],
            'profiles' => [
                1 => [
                    'stage_two_pass_ready' => false,
                    'stage_two_question_count' => 3,
                    'stage_two_accuracy' => 50.0,
                    'stage_two_fallback_used' => false,
                ],
            ],
        ]);

        $result = $this->service->evaluateTask($task, $topic, $context);

        $this->assertSame('partial', $result['state']);
        $this->assertLessThan(1.0, $result['progress']);
    }

    public function test_frontier_quiz_pending_when_no_attempts(): void
    {
        $task = $this->buildTask('frontier_topic_quiz', 1, ['study_mode' => 'quiz']);
        $topic = $this->createMockTopic(1);
        $context = $this->buildContext([
            'topic_attempts_today' => [],
            'profiles' => [
                1 => [
                    'stage_two_pass_ready' => false,
                    'stage_two_question_count' => 0,
                    'stage_two_accuracy' => 0.0,
                ],
            ],
        ]);

        $result = $this->service->evaluateTask($task, $topic, $context);

        $this->assertSame('pending', $result['state']);
    }

    // ────────────────────────────────────────────────────────────
    // Random quiz exclusion — NEVER completes a planner task
    // ────────────────────────────────────────────────────────────

    public function test_random_quiz_never_completes_tutor_task(): void
    {
        $task = $this->buildTask('start_topic', 1, ['study_mode' => 'tutor']);
        $context = $this->buildContext([
            'topic_attempts_today' => [
                $this->buildAttempt(1, 'tutor', 100.0, 'random_quiz'),
            ],
        ]);

        $result = $this->service->evaluateTask($task, null, $context);

        $this->assertSame('pending', $result['state'], 'Random quiz must NEVER complete a planner task');
    }

    public function test_random_quiz_type_excluded_from_matching(): void
    {
        $task = $this->buildTask('frontier_topic_quiz', 1, ['study_mode' => 'quiz']);
        $topic = $this->createMockTopic(1);
        $context = $this->buildContext([
            'topic_attempts_today' => [
                array_merge($this->buildAttempt(1, 'quiz', 90.0), ['quiz_type' => 'random']),
            ],
            'profiles' => [
                1 => [
                    'stage_two_pass_ready' => true,
                    'stage_two_question_count' => 5,
                    'stage_two_accuracy' => 90.0,
                ],
            ],
        ]);

        $result = $this->service->evaluateTask($task, $topic, $context);

        $this->assertSame('pending', $result['state'], 'Quiz with quiz_type=random must be excluded from completion matching');
    }

    // ────────────────────────────────────────────────────────────
    // Wrong mode — no completion
    // ────────────────────────────────────────────────────────────

    public function test_wrong_mode_never_completes_task(): void
    {
        // Task requires tutor mode, but attempt is quiz mode
        $task = $this->buildTask('start_topic', 1, ['study_mode' => 'tutor']);
        $context = $this->buildContext([
            'topic_attempts_today' => [
                $this->buildAttempt(1, 'quiz', 100.0),
            ],
        ]);

        $result = $this->service->evaluateTask($task, null, $context);

        $this->assertSame('pending', $result['state'], 'Wrong mode must never complete a task');
    }

    public function test_wrong_topic_never_completes_task(): void
    {
        $task = $this->buildTask('start_topic', 1, ['study_mode' => 'tutor']);
        $context = $this->buildContext([
            'topic_attempts_today' => [
                $this->buildAttempt(999, 'tutor'), // Different topic
            ],
        ]);

        $result = $this->service->evaluateTask($task, null, $context);

        $this->assertSame('pending', $result['state'], 'Wrong topic must never complete a task');
    }

    // ────────────────────────────────────────────────────────────
    // Source matching — case-insensitive
    // ────────────────────────────────────────────────────────────

    public function test_source_matching_is_case_insensitive(): void
    {
        $task = $this->buildTask('start_topic', 1, ['study_mode' => 'tutor', 'source' => 'Assuit']);
        $context = $this->buildContext([
            'topic_attempts_today' => [
                array_merge($this->buildAttempt(1, 'tutor'), ['source' => 'assuit']),
            ],
        ]);

        $result = $this->service->evaluateTask($task, null, $context);

        $this->assertSame('complete', $result['state'], 'Source matching must be case-insensitive');
    }

    public function test_null_source_on_task_matches_any_attempt_source(): void
    {
        $task = $this->buildTask('start_topic', 1, ['study_mode' => 'tutor', 'source' => null]);
        $context = $this->buildContext([
            'topic_attempts_today' => [
                array_merge($this->buildAttempt(1, 'tutor'), ['source' => 'Cairo']),
            ],
        ]);

        $result = $this->service->evaluateTask($task, null, $context);

        $this->assertSame('complete', $result['state'], 'Null source on task must match any attempt source');
    }

    // ────────────────────────────────────────────────────────────
    // Classic Repair — mastery graduation tracking
    // ────────────────────────────────────────────────────────────

    public function test_classic_repair_pending_when_no_attempts(): void
    {
        $task = $this->buildTask('repair_topic', 1, [
            'study_mode' => 'quiz',
            'assigned_mistake_ids' => [10, 20, 30],
            'repair_difficulty' => 'easy',
        ]);
        $context = $this->buildContext([
            'topic_attempts_today' => [],
        ]);

        $result = $this->service->evaluateTask($task, null, $context);

        $this->assertSame('pending', $result['state']);
    }

    public function test_classic_repair_pending_when_no_assigned_mistakes(): void
    {
        $task = $this->buildTask('repair_topic', 1, [
            'study_mode' => 'quiz',
            'assigned_mistake_ids' => [],
            'repair_difficulty' => 'easy',
        ]);
        $context = $this->buildContext([
            'topic_attempts_today' => [
                $this->buildAttempt(1, 'quiz'),
            ],
        ]);

        $result = $this->service->evaluateTask($task, null, $context);

        // Malformed task with no assigned mistakes stays pending
        $this->assertSame('pending', $result['state']);
    }

    // ────────────────────────────────────────────────────────────
    // Final Exam Sprint — 60% threshold
    // ────────────────────────────────────────────────────────────

    public function test_final_exam_sprint_frontier_replace_uses_topic_evaluation(): void
    {
        $task = $this->buildTask('final_exam_sprint', 1, [
            'study_mode' => 'quiz',
            'simulation_scope' => 'frontier_replace',
        ]);
        $topic = $this->createMockTopic(1);
        $context = $this->buildContext([
            'topic_attempts_today' => [
                $this->buildAttempt(1, 'quiz', 65.0),
            ],
        ]);

        $result = $this->service->evaluateTask($task, $topic, $context);

        // 65% >= 60% threshold for simulation
        $this->assertSame('complete', $result['state']);
    }

    // ────────────────────────────────────────────────────────────
    // Unknown task type — defaults to pending
    // ────────────────────────────────────────────────────────────

    public function test_unknown_task_type_returns_pending(): void
    {
        $task = $this->buildTask('mixed_quiz_sprint', 1);
        $context = $this->buildContext();

        $result = $this->service->evaluateTask($task, null, $context);

        $this->assertSame('pending', $result['state']);
    }

    // ────────────────────────────────────────────────────────────
    // matchingTopicAttempts — private method via Reflection
    // ────────────────────────────────────────────────────────────

    public function test_matching_excludes_random_quiz_session_type(): void
    {
        $task = ['topic_id' => 1, 'study_mode' => 'quiz', 'source' => null, 'type' => 'frontier_topic_quiz'];
        $attemptSignals = [
            'topic_attempts_today' => [
                $this->buildAttempt(1, 'quiz', 90.0, 'random_quiz'),
                $this->buildAttempt(1, 'quiz', 80.0),
            ],
        ];

        $method = new ReflectionMethod(TaskCompletionService::class, 'matchingTopicAttempts');
        $method->setAccessible(true);
        $matches = $method->invoke($this->service, $task, $attemptSignals);

        $this->assertCount(1, $matches, 'Only non-random attempt should match');
        $this->assertSame(80.0, $matches->first()['percentage_grade']);
    }

    public function test_matching_uses_today_only_window(): void
    {
        $task = ['topic_id' => 1, 'study_mode' => 'quiz', 'source' => null, 'type' => 'frontier_topic_quiz'];
        $attemptSignals = [
            'topic_attempts_today' => [
                $this->buildAttempt(1, 'quiz', 85.0),
            ],
            // random_attempts_today uses 90-day window — must NOT be used for task matching
            'random_attempts_today' => [
                $this->buildAttempt(1, 'quiz', 95.0, 'random_quiz'),
            ],
        ];

        $method = new ReflectionMethod(TaskCompletionService::class, 'matchingTopicAttempts');
        $method->setAccessible(true);
        $matches = $method->invoke($this->service, $task, $attemptSignals);

        $this->assertCount(1, $matches);
        $this->assertSame(85.0, $matches->first()['percentage_grade']);
    }

    // ────────────────────────────────────────────────────────────
    // resolveRequiredStudyMode — mode inference
    // ────────────────────────────────────────────────────────────

    public function test_resolve_mode_uses_configured_study_mode(): void
    {
        $method = new ReflectionMethod(TaskCompletionService::class, 'resolveRequiredStudyMode');
        $method->setAccessible(true);

        $this->assertSame('tutor', $method->invoke($this->service, ['study_mode' => 'tutor', 'type' => 'start_topic']));
        $this->assertSame('quiz', $method->invoke($this->service, ['study_mode' => 'quiz', 'type' => 'frontier_topic_quiz']));
        $this->assertSame('mistake_review', $method->invoke($this->service, ['study_mode' => 'mistake_review', 'type' => 'topic_mistake_review']));
    }

    public function test_resolve_mode_infers_from_type_when_no_study_mode(): void
    {
        $method = new ReflectionMethod(TaskCompletionService::class, 'resolveRequiredStudyMode');
        $method->setAccessible(true);

        $this->assertSame('tutor', $method->invoke($this->service, ['type' => 'start_topic']));
        $this->assertSame('tutor', $method->invoke($this->service, ['type' => 'rapid_overview']));
        $this->assertSame('tutor', $method->invoke($this->service, ['type' => 'concept_repair']));
        $this->assertSame('quiz', $method->invoke($this->service, ['type' => 'frontier_topic_quiz']));
        $this->assertSame('mistake_review', $method->invoke($this->service, ['type' => 'topic_mistake_review']));
        $this->assertSame('mistake_review', $method->invoke($this->service, ['type' => 'mistakes_cleanup']));
    }

    // ────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────

    private function buildTask(string $type, int $topicId, array $overrides = []): array
    {
        return array_merge([
            'id' => $type . '_' . $topicId,
            'type' => $type,
            'topic_id' => $topicId,
            'study_mode' => null,
            'source' => null,
            'assigned_mistake_ids' => [],
            'repair_difficulty' => null,
            'simulation_scope' => null,
            'completed' => false,
            'counts_toward_progression' => true,
            'is_preview' => false,
        ], $overrides);
    }

    private function buildAttempt(int $topicId, string $studyMode, float $percentageGrade = 0.0, ?string $sessionType = null): array
    {
        return [
            'topic_id' => $topicId,
            'study_mode' => $studyMode,
            'session_type' => $sessionType,
            'quiz_type' => null,
            'source' => null,
            'score' => (int) round($percentageGrade),
            'total_questions' => 100,
            'duration_seconds' => 600,
            'percentage_grade' => $percentageGrade,
            'created_at' => now()->toIso8601String(),
        ];
    }

    private function buildContext(array $overrides = []): PlannerContext
    {
        $profiles = $overrides['profiles'] ?? [];
        $attemptSignals = [
            'topic_attempts_today' => $overrides['topic_attempts_today'] ?? [],
            'random_attempts_today' => $overrides['random_attempts_today'] ?? [],
            'mistake_reviews_today' => 0,
            'mistake_reviews_today_in_course' => 0,
            'mistake_reviews_today_by_topic' => [],
            'course_topic_ids' => [1, 2, 3],
        ];

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
            courseTopicIds: [1, 2, 3],
            openMistakesCount: 0,
            overallAccuracy: 75.0,
            topicSignals: ['profiles' => $profiles],
            attemptSignals: $attemptSignals,
            dueMistakeSignals: ['total_due' => 0, 'by_topic' => []],
            backlogSignals: ['due_spaced_reviews' => 0, 'reviewable_backlog' => 0, 'by_topic' => []],
            assignmentSignals: [],
            conceptSignals: ['by_topic' => [], 'weakest' => []],
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

    private function createMockTopic(int $id): Topic
    {
        $topic = new Topic();
        $topic->id = $id;
        $topic->name = 'Topic ' . $id;
        $topic->display_order = $id;
        $topic->is_locked = false;

        return $topic;
    }

    private function createMockCourse(): object
    {
        return new class {
            public int $id = 1;
        };
    }
}
