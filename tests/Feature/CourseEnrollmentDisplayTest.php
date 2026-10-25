<?php

namespace Tests\Feature;

use App\Models\{Course, User};
use App\Services\CourseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseEnrollmentDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_exact_display_total_without_creating_enrollments(): void
    {
        $course = Course::factory()->free()->create();
        $this->actingAs(User::factory()->admin()->create())
            ->postJson(route('admin.courses.fake-enrolled.update', $course), [
                'fake_enrolled_enabled' => true, 'fake_enrolled_count' => 200,
            ])->assertOk();
        $this->assertTrue($course->fresh()->fake_enrolled_enabled);
        $this->assertSame(200, $course->fresh()->fake_enrolled_count);
        $this->get(route('courses.index'))->assertOk()->assertSee('200 students');
        $cards = app(CourseService::class)->getCoursesForIndex([])['data']['courses'];
        $this->assertSame(0, $cards->firstWhere('id', $course->id)->enrolled_users_count);
        $this->assertDatabaseCount('course_user', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_bulk_save_refreshes_guest_cards_and_turning_off_restores_real_total(): void
    {
        $course = Course::factory()->free()->create();
        $student = User::factory()->student()->create(['show_on_leaderboard' => true]);
        $student->courses()->attach($course, ['is_enrolled' => true, 'enrolled_at' => now()]);
        $this->get(route('courses.index'))->assertOk(); // Warm the public metadata cache.
        $this->actingAs(User::factory()->admin()->create())
            ->postJson(route('admin.courses.fake-enrolled.bulk'), ['courses' => [[
                'id' => $course->id, 'fake_enrolled_enabled' => true, 'fake_enrolled_count' => 200,
            ]]])->assertOk();
        auth()->forgetGuards();
        $this->get(route('courses.index'))->assertOk()->assertSee('200 students');
        $this->actingAs(User::factory()->admin()->create())
            ->postJson(route('admin.courses.fake-enrolled.update', $course), [
                'fake_enrolled_enabled' => false, 'fake_enrolled_count' => 200,
            ])->assertOk();
        auth()->forgetGuards();
        $this->get(route('courses.index'))->assertOk()->assertDontSee('200 students');
        $card = app(CourseService::class)->getCoursesForIndex([])['data']['courses']->firstWhere('id', $course->id);
        $this->assertSame(1, $card->displayed_enrollment_count);
        $this->assertCount(1, $card->enrolled_preview);
        $this->assertDatabaseCount('course_user', 1);
    }

    public function test_zero_and_single_student_totals_do_not_show_extra_avatars_or_negative_counts(): void
    {
        $course = Course::factory()->free()->create(['fake_enrolled_enabled' => true, 'fake_enrolled_count' => 0]);
        User::factory()->student()->create(['show_on_leaderboard' => true])->courses()
            ->attach($course, ['is_enrolled' => true, 'enrolled_at' => now()]);
        $this->get(route('courses.index'))->assertOk()->assertSee('0 students');
        $this->assertTrue(app(CourseService::class)->getCoursesForIndex([])['data']['courses']->first()->enrolled_preview->isEmpty());
        $course->update(['fake_enrolled_count' => 1]);
        $this->get(route('courses.index'))->assertOk()->assertSee('1 student</span>', false);
    }

    public function test_only_admins_can_change_counts_and_invalid_totals_are_rejected_atomically(): void
    {
        $course = Course::factory()->free()->create();
        $url = route('admin.courses.fake-enrolled.update', $course);
        $this->actingAs(User::factory()->student()->create())->postJson($url, [
            'fake_enrolled_enabled' => true, 'fake_enrolled_count' => 200,
        ])->assertForbidden();
        $this->actingAs(User::factory()->admin()->create());
        foreach ([-1, 10000, 1.5, 'bad'] as $count) {
            $this->postJson($url, ['fake_enrolled_enabled' => true, 'fake_enrolled_count' => $count])
                ->assertUnprocessable();
        }
        $other = Course::factory()->free()->create();
        $this->postJson(route('admin.courses.fake-enrolled.bulk'), ['courses' => [
            ['id' => $course->id, 'fake_enrolled_enabled' => true, 'fake_enrolled_count' => 200],
            ['id' => $other->id, 'fake_enrolled_enabled' => true, 'fake_enrolled_count' => -1],
        ]])->assertUnprocessable();
        $this->assertFalse($course->fresh()->fake_enrolled_enabled);
    }
}
