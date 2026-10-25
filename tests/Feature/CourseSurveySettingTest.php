<?php

namespace Tests\Feature;

use App\Http\Controllers\TopicAccessController;
use App\Models\{AdminSetting, Course, Topic, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseSurveySettingTest extends TestCase
{
    use RefreshDatabase;

    private function setSurveyRequired(bool $required): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->postJson(route('admin.settings.update'), [
                'key' => 'free_course_survey_required', 'value' => $required, 'type' => 'boolean',
            ])->assertOk()->assertJsonPath('success', true);
    }

    private function enroll(User $student, Course $course)
    {
        return $this->actingAs($student)->postJson(route('courses.enroll', $course), [
            'course_id' => $course->id,
        ], ['X-Requested-With' => 'XMLHttpRequest']);
    }

    public function test_survey_is_required_by_default_and_only_admins_can_change_it(): void
    {
        $student = User::factory()->student()->create();
        $course = Course::factory()->free()->create();
        $this->assertTrue(AdminSetting::requiresFreeCourseSurvey());
        $this->enroll($student, $course)->assertStatus(423);
        $this->postJson(route('admin.settings.update'), [
            'key' => 'free_course_survey_required', 'value' => false, 'type' => 'boolean',
        ])->assertForbidden();
        $this->assertTrue(AdminSetting::requiresFreeCourseSurvey());
        $this->get(route('topics.forCourse', $course))->assertRedirect();
    }

    public function test_disabling_survey_allows_free_enrollment_without_faking_survey_completion(): void
    {
        $student = User::factory()->student()->create();
        $course = Course::factory()->free()->create();
        $this->setSurveyRequired(false);
        $this->enroll($student, $course)->assertOk()->assertJsonPath('success', true);
        $this->assertSame(User::COURSE_ACCESS_FULL, $student->fresh()->getCourseAccessLevel($course));
        $this->get(route('topics.forCourse', $course))->assertOk();
        $this->get(route('courses.index', ['survey' => 1]))->assertOk()
            ->assertDontSee('id="freeCourseSurveyModal"', false)
            ->assertDontSee('class="action-btn enroll free-course-survey-trigger"', false);
        $this->assertFalse($student->fresh()->hasCompletedFreeCourseSurvey());
        $this->assertFalse((bool) $student->fresh()->free_courses_unlocked);
        $this->assertDatabaseCount('survey_responses', 0);
        $this->assertDatabaseCount('survey_drafts', 0);
    }

    public function test_disabling_survey_preserves_preview_limits_and_paid_access(): void
    {
        $student = User::factory()->student()->create();
        $preview = Course::factory()->preview()->create();
        $openTopic = Topic::factory()->forCourse($preview)->create(['is_preview_enabled' => true]);
        $lockedTopic = Topic::factory()->forCourse($preview)->create(['is_preview_enabled' => false]);
        $paid = Course::factory()->premium()->create();
        $this->setSurveyRequired(false);
        $this->enroll($student, $preview)->assertOk()->assertJsonPath('success', true);
        $student = $student->fresh();
        $this->assertSame(User::COURSE_ACCESS_PREVIEW, $student->getCourseAccessLevel($preview));
        $access = app(TopicAccessController::class);
        $this->assertTrue($access->checkAccess($openTopic, $student)['can_access']);
        $this->assertFalse($access->checkAccess($lockedTopic, $student)['can_access']);
        $this->enroll($student, $paid)->assertStatus(400)->assertJsonPath('success', false);
        $this->assertNull($student->fresh()->getCourseAccessLevel($paid));
        $this->assertDatabaseMissing('course_user', ['user_id' => $student->id, 'course_id' => $paid->id]);
    }

    public function test_reenabling_survey_restores_gate_and_invalidates_cached_course_cards(): void
    {
        $student = User::factory()->student()->create();
        $course = Course::factory()->free()->create();
        $this->setSurveyRequired(false);
        $this->enroll($student, $course)->assertOk();
        $student = $student->fresh();
        $this->assertSame(User::COURSE_ACCESS_FULL, $student->getCourseAccessLevel($course));
        $this->get(route('courses.index'))->assertOk();
        $oldSignature = $student->courseAccessStateSignature();
        $this->setSurveyRequired(true);
        $this->assertNotSame($oldSignature, $student->courseAccessStateSignature());
        $this->assertNull($student->getCourseAccessLevel($course));
        $this->actingAs($student)->get(route('courses.index'))->assertOk()
            ->assertSee('class="action-btn enroll free-course-survey-trigger"', false);
        $this->getJson(route('topics.forCourse', $course))->assertStatus(423);
        $student->forceFill(['survey_completed' => true, 'free_courses_unlocked' => true])->save();
        $this->assertSame(User::COURSE_ACCESS_FULL, $student->fresh()->getCourseAccessLevel($course));
    }

    public function test_setting_rejects_invalid_boolean_values_and_displays_in_manage(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->get(route('admin.settings.get', 'free_course_survey_required'))
            ->assertOk()->assertJsonPath('value', true);
        foreach ([['nope', 'boolean'], ['0', 'string']] as [$value, $type]) {
            $this->postJson(route('admin.settings.update'), [
                'key' => 'free_course_survey_required', 'value' => $value, 'type' => $type,
            ])->assertUnprocessable();
        }
        $this->get(route('admin.user-course', ['tab' => 'settings']))->assertOk()
            ->assertSee('Require survey before course access')
            ->assertSee('id="courseSurveyRequiredToggle"', false);
    }
}
