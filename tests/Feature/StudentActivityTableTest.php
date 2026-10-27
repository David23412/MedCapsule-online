<?php

namespace Tests\Feature;

use App\Models\{Course, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentActivityTableTest extends TestCase
{
    use RefreshDatabase;

    private function activity(Course $course)
    {
        return $this->getJson(route('admin.last-active-users', ['course_id' => $course->id]));
    }

    public function test_online_requires_a_recent_authenticated_session_not_just_last_activity(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->premium()->create();
        $loggedOut = User::factory()->student()->create(['last_active_at' => now()]);
        $online = User::factory()->student()->create(['last_active_at' => now()->subMinute()]);
        $stale = User::factory()->student()->create(['last_active_at' => now()]);
        foreach ([$online->id => now()->subMinute()->timestamp, $stale->id => now()->subMinutes(6)->timestamp] as $id => $time) {
            DB::table('sessions')->insert(['id' => 'activity-'.$id, 'user_id' => $id, 'payload' => '', 'last_activity' => $time]);
        }
        $rows = $this->actingAs($admin)->activity($course)->assertOk()->json('users');
        $rows = collect($rows)->keyBy('id');
        $this->assertSame('online', $rows[$online->id]['status']);
        $this->assertSame('offline', $rows[$loggedOut->id]['status']);
        $this->assertSame('offline', $rows[$stale->id]['status']);
        $this->assertArrayNotHasKey('level', $rows[$online->id]);
        DB::table('sessions')->where('user_id', $online->id)->delete();
        $rows = collect($this->activity($course)->assertOk()->json('users'))->keyBy('id');
        $this->assertSame('offline', $rows[$online->id]['status']);
    }

    public function test_enrollment_is_specific_to_selected_course_and_distinguishes_preview_and_revoked_access(): void
    {
        $course = Course::factory()->premium()->create();
        $other = Course::factory()->premium()->create();
        $full = User::factory()->student()->create();
        $preview = User::factory()->student()->create();
        $revoked = User::factory()->student()->create();
        $full->courses()->attach($course, ['is_enrolled' => true, 'access_level' => 'full']);
        $preview->courses()->attach($course, ['is_enrolled' => true, 'access_level' => 'preview']);
        $revoked->courses()->attach($course, ['is_enrolled' => false, 'access_level' => 'full']);
        $this->actingAs(User::factory()->admin()->create());
        $rows = collect($this->activity($course)->assertOk()->json('users'))->keyBy('id');
        $this->assertTrue($rows[$full->id]['is_enrolled']);
        $this->assertFalse($rows[$preview->id]['is_enrolled']);
        $this->assertSame('Preview only', $rows[$preview->id]['enrollment_label']);
        $this->assertFalse($rows[$revoked->id]['is_enrolled']);
        $rows = collect($this->activity($other)->assertOk()->json('users'))->keyBy('id');
        $this->assertFalse($rows[$full->id]['is_enrolled']);
    }

    public function test_quick_enroll_sets_explicit_state_and_updates_activity_without_duplicate_enrollments(): void
    {
        $course = Course::factory()->premium()->create();
        $student = User::factory()->student()->create();
        $this->actingAs(User::factory()->admin()->create());
        foreach ([true, true, false, false] as $enabled) {
            $this->postJson(route('admin.user-course.revoke'), [
                'user_id' => $student->id, 'course_id' => $course->id, 'is_enrolled' => $enabled,
            ])->assertOk()->assertJsonPath('is_enrolled', $enabled);
            $rows = collect($this->activity($course)->assertOk()->json('users'))->keyBy('id');
            $this->assertSame($enabled, $rows[$student->id]['is_enrolled']);
        }
        $this->assertDatabaseCount('course_user', 1);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_activity_is_admin_only_and_rejects_invalid_filters(): void
    {
        $course = Course::factory()->premium()->create();
        $this->actingAs(User::factory()->student()->create())->activity($course)->assertForbidden();
        $this->actingAs(User::factory()->admin()->create());
        foreach ([['course_id' => 9999], ['search' => ['bad']], ['page' => 'bad']] as $params) {
            $this->getJson(route('admin.last-active-users', $params))->assertUnprocessable();
        }
    }

    public function test_manage_table_replaces_level_with_course_enrollment(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.user-course', ['tab' => 'access']))
            ->assertOk()->assertSee('<th>Enrolled</th>', false)->assertDontSee('<th>Level</th>', false)
            ->assertSee('id="lastActiveCourse"', false);
    }
}
