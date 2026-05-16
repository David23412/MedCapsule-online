<?php

namespace Tests\Unit\Services;

use App\Models\Question;
use App\Models\Topic;
use App\Services\TsvQuestionImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TsvQuestionImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_rows_using_selected_topic_and_supported_tsv_shape(): void
    {
        $selectedTopic = Topic::factory()->create();
        $pastedTopic = Topic::factory()->create();

        $tsv = implode("\n", [
            "{$pastedTopic->id}\tEnumerate bone resorption markers.\tMedCapsule\t-\t-\t-\t-\t-\tTRAP, CTP, betaCTX, NTX\twritten\tWritten explanation.",
            "{$pastedTopic->id}\tWhich marker reflects bone formation?\tMedCapsule\tNTX\tTRAP\tOsteocalcin\tbetaCTX\tCTP\tC\tmultiple_choice\tMCQ explanation.",
        ]);

        $result = app(TsvQuestionImportService::class)->import($tsv, $selectedTopic->id);

        $this->assertSame(2, $result['imported']);
        $this->assertSame([], $result['errors']);

        $this->assertDatabaseHas('questions', [
            'topic_id' => $selectedTopic->id,
            'question' => 'Enumerate bone resorption markers.',
            'source' => 'MedCapsule',
            'correct_answer' => 'TRAP, CTP, betaCTX, NTX',
            'question_type' => Question::TYPE_WRITTEN,
            'explanation' => 'Written explanation.',
        ]);

        $this->assertDatabaseHas('questions', [
            'topic_id' => $selectedTopic->id,
            'question' => 'Which marker reflects bone formation?',
            'option_a' => 'NTX',
            'option_e' => 'CTP',
            'correct_answer' => 'C',
            'question_type' => Question::TYPE_MULTIPLE_CHOICE,
        ]);
    }

    public function test_returns_errors_without_importing_invalid_rows(): void
    {
        $topic = Topic::factory()->create();

        $result = app(TsvQuestionImportService::class)->import(
            "Bad question\tMedCapsule\tA\tB\tC\tD\tE\tZ\tmultiple_choice\tBad answer.",
            $topic->id
        );

        $this->assertSame(0, $result['imported']);
        $this->assertNotEmpty($result['errors']);
        $this->assertDatabaseCount('questions', 0);
    }
}
