<?php

namespace Tests\Feature;

use App\Models\{Course, Mistake, Question, Topic, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Cache, DB};
use Tests\TestCase;

class AdminCleanupFlowTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->premium()->create();
        $topic = Topic::factory()->forCourse($course)->create();
        $question = Question::factory()->multipleChoice()->forTopic($topic)->create();
        $this->actingAs($admin);
        return [$admin, $course, $topic, $question];
    }

    public function test_all_cleanup_pages_render_and_require_admin_access(): void
    {
        $this->fixture();
        foreach (['index', 'courses', 'topics', 'questions', 'users'] as $page) {
            $this->get(route('admin.delete.'.$page))->assertOk();
        }
        $student = User::factory()->student()->create();
        $this->actingAs($student);
        foreach (['index', 'courses', 'topics', 'questions', 'users'] as $page) {
            $this->getJson(route('admin.delete.'.$page))->assertForbidden();
        }
    }

    public function test_user_filters_allow_all_roles_and_reject_malformed_searches(): void
    {
        [$admin] = $this->fixture();
        $student = User::factory()->student()->create();
        $url = route('admin.delete.users');
        $this->get($url.'?is_admin=')->assertOk()->assertSee($student->email);
        $this->get($url.'?is_admin=1')->assertOk()->assertDontSee($student->email);
        $this->getJson($url.'?search[]=bad')->assertUnprocessable();
    }

    public function test_question_deletion_removes_question_and_mistakes_without_flushing_admin_session(): void
    {
        [$admin, $course, , $question] = $this->fixture();
        $mistake = Mistake::recordFailure($admin->id, $question, 'B');
        Cache::put("mistakes_count_{$admin->id}", 1, 300);
        $this->getJson(route('courses.search-questions', ['course' => $course, 'query' => $question->question]))->assertOk();
        $this->withSession(['cleanup_session_marker' => 'keep-me'])->delete(route('admin.delete.question', $question))
            ->assertRedirect(route('admin.delete.questions'))->assertSessionHas('cleanup_session_marker', 'keep-me')->assertSessionHas('success');
        $this->assertAuthenticatedAs($admin);
        $this->assertModelMissing($question);
        $this->assertModelMissing($mistake);
        $this->assertFalse(Cache::has("mistakes_count_{$admin->id}"));
        $this->assertFalse(Cache::has("course_{$course->id}_questions_search_payload_v11"));
    }

    public function test_bulk_deletion_validates_selection_and_keeps_unselected_questions(): void
    {
        [, , $topic, $question] = $this->fixture();
        $other = Question::factory()->multipleChoice()->forTopic($topic)->create();
        $url = route('admin.delete.questions.bulk');
        $this->postJson($url, ['question_ids' => []])->assertUnprocessable();
        $this->postJson($url, ['question_ids' => [$question->id, $question->id]])->assertUnprocessable();
        $this->postJson($url, ['question_ids' => [$question->id, 999999]])->assertUnprocessable();
        $this->assertModelExists($question);
        $this->withSession(['cleanup_session_marker' => 'keep-me'])->post($url, ['question_ids' => [$question->id]])
            ->assertRedirect(route('admin.delete.questions'))->assertSessionHas('cleanup_session_marker', 'keep-me');
        $this->assertModelMissing($question);
        $this->assertModelExists($other);
    }

    public function test_bulk_deletion_rolls_back_all_records_when_one_delete_fails(): void
    {
        [, , $topic, $question] = $this->fixture();
        $other = Question::factory()->multipleChoice()->forTopic($topic)->create();
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        DB::statement("CREATE TRIGGER fail_second_question_delete BEFORE DELETE ON questions WHEN OLD.id = {$other->id} BEGIN SELECT RAISE(ABORT, 'Synthetic delete failure'); END");
        try {
            $this->from(route('admin.delete.questions'))->post(route('admin.delete.questions.bulk'), ['question_ids' => [$question->id, $other->id]])
                ->assertRedirect(route('admin.delete.questions'))->assertSessionHas('error');
            $this->assertModelExists($question);
            $this->assertModelExists($other);
        } finally {
            DB::statement('DROP TRIGGER fail_second_question_delete');
        }
    }

    public function test_course_deletion_uses_route_id_without_a_duplicate_form_field(): void
    {
        [, $course, $topic, $question] = $this->fixture();
        $this->delete(route('admin.delete.course', $course))->assertRedirect(route('admin.delete.courses'))->assertSessionHas('success');
        $this->assertModelMissing($course);
        $this->assertModelMissing($topic);
        $this->assertModelMissing($question);
    }

    public function test_topic_deletion_removes_its_questions_and_clears_student_mistake_cache(): void
    {
        [$admin, , $topic, $question] = $this->fixture();
        Mistake::recordFailure($admin->id, $question, 'B');
        Cache::put("mistakes_count_{$admin->id}", 1, 300);
        $this->delete(route('admin.delete.topic', $topic))->assertRedirect(route('admin.delete.topics'))->assertSessionHas('success');
        $this->assertModelMissing($topic);
        $this->assertModelMissing($question);
        $this->assertFalse(Cache::has("mistakes_count_{$admin->id}"));
    }

    public function test_question_creation_edit_move_and_deletion_invalidate_linked_topic_versions(): void
    {
        [, $course, $topic, $question] = $this->fixture();
        $linked = Topic::factory()->forCourse($course)->create();
        Cache::forget("quiz_questions_version:topic_{$linked->id}");
        $question->update(['extra_topic' => (string) $linked->id]);
        $this->assertGreaterThan(1, Cache::get("quiz_questions_version:topic_{$linked->id}"));
        $version = Cache::get("quiz_questions_version:topic_{$linked->id}");
        Cache::put("topic:{$linked->id}:all_questions", ['stale'], 3600);
        $question->update(['correct_answer' => 'B']);
        $this->assertFalse(Cache::has("topic:{$linked->id}:all_questions"));
        $this->assertGreaterThan($version, Cache::get("quiz_questions_version:topic_{$linked->id}"));
        $version = Cache::get("quiz_questions_version:topic_{$linked->id}");
        $question->update(['extra_topic' => null]);
        $this->assertGreaterThan($version, Cache::get("quiz_questions_version:topic_{$linked->id}"));
        $question->update(['extra_topic' => (string) $linked->id]);
        $version = Cache::get("quiz_questions_version:topic_{$linked->id}");
        $this->delete(route('admin.delete.question', $question))->assertRedirect();
        $this->assertGreaterThan($version, Cache::get("quiz_questions_version:topic_{$linked->id}"));
    }

    public function test_previews_use_actual_columns_live_counts_and_not_found_responses(): void
    {
        [$admin, $course, $topic, $question] = $this->fixture();
        $url = route('admin.delete.preview', ['type' => 'question', 'id' => $question->id]);
        $this->getJson($url)->assertOk()->assertJsonPath('stats.mistakes', 0);
        Mistake::recordFailure($admin->id, $question, 'B');
        $this->getJson($url)->assertOk()->assertJsonPath('stats.mistakes', 1);
        foreach (['course', 'topic', 'question', 'user'] as $type) {
            $this->getJson(route('admin.delete.preview', ['type' => $type, 'id' => 999999]))->assertNotFound();
        }
    }
}
