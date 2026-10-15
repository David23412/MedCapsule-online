<?php

namespace Tests\Feature;

use App\Models\AdminSetting;
use App\Models\Course;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmptyTopicVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_filter_counts_direct_and_exact_linked_questions_and_keeps_videos(): void
    {
        $course = Course::factory()->create();
        $direct = Topic::factory()->forCourse($course)->quiz()->create();
        $linked = Topic::factory()->forCourse($course)->quiz()->create();
        $empty = Topic::factory()->forCourse($course)->quiz()->create();
        $video = Topic::factory()->forCourse($course)->create(['case_type' => 'video']);
        $exam = Topic::factory()->forCourse($course)->quiz()->exam()->create();
        Question::factory()->forTopic($direct)->create([
            'extra_topic' => " {$linked->id}, {$exam->id}, " . ($empty->id + 1000),
        ]);
        $visible = fn () => Topic::where('course_id', $course->id)->withVisibleContent()->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$direct->id, $linked->id, $video->id], $visible());
        Question::factory()->forTopic($empty)->create();
        $this->assertContains($empty->id, $visible());
        Question::factory()->forTopic($exam)->create();
        $this->assertContains($exam->id, $visible());
        $this->assertSame(5, Topic::where('course_id', $course->id)->count());
    }

    public function test_admin_can_enable_and_disable_the_persistent_setting(): void
    {
        $this->assertFalse(AdminSetting::get('hide_empty_topics', false));
        $this->actingAs(User::factory()->admin()->create());
        foreach (['1' => true, '0' => false] as $value => $expected) {
            $this->postJson(route('admin.settings.update'), [
                'key' => 'hide_empty_topics', 'value' => (string) $value, 'type' => 'boolean',
            ])->assertOk()->assertJsonPath('success', true);
            $this->assertSame($expected, AdminSetting::get('hide_empty_topics', false));
        }
    }

    public function test_topics_page_hides_empty_topics_only_when_the_setting_is_enabled(): void
    {
        // Match the existing MySQL function used elsewhere in the topics view.
        \Illuminate\Support\Facades\DB::connection()->getPdo()->sqliteCreateFunction('FIND_IN_SET',
            static function ($needle, $csv) {
                $position = array_search((string) $needle, explode(',', (string) $csv), true);
                return $position === false ? 0 : $position + 1;
            });
        $this->actingAs(User::factory()->admin()->create());
        $course = Course::factory()->premium()->create();
        $empty = Topic::factory()->forCourse($course)->quiz()->create();
        $populated = Topic::factory()->forCourse($course)->quiz()->create();
        Question::factory()->forTopic($populated)->create();
        $url = route('topics.forCourse', $course);
        $this->get($url)->assertOk()->assertSee('data-topic-id="' . $empty->id . '"', false);
        AdminSetting::set('hide_empty_topics', true, 'boolean');
        $this->get($url)->assertOk()->assertDontSee('data-topic-id="' . $empty->id . '"', false)
            ->assertSee('data-topic-id="' . $populated->id . '"', false);
        AdminSetting::set('hide_empty_topics', false, 'boolean');
        $this->get($url)->assertOk()->assertSee('data-topic-id="' . $empty->id . '"', false);
    }

    public function test_students_cannot_change_topic_visibility(): void
    {
        $this->actingAs(User::factory()->student()->create())
            ->postJson(route('admin.settings.update'), [
                'key' => 'hide_empty_topics', 'value' => '1', 'type' => 'boolean',
            ])->assertForbidden();
        $this->assertFalse(AdminSetting::get('hide_empty_topics', false));
    }
}
