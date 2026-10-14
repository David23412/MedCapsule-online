<?php

namespace Tests\Unit\Services\Grading;

use App\Services\MedicalKnowledgeService;
use App\Services\TextProcessingService;
use App\Services\WrittenAnswerGrader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WrittenAnswerGraderToleranceTest extends TestCase
{
    #[DataProvider('answers')]
    public function test_tolerance_preserves_meaning(string $submitted, string $reference, bool $correct): void
    {
        $grader = new WrittenAnswerGrader(new TextProcessingService(), new MedicalKnowledgeService());
        $result = $grader->evaluateAnswer($submitted, $reference);
        $this->assertSame($correct, $result['isCorrect'], $result['reasonCode']);
    }

    public static function answers(): array
    {
        return [
            'transposed short word' => ['heatr', 'heart', true],
            'transposed organ' => ['kindey', 'kidney', true],
            'missing letter in organ' => ['kidny', 'kidney', true],
            'missing letter in short organ' => ['livr', 'liver', true],
            'extra letter in organ' => ['liveru', 'liver', true],
            'missing letter' => ['pancretic secretion', 'pancreatic secretion', true],
            'extra letter' => ['pancreattic secretion', 'pancreatic secretion', true],
            'reordered list with typo' => ['social factor, biologcal factor, environmental factor', 'biological factor, environmental factor, social factor', true],
            'negated effect' => ['does not increase heart rate', 'increases heart rate', false],
            'negated concept' => ['not insulin', 'insulin', false],
            'missing negation' => ['insulin', 'not insulin', false],
            'swapped effects' => ['decreases heart rate and increases digestion', 'increases heart rate and decreases digestion', false],
            'opposite prefix' => ['hyperthyroidism', 'hypothyroidism', false],
            'different anatomy' => ['ileum', 'ilium', false],
            'different units' => ['5 mg', '5 g', false],
            'zero' => ['0', '0', true],
            'punctuation is not an answer' => ['!!!', '???', false],
            'blank punctuation' => ['...', 'heart', false],
            'unrelated answer' => ['kidney', 'heart', false],
            'negation equivalent' => ['no insulin', 'no insulin', true],
            'case and punctuation' => ['HEART!', 'heart', true],
        ];
    }
}
