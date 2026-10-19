<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentProgressAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_student_sees_only_their_own_progress(): void
    {
        $student = User::factory()->student()->create();
        $other = User::factory()->student()->create(['email' => 'private-peer@example.test']);
        $assignment = Assignment::factory()->active()->completeTopic()->create();
        $assignment->assignedUsers()->attach([$student->id, $other->id]);
        QuizAttempt::factory()->create([
            'user_id' => $student->id, 'assignment_id' => $assignment->id, 'topic_id' => $assignment->topic_id,
            'score' => 3, 'total_questions' => 4, 'percentage_grade' => 75, 'quiz_type' => 'assignment',
        ]);

        QuizAttempt::factory()->create([
            'user_id' => $other->id, 'assignment_id' => $assignment->id, 'topic_id' => $assignment->topic_id,
            'score' => 0, 'total_questions' => 4, 'percentage_grade' => 0, 'quiz_type' => 'assignment',
        ]);

        $this->actingAs($student)->getJson(route('assignment.progress', $assignment))
            ->assertOk()->assertJsonPath('success', true)->assertJsonPath('progress', 75)
            ->assertJsonMissingPath('students')->assertJsonMissingPath('stats')
            ->assertDontSee($other->email);
    }

    public function test_unassigned_student_is_denied(): void
    {
        $student = User::factory()->student()->create();
        $assignment = Assignment::factory()->create();
        $this->actingAs($student)->getJson(route('assignment.progress', $assignment))->assertForbidden();
    }

    public function test_unrelated_tutor_is_denied(): void
    {
        $tutor = User::factory()->tutor()->create();
        $assignment = Assignment::factory()->create();
        $this->actingAs($tutor)->getJson(route('assignment.progress', $assignment))->assertForbidden();
    }

    public function test_course_tutor_can_view_class_progress(): void
    {
        $tutor = User::factory()->tutor()->create();
        $course = Course::factory()->create();
        $tutor->courses()->attach($course);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);
        $student = User::factory()->student()->create();
        $assignment->assignedUsers()->attach($student);

        $this->actingAs($tutor)->getJson(route('assignment.progress', $assignment))
            ->assertOk()->assertJsonPath('students.0.id', $student->id)->assertJsonPath('stats.total', 1);
    }

    public function test_admin_can_view_class_progress_without_course_membership(): void
    {
        $admin = User::factory()->admin()->create();
        $assignment = Assignment::factory()->create();
        $student = User::factory()->student()->create();
        $assignment->assignedUsers()->attach($student);
        $this->actingAs($admin)->getJson(route('assignment.progress', $assignment))
            ->assertOk()->assertJsonPath('students.0.id', $student->id);
    }
    public function test_guest_cannot_read_assignment_progress(): void
    {
        $assignment = Assignment::factory()->create();
        $this->getJson(route('assignment.progress', $assignment))->assertUnauthorized();
    }

}
