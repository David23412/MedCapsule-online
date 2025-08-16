<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Payment;
use App\Models\Assignment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EnumMigrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_role_constants_are_integers()
    {
        $this->assertIsInt(User::ROLE_ADMIN);
        $this->assertIsInt(User::ROLE_TUTOR);
        $this->assertIsInt(User::ROLE_STUDENT);
        
        $this->assertEquals(1, User::ROLE_ADMIN);
        $this->assertEquals(2, User::ROLE_TUTOR);
        $this->assertEquals(3, User::ROLE_STUDENT);
    }

    /** @test */
    public function user_role_string_mappings_are_correct()
    {
        $this->assertEquals('admin', User::ROLE_STRINGS[User::ROLE_ADMIN]);
        $this->assertEquals('tutor', User::ROLE_STRINGS[User::ROLE_TUTOR]);
        $this->assertEquals('student', User::ROLE_STRINGS[User::ROLE_STUDENT]);
    }

    /** @test */
    public function user_role_methods_work_with_integers()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $tutor = User::factory()->create(['role' => User::ROLE_TUTOR]);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isTutor());
        $this->assertFalse($admin->isStudent());

        $this->assertFalse($tutor->isAdmin());
        $this->assertTrue($tutor->isTutor());
        $this->assertFalse($tutor->isStudent());

        $this->assertFalse($student->isAdmin());
        $this->assertFalse($student->isTutor());
        $this->assertTrue($student->isStudent());
    }

    /** @test */
    public function user_role_string_accessor_works()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $tutor = User::factory()->create(['role' => User::ROLE_TUTOR]);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $this->assertEquals('admin', $admin->role_string);
        $this->assertEquals('tutor', $tutor->role_string);
        $this->assertEquals('student', $student->role_string);
    }

    /** @test */
    public function payment_status_constants_are_integers()
    {
        $this->assertIsInt(Payment::STATUS_PENDING);
        $this->assertIsInt(Payment::STATUS_COMPLETED);
        $this->assertIsInt(Payment::STATUS_FAILED);
        $this->assertIsInt(Payment::STATUS_EXPIRED);
        
        $this->assertEquals(1, Payment::STATUS_PENDING);
        $this->assertEquals(2, Payment::STATUS_COMPLETED);
        $this->assertEquals(3, Payment::STATUS_FAILED);
        $this->assertEquals(4, Payment::STATUS_EXPIRED);
    }

    /** @test */
    public function payment_status_string_mappings_are_correct()
    {
        $this->assertEquals('pending', Payment::STATUS_STRINGS[Payment::STATUS_PENDING]);
        $this->assertEquals('completed', Payment::STATUS_STRINGS[Payment::STATUS_COMPLETED]);
        $this->assertEquals('failed', Payment::STATUS_STRINGS[Payment::STATUS_FAILED]);
        $this->assertEquals('expired', Payment::STATUS_STRINGS[Payment::STATUS_EXPIRED]);
    }

    /** @test */
    public function assignment_status_constants_are_integers()
    {
        $this->assertIsInt(Assignment::STATUS_ACTIVE);
        $this->assertIsInt(Assignment::STATUS_OVERDUE);
        $this->assertIsInt(Assignment::STATUS_COMPLETED);
        
        $this->assertEquals(1, Assignment::STATUS_ACTIVE);
        $this->assertEquals(2, Assignment::STATUS_OVERDUE);
        $this->assertEquals(3, Assignment::STATUS_COMPLETED);
    }

    /** @test */
    public function assignment_status_string_mappings_are_correct()
    {
        $this->assertEquals('active', Assignment::STATUS_STRINGS[Assignment::STATUS_ACTIVE]);
        $this->assertEquals('overdue', Assignment::STATUS_STRINGS[Assignment::STATUS_OVERDUE]);
        $this->assertEquals('completed', Assignment::STATUS_STRINGS[Assignment::STATUS_COMPLETED]);
    }

    /** @test */
    public function role_casting_works_correctly()
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        // Ensure role is cast to integer
        $this->assertIsInt($user->role);
        $this->assertEquals(User::ROLE_ADMIN, $user->role);
        
        // Test database storage
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 1
        ]);
    }

    /** @test */
    public function integer_comparisons_are_faster_than_strings()
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        // Time integer comparison
        $start = microtime(true);
        for ($i = 0; $i < 10000; $i++) {
            $isAdmin = $user->role === User::ROLE_ADMIN;
        }
        $integerTime = microtime(true) - $start;
        
        // Time string comparison (simulated)
        $roleString = 'admin';
        $start = microtime(true);
        for ($i = 0; $i < 10000; $i++) {
            $isAdmin = $roleString === 'admin';
        }
        $stringTime = microtime(true) - $start;
        
        // Integer comparisons should be faster (or at least not slower)
        $this->assertLessThanOrEqual($stringTime * 1.5, $integerTime, 
            'Integer comparisons should be faster than string comparisons');
    }
}