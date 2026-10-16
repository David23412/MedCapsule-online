<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use App\Services\QuizService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RandomQuizOptionEResumeTest extends TestCase
{
    use RefreshDatabase;

    public function test_option_e_survives_reloading_an_existing_random_quiz(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->premium()->create();
        $topic = Topic::factory()->forCourse($course)->quiz()->create();
        $question = Question::factory()->multipleChoice()->forTopic($topic)->create([
            'correct_answer' => 'E', 'option_e' => 'Fifth choice survives resume',
        ]);
        $this->mock(QuizService::class, function ($mock) use ($question) {
            $mock->shouldReceive('generateRandomQuiz')->once()->andReturn([
                'success' => true, 'questions' => collect([$question]),
            ]);
        });
        $this->actingAs($admin);
        $url = route('quiz.random.show', ['selected_courses' => [$course->id], 'question_limit' => 1]);
        $this->get($url)->assertOk()->assertSee('Fifth choice survives resume');
        $this->get($url)->assertOk()->assertSee('Fifth choice survives resume');
    }
}
