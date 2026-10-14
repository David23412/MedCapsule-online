<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSearchQuestionEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_option_e_can_be_loaded_edited_and_selected_as_the_correct_answer(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->premium()->create();
        $topic = Topic::factory()->forCourse($course)->create();
        $question = Question::factory()->multipleChoice()->forTopic($topic)->create([
            'question' => 'Identify the endocrine structure.',
            'option_e' => 'Original fifth choice',
            'correct_answer' => 'E',
        ]);
        $this->actingAs($admin);
        $search = route('courses.search-questions', ['course' => $course, 'query' => 'endocrine']);
        $this->getJson($search)->assertOk()
            ->assertJsonPath('questions.0.option_e', 'Original fifth choice')
            ->assertJsonPath('questions.0.correct_answer', 'E: Original fifth choice');

        $edit = route('quiz.tutor.update-question', $question);
        $this->putJson($edit, [
            'question' => $question->question,
            'question_type' => 'multiple_choice',
            'option_e' => 'Updated fifth choice',
            'correct_answer' => 'E',
        ])->assertOk()->assertJsonPath('data.option_e', 'Updated fifth choice');
        $this->assertDatabaseHas('questions', [
            'id' => $question->id, 'option_e' => 'Updated fifth choice', 'correct_answer' => 'E',
        ]);
        $this->getJson($search)->assertOk()
            ->assertJsonPath('questions.0.option_e', 'Updated fifth choice')
            ->assertJsonPath('questions.0.correct_answer', 'E: Updated fifth choice');

        // Older edit forms omit E; they must preserve the existing fifth choice.
        $this->putJson($edit, [
            'question' => $question->question, 'correct_answer' => 'A',
        ])->assertOk()->assertJsonPath('data.option_e', 'Updated fifth choice');
        $this->putJson($edit, [
            'question' => $question->question, 'question_type' => 'written', 'correct_answer' => 'Pancreas',
        ])->assertOk()->assertJsonPath('data.option_e', null);
    }
}
