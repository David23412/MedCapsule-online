<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class PaymentSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $course;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);
        
        $this->course = Course::factory()->create([
            'name' => 'Test Course',
            'price' => 100,
        ]);
    }

    /** @test */
    public function it_prevents_unauthorized_payment_verification()
    {
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'amount' => 100,
            'status' => 'pending',
        ]);

        // Try to verify payment without admin privileges
        $response = $this->actingAs($this->user)
            ->post("/admin/payments/{$payment->id}/verify");

        $response->assertStatus(403);
        
        // Ensure payment status wasn't changed
        $payment->refresh();
        $this->assertEquals('pending', $payment->status);
    }

    /** @test */
    public function it_validates_payment_amount_correctly()
    {
        $this->actingAs($this->user);

        // Test payment with incorrect amount
        $response = $this->post("/payments/{$this->course->id}/process-manual", [
            'amount' => 50, // Wrong amount (should be 100)
            'reference_code' => 'TEST123',
            'payment_method' => 'vodafone_cash',
        ]);

        $response->assertSessionHasErrors(['amount']);
    }

    /** @test */
    public function it_prevents_duplicate_payment_processing()
    {
        // Create existing payment
        Payment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'reference_code' => 'DUPLICATE123',
            'status' => 'verified',
        ]);

        $this->actingAs($this->user);

        // Try to create another payment with same reference
        $response = $this->post("/payments/{$this->course->id}/process-manual", [
            'amount' => 100,
            'reference_code' => 'DUPLICATE123',
            'payment_method' => 'vodafone_cash',
        ]);

        $response->assertSessionHasErrors(['reference_code']);
    }

    /** @test */
    public function it_handles_sql_injection_attempts_in_payment_reference()
    {
        $this->actingAs($this->user);

        $maliciousReference = "'; DROP TABLE payments; --";

        $response = $this->post("/payments/{$this->course->id}/process-manual", [
            'amount' => 100,
            'reference_code' => $maliciousReference,
            'payment_method' => 'vodafone_cash',
        ]);

        // Should reject malicious input
        $response->assertSessionHasErrors(['reference_code']);
        
        // Verify payments table still exists
        $this->assertDatabaseTableExists('payments');
    }

    /** @test */
    public function it_rate_limits_payment_attempts()
    {
        $this->actingAs($this->user);

        // Make multiple rapid payment attempts
        for ($i = 0; $i < 10; $i++) {
            $response = $this->post("/payments/{$this->course->id}/process-manual", [
                'amount' => 100,
                'reference_code' => "TEST{$i}",
                'payment_method' => 'vodafone_cash',
            ]);
        }

        // Should be rate limited
        $response->assertStatus(429);
    }

    /** @test */
    public function it_validates_payment_proof_upload_security()
    {
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->user);

        // Test malicious file upload
        $maliciousFile = \Illuminate\Http\UploadedFile::fake()
            ->create('malicious.php', 1024, 'application/x-php');

        $response = $this->post("/payments/{payment->id}/submit-proof", [
            'payment_proof' => $maliciousFile,
        ]);

        $response->assertSessionHasErrors(['payment_proof']);
    }

    /** @test */
    public function it_verifies_course_access_after_successful_payment()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'amount' => 100,
            'status' => 'pending',
        ]);

        // Admin verifies payment
        $response = $this->actingAs($admin)
            ->post("/admin/payments/{$payment->id}/verify");

        $response->assertRedirect();
        
        // Verify user now has access to course
        $this->assertTrue($this->user->hasAccessToCourse($this->course->id));
        
        $payment->refresh();
        $this->assertEquals('verified', $payment->status);
    }

    /** @test */
    public function it_handles_payment_fraud_detection()
    {
        $this->actingAs($this->user);

        // Simulate suspicious payment pattern (same amount, different references, rapid succession)
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->post("/payments/{$this->course->id}/process-manual", [
                'amount' => 100,
                'reference_code' => "SUSPICIOUS{$i}",
                'payment_method' => 'vodafone_cash',
            ]);
        }

        // Should detect fraud pattern and prevent processing
        $lastResponse = end($responses);
        $this->assertTrue(
            $lastResponse->status() === 429 || // Rate limited
            $lastResponse->status() === 422    // Validation failed
        );
    }

    /** @test */
    public function it_prevents_payment_tampering_via_direct_database_access()
    {
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'amount' => 100,
            'status' => 'pending',
        ]);

        // Ensure payment observers are working
        $payment->update(['status' => 'verified']);
        
        // Verify course access was granted
        $this->assertTrue($this->user->hasAccessToCourse($this->course->id));
    }

    /** @test */
    public function it_validates_bundle_payment_security()
    {
        $courses = Course::factory()->count(3)->create(['price' => 50]);
        
        $this->actingAs($this->user);

        // Test bundle payment with tampered course list
        $response = $this->post('/payments/bundle/process-manual', [
            'courses' => $courses->pluck('id')->toArray(),
            'amount' => 100, // Should be 150 (3 * 50)
            'reference_code' => 'BUNDLE123',
            'payment_method' => 'vodafone_cash',
        ]);

        $response->assertSessionHasErrors(['amount']);
    }

    /** @test */
    public function it_handles_concurrent_payment_processing()
    {
        $this->actingAs($this->user);

        // Simulate concurrent payment attempts
        $response1 = $this->post("/payments/{$this->course->id}/process-manual", [
            'amount' => 100,
            'reference_code' => 'CONCURRENT123',
            'payment_method' => 'vodafone_cash',
        ]);

        $response2 = $this->post("/payments/{$this->course->id}/process-manual", [
            'amount' => 100,
            'reference_code' => 'CONCURRENT123',
            'payment_method' => 'vodafone_cash',
        ]);

        // Only one should succeed
        $this->assertTrue(
            ($response1->isSuccessful() && !$response2->isSuccessful()) ||
            (!$response1->isSuccessful() && $response2->isSuccessful())
        );
    }

    /** @test */
    public function it_logs_payment_security_events()
    {
        $this->actingAs($this->user);

        // Trigger a security event
        $response = $this->post("/payments/{$this->course->id}/process-manual", [
            'amount' => -100, // Invalid amount
            'reference_code' => 'INVALID123',
            'payment_method' => 'vodafone_cash',
        ]);

        $response->assertSessionHasErrors();
        
        // Verify security event was logged
        $this->assertDatabaseHas('payment_logs', [
            'user_id' => $this->user->id,
            'action' => 'payment_attempt_failed',
        ]);
    }
}