<?php

namespace Tests\Unit\Models;

use App\Models\Course;
use App\Models\Question;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionWrittenGradingTest extends TestCase
{
    use RefreshDatabase;

    private function createWrittenQuestion(string $correctAnswer, array $attributes = []): Question
    {
        $course = Course::factory()->create();
        $topic = Topic::factory()->create([
            'course_id' => $course->id,
        ]);

        return Question::factory()->create(array_merge([
            'topic_id' => $topic->id,
            'question' => 'Test written question',
            'question_type' => Question::TYPE_WRITTEN,
            'correct_answer' => $correctAnswer,
            'source' => 'Test Source',
        ], $attributes));
    }

    public function test_it_accepts_semantic_medical_paraphrases_through_the_question_model(): void
    {
        $question = $this->createWrittenQuestion(
            'The parasympathetic nervous system decreases heart rate and increases digestion'
        );

        $this->assertTrue($question->isAnswerCorrect('PNS decreases HR and increases GI activity'));
    }

    public function test_it_rejects_reversed_medical_effects_through_the_question_model(): void
    {
        $question = $this->createWrittenQuestion(
            'The sympathetic nervous system increases heart rate and decreases digestion'
        );

        $this->assertFalse($question->isAnswerCorrect('SNS decreases heart rate and increases digestion'));
    }

    public function test_it_rejects_wrong_numerical_values_through_the_question_model(): void
    {
        $question = $this->createWrittenQuestion('Blood pressure is 120/80 mmHg');

        $this->assertFalse($question->isAnswerCorrect('160/100 mmHg'));
    }

    public function test_it_accepts_newline_defined_alternative_answers_through_the_question_model(): void
    {
        $question = $this->createWrittenQuestion('Doctors diagnose disease', [
            'alternative_answers' => "The physician diagnoses illness\nA doctor can diagnose disease",
        ]);

        $this->assertTrue($question->isAnswerCorrect('The physician diagnoses illness'));
    }

    public function test_it_accepts_list_style_written_answers_through_the_question_model(): void
    {
        $question = $this->createWrittenQuestion(
            'The water cycle includes evaporation, condensation, and precipitation'
        );

        $this->assertTrue($question->isAnswerCorrect('evaporation, condensation, precipitation'));
    }
}
