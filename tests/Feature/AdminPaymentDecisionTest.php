<?php

namespace Tests\Feature;

use App\Models\{Course, Payment, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminPaymentDecisionTest extends TestCase
{
    use RefreshDatabase;

    private function payment(): Payment
    {
        $this->actingAs(User::factory()->admin()->create());
        return Payment::factory()->create([
            'user_id' => User::factory()->student()->create()->id,
            'course_id' => Course::factory()->premium()->create()->id, 'status' => 'pending',
        ]);
    }

    public function test_approval_is_atomic_and_repeated_approval_does_not_regrant_revoked_access(): void
    {
        $payment = $this->payment();
        $url = route('admin.payments.verify', $payment);
        $this->postJson($url)->assertOk()->assertJsonPath('success', true);
        $this->assertSame('completed', $payment->fresh()->status);
        $this->assertTrue($payment->user->hasFullAccessToCourse($payment->course));
        DB::table('course_user')->where('user_id', $payment->user_id)->update(['is_enrolled' => false]);
        $this->postJson($url)->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('course_user', ['user_id' => $payment->user_id, 'is_enrolled' => false]);
        $this->postJson(route('admin.payments.reject', $payment))->assertConflict();
        $this->assertSame('completed', $payment->fresh()->status);
    }

    public function test_failed_payment_update_rolls_back_enrollment(): void
    {
        $payment = $this->payment();
        // Tests use an isolated in-memory SQLite database.
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        DB::statement("CREATE TRIGGER fail_payment_update BEFORE UPDATE ON payments BEGIN SELECT RAISE(ABORT, 'Synthetic payment write failure'); END");
        try {
            $this->postJson(route('admin.payments.verify', $payment))->assertStatus(500)->assertJsonPath('success', false);
            $this->assertSame('pending', $payment->fresh()->status);
            $this->assertDatabaseMissing('course_user', ['user_id' => $payment->user_id, 'course_id' => $payment->course_id]);
        } finally {
            DB::statement('DROP TRIGGER fail_payment_update');
        }
    }

    public function test_rejection_is_repeatable_and_conflicting_approval_is_rejected(): void
    {
        $payment = $this->payment();
        $url = route('admin.payments.reject', $payment);
        $this->postJson($url)->assertOk()->assertJsonPath('success', true);
        $this->postJson($url)->assertOk()->assertJsonPath('success', true);
        $this->postJson(route('admin.payments.verify', $payment))->assertConflict();
        $this->assertSame('rejected', $payment->fresh()->status);
        $this->assertDatabaseMissing('course_user', ['user_id' => $payment->user_id, 'course_id' => $payment->course_id]);
    }

    public function test_revenue_counts_completed_payments_only(): void
    {
        $payment = $this->payment();
        $payment->update(['amount' => 100]);
        Payment::factory()->create(['user_id' => $payment->user_id, 'course_id' => $payment->course_id, 'status' => 'completed', 'amount' => 200]);
        Payment::factory()->create(['user_id' => $payment->user_id, 'course_id' => $payment->course_id, 'status' => 'rejected', 'amount' => 300]);
        $this->getJson(route('admin.user-course.payments'))->assertOk()
            ->assertJsonPath('stats.total_revenue', 200)
            ->assertJsonPath('stats.pending_count', 1)->assertJsonPath('stats.completed_count', 1)
            ->assertJsonPath('stats.rejected_count', 1);
    }

    public function test_students_cannot_approve_or_reject_payments(): void
    {
        $payment = $this->payment();
        $this->actingAs(User::factory()->student()->create());
        $this->postJson(route('admin.payments.verify', $payment))->assertForbidden();
        $this->postJson(route('admin.payments.reject', $payment))->assertForbidden();
        $this->assertSame('pending', $payment->fresh()->status);
    }
}
