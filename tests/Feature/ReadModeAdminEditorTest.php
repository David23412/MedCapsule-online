<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadModeAdminEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_has_the_shared_editor_with_all_five_options_and_written_answers(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->renderReadMode()->assertSee('aria-label="Edit question 1"', false)
            ->assertSee('id="edit-question-overlay"', false)
            ->assertSee('id="edit-option-e"', false)
            ->assertSee('id="edit-alternative-answers"', false)
            ->assertSee('refreshAfterEdit: true', false)
            ->assertSee('tutor-admin-edit.js');
    }

    public function test_student_can_read_answers_but_cannot_access_the_editor_or_update_questions(): void
    {
        $this->actingAs(User::factory()->student()->create());
        $this->renderReadMode()->assertSee('Fifth answer')
            ->assertDontSee('id="edit-question-overlay"', false)
            ->assertDontSee('tutor-admin-edit.js');
        $question = Question::firstOrFail();
        $this->putJson(route('quiz.tutor.update-question', $question), [
            'question' => 'Unauthorized edit', 'correct_answer' => 'A',
        ])->assertForbidden();
        $this->assertDatabaseMissing('questions', ['question' => 'Unauthorized edit']);
    }

    public function test_read_route_loads_option_e_and_refreshes_it_after_an_admin_edit(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $course = Course::factory()->premium()->create();
        $topic = Topic::factory()->forCourse($course)->create(['is_exam' => true]);
        $question = Question::factory()->multipleChoice()->forTopic($topic)->create([
            'option_e' => 'Original fifth answer', 'correct_answer' => 'E',
        ]);
        $url = route('quiz.read', ['course' => $course, 'topic' => $topic]);
        $this->get($url)->assertOk()->assertSee('Original fifth answer');
        $this->putJson(route('quiz.tutor.update-question', $question), [
            'question' => $question->question, 'correct_answer' => 'E', 'option_e' => 'Updated fifth answer',
        ])->assertOk();
        $this->get($url)->assertOk()->assertSee('Updated fifth answer')->assertDontSee('Original fifth answer');
    }

    private function renderReadMode(): \Illuminate\Testing\TestView
    {
        $course = Course::factory()->premium()->create();
        $topic = Topic::factory()->forCourse($course)->create();
        $question = Question::factory()->multipleChoice()->forTopic($topic)->create([
            'option_e' => 'Fifth answer', 'correct_answer' => 'E',
        ]);
        return $this->view('quiz.read_mode', [
            'course' => $course, 'topic' => $topic, 'questions' => collect([$question]),
            'selected_sources' => ['Lecture'], 'mistakeQuestionIds' => [],
        ]);
    }
}
