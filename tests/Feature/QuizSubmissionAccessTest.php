<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use App\Services\AssignmentRetrievalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizSubmissionAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_unenrolled_student_cannot_submit_answers_to_a_paid_course(): void
    {
        $student = User::factory()->student()->create(['xp' => 0]);
        [$course, $topic, $question] = $this->quiz();
        $this->actingAs($student)->postJson(route('quiz.grade', [$course, $topic]), $this->answers($question))
            ->assertForbidden();
        $this->assertNoChanges($student);
    }

    public function test_questions_from_an_unrelated_topic_are_rejected(): void
    {
        $admin = User::factory()->admin()->create(['xp' => 0]);
        [$course, $topic] = $this->quiz();
        [, , $foreign] = $this->quiz();
        $this->actingAs($admin)->postJson(route('quiz.grade', [$course, $topic]), $this->answers($foreign))
            ->assertUnprocessable();
        $this->assertNoChanges($admin);
    }

    public function test_student_cannot_submit_someone_elses_assignment(): void
    {
        $student = User::factory()->student()->create(['xp' => 0]);
        [$course, $topic, $question] = $this->quiz();
        $this->enroll($student, $course);
        $assignment = Assignment::factory()->active()->create(['topic_id' => $topic->id, 'course_id' => $course->id]);
        $this->actingAs($student)->postJson(route('quiz.grade', [$course, $topic]), [
            ...$this->answers($question), 'assignment_id' => $assignment->id,
        ])->assertForbidden();
        $this->assertNoChanges($student);
    }

    public function test_mismatched_assignment_is_rejected_without_creating_an_attempt(): void
    {
        $admin = User::factory()->admin()->create(['xp' => 0]);
        [$course, $topic, $question] = $this->quiz();
        $assignment = Assignment::factory()->create();
        $this->actingAs($admin)->postJson(route('quiz.grade', [$course, $topic]), [
            ...$this->answers($question), 'assignment_id' => $assignment->id,
        ])->assertUnprocessable();
        $this->assertNoChanges($admin);
    }

    public function test_expired_assigned_quiz_is_rejected(): void
    {
        $student = User::factory()->student()->create(['xp' => 0]);
        [$course, $topic, $question] = $this->quiz();
        $this->enroll($student, $course);
        $assignment = Assignment::factory()->overdue()->create(['topic_id' => $topic->id, 'course_id' => $course->id]);
        $assignment->assignedUsers()->attach($student);
        $this->actingAs($student)->postJson(route('quiz.grade', [$course, $topic]), [
            ...$this->answers($question), 'assignment_id' => $assignment->id,
        ])->assertUnprocessable();
        $this->assertNoChanges($student);
    }

    public function test_student_can_submit_with_a_valid_personal_deadline_extension(): void
    {
        $student = User::factory()->student()->create();
        [$course, $topic, $question] = $this->quiz();
        $this->enroll($student, $course);
        $assignment = Assignment::factory()->overdue()->completeTopic()->create([
            'topic_id' => $topic->id, 'course_id' => $course->id,
        ]);
        $assignment->assignedUsers()->attach($student, ['extended_deadline' => now()->addDay()]);

        $this->assertTrue($assignment->isLocked());
        $this->assertTrue($assignment->canUserStart($student));
        $this->assertNull($assignment->getLockReasonForUser($student));
        $this->actingAs($student)->postJson(route('quiz.grade', [$course, $topic]), [
            ...$this->answers($question), 'assignment_id' => $assignment->id,
        ])->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('quiz_attempts', [
            'user_id' => $student->id, 'assignment_id' => $assignment->id, 'quiz_type' => 'assignment',
        ]);
    }

    public function test_expired_personal_extension_still_rejects_submission(): void
    {
        $student = User::factory()->student()->create(['xp' => 0]);
        [$course, $topic, $question] = $this->quiz();
        $this->enroll($student, $course);
        $assignment = Assignment::factory()->overdue()->completeTopic()->create([
            'topic_id' => $topic->id, 'course_id' => $course->id,
        ]);
        $assignment->assignedUsers()->attach($student, ['extended_deadline' => now()->subMinute()]);

        $this->assertFalse($assignment->canUserStart($student));
        $this->assertSame('This assignment is past the deadline.', $assignment->getLockReasonForUser($student));
        $this->assertSame('This assignment is past the deadline.', $assignment->getUserProgressWithStatus($student)['lock_reason']);
        $this->actingAs($student)->postJson(route('quiz.grade', [$course, $topic]), [
            ...$this->answers($question), 'assignment_id' => $assignment->id,
        ])->assertUnprocessable();
        $this->assertNoChanges($student);
    }

    public function test_dashboard_counts_use_each_students_personal_deadline(): void
    {
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();
        $assignment = Assignment::factory()->overdue()->completeTopic()->create();
        $assignment->assignedUsers()->attach($student, ['extended_deadline' => now()->addDay()]);
        $assignment->assignedUsers()->attach($otherStudent);
        $service = app(AssignmentRetrievalService::class);

        $this->assertSame(['active' => 1, 'completed' => 0, 'locked' => 0, 'overdue' => 0],
            $service->getAssignmentStatusCounts($student->id, null));
        $this->assertSame(['active' => 0, 'completed' => 0, 'locked' => 0, 'overdue' => 1],
            $service->getAssignmentStatusCounts($otherStudent->id, null));
    }

    public function test_legitimate_linked_question_can_still_be_graded(): void
    {
        $admin = User::factory()->admin()->create();
        [$course, $topic] = $this->quiz();
        $other = Topic::factory()->forCourse($course)->quiz()->create();
        $linked = Question::factory()->multipleChoice()->forTopic($other)->create([
            'extra_topic' => (string) $topic->id, 'correct_answer' => 'A',
        ]);
        $this->actingAs($admin)->postJson(route('quiz.grade', [$course, $topic]), $this->answers($linked))
            ->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('quiz_attempts', ['user_id' => $admin->id, 'topic_id' => $topic->id]);
    }

    public function test_assigned_student_can_submit_an_active_assignment(): void
    {
        $student = User::factory()->student()->create();
        [$course, $topic, $question] = $this->quiz();
        $this->enroll($student, $course);
        $assignment = Assignment::factory()->active()->completeTopic()->create(['topic_id' => $topic->id, 'course_id' => $course->id]);
        $assignment->assignedUsers()->attach($student);
        $this->actingAs($student)->postJson(route('quiz.grade', [$course, $topic]), [
            ...$this->answers($question), 'assignment_id' => $assignment->id,
        ])->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('quiz_attempts', [
            'user_id' => $student->id, 'assignment_id' => $assignment->id, 'quiz_type' => 'assignment',
        ]);
    }

    public function test_exam_rejects_questions_that_are_only_linked_to_it(): void
    {
        $admin = User::factory()->admin()->create(['xp' => 0]);
        [$course, $topic] = $this->quiz();
        $topic->update(['is_exam' => true]);
        $other = Topic::factory()->forCourse($course)->quiz()->create();
        $question = Question::factory()->multipleChoice()->forTopic($other)->create(['extra_topic' => (string) $topic->id]);
        $this->actingAs($admin)->postJson(route('quiz.grade', [$course, $topic]), $this->answers($question))
            ->assertUnprocessable();
        $this->assertNoChanges($admin);
    }

    public function test_random_quiz_still_accepts_mixed_topics_with_the_legacy_quiz_mode(): void
    {
        $admin = User::factory()->admin()->create();
        [, , $first] = $this->quiz();
        [, , $second] = $this->quiz();
        $this->actingAs($admin)->postJson(route('quiz.grade-random'), [
            'answers' => [$first->id => 'A', $second->id => 'A'], 'time_taken' => 30, 'mode' => 'quiz',
        ])->assertOk();
    }

    private function quiz(): array
    {
        $course = Course::factory()->premium()->create();
        $topic = Topic::factory()->forCourse($course)->quiz()->create();
        $question = Question::factory()->multipleChoice()->forTopic($topic)->create(['correct_answer' => 'A']);
        return [$course, $topic, $question];
    }

    private function answers(Question $question): array
    {
        return ['answers' => [$question->id => 'A'], 'time_taken' => 30];
    }

    private function enroll(User $user, Course $course): void
    {
        $user->courses()->attach($course, ['is_enrolled' => true, 'access_level' => User::COURSE_ACCESS_FULL]);
    }

    private function assertNoChanges(User $user): void
    {
        $this->assertDatabaseCount('quiz_attempts', 0);
        $this->assertDatabaseCount('mistakes', 0);
        $this->assertSame(0, (int) $user->fresh()->xp);
    }
}
