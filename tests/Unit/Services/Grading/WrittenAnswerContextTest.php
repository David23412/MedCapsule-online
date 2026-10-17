<?php

namespace Tests\Unit\Services\Grading;

use App\Models\Question;
use App\Services\MedicalKnowledgeService;
use App\Services\TextProcessingService;
use App\Services\WrittenAnswerGrader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WrittenAnswerContextTest extends TestCase
{
    #[DataProvider('answers')]
    public function test_context_preserves_meaning(string $submitted, string $reference, bool $expected): void
    {
        $grader = new WrittenAnswerGrader(new TextProcessingService(), new MedicalKnowledgeService());
        $result = $grader->evaluateAnswer($submitted, $reference);
        $this->assertSame($expected, $result['isCorrect'], $result['reasonCode']);
    }

    public static function answers(): array
    {
        return [
            'curly apostrophe' => ['It doesn’t increase blood pressure', 'It does not increase blood pressure', true],
            'straight apostrophe' => ["It doesn't increase blood pressure", 'It does not increase blood pressure', true],
            'negative contraction retained' => ['It doesn’t increase blood pressure', 'It increases blood pressure', false],
            'cannot contraction' => ["cannot move", "can't move", true],
            'absence wording' => ['insulin is not present', 'insulin is absent', true],
            'absence reversed' => ['insulin is present', 'insulin is absent', false],
            'explicit excluded alternative' => ['insulin, not glucagon', 'insulin', true],
            'excluded answer is not correct' => ['glucagon, not insulin', 'insulin', false],
            'exclusion before answer' => ['not glucagon but insulin', 'insulin', true],
            'negative prefix remains wrong' => ['not insulin but glucagon', 'insulin', false],
            'negation belongs to different concept' => ['no insulin and glucagon', 'insulin and no glucagon', false],
            'reordered negative clauses' => ['glucagon and no insulin', 'no insulin and glucagon', true],
            'typo within sentence' => ['The answer is kindey', 'kidney', true],
            'alternative phrasing' => ['The heart pumps blood around the body', 'The heart circulates blood throughout the body', true],
            'wrong anatomy still wrong' => ['ilium', 'ileum', false],
            'opposite direction still wrong' => ['increases heart rate', 'decreases heart rate', false],
        ];
    }

    public function test_list_keeps_compound_items_together(): void
    {
        $grader = new WrittenAnswerGrader(new TextProcessingService(), new MedicalKnowledgeService());
        $question = new Question([
            'question' => 'Name two pairs.',
            'correct_answer' => 'sodium and potassium; calcium and magnesium',
            'question_type' => 'written',
        ]);
        $this->assertTrue($grader->evaluateQuestion($question, 'calcium and magnesium; sodium and potassium')['isCorrect']);
    }

    public function test_list_uses_the_number_requested_by_the_question(): void
    {
        $grader = new WrittenAnswerGrader(new TextProcessingService(), new MedicalKnowledgeService());
        $question = new Question([
            'question' => 'Name any two items.', 'correct_answer' => 'heart; kidney; liver',
            'question_type' => 'written',
        ]);
        $this->assertTrue($grader->evaluateQuestion($question, 'kidny and heart')['isCorrect']);
        $this->assertFalse($grader->evaluateQuestion($question, 'heart')['isCorrect']);
    }
}
