<?php

namespace Tests\Feature\Controllers;

use App\Models\Course;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TopicPlannerPayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_page_exposes_resolved_planner_settings_in_game_plan_payload(): void
    {
        $user = User::factory()->student()->create([
            'planner_focus' => 'balanced',
            'target_score' => 81,
            'daily_study_minutes' => 90,
            'repair_mode' => 'hard',
            'mistake_mastery_mode' => 'hard',
            'survey_completed' => true,
            'free_courses_unlocked' => true,
            'has_sr_access' => true,
        ]);
        $course = Course::create([
            'name' => 'Planner feature course',
            'description' => 'Feature test course',
            'type' => 'medical',
            'academic_year' => '2026-1',
            'access_type' => 'free',
            'price' => 0,
        ]);
        DB::table('course_user')->insert([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'is_enrolled' => true,
            'access_level' => User::COURSE_ACCESS_FULL,
            'planner_focus' => 'revision',
            'planner_daily_study_minutes' => 40,
            'planner_exam_date' => now()->addDays(7)->toDateString(),
            'planner_repair_mode' => 'spaced',
            'planner_mistake_mastery_mode' => 'easy',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $topic = Topic::create([
            'course_id' => $course->id,
            'name' => 'Hemodynamics',
            'case_type' => 'quiz',
            'display_order' => 1,
            'is_locked' => false,
        ]);
        Question::create([
            'topic_id' => $topic->id,
            'question' => 'What is the correct answer?',
            'question_type' => Question::TYPE_MULTIPLE_CHOICE,
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_answer' => 'A',
            'source' => 'Cairo',
            'concept' => 'Cardiac cycle',
        ]);

        $response = $this->actingAs($user)->get(route('topics.forCourse', $course->id));

        $response->assertOk();
        $response->assertDontSee('name="planner_exam_date"', false);
        $response->assertViewHas('gamePlan', function (array $gamePlan) {
            return data_get($gamePlan, 'settings.resolved_settings.planner_focus') === 'revision'
                && data_get($gamePlan, 'settings.resolved_settings.effective_daily_study_minutes') === 40
                && data_get($gamePlan, 'settings.resolved_settings.repair_mode') === 'spaced'
                && data_get($gamePlan, 'settings.resolved_settings.mistake_mastery_mode') === 'easy'
                && array_key_exists('planned_minutes', $gamePlan)
                && array_key_exists('planned_questions', $gamePlan)
                && array_key_exists('daily_progress', $gamePlan)
                && !array_key_exists('debug', $gamePlan)
                && array_key_exists('tracked_minutes', $gamePlan['daily_progress'])
                && array_key_exists('target_minutes', $gamePlan['daily_progress']);
        });
    }

    public function test_course_page_exposes_planner_debug_payload_only_to_admin_viewers(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'survey_completed' => true,
            'free_courses_unlocked' => true,
        ]);
        $course = Course::create([
            'name' => 'Planner debug course',
            'description' => 'Feature test course',
            'type' => 'medical',
            'academic_year' => '2026-1',
            'price' => 0,
        ]);
        $topic = Topic::create([
            'course_id' => $course->id,
            'name' => 'Hemodynamics',
            'case_type' => 'quiz',
            'display_order' => 1,
            'is_locked' => false,
        ]);
        Question::create([
            'topic_id' => $topic->id,
            'question' => 'What is the correct answer?',
            'question_type' => Question::TYPE_MULTIPLE_CHOICE,
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_answer' => 'A',
            'source' => 'Cairo',
            'concept' => 'Cardiac cycle',
        ]);

        $response = $this->actingAs($admin)->get(route('topics.forCourse', $course->id));

        $response->assertOk();
        $response->assertViewHas('gamePlan', function (array $gamePlan) {
            return array_key_exists('debug', $gamePlan)
                && data_get($gamePlan, 'debug.frontier_topic_id') !== null
                && is_array(data_get($gamePlan, 'debug.tasks'))
                && is_array(data_get($gamePlan, 'debug.topics'));
        });
    }

    public function test_course_exam_date_update_preserves_other_course_planner_overrides(): void
    {
        $this->withoutExceptionHandling(); $user = User::factory()->student()->create();
        $course = Course::create([
            'name' => 'Planner override course',
            'description' => 'Feature test course',
            'type' => 'medical',
            'academic_year' => '2026-1',
            'access_type' => 'free',
            'price' => 0,
        ]);

        DB::table('course_user')->insert([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'is_enrolled' => true,
            'access_level' => User::COURSE_ACCESS_FULL,
            'planner_daily_study_minutes' => 55,
            'planner_focus' => 'revision',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('profile.update-course-planner-settings'), [
            'course_id' => $course->id,
            'planner_exam_date' => now()->addDays(12)->toDateString(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Course planner settings updated successfully!');

        $enrollment = DB::table('course_user')
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        $this->assertSame(55, (int) $enrollment->planner_daily_study_minutes);
        $this->assertSame('revision', $enrollment->planner_focus);
        $this->assertSame(now()->addDays(12)->toDateString(), $enrollment->planner_exam_date);
    }
}
