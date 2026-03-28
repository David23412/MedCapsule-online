<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Services\CourseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CourseAccessPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_course_with_zero_price_does_not_require_survey_unlock(): void
    {
        $user = User::factory()->student()->create([
            'survey_completed' => false,
            'free_courses_unlocked' => false,
        ]);

        $course = Course::factory()->premium()->create([
            'price' => 99.99,
            'access_type' => Course::ACCESS_TYPE_PAID,
        ]);

        DB::table('courses')
            ->where('id', $course->id)
            ->update(['price' => 0]);

        $course->refresh();

        $this->assertFalse($user->requiresSurveyUnlockForCourse($course));
    }

    public function test_survey_unlock_backfill_only_enrolls_courses_marked_survey_unlockable(): void
    {
        $user = User::factory()->student()->create();
        $freeCourse = Course::factory()->free()->create();
        $previewCourse = Course::factory()->preview()->create();
        $paidCourse = Course::factory()->premium()->create([
            'price' => 149.99,
            'access_type' => Course::ACCESS_TYPE_PAID,
        ]);

        DB::table('courses')
            ->where('id', $paidCourse->id)
            ->update(['price' => 0]);

        $enrolledCount = $user->enrollInAllFreeCourses();

        $this->assertSame(2, $enrolledCount);
        $this->assertTrue((bool) DB::table('course_user')
            ->where('user_id', $user->id)
            ->where('course_id', $freeCourse->id)
            ->value('is_enrolled'));
        $this->assertSame(User::COURSE_ACCESS_FULL, DB::table('course_user')
            ->where('user_id', $user->id)
            ->where('course_id', $freeCourse->id)
            ->value('access_level'));

        $this->assertTrue((bool) DB::table('course_user')
            ->where('user_id', $user->id)
            ->where('course_id', $previewCourse->id)
            ->value('is_enrolled'));
        $this->assertSame(User::COURSE_ACCESS_PREVIEW, DB::table('course_user')
            ->where('user_id', $user->id)
            ->where('course_id', $previewCourse->id)
            ->value('access_level'));

        $this->assertFalse(DB::table('course_user')
            ->where('user_id', $user->id)
            ->where('course_id', $paidCourse->id)
            ->exists());
    }

    public function test_stored_survey_response_does_not_restore_unlock_when_flags_are_false(): void
    {
        $user = User::factory()->student()->create([
            'survey_completed' => false,
            'free_courses_unlocked' => false,
        ]);

        $course = Course::factory()->free()->create();

        SurveyResponse::create([
            'user_id' => $user->id,
            'survey_key' => (string) config('free_course_survey.survey_key', 'free_course_unlock_v1'),
            'answers' => [],
            'redirect_to' => '/courses',
            'submitted_at' => now(),
        ]);

        $this->assertFalse($user->hasCompletedFreeCourseSurvey());
        $this->assertFalse($user->hasUnlockedFreeCourses());
        $this->assertTrue($user->requiresSurveyUnlockForCourse($course));
    }

    public function test_stale_survey_course_enrollment_does_not_grant_access_when_unlock_flags_are_false(): void
    {
        $user = User::factory()->student()->create([
            'survey_completed' => false,
            'free_courses_unlocked' => false,
        ]);

        $course = Course::factory()->free()->create();

        DB::table('course_user')->insert([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'is_enrolled' => true,
            'access_level' => User::COURSE_ACCESS_FULL,
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNull($user->getCourseAccessLevel($course));
        $this->assertFalse($user->hasAccessToCourse($course->id));
        $this->assertTrue($user->requiresSurveyUnlockForCourse($course));
    }

    public function test_courses_index_recomputes_stale_cached_access_when_survey_flags_change(): void
    {
        $user = User::factory()->student()->create([
            'survey_completed' => false,
            'free_courses_unlocked' => false,
        ]);

        $course = Course::factory()->free()->create();

        Cache::put("user_course_optimization_data_{$user->id}", [
            'access_state_signature' => 'stale-signature',
            'course_access_levels' => [$course->id => User::COURSE_ACCESS_FULL],
            'pending_payment_course_ids' => [],
            'is_admin' => false,
        ], 300);

        $this->actingAs($user);

        $payload = app(CourseService::class)->getCoursesForIndex([]);
        $courseForView = $payload['data']['courses']->firstWhere('id', $course->id);

        $this->assertNotNull($courseForView);
        $this->assertFalse($courseForView->is_enrolled);
        $this->assertTrue($courseForView->requires_survey_unlock);
    }
}
