<?php

namespace Tests\Unit\Listeners;

use App\Events\QuizCompleted;
use App\Listeners\SyncPlannerAfterQuiz;
use App\Services\StudyGamePlanService;
use PHPUnit\Framework\TestCase;
use Mockery;

/**
 * Tests for SyncPlannerAfterQuiz event listener.
 *
 * Critical rules:
 * - Random quiz (study_mode=random OR session_type=random_quiz) NEVER triggers sync
 * - Preview frontier_topic_quiz tasks MUST trigger sync (no early return for previews)
 * - Missing course gracefully returns without error
 */
class SyncPlannerAfterQuizTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_random_quiz_does_not_trigger_sync(): void
    {
        $mockService = Mockery::mock(StudyGamePlanService::class);
        $mockService->shouldNotReceive('syncAfterSession');

        $listener = new SyncPlannerAfterQuiz($mockService);

        $event = $this->createQuizCompletedEvent([
            'study_mode' => 'random',
            'session_type' => 'random_quiz',
        ]);

        $listener->handle($event);

        // If syncAfterSession was called, Mockery would fail
        $this->assertTrue(true, 'Random quiz should NOT trigger syncAfterSession');
    }

    public function test_random_session_type_does_not_trigger_sync(): void
    {
        $mockService = Mockery::mock(StudyGamePlanService::class);
        $mockService->shouldNotReceive('syncAfterSession');

        $listener = new SyncPlannerAfterQuiz($mockService);

        $event = $this->createQuizCompletedEvent([
            'study_mode' => 'quiz',
            'session_type' => 'random_quiz',
        ]);

        $listener->handle($event);

        $this->assertTrue(true, 'session_type=random_quiz should NOT trigger syncAfterSession');
    }

    public function test_preview_task_does_trigger_sync(): void
    {
        $mockService = Mockery::mock(StudyGamePlanService::class);
        $mockService->shouldReceive('syncAfterSession')->once();

        $mockCourse = Mockery::mock(\App\Models\Course::class)->makePartial();

        $relation = Mockery::mock(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
        $relation->shouldReceive('first')->andReturn($mockCourse);

        $listener = new SyncPlannerAfterQuiz($mockService);

        $event = $this->createQuizCompletedEvent([
            'study_mode' => 'quiz',
            'session_type' => null,
            'planner_task_id' => 'preview_frontier_topic_quiz_1',
        ], $mockCourse);

        $listener->handle($event);

        // syncAfterSession should be called for preview frontier quiz tasks
        $this->assertTrue(true, 'Preview frontier_topic_quiz MUST trigger syncAfterSession for stage transition evaluation');
    }

    public function test_normal_quiz_triggers_sync(): void
    {
        $mockService = Mockery::mock(StudyGamePlanService::class);
        $mockService->shouldReceive('syncAfterSession')->once();

        $mockCourse = Mockery::mock(\App\Models\Course::class)->makePartial();

        $listener = new SyncPlannerAfterQuiz($mockService);

        $event = $this->createQuizCompletedEvent([
            'study_mode' => 'quiz',
            'session_type' => null,
        ], $mockCourse);

        $listener->handle($event);

        $this->assertTrue(true, 'Normal quiz should trigger syncAfterSession');
    }

    public function test_tutor_session_triggers_sync(): void
    {
        $mockService = Mockery::mock(StudyGamePlanService::class);
        $mockService->shouldReceive('syncAfterSession')->once();

        $mockCourse = Mockery::mock(\App\Models\Course::class)->makePartial();

        $listener = new SyncPlannerAfterQuiz($mockService);

        $event = $this->createQuizCompletedEvent([
            'study_mode' => 'tutor',
            'session_type' => null,
        ], $mockCourse);

        $listener->handle($event);

        $this->assertTrue(true, 'Tutor session should trigger syncAfterSession');
    }

    public function test_missing_course_gracefully_returns(): void
    {
        $mockService = Mockery::mock(StudyGamePlanService::class);
        $mockService->shouldNotReceive('syncAfterSession');

        $listener = new SyncPlannerAfterQuiz($mockService);

        $event = $this->createQuizCompletedEvent([
            'study_mode' => 'quiz',
            'session_type' => null,
        ], null); // No course

        $listener->handle($event);

        $this->assertTrue(true, 'Missing course should return gracefully without error');
    }

    // ────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────

    private function createQuizCompletedEvent(array $attemptOverrides, $course = null): QuizCompleted
    {
        $quizAttempt = Mockery::mock(\App\Models\QuizAttempt::class)->makePartial();
        $quizAttempt->study_mode = $attemptOverrides['study_mode'] ?? '';
        $quizAttempt->session_type = $attemptOverrides['session_type'] ?? '';
        $quizAttempt->planner_task_id = $attemptOverrides['planner_task_id'] ?? null;

        $user = Mockery::mock(\App\Models\User::class)->makePartial();
        $user->id = 1;

        $topicCourseRelation = Mockery::mock(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
        if ($course !== null) {
            $topicCourseRelation->shouldReceive('first')->andReturn($course);
        } else {
            $topicCourseRelation->shouldReceive('first')->andReturn(null);
        }

        $topic = Mockery::mock(\App\Models\Topic::class)->makePartial();
        $topic->id = 1;
        $topic->shouldReceive('course')->andReturn($topicCourseRelation);

        $event = new QuizCompleted($user, $topic, $quizAttempt, []);

        return $event;
    }
}
