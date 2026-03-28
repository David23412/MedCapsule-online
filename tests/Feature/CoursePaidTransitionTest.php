<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CoursePaidTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_enrollment_is_revoked_when_free_course_becomes_paid(): void
    {
        $student = User::factory()->student()->create();
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create([
            'price' => 0,
            'access_type' => 'free',
        ]);

        DB::table('course_user')->insert([
            [
                'user_id' => $student->id,
                'course_id' => $course->id,
                'is_enrolled' => true,
                'access_level' => User::COURSE_ACCESS_FULL,
                'enrolled_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $admin->id,
                'course_id' => $course->id,
                'is_enrolled' => true,
                'access_level' => User::COURSE_ACCESS_FULL,
                'enrolled_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $course->update([
            'price' => 99.99,
            'access_type' => Course::ACCESS_TYPE_PAID,
        ]);

        $this->assertFalse((bool) DB::table('course_user')
            ->where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->value('is_enrolled'));

        $this->assertTrue((bool) DB::table('course_user')
            ->where('user_id', $admin->id)
            ->where('course_id', $course->id)
            ->value('is_enrolled'));
    }
}
