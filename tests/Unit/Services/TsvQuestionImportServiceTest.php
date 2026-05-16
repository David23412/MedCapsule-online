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

    public function test_imports_rows_using_topic_id_from_each_tsv_row(): void
    {
        $firstTopic = Topic::factory()->create();
        $secondTopic = Topic::factory()->create();

        $tsv = implode("\n", [
            "{$firstTopic->id}\tEnumerate bone resorption markers.\tMedCapsule\t-\t-\t-\t-\t-\tTRAP, CTP, betaCTX, NTX\twritten\tWritten explanation.",
            "{$secondTopic->id}\tWhich marker reflects bone formation?\tMedCapsule\tNTX\tTRAP\tOsteocalcin\tbetaCTX\tCTP\tC\tmultiple_choice\tMCQ explanation.",
        ]);

        $result = app(TsvQuestionImportService::class)->import($tsv);

        $this->assertSame(2, $result['imported']);
        $this->assertSame([], $result['errors']);

        $this->assertDatabaseHas('questions', [
            'topic_id' => $firstTopic->id,
            'question' => 'Enumerate bone resorption markers.',
            'source' => 'MedCapsule',
            'correct_answer' => 'TRAP, CTP, betaCTX, NTX',
            'question_type' => Question::TYPE_WRITTEN,
            'explanation' => 'Written explanation.',
        ]);

        $this->assertDatabaseHas('questions', [
            'topic_id' => $secondTopic->id,
            'question' => 'Which marker reflects bone formation?',
            'option_a' => 'NTX',
            'option_e' => 'CTP',
            'correct_answer' => 'C',
            'question_type' => Question::TYPE_MULTIPLE_CHOICE,
        ]);
    }

    public function test_uses_fallback_topic_for_rows_without_topic_id_column(): void
    {
        $topic = Topic::factory()->create();

        $result = app(TsvQuestionImportService::class)->import(
            "Which marker reflects bone formation?\tMedCapsule\tNTX\tTRAP\tOsteocalcin\tbetaCTX\tCTP\tC\tmultiple_choice\tMCQ explanation.",
            $topic->id
        );

        $this->assertSame(1, $result['imported']);
        $this->assertSame([], $result['errors']);
        $this->assertDatabaseHas('questions', [
            'topic_id' => $topic->id,
            'question' => 'Which marker reflects bone formation?',
        ]);
    }

    public function test_returns_error_when_no_row_topic_or_fallback_topic_exists(): void
    {
        $result = app(TsvQuestionImportService::class)->import(
            "Question without topic\tMedCapsule\tA\tB\tC\tD\tE\tA\tmultiple_choice\tExplanation."
        );

        $this->assertSame(0, $result['imported']);
        $this->assertSame(['Row 1: topic id is required. Add it as the first TSV column or choose a selected topic mode.'], $result['errors']);
        $this->assertDatabaseCount('questions', 0);
    }

    public function test_can_force_all_rows_into_selected_topic(): void
    {
        $rowTopic = Topic::factory()->create();
        $selectedTopic = Topic::factory()->create();

        $result = app(TsvQuestionImportService::class)->import(
            "{$rowTopic->id}\tWhich marker reflects bone formation?\tMedCapsule\tNTX\tTRAP\tOsteocalcin\tbetaCTX\tCTP\tC\tmultiple_choice\tMCQ explanation.",
            $selectedTopic->id,
            true
        );

        $this->assertSame(1, $result['imported']);
        $this->assertSame([], $result['errors']);
        $this->assertDatabaseHas('questions', [
            'topic_id' => $selectedTopic->id,
            'question' => 'Which marker reflects bone formation?',
        ]);
        $this->assertDatabaseMissing('questions', [
            'topic_id' => $rowTopic->id,
            'question' => 'Which marker reflects bone formation?',
        ]);
    }

    public function test_allows_true_false_multiple_choice_rows_with_empty_later_options(): void
    {
        $topic = Topic::factory()->create();

        $result = app(TsvQuestionImportService::class)->import(
            "{$topic->id}\tThe clavicle connects the upper limb to the trunk.\tAssuit\tTrue\tFalse\t-\t-\t-\tA\tmultiple_choice\tThe clavicle is the only direct bony connection.",
        );

        $this->assertSame(1, $result['imported']);
        $this->assertSame([], $result['errors']);
        $this->assertDatabaseHas('questions', [
            'topic_id' => $topic->id,
            'question' => 'The clavicle connects the upper limb to the trunk.',
            'option_a' => 'True',
            'option_b' => 'False',
            'option_c' => null,
            'option_d' => null,
            'correct_answer' => 'A',
        ]);
    }

    public function test_rejects_multiple_choice_when_correct_answer_points_to_empty_option(): void
    {
        $topic = Topic::factory()->create();

        $result = app(TsvQuestionImportService::class)->import(
            "{$topic->id}\tThe clavicle connects the upper limb to the trunk.\tAssuit\tTrue\tFalse\t-\t-\t-\tC\tmultiple_choice\tThe clavicle is the only direct bony connection.",
        );

        $this->assertSame(0, $result['imported']);
        $this->assertSame(['Row 1: correct answer C points to an empty option.'], $result['errors']);
        $this->assertDatabaseCount('questions', 0);
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
