<?php

namespace Tests\Feature\SSOT;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Payment;
use App\Support\State\EnrollmentService;
use App\Observers\EnrollmentObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class EnrollmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private EnrollmentService $enrollmentService;
    private EnrollmentObserver $enrollmentObserver;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->enrollmentService = app(EnrollmentService::class);
        $this->enrollmentObserver = app(EnrollmentObserver::class);
        
        // Enable SSOT sync for testing
        config(['ssot.sync_enabled' => true]);
        config(['ssot.observers.enrollment.enabled' => true]);
    }

    /** @test */
    public function it_determines_enrollment_status_from_completed_payment()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['price' => 100]);
        
        // Create completed payment
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'completed',
            'paid_at' => now()
        ]);
        
        // Create enrollment record
        DB::table('course_user')->insert([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'is_enrolled' => true,
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $status = $this->enrollmentService->getEnrollmentStatus($user->id, $course->id);
        
        $this->assertTrue($status['enrolled']);
        $this->assertEquals('payment', $status['method']);
        $this->assertNotNull($status['enrolled_at']);
        $this->assertEquals($payment->id, $status['payment_id']);
    }

    /** @test */
    public function it_handles_free_course_auto_enrollment()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['price' => 0]);
        
        $status = $this->enrollmentService->getEnrollmentStatus($user->id, $course->id);
        
        $this->assertTrue($status['enrolled']);
        $this->assertEquals('free_auto', $status['method']);
        
        // Verify enrollment record was created
        $enrollment = DB::table('course_user')
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();
            
        $this->assertNotNull($enrollment);
        $this->assertTrue((bool) $enrollment->is_enrolled);
        $this->assertNotNull($enrollment->enrolled_at);
    }

    /** @test */
    public function it_returns_not_enrolled_for_paid_course_without_payment()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['price' => 100]);
        
        $status = $this->enrollmentService->getEnrollmentStatus($user->id, $course->id);
        
        $this->assertFalse($status['enrolled']);
        $this->assertEquals('none', $status['method']);
        $this->assertNull($status['enrolled_at']);
    }

    /** @test */
    public function it_enrolls_user_through_service()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['price' => 100]);
        
        $result = $this->enrollmentService->enrollUser($user->id, $course->id, 'manual');
        
        $this->assertTrue($result);
        
        // Verify enrollment record
        $enrollment = DB::table('course_user')
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();
            
        $this->assertNotNull($enrollment);
        $this->assertTrue((bool) $enrollment->is_enrolled);
        $this->assertNotNull($enrollment->enrolled_at);
        
        // Verify SSOT status
        $status = $this->enrollmentService->getEnrollmentStatus($user->id, $course->id);
        $this->assertTrue($status['enrolled']);
    }

    /** @test */
    public function it_unenrolls_user_through_service()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['price' => 100]);
        
        // First enroll
        $this->enrollmentService->enrollUser($user->id, $course->id);
        
        // Then unenroll
        $result = $this->enrollmentService->unenrollUser($user->id, $course->id);
        
        $this->assertTrue($result);
        
        // Verify enrollment record updated
        $enrollment = DB::table('course_user')
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();
            
        $this->assertNotNull($enrollment);
        $this->assertFalse((bool) $enrollment->is_enrolled);
        
        // Verify SSOT status
        $status = $this->enrollmentService->getEnrollmentStatus($user->id, $course->id);
        $this->assertFalse($status['enrolled']);
    }

    /** @test */
    public function it_processes_payment_enrollment()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['price' => 100]);
        
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'completed',
            'paid_at' => now()
        ]);
        
        $result = $this->enrollmentService->processPaymentEnrollment($payment);
        
        $this->assertTrue($result);
        
        // Verify enrollment was created
        $enrollment = DB::table('course_user')
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();
            
        $this->assertNotNull($enrollment);
        $this->assertTrue((bool) $enrollment->is_enrolled);
    }

    /** @test */
    public function it_gets_user_enrolled_courses()
    {
        $user = User::factory()->create();
        $paidCourse = Course::factory()->create(['price' => 100]);
        $freeCourse = Course::factory()->create(['price' => 0]);
        $notEnrolledCourse = Course::factory()->create(['price' => 200]);
        
        // Create payment for paid course
        Payment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $paidCourse->id,
            'status' => 'completed',
            'paid_at' => now()
        ]);
        
        // Create enrollment for paid course
        DB::table('course_user')->insert([
            'user_id' => $user->id,
            'course_id' => $paidCourse->id,
            'is_enrolled' => true,
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $enrolledCourses = $this->enrollmentService->getUserEnrolledCourses($user->id);
        
        // Should include paid course and free course (auto-enrolled)
        $this->assertCount(2, $enrolledCourses);
        
        $courseIds = collect($enrolledCourses)->pluck('course_id')->toArray();
        $this->assertContains($paidCourse->id, $courseIds);
        $this->assertContains($freeCourse->id, $courseIds);
        $this->assertNotContains($notEnrolledCourse->id, $courseIds);
    }

    /** @test */
    public function observer_detects_legacy_enrollment_conflicts()
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('SSOT Enrollment Conflict Detected', \Mockery::type('array'));
            
        $user = User::factory()->create();
        $course = Course::factory()->create(['price' => 100]);
        
        // Create enrollment record with conflicting state
        DB::table('course_user')->insert([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'is_enrolled' => true,
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Update to create conflict (enrolled but no payment)
        DB::table('course_user')
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->update(['is_enrolled' => false]);
    }

    /** @test */
    public function observer_syncs_ssot_to_legacy_fields()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['price' => 100]);
        
        // Enroll through SSOT service
        $this->enrollmentService->enrollUser($user->id, $course->id);
        
        // Get SSOT status
        $ssotStatus = $this->enrollmentService->getEnrollmentStatus($user->id, $course->id);
        
        // Sync to legacy fields
        $this->enrollmentObserver->syncSSOTToLegacy($user->id, $course->id, $ssotStatus);
        
        // Verify legacy fields match SSOT
        $enrollment = DB::table('course_user')
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();
            
        $this->assertEquals($ssotStatus['enrolled'], (bool) $enrollment->is_enrolled);
        $this->assertNotNull($enrollment->enrolled_at);
    }

    /** @test */
    public function it_verifies_enrollment_consistency()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['price' => 100]);
        
        // Create consistent enrollment
        $this->enrollmentService->enrollUser($user->id, $course->id);
        
        $consistency = $this->enrollmentObserver->verifyConsistency($user->id, $course->id);
        
        $this->assertTrue($consistency['consistent']);
        $this->assertEmpty($consistency['inconsistencies']);
        
        // Create inconsistency
        DB::table('course_user')
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->update(['is_enrolled' => false]);
            
        $consistency = $this->enrollmentObserver->verifyConsistency($user->id, $course->id);
        
        $this->assertFalse($consistency['consistent']);
        $this->assertNotEmpty($consistency['inconsistencies']);
    }

    /** @test */
    public function it_handles_bulk_enrollment_status_check()
    {
        $user = User::factory()->create();
        $course1 = Course::factory()->create(['price' => 0]); // Free
        $course2 = Course::factory()->create(['price' => 100]); // Paid
        $course3 = Course::factory()->create(['price' => 200]); // Paid, not enrolled
        
        // Create payment for course2
        Payment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course2->id,
            'status' => 'completed',
            'paid_at' => now()
        ]);
        
        DB::table('course_user')->insert([
            'user_id' => $user->id,
            'course_id' => $course2->id,
            'is_enrolled' => true,
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $courseIds = [$course1->id, $course2->id, $course3->id];
        $bulkStatus = $this->enrollmentService->getBulkEnrollmentStatus($user->id, $courseIds);
        
        $this->assertCount(3, $bulkStatus);
        $this->assertTrue($bulkStatus[$course1->id]['enrolled']); // Free course
        $this->assertTrue($bulkStatus[$course2->id]['enrolled']); // Paid course
        $this->assertFalse($bulkStatus[$course3->id]['enrolled']); // Not enrolled
    }

    /** @test */
    public function it_clears_enrollment_cache_properly()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['price' => 100]);
        
        // Prime cache
        $this->enrollmentService->getEnrollmentStatus($user->id, $course->id);
        
        // Verify cache exists
        $cacheKey = "enrollment_status_{$user->id}_{$course->id}";
        $this->assertTrue(Cache::has($cacheKey));
        
        // Clear cache
        $this->enrollmentService->clearEnrollmentCache($user->id, $course->id);
        
        // Verify cache cleared
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function it_prevents_infinite_recursion_in_observers()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['price' => 100]);
        
        // This should not cause infinite recursion
        $this->enrollmentService->enrollUser($user->id, $course->id);
        
        // Verify enrollment was created successfully
        $status = $this->enrollmentService->getEnrollmentStatus($user->id, $course->id);
        $this->assertTrue($status['enrolled']);
    }

    /** @test */
    public function it_respects_ssot_sync_enabled_config()
    {
        config(['ssot.sync_enabled' => false]);
        
        $user = User::factory()->create();
        $course = Course::factory()->create(['price' => 100]);
        
        // Create enrollment record directly
        DB::table('course_user')->insert([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'is_enrolled' => true,
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Observer should not sync when disabled
        Log::shouldNotReceive('info');
        
        // Update enrollment record (should not trigger observer)
        DB::table('course_user')
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->update(['updated_at' => now()]);
    }
}