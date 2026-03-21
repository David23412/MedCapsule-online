<?php

namespace Tests\Unit\Services\Planner;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Mistake;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserMistakeMastery;
use App\Services\Planner\PlannerSignalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PlannerSignalServiceTest extends TestCase
{
    use RefreshDatabase;

    private PlannerSignalService $service;
    private User $user;
    private Course $course;
    private Topic $topic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PlannerSignalService::class);
        $this->user = User::factory()->student()->create();
        $this->course = Course::create([
            'name' => 'Cardiology',
            'description' => 'Planner course',
            'type' => 'medical',
            'academic_year' => '2026-1',
            'price' => 0,
        ]);
        $this->topic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Hemodynamics',
            'case_type' => 'quiz',
            'display_order' => 1,
            'is_locked' => false,
        ]);
    }

    public function test_it_separates_due_active_and_deferred_backlog_signals(): void
    {
        $dueQuestion = $this->createQuestion('Cardiac cycle');
        $activeQuestion = $this->createQuestion('Stroke volume');
        $deferredQuestion = $this->createQuestion('Preload');

        $this->createMistake($dueQuestion, [
            'next_review_at' => now()->subHours(3),
            'last_reviewed_at' => now()->subDays(2),
            'retrievability' => 0.55,
            'stability' => 1.1,
            'skipped' => false,
        ]);
        $this->createMistake($activeQuestion, [
            'next_review_at' => now()->addHours(6),
            'last_reviewed_at' => now()->subDay(),
            'retrievability' => 0.82,
            'stability' => 2.2,
            'skipped' => false,
        ]);
        $this->createMistake($deferredQuestion, [
            'next_review_at' => now()->addHours(2),
            'skip_until' => now()->addHours(4),
            'last_reviewed_at' => now()->subDay(),
            'retrievability' => 0.88,
            'stability' => 1.5,
            'skipped' => true,
        ]);

        $signals = $this->service->buildTopicBacklogSignals($this->user->id, [$this->topic->id]);
        $topicSignal = collect($signals['by_topic'])->firstWhere('topic_id', $this->topic->id);

        $this->assertSame(1, $signals['due_spaced_reviews']);
        $this->assertSame(1, $signals['active_open_backlog']);
        $this->assertSame(1, $signals['deferred_skipped_backlog']);
        $this->assertSame(2, $signals['reviewable_backlog']);
        $this->assertSame(3, $signals['total_backlog']);

        $this->assertSame(1, $topicSignal['due_spaced_reviews']);
        $this->assertSame(1, $topicSignal['active_open_backlog']);
        $this->assertSame(1, $topicSignal['deferred_skipped_backlog']);
        $this->assertSame(2, $topicSignal['reviewable_backlog']);
        $this->assertGreaterThan(0, $topicSignal['minutes_overdue']);
        $this->assertGreaterThan(0.0, $topicSignal['average_risk']);
    }

    public function test_it_summarizes_assignment_urgency_for_course_and_topics(): void
    {
        $overdueAssignment = Assignment::create([
            'course_id' => $this->course->id,
            'topic_id' => $this->topic->id,
            'title' => 'Overdue drill',
            'deadline' => now()->subHours(6),
            'assignment_mode' => Assignment::MODE_COMPLETE_TOPIC,
        ]);
        $urgentAssignment = Assignment::create([
            'course_id' => $this->course->id,
            'topic_id' => $this->topic->id,
            'title' => 'Urgent drill',
            'deadline' => now()->addHours(18),
            'assignment_mode' => Assignment::MODE_COMPLETE_TOPIC,
        ]);

        $overdueAssignment->assignedUsers()->attach($this->user->id);
        $urgentAssignment->assignedUsers()->attach($this->user->id);

        $signals = $this->service->buildCourseAssignmentSignals($this->user->id, [$this->course->id]);
        $courseSignals = $signals[$this->course->id];
        $topicSignals = collect($courseSignals['by_topic'])->firstWhere('topic_id', $this->topic->id);

        $this->assertSame(1, $courseSignals['overdue_assignment_count']);
        $this->assertSame(1, $courseSignals['due_within_48h_count']);
        $this->assertSame(2, $courseSignals['active_assignment_count']);
        $this->assertNotNull($courseSignals['nearest_assignment_deadline']);
        $this->assertNotNull($topicSignals);
        $this->assertSame(1, $topicSignals['overdue_assignment_count']);
        $this->assertSame(1, $topicSignals['due_within_48h_count']);
        $this->assertSame(2, $topicSignals['active_assignment_count']);
    }

    public function test_it_filters_classic_reviewable_ids_through_persisted_mastery(): void
    {
        $resolvedQuestion = $this->createQuestion('Afterload');
        $openQuestion = $this->createQuestion('Preload');

        $resolvedMistake = $this->createMistake($resolvedQuestion, [
            'next_review_at' => now()->addHours(5),
        ]);
        $openMistake = $this->createMistake($openQuestion, [
            'next_review_at' => now()->addHours(7),
        ]);

        UserMistakeMastery::create([
            'user_id' => $this->user->id,
            'question_id' => $resolvedQuestion->id,
            'course_id' => $this->course->id,
            'consecutive_correct' => 2,
            'graduated_at' => now()->subHour(),
            'last_attempt_at' => now()->subHour(),
            'last_attempt_result' => 'correct',
            'total_attempts' => 3,
        ]);

        $signals = $this->service->buildTopicBacklogSignals($this->user->id, [$this->topic->id], 2);
        $topicSignal = collect($signals['by_topic'])->firstWhere('topic_id', $this->topic->id);

        $this->assertSame(2, $topicSignal['reviewable_backlog']);
        $this->assertSame(1, $topicSignal['classic_reviewable_backlog']);
        $this->assertSame([$openMistake->id], $topicSignal['classic_reviewable_mistake_ids']);
        $this->assertNotContains($resolvedMistake->id, $topicSignal['classic_reviewable_mistake_ids']);
    }

    private function createQuestion(?string $concept): Question
    {
        return Question::create([
            'topic_id' => $this->topic->id,
            'question' => 'What is the correct answer?',
            'question_type' => Question::TYPE_MULTIPLE_CHOICE,
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_answer' => 'A',
            'concept' => $concept,
            'source' => 'Cairo',
        ]);
    }

    private function createMistake(Question $question, array $overrides = []): Mistake
    {
        return Mistake::create(array_merge([
            'user_id' => $this->user->id,
            'question_id' => $question->id,
            'submitted_answer' => 'B',
            'question_text' => $question->question,
            'correct_answer' => $question->correct_answer,
            'last_attempt_date' => Carbon::now()->subDay(),
            'last_reviewed_at' => Carbon::now()->subDay(),
            'next_review_at' => Carbon::now()->addHour(),
            'difficulty' => 0.48,
            'difficulty_baseline' => 0.48,
            'stability' => 1.2,
            'retrievability' => 0.72,
            'review_count' => 1,
            'lapse_count' => 1,
            'mastered' => false,
            'skipped' => false,
        ], $overrides));
    }
}
