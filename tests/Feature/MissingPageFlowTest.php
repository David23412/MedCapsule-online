<?php

namespace Tests\Feature;

use App\Models\{Assignment, Course, QuizAttempt, Topic, User};
use App\Services\UniversalNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissingPageFlowTest extends TestCase
{
    use RefreshDatabase;

    private function assignment(): array
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $course = Course::factory()->premium()->create();
        $topic = Topic::factory()->forCourse($course)->create();
        $assignment = Assignment::factory()->completeTopic()->create(['title' => 'Sample assignment', 'course_id' => $course->id, 'topic_id' => $topic->id, 'deadline' => now()->addDay()]);
        $assignment->assignedUsers()->attach($student, ['extended_deadline' => now()->addDays(2)]);
        $student->courses()->attach($course, ['is_enrolled' => true, 'access_level' => User::COURSE_ACCESS_FULL]);
        return [$admin, $student, $course, $topic, $assignment];
    }

    public function test_course_aliases_use_existing_forms_and_topic_overview(): void
    {
        [$admin, $student, $course] = $this->assignment();
        $this->actingAs($admin)->get(route('courses.create'))->assertOk()->assertSee('Add New Course');
        $this->get(route('admin.courses.create'))->assertRedirect(route('add-course.form'));
        $this->get(route('admin.courses.show', $course))->assertRedirect(route('topics.forCourse', $course));
        $this->get(route('admin.courses.index'))->assertOk();
        $this->actingAs($student)->getJson(route('courses.create'))->assertForbidden();
    }

    public function test_assignment_management_pages_and_modal_render(): void
    {
        [$admin, , , , $assignment] = $this->assignment();
        $this->actingAs($admin)->get(route('assignments.show', $assignment))->assertOk()->assertSee('Assigned Students');
        $this->get(route('assignments.edit', $assignment))->assertOk()->assertSee('Individual Deadline Extensions');
        $this->get(route('assignments.edit', $assignment), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertSee('editAssignmentForm');
    }

    public function test_tutor_assignment_pages_render_existing_and_available_students(): void
    {
        [$admin, $student] = $this->assignment();
        $tutor = User::factory()->tutor()->create();
        $tutor->assignedStudents()->attach($student, ['assigned_at' => now()]);
        $available = User::factory()->student()->create();
        $this->actingAs($admin)->get(route('admin.tutors.index'))->assertOk();
        $this->get(route('admin.tutors.assign-students', $tutor))->assertOk()->assertSee($available->email);
        $this->get(route('admin.tutors.bulk-operations'))->assertOk()->assertSee($tutor->email)->assertSee($student->email);
    }

    public function test_course_locking_page_renders_and_rejects_students(): void
    {
        [$admin, $student, $course, $topic] = $this->assignment();
        $this->actingAs($admin)->get(route('topic-locking.course', $course))->assertOk()->assertSee($topic->name);
        $this->actingAs($student)->getJson(route('topic-locking.course', $course))->assertForbidden();
    }

    public function test_student_pages_show_real_assignments_personal_deadlines_and_start_the_quiz(): void
    {
        [, $student, $course, $topic, $assignment] = $this->assignment();
        $this->actingAs($student)->get(route('student.assignments.index'))->assertOk()->assertSee($assignment->title);
        $this->get(route('student.assignments.show', $assignment))->assertOk()
            ->assertSee($assignment->getDeadlineForUser($student->id)->format('M j, Y H:i'));
        $this->post(route('student.assignments.start', $assignment))->assertRedirect(route('quiz.start', [
            'course' => $course->id, 'topic' => $topic->id, 'assignment_id' => $assignment->id, 'sources' => ['all'],
        ]));
        $this->getJson(route('student.assignments.countdown', $assignment))->assertOk()
            ->assertJsonPath('deadline', $assignment->getDeadlineForUser($student->id)->toISOString());
        $this->get(route('student.assignments.history'))->assertOk()->assertSee('No assignment attempts yet.');
    }

    public function test_completion_cannot_be_faked_and_history_and_stats_use_saved_attempts(): void
    {
        [, $student, $course, $topic, $assignment] = $this->assignment();
        $this->actingAs($student)->postJson(route('student.assignments.complete', $assignment), ['score' => 100])->assertUnprocessable();
        $this->assertDatabaseCount('quiz_attempts', 0);
        QuizAttempt::factory()->create(['user_id' => $student->id, 'topic_id' => $topic->id,
            'assignment_id' => $assignment->id, 'quiz_type' => 'assignment', 'score' => 4, 'total_questions' => 5, 'percentage_grade' => 80]);
        $this->postJson(route('student.assignments.complete', $assignment))->assertOk()->assertJsonPath('success', true);
        $this->get(route('student.assignments.history'))->assertOk()->assertSee($assignment->title)->assertSee('Score: 80%');
        $this->getJson(route('student.assignments.stats'))->assertOk()->assertJsonPath('stats.completed', 1);
        $this->getJson(route('student.assignments.widget.progress'))->assertOk()->assertJsonPath('progress', 100);
        $this->get(route('student.assignments.index'))->assertOk();
    }

    public function test_zero_scores_remain_visible_and_do_not_count_as_passed(): void
    {
        [, $student, , $topic, $assignment] = $this->assignment();
        QuizAttempt::factory()->create(['user_id' => $student->id, 'topic_id' => $topic->id, 'assignment_id' => $assignment->id,
            'quiz_type' => 'assignment', 'score' => 0, 'total_questions' => 5, 'percentage_grade' => 0]);
        $this->actingAs($student)->get(route('student.assignments.history'))->assertOk()->assertSee('Score: 0%');
        $this->getJson(route('student.assignments.stats'))->assertOk()->assertJsonPath('stats.completed', 0);
        $this->postJson(route('student.assignments.complete', $assignment))->assertOk()
            ->assertJsonPath('success', true)->assertJsonPath('progress.completed', false);
        $this->assertDatabaseCount('quiz_attempts', 1);
    }

    public function test_other_students_cannot_access_or_mutate_assignments(): void
    {
        [, , , , $assignment] = $this->assignment();
        $other = User::factory()->student()->create();
        $this->actingAs($other);
        foreach (['show', 'countdown'] as $route) $this->getJson(route('student.assignments.'.$route, $assignment))->assertNotFound();
        foreach (['start', 'complete', 'request-extension'] as $route) $this->postJson(route('student.assignments.'.$route, $assignment))->assertNotFound();
        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    public function test_extension_request_reaches_admin_review_without_changing_deadline(): void
    {
        [$admin, $student, , , $assignment] = $this->assignment();
        $original = $assignment->deadline->toDateTimeString();
        $url = route('student.assignments.request-extension', $assignment);
        $this->actingAs($student)->postJson($url, ['reason' => 'Sample request', 'requested_deadline' => now()->addDays(5)->toDateTimeString()])
            ->assertOk()->assertJsonPath('success', true);
        $notifications = UniversalNotificationService::getNotifications($admin);
        $this->assertSame($assignment->id, $notifications->first()['data']['assignment_id']);
        $this->assertSame($student->id, $notifications->first()['data']['student_id']);
        $this->assertSame($original, $assignment->fresh()->deadline->toDateTimeString());
        $this->postJson($url, ['reason' => ['invalid'], 'requested_deadline' => 'yesterday'])->assertUnprocessable();
    }
}
