<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use App\Services\QuizService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

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
    #[DataProvider('sessionAges')]
    public function test_random_session_lifetime(int $ageSeconds, bool $shouldRegenerate): void
    {
        $this->freezeTime();
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->premium()->create();
        $topic = Topic::factory()->forCourse($course)->quiz()->create();
        $original = Question::factory()->multipleChoice()->forTopic($topic)->create([
            'question' => 'Original saved session question',
        ]);
        $replacement = Question::factory()->multipleChoice()->forTopic($topic)->create([
            'question' => 'Fresh replacement session question',
        ]);
        $this->mock(QuizService::class, function ($mock) use ($original, $replacement, $shouldRegenerate) {
            $expectation = $mock->shouldReceive('generateRandomQuiz')->times($shouldRegenerate ? 2 : 1);
            $expectation->andReturn(
                ['success' => true, 'questions' => collect([$original])],
                ['success' => true, 'questions' => collect([$replacement])],
            );
        });
        $this->actingAs($admin);
        $url = route('quiz.random.show', ['selected_courses' => [$course->id], 'question_limit' => 1]);
        $this->get($url)->assertOk()->assertSee('Original saved session question');
        $this->travel($ageSeconds)->seconds();
        $this->get($url)->assertOk()->assertSee($shouldRegenerate
            ? 'Fresh replacement session question' : 'Original saved session question');
        // The newly generated session must itself remain resumable.
        $this->get($url)->assertOk()->assertSee($shouldRegenerate
            ? 'Fresh replacement session question' : 'Original saved session question');
    }

    public static function sessionAges(): array
    {
        return [
            'fresh' => [0, false],
            'just before expiry' => [1799, false],
            'at expiry' => [1800, true],
            'after expiry' => [1801, true],
            'day old' => [86400, true],
            'future timestamp' => [-60, true],
        ];
    }

}
