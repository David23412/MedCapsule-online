<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use App\Services\WrittenAnswerEvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WrittenGradingContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_context_aware_written_answer_records_a_correct_quiz_attempt(): void
    {
        $user = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $topic = Topic::factory()->forCourse($course)->quiz()->create();
        $question = Question::factory()->forTopic($topic)->create([
            'question_type' => Question::TYPE_WRITTEN,
            'correct_answer' => 'does not increase blood pressure',
        ]);

        $this->actingAs($user)->postJson(route('quiz.grade', [$course, $topic]), [
            'answers' => [$question->id => 'doesn’t increase blood pressure'],
            'time_taken' => 30,
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('quiz_attempts', ['user_id' => $user->id, 'topic_id' => $topic->id, 'score' => 1]);
        $this->assertDatabaseCount('mistakes', 0);
    }

    public function test_requested_item_coverage_can_be_made_strict(): void
    {
        $question = new Question([
            'question_type' => Question::TYPE_WRITTEN,
            'question' => 'Name four items.', 'correct_answer' => 'heart; kidney; liver; lung',
        ]);
        $grader = app(WrittenAnswerEvaluationService::class);
        config()->set('written_grading.shadow_mode_enabled', false);
        config()->set('written_grading.minimum_requested_item_coverage', 0.75);
        $this->assertTrue($grader->evaluateQuestionAnswer($question, 'heart; kidney; liver')['isCorrect']);
        config()->set('written_grading.minimum_requested_item_coverage', 1.0);
        $result = $grader->evaluateQuestionAnswer($question, 'heart; kidney; liver');
        $this->assertFalse($result['isCorrect']);
        $this->assertSame('required_items_missing', $result['reasonCode']);
    }
}
