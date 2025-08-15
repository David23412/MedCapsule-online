<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class SplitBrainConsolidationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function enrollment_ssot_uses_enrolled_at_timestamp()
    {
        // Create test data
        $user = User::factory()->create();
        $course = Course::factory()->create();

        // Test enrollment using SSOT pattern
        $user->enrollInCourse($course->id);

        // Verify enrollment is detected via enrolled_at timestamp
        $this->assertTrue($user->isEnrolledInCourse($course->id));

        // Verify the enrollment record has both fields during transition
        $enrollment = DB::table('course_user')
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        $this->assertNotNull($enrollment->enrolled_at, 'enrolled_at should be set (SSOT)');
        $this->assertTrue((bool)$enrollment->is_enrolled, 'is_enrolled should be true during transition');
    }

    /** @test */
    public function enrollment_ssot_ignores_is_enrolled_when_enrolled_at_is_null()
    {
        // Create test data
        $user = User::factory()->create();
        $course = Course::factory()->create();

        // Create inconsistent state: is_enrolled=true but enrolled_at=null
        DB::table('course_user')->insert([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'is_enrolled' => true,
            'enrolled_at' => null, // SSOT says not enrolled
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // SSOT should return false because enrolled_at is null
        $this->assertFalse($user->isEnrolledInCourse($course->id));
    }

    /** @test */
    public function payment_status_ssot_uses_paid_at_timestamp()
    {
        // Create test data
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'pending',
            'paid_at' => null
        ]);

        // Initially should be pending
        $this->assertFalse($payment->isCompleted());
        $this->assertTrue($payment->isPending());
        $this->assertEquals('pending', $payment->computed_status);

        // Mark as paid using SSOT pattern
        $payment->markAsPaid();

        // Verify completion is detected via paid_at timestamp
        $this->assertTrue($payment->isCompleted());
        $this->assertFalse($payment->isPending());
        $this->assertEquals('completed', $payment->computed_status);
        $this->assertNotNull($payment->paid_at);
    }

    /** @test */
    public function payment_status_ssot_ignores_status_when_paid_at_is_null()
    {
        // Create inconsistent state: status='completed' but paid_at=null
        $payment = Payment::factory()->create([
            'status' => 'completed',
            'paid_at' => null // SSOT says not completed
        ]);

        // SSOT should return false because paid_at is null
        $this->assertFalse($payment->isCompleted());
        $this->assertEquals('pending', $payment->computed_status);
    }

    /** @test */
    public function payment_expires_after_24_hours_without_completion()
    {
        // Create old payment without completion
        $payment = Payment::factory()->create([
            'status' => 'pending',
            'paid_at' => null,
            'created_at' => now()->subHours(25) // 25 hours ago
        ]);

        // Should be expired based on SSOT logic
        $this->assertTrue($payment->isExpired());
        $this->assertEquals('expired', $payment->computed_status);
    }

    /** @test */
    public function enrollment_courses_relationship_uses_ssot()
    {
        // Create test data
        $user = User::factory()->create();
        $course1 = Course::factory()->create();
        $course2 = Course::factory()->create();

        // Enroll in course1 properly
        $user->enrollInCourse($course1->id);

        // Create inconsistent enrollment for course2: is_enrolled=true but enrolled_at=null
        DB::table('course_user')->insert([
            'user_id' => $user->id,
            'course_id' => $course2->id,
            'is_enrolled' => true,
            'enrolled_at' => null, // SSOT says not enrolled
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // courses() relationship should only return course1 (where enrolled_at is not null)
        $enrolledCourses = $user->courses;
        $this->assertCount(1, $enrolledCourses);
        $this->assertEquals($course1->id, $enrolledCourses->first()->id);
    }

    /** @test */
    public function payment_scopes_use_ssot()
    {
        // Create test payments
        $completedPayment = Payment::factory()->create([
            'status' => 'completed',
            'paid_at' => now()->subHour()
        ]);

        $pendingPayment = Payment::factory()->create([
            'status' => 'pending',
            'paid_at' => null,
            'created_at' => now()->subHour()
        ]);

        $expiredPayment = Payment::factory()->create([
            'status' => 'pending',
            'paid_at' => null,
            'created_at' => now()->subHours(25)
        ]);

        // Test scopes use SSOT (paid_at timestamp)
        $this->assertCount(1, Payment::completed()->get());
        $this->assertCount(1, Payment::pending()->get());
        $this->assertCount(1, Payment::expired()->get());

        // Verify specific payments
        $this->assertTrue(Payment::completed()->get()->contains($completedPayment));
        $this->assertTrue(Payment::pending()->get()->contains($pendingPayment));
        $this->assertTrue(Payment::expired()->get()->contains($expiredPayment));
    }
}