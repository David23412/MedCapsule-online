<?php

/**
 * Split-Brain Fix Verification Script
 * Tests SSOT behavior without requiring phpunit
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Course;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class SplitBrainFixTester
{
    private $passed = 0;
    private $failed = 0;
    private $errors = [];

    public function runTests(): void
    {
        echo "🧪 SPLIT-BRAIN FIX VERIFICATION TESTS\n";
        echo "=====================================\n\n";

        $this->testEnrollmentSSoT();
        $this->testPaymentStatusSSoT();
        $this->testInconsistentDataHandling();
        
        $this->printSummary();
    }

    private function testEnrollmentSSoT(): void
    {
        echo "🔍 Testing Enrollment SSOT...\n";

        try {
            // Create test user and course with unique identifiers
            $timestamp = time() + 2;
            $user = User::create([
                'name' => "Test User {$timestamp}",
                'email' => "test{$timestamp}@example.com",
                'password' => bcrypt('password'),
                'role' => 'student'
            ]);

            $course = Course::create([
                'name' => "Test Course {$timestamp}",
                'description' => 'Test Description',
                'type' => 'medical',
                'price' => 100
            ]);

            // Test enrollment
            $user->enrollInCourse($course->id);
            
            // Verify enrollment is detected
            if ($user->isEnrolledInCourse($course->id)) {
                $this->pass("✅ Enrollment detected via SSOT");
            } else {
                $this->fail("❌ Enrollment not detected via SSOT");
            }

            // Verify enrollment record has SSOT field
            $enrollment = DB::table('course_user')
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();

            if ($enrollment && $enrollment->enrolled_at) {
                $this->pass("✅ Enrollment record uses SSOT (enrolled_at only)");
            } else {
                $this->fail("❌ Enrollment record missing SSOT field");
            }

            // Test courses relationship uses SSOT
            $enrolledCourses = $user->courses;
            if ($enrolledCourses->count() === 1 && $enrolledCourses->first()->id === $course->id) {
                $this->pass("✅ Courses relationship uses SSOT (enrolled_at)");
            } else {
                $this->fail("❌ Courses relationship not using SSOT");
            }

            // Cleanup
            $user->delete();
            $course->delete();

        } catch (\Exception $e) {
            $this->fail("❌ Enrollment SSOT test failed: " . $e->getMessage());
        }
    }

    private function testPaymentStatusSSoT(): void
    {
        echo "\n🔍 Testing Payment Status SSOT...\n";

        try {
            // Create test data with unique identifiers
            $timestamp = time();
            $user = User::create([
                'name' => "Test User {$timestamp}",
                'email' => "test{$timestamp}@example.com",
                'password' => bcrypt('password'),
                'role' => 'student'
            ]);

            $course = Course::create([
                'name' => "Test Course {$timestamp}",
                'description' => 'Test Description',
                'type' => 'medical',
                'price' => 200
            ]);

            $payment = Payment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'reference_number' => "TEST{$timestamp}",
                'amount' => 200,
                'payment_method' => 'test',
                'paid_at' => null
            ]);

            // Test initial state
            if (!$payment->isCompleted() && $payment->isPending()) {
                $this->pass("✅ Payment initially pending via SSOT");
            } else {
                $this->fail("❌ Payment initial state incorrect");
            }

            // Test status accessor
            if ($payment->status === 'pending') {
                $this->pass("✅ Status accessor works correctly");
            } else {
                $this->fail("❌ Status accessor incorrect: " . $payment->status);
            }

            // Test marking as paid
            $payment->markAsPaid();
            
            if ($payment->isCompleted() && !$payment->isPending()) {
                $this->pass("✅ Payment completion detected via SSOT");
            } else {
                $this->fail("❌ Payment completion not detected via SSOT");
            }

            if ($payment->status === 'completed') {
                $this->pass("✅ Status accessor updated correctly");
            } else {
                $this->fail("❌ Status accessor not updated: " . $payment->status);
            }

            // Cleanup
            $payment->delete();
            $user->delete();
            $course->delete();

        } catch (\Exception $e) {
            $this->fail("❌ Payment Status SSOT test failed: " . $e->getMessage());
        }
    }

    private function testInconsistentDataHandling(): void
    {
        echo "\n🔍 Testing Inconsistent Data Handling...\n";

        try {
            // Create test user and course with unique identifiers
            $timestamp = time() + 1;
            $user = User::create([
                'name' => "Test User {$timestamp}",
                'email' => "test{$timestamp}@example.com",
                'password' => bcrypt('password'),
                'role' => 'student'
            ]);

            $course = Course::create([
                'name' => "Test Course {$timestamp}",
                'description' => 'Test Description',
                'type' => 'medical',
                'price' => 300
            ]);

            // Create enrollment with null enrolled_at (not enrolled)
            DB::table('course_user')->insert([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'enrolled_at' => null, // SSOT says not enrolled
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // SSOT should return false because enrolled_at is null
            if (!$user->isEnrolledInCourse($course->id)) {
                $this->pass("✅ SSOT correctly uses enrolled_at timestamp");
            } else {
                $this->fail("❌ SSOT not working - should check enrolled_at timestamp");
            }

            // Test payment inconsistency
            $payment = Payment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'reference_number' => "TEST{$timestamp}B",
                'amount' => 300,
                'payment_method' => 'test',
                'paid_at' => null // No timestamp = not completed
            ]);

            // SSOT should return false because paid_at is null
            if (!$payment->isCompleted()) {
                $this->pass("✅ SSOT correctly uses paid_at timestamp");
            } else {
                $this->fail("❌ SSOT not working - should check paid_at timestamp");
            }

            if ($payment->status === 'pending') {
                $this->pass("✅ Status accessor correctly derives from timestamps");
            } else {
                $this->fail("❌ Status accessor not working: " . $payment->status);
            }

            // Cleanup
            $payment->delete();
            DB::table('course_user')->where('user_id', $user->id)->delete();
            $user->delete();
            $course->delete();

        } catch (\Exception $e) {
            $this->fail("❌ Inconsistent data handling test failed: " . $e->getMessage());
        }
    }

    private function pass(string $message): void
    {
        echo "  {$message}\n";
        $this->passed++;
    }

    private function fail(string $message): void
    {
        echo "  {$message}\n";
        $this->failed++;
        $this->errors[] = $message;
    }

    private function printSummary(): void
    {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "📊 TEST SUMMARY\n";
        echo str_repeat("=", 50) . "\n";
        echo "✅ PASSED: {$this->passed}\n";
        echo "❌ FAILED: {$this->failed}\n";

        if ($this->failed > 0) {
            echo "\n🚨 FAILURES:\n";
            foreach ($this->errors as $error) {
                echo "  - {$error}\n";
            }
            echo "\n❌ RESULT: Tests failed - SSOT implementation needs fixes\n";
            exit(1);
        } else {
            echo "\n✅ RESULT: All tests passed - SSOT implementation working correctly\n";
            echo "🎉 Ready for staging deployment\n";
            exit(0);
        }
    }
}

// Run the tests
$tester = new SplitBrainFixTester();
$tester->runTests();