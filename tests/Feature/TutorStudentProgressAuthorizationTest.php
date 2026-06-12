<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TutorStudentProgressAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tutor_progress_endpoints_reject_students_outside_tutor_courses(): void
    {
        $tutor = User::factory()->tutor()->create();
        $student = User::factory()->student()->create();
        $course = Course::factory()->create();
        $topic = Topic::factory()->forCourse($course)->create();

        $student->courses()->attach($course->id, [
            'is_enrolled' => true,
            'access_level' => User::COURSE_ACCESS_FULL,
            'enrolled_at' => now(),
        ]);

        DB::table('quiz_attempts')->insert([
            'user_id' => $student->id,
            'topic_id' => $topic->id,
            'quiz_type' => 'normal',
            'study_mode' => 'quiz',
            'score' => 8,
            'total_questions' => 10,
            'percentage_grade' => 80,
            'duration_seconds' => 120,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($tutor)
            ->getJson(route('tutor.student.progress', $student->id))
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->actingAs($tutor)
            ->getJson(route('tutor.student.updates', $student->id))
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_tutor_progress_endpoints_allow_students_in_tutor_courses(): void
    {
        $tutor = User::factory()->tutor()->create();
        $student = User::factory()->student()->create();
        $course = Course::factory()->create();
        $topic = Topic::factory()->forCourse($course)->create();

        $tutor->courses()->attach($course->id, [
            'is_enrolled' => true,
            'access_level' => User::COURSE_ACCESS_FULL,
            'enrolled_at' => now(),
        ]);
        $student->courses()->attach($course->id, [
            'is_enrolled' => true,
            'access_level' => User::COURSE_ACCESS_FULL,
            'enrolled_at' => now(),
        ]);

        DB::table('quiz_attempts')->insert([
            'user_id' => $student->id,
            'topic_id' => $topic->id,
            'quiz_type' => 'normal',
            'study_mode' => 'quiz',
            'score' => 9,
            'total_questions' => 10,
            'percentage_grade' => 90,
            'duration_seconds' => 180,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($tutor)
            ->getJson(route('tutor.student.progress', $student->id))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($tutor)
            ->getJson(route('tutor.student.updates', $student->id))
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
