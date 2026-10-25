<?php

namespace Tests\Feature;

use App\Models\{Course, Question, Topic, User};
use App\Services\CourseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PaidCoursePreviewTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        \Illuminate\Support\Facades\DB::connection()->getPdo()->sqliteCreateFunction('FIND_IN_SET',
            static fn ($needle, $list) => in_array((string) $needle, explode(',', (string) $list), true) ? 1 : 0);
        $student = User::factory()->student()->create();
        $course = Course::factory()->premium()->create();
        $open = Topic::factory()->forCourse($course)->create(['is_preview_enabled' => true]);
        $locked = Topic::factory()->forCourse($course)->create(['is_preview_enabled' => false]);
        foreach ([$open, $locked] as $topic) {
            Question::factory()->create([
                'topic_id' => $topic->id, 'question_type' => 'multiple_choice',
                'correct_answer' => 'A', 'option_a' => 'Correct', 'option_b' => 'Incorrect', 'source' => 'Preview test',
            ]);
        }
        return [$student, $course, $open, $locked];
    }

    public function test_student_can_open_selected_paid_course_preview_without_payment_enrollment_or_survey(): void
    {
        [$student, $course, $open] = $this->fixture();
        $this->actingAs($student);
        $this->assertSame(User::COURSE_ACCESS_PREVIEW, $student->getCourseAccessLevel($course));
        $this->get(route('courses.index'))->assertOk()->assertSee('Preview 1 Topics');
        $card = app(CourseService::class)->getCoursesForIndex([])['data']['courses']->first();
        $this->assertTrue($card->is_preview_access);
        $this->assertFalse($card->requires_survey_unlock);
        $this->get(route('topics.forCourse', $course))->assertOk();
        $this->get(route('quiz.read', [$course, $open]))->assertOk();
        Cache::put("user_access_{$student->id}_{$course->id}", false, 300);
        $this->get(route('quiz.tutor', [$course, $open]))->assertOk();
        $this->get(route('quiz.start', [$course, $open]))->assertOk();
        $question = $open->questions()->first();
        $this->postJson(route('quiz.grade', [$course, $open]), [
            'answers' => [$question->id => 'A'], 'time_taken' => 30,
        ])->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseCount('course_user', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('survey_responses', 0);
    }

    public function test_other_topics_stay_locked_in_every_quiz_mode_and_cannot_be_submitted(): void
    {
        [$student, $course, $open, $locked] = $this->fixture();
        $this->assertTrue($student->isTopicPreviewLocked($locked));
        $this->assertFalse($student->isTopicPreviewLocked($open));
        $this->actingAs($student);
        foreach (['quiz.read', 'quiz.tutor', 'quiz.start'] as $route) {
            $this->get(route($route, [$course, $locked]))->assertRedirect();
        }
        $question = $locked->questions()->first();
        $this->postJson(route('quiz.grade', [$course, $locked]), [
            'answers' => [$question->id => 'A'], 'question_ids' => [$question->id],
        ])->assertForbidden();
        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    public function test_removing_preview_selection_closes_public_access_without_a_default_paid_preview(): void
    {
        [$student, $course, $open] = $this->fixture();
        $this->actingAs($student)->get(route('courses.index'))->assertOk()->assertSee('Preview 1 Topics');
        $open->update(['is_preview_enabled' => false]);
        $student = $student->fresh();
        $this->assertNull($student->getCourseAccessLevel($course));
        $this->actingAs($student)->get(route('courses.index'))->assertOk()->assertDontSee('Preview 1 Topics');
        $this->get(route('topics.forCourse', $course))->assertRedirect(route('courses.index'));
        $this->get(route('quiz.tutor', [$course, $open]))->assertRedirect();
        $this->assertSame(0, $course->fresh()->getPreviewTopicLimit());
        $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/courses/'.$course->id.'/topics')->assertOk()
            ->assertJsonPath('meta.open_to_preview_count', 0)
            ->assertJsonPath('meta.using_default_preview', false);
    }

    public function test_full_enrollment_keeps_all_topics_open_and_revocation_leaves_only_public_preview(): void
    {
        [$student, $course, , $locked] = $this->fixture();
        $student->enrollInCourse($course->id);
        $student = $student->fresh();
        $this->assertSame(User::COURSE_ACCESS_FULL, $student->getCourseAccessLevel($course));
        $this->assertFalse($student->isTopicPreviewLocked($locked));
        $student->courses()->updateExistingPivot($course, ['is_enrolled' => false]);
        $student = $student->fresh();
        $this->assertSame(User::COURSE_ACCESS_PREVIEW, $student->getCourseAccessLevel($course));
        $this->assertTrue($student->isTopicPreviewLocked($locked));
    }

    public function test_platform_overview_has_no_collapsible_dropdown(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.user-course'))->assertOk()
            ->assertSee('id="platformOverview"', false)
            ->assertDontSee('<details class="card mb-3" id="platformOverview">', false);
    }
}
