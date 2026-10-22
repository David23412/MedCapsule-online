<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminQuestionQualityTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_scan_question_quality(): void
    {
        $url = route('admin.question-quality');
        $this->getJson($url)->assertUnauthorized();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_STUDENT]))->getJson($url)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_TUTOR]))->getJson($url)->assertForbidden();
    }

    public function test_it_finds_invalid_options_and_answer_only_explanations_without_flagging_real_explanations(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $topic = Topic::factory()->forCourse(Course::factory()->premium()->create())->create();
        $base = ['topic_id' => $topic->id, 'option_c' => 'Yellow nerve', 'correct_answer' => 'C'];
        $broken = Question::factory()->multipleChoice()->create(array_merge($base, ['correct_answer' => 'F']));
        $empty = Question::factory()->multipleChoice()->create(array_merge($base, ['correct_answer' => 'E', 'option_e' => ' - ']));
        $repeated = Question::factory()->multipleChoice()->create(array_merge($base, ['explanation' => '<p>YELLOW&nbsp;nerve.</p>']));
        $labelled = Question::factory()->multipleChoice()->create(array_merge($base, ['explanation' => 'C (Yellow nerve)']));
        $written = Question::factory()->create(['question_type' => 'written', 'topic_id' => $topic->id, 'correct_answer' => 'Brachial plexus', 'explanation' => 'brachial plexus']);
        Question::factory()->multipleChoice()->create(array_merge($base, ['correct_answer' => 'E', 'option_e' => 'Fifth nerve', 'explanation' => 'The fifth nerve carries facial sensation.']));
        $good = Question::factory()->multipleChoice()->create(array_merge($base, ['explanation' => 'Yellow nerve supplies this muscle.']));
        $blank = Question::factory()->multipleChoice()->create(array_merge($base, ['explanation' => null]));
        $this->getJson(route('admin.question-quality'))->assertOk()
            ->assertJsonPath('counts.broken_answer', 2)->assertJsonPath('counts.repeated_explanation', 3)
            ->assertJsonPath('total', 5)->assertJsonCount(5, 'items');
        $ids = $this->getJson(route('admin.question-quality', ['issue' => 'repeated_explanation']))->assertOk()->json('items');
        $this->assertEqualsCanonicalizing([$repeated->id, $labelled->id, $written->id], array_column($ids, 'id'));
        $this->assertNotContains($good->id, array_column($ids, 'id'));
        $this->assertNotContains($blank->id, array_column($ids, 'id'));
        $this->getJson(route('admin.question-quality', ['issue' => 'broken_answer']))
            ->assertJsonPath('total', 2)->assertJsonPath('items.0.id', $broken->id)
            ->assertJsonPath('items.1.id', $empty->id);
    }

    public function test_course_filter_pagination_and_edit_refresh_use_current_saved_data(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $course = Course::factory()->premium()->create();
        $topic = Topic::factory()->forCourse($course)->create();
        $questions = Question::factory()->multipleChoice()->count(21)->create([
            'topic_id' => $topic->id, 'correct_answer' => 'F', 'explanation' => 'A useful explanation.',
        ]);
        Question::factory()->multipleChoice()->create(['correct_answer' => 'F']);
        $url = route('admin.question-quality', ['course_id' => $course->id]);
        $this->getJson($url)->assertOk()->assertJsonPath('total', 21)->assertJsonPath('last_page', 2)->assertJsonCount(20, 'items');
        $this->getJson($url . '&page=2')->assertOk()->assertJsonCount(1, 'items');
        $question = $questions->first();
        $this->putJson(route('quiz.tutor.update-question', $question), [
            'question' => $question->question, 'correct_answer' => 'A',
            'option_a' => 'Valid option', 'explanation' => 'This option explains the clinical finding.',
        ])->assertOk();
        $this->getJson($url)->assertOk()->assertJsonPath('total', 20)->assertJsonPath('last_page', 1);
        $this->putJson(route('quiz.tutor.update-question', $question), [
            'question' => $question->question, 'correct_answer' => 'E', 'option_e' => '',
        ])->assertUnprocessable();
        $this->getJson(route('admin.question-quality', ['issue' => 'unknown']))->assertUnprocessable();
        $this->getJson(route('admin.question-quality', ['page' => 0]))->assertUnprocessable();
    }

    public function test_manage_access_has_the_quality_tab_and_editor(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.user-course', ['tab' => 'question-quality']))->assertOk()
            ->assertSee('Question Quality')->assertSee('Answer-only explanation')->assertSee('quality-editor', false);
    }
}
