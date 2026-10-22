<?php

namespace Tests\Feature;

use App\Models\{Course, Payment, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualEnrollmentRevenueTest extends TestCase
{
    use RefreshDatabase;

    private function setupPaidEnrollment(): array
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $course = Course::factory()->premium()->create(['price' => 125]);
        $this->actingAs($admin);
        return [$student, $course];
    }

    public function test_existing_manual_paid_enrollments_are_included_without_creating_payment_records(): void
    {
        [$student, $course] = $this->setupPaidEnrollment();
        $course->users()->attach($student, ['is_enrolled' => true, 'access_level' => null]);
        $other = User::factory()->student()->create();
        $course->users()->attach($other, ['is_enrolled' => true, 'access_level' => User::COURSE_ACCESS_FULL]);
        $this->getJson(route('admin.user-course.payments'))->assertOk()
            ->assertJsonPath('stats.total_revenue', 250)->assertJsonPath('stats.manual_enrollment_revenue', 250)
            ->assertJsonPath('stats.manual_enrollment_count', 2)->assertJsonPath('stats.confirmed_payment_revenue', 0)
            ->assertJsonPath('stats.completed_count', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_manual_grant_retry_revoke_and_regrant_recalculate_the_total(): void
    {
        [$student, $course] = $this->setupPaidEnrollment();
        $payload = ['user_id' => $student->id, 'course_id' => $course->id];
        foreach ([1, 2] as $retry) {
            $this->postJson(route('admin.user-course.enroll'), $payload)->assertOk();
            $this->getJson(route('admin.user-course.payments'))->assertOk()->assertJsonPath('stats.total_revenue', 125)
                ->assertJsonPath('stats.manual_enrollment_count', 1);
        }
        $this->postJson(route('admin.user-course.revoke'), $payload + ['is_enrolled' => false])->assertOk();
        $this->getJson(route('admin.user-course.payments'))->assertOk()->assertJsonPath('stats.total_revenue', 0);
        $this->postJson(route('admin.user-course.enroll'), $payload)->assertOk();
        $this->getJson(route('admin.user-course.payments'))->assertOk()->assertJsonPath('stats.total_revenue', 125);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_approval_replaces_manual_value_instead_of_double_counting_it(): void
    {
        [$student, $course] = $this->setupPaidEnrollment();
        $course->users()->attach($student, ['is_enrolled' => true, 'access_level' => User::COURSE_ACCESS_FULL]);
        $payment = Payment::factory()->create(['user_id' => $student->id, 'course_id' => $course->id, 'status' => 'pending', 'amount' => 125]);
        $this->getJson(route('admin.user-course.payments'))->assertOk()->assertJsonPath('stats.total_revenue', 125)
            ->assertJsonPath('stats.manual_enrollment_revenue', 125)->assertJsonPath('stats.pending_count', 1);
        $this->postJson(route('admin.payments.verify', $payment))->assertOk();
        $this->getJson(route('admin.user-course.payments'))->assertOk()->assertJsonPath('stats.total_revenue', 125)
            ->assertJsonPath('stats.manual_enrollment_revenue', 0)->assertJsonPath('stats.confirmed_payment_revenue', 125)
            ->assertJsonPath('stats.completed_count', 1);
        $this->postJson(route('admin.user-course.revoke'), ['user_id' => $student->id, 'course_id' => $course->id, 'is_enrolled' => false])->assertOk();
        $this->getJson(route('admin.user-course.payments'))->assertOk()->assertJsonPath('stats.total_revenue', 125);
    }

    public function test_recorded_payment_amount_wins_over_course_price_and_search_does_not_change_totals(): void
    {
        [$student, $course] = $this->setupPaidEnrollment();
        $course->users()->attach($student, ['is_enrolled' => true, 'access_level' => User::COURSE_ACCESS_FULL]);
        foreach (['completed' => 80.50, 'pending' => 500, 'rejected' => 900] as $status => $amount) {
            Payment::factory()->create(['user_id' => $student->id, 'course_id' => $course->id, 'status' => $status, 'amount' => $amount]);
        }
        $this->getJson(route('admin.user-course.payments', ['search' => 'nonmatching-search']))->assertOk()
            ->assertJsonPath('stats.total_revenue', 80.5)->assertJsonPath('stats.manual_enrollment_revenue', 0)
            ->assertJsonPath('stats.pending_count', 1)->assertJsonCount(0, 'payments');
    }

    public function test_free_preview_revoked_and_staff_access_is_excluded_but_paid_preview_upgrades_count(): void
    {
        [$student, $course] = $this->setupPaidEnrollment();
        foreach ([User::factory()->admin()->create(), User::factory()->tutor()->create()] as $staff) {
            $course->users()->attach($staff, ['is_enrolled' => true, 'access_level' => User::COURSE_ACCESS_FULL]);
        }
        $course->users()->attach($student, ['is_enrolled' => false, 'access_level' => User::COURSE_ACCESS_FULL]);
        $free = Course::factory()->create(['access_type' => Course::ACCESS_TYPE_FREE, 'price' => 0]);
        $free->users()->attach($student, ['is_enrolled' => true, 'access_level' => User::COURSE_ACCESS_FULL]);
        $preview = Course::factory()->create(['access_type' => Course::ACCESS_TYPE_PREVIEW, 'price' => 50]);
        $preview->users()->attach($student, ['is_enrolled' => true, 'access_level' => User::COURSE_ACCESS_PREVIEW]);
        $preview->users()->attach(User::factory()->student()->create(), ['is_enrolled' => true, 'access_level' => null]);
        $this->getJson(route('admin.user-course.payments'))->assertOk()->assertJsonPath('stats.total_revenue', 0);
        $preview->users()->updateExistingPivot($student->id, ['access_level' => User::COURSE_ACCESS_FULL]);
        $this->getJson(route('admin.user-course.payments'))->assertOk()->assertJsonPath('stats.total_revenue', 50)
            ->assertJsonPath('stats.manual_enrollment_count', 1);
    }
}
