<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManageAccessFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrollment_status_distinguishes_full_preview_and_revoked_access(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $full = Course::factory()->create(['name' => 'Full course']);
        $preview = Course::factory()->create(['name' => 'Preview course']);
        $revoked = Course::factory()->create(['name' => 'Revoked course']);
        $student->courses()->attach($full, ['is_enrolled' => true, 'access_level' => null]);
        $student->courses()->attach($preview, ['is_enrolled' => true, 'access_level' => User::COURSE_ACCESS_PREVIEW]);
        $student->courses()->attach($revoked, ['is_enrolled' => false, 'access_level' => User::COURSE_ACCESS_FULL]);

        $this->actingAs($admin)->getJson(route('admin.user-course.user-enrollments', ['user_id' => $student->id]))
            ->assertOk()->assertJsonPath('success', true)
            ->assertJsonFragment(['id' => $full->id, 'is_enrolled' => true, 'access_level' => 'full'])
            ->assertJsonFragment(['id' => $preview->id, 'is_enrolled' => true, 'access_level' => 'preview'])
            ->assertJsonFragment(['id' => $revoked->id, 'is_enrolled' => false, 'access_level' => null]);
    }

    public function test_saved_access_status_updates_after_grant_and_revoke(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $course = Course::factory()->premium()->create();
        $payload = ['user_id' => $student->id, 'course_id' => $course->id];
        $url = route('admin.user-course.user-enrollments', ['user_id' => $student->id]);
        $this->actingAs($admin)->postJson(route('admin.user-course.enroll'), $payload)->assertOk();
        $this->getJson($url)->assertJsonFragment(['id' => $course->id, 'is_enrolled' => true, 'access_level' => 'full']);
        $this->postJson(route('admin.user-course.revoke'), $payload)->assertOk();
        $this->getJson($url)->assertJsonFragment(['id' => $course->id, 'is_enrolled' => false, 'access_level' => null]);
    }

    public function test_mentorship_link_opens_its_panel(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.user-course', ['tab' => 'mentorship']))
            ->assertOk()
            ->assertSee('id="mentorship"', false)
            ->assertSee('tab-pane fade show active" id="mentorship"', false);
    }

    public function test_students_cannot_read_other_users_enrollments(): void
    {
        $student = User::factory()->student()->create();
        $this->actingAs($student)->getJson(route('admin.user-course.user-enrollments', ['user_id' => $student->id]))
            ->assertForbidden();
    }
}
