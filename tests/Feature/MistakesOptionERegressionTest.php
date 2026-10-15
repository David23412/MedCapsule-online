<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Mistake;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MistakesOptionERegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_page_renders_when_option_e_is_the_correct_answer(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->premium()->create();
        $topic = Topic::factory()->forCourse($course)->quiz()->create();
        $question = Question::factory()->multipleChoice()->forTopic($topic)->create([
            'correct_answer' => 'E', 'option_e' => 'Fifth answer in saved mistakes',
        ]);
        Mistake::create(['user_id' => $admin->id, 'question_id' => $question->id, 'submitted_answer' => 'A']);
        $this->actingAs($admin)->get(route('review.index'))->assertOk()
            ->assertSee('Fifth answer in saved mistakes');
    }
}
