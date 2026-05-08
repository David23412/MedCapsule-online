<?php

namespace Tests\Unit\Services\Grading;

use App\Models\Question;
use App\Models\Grading\Diagnostic;
use App\Services\LegacyWrittenAnswerEvaluator;
use App\Services\WrittenAnswerEvaluationService;
use App\Services\WrittenAnswerGrader;
use App\Services\WrittenGradingDiagnosticsService;
use Mockery;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase as LaravelTestCase;

class WrittenAnswerEvaluationArchitectureTest extends LaravelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('grading_diagnostics');
        Schema::create('grading_diagnostics', function (Blueprint $table) {
            $table->id();
            $table->string('grader_version');
            $table->unsignedBigInteger('question_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('submitted_answer_hash', 64)->nullable();
            $table->boolean('old_result')->nullable();
            $table->boolean('new_result')->nullable();
            $table->boolean('disagreement')->default(false);
            $table->string('reason_code');
            $table->json('diagnostics')->nullable();
            $table->timestamps();
        });
    }

    public function test_wrapper_delegates_to_canonical_grader(): void
    {
        $question = new Question([
            'question_type' => Question::TYPE_WRITTEN,
            'correct_answer' => 'Heart pumps blood',
        ]);
        $question->id = 44;

        $grader = Mockery::mock(WrittenAnswerGrader::class);
        $grader->shouldReceive('evaluateQuestion')
            ->once()
            ->with($question, 'Heart pumps blood')
            ->andReturn([
                'isCorrect' => true,
                'confidence' => 1.0,
                'reasonCode' => 'normalized_exact_match',
                'feedback' => 'ok',
                'diagnostics' => [],
                'similarity' => 1.0,
                'reason' => 'normalized_exact_match',
            ]);

        config()->set('written_grading.shadow_mode_enabled', false);
        config()->set('written_grading.grader_primary', 'new');

        $service = new WrittenAnswerEvaluationService($grader, null, null, Mockery::mock(LegacyWrittenAnswerEvaluator::class), app(WrittenGradingDiagnosticsService::class));

        $result = $service->evaluateQuestionAnswer($question, 'Heart pumps blood');

        $this->assertTrue($result['isCorrect']);
        $this->assertSame('normalized_exact_match', $result['reasonCode']);
    }

    public function test_question_model_uses_wrapper_from_container(): void
    {
        $question = new Question([
            'question_type' => Question::TYPE_WRITTEN,
            'correct_answer' => 'Heart pumps blood',
        ]);

        $wrapper = Mockery::mock(WrittenAnswerEvaluationService::class);
        $wrapper->shouldReceive('isCorrect')
            ->once()
            ->with($question, 'heart answer')
            ->andReturn(true);

        $this->app->instance(WrittenAnswerEvaluationService::class, $wrapper);

        $this->assertTrue($question->isAnswerCorrect('heart answer'));
    }

    public function test_question_model_fails_closed_when_wrapper_errors(): void
    {
        $question = new Question([
            'question_type' => Question::TYPE_WRITTEN,
            'correct_answer' => 'Heart pumps blood',
        ]);

        $wrapper = Mockery::mock(WrittenAnswerEvaluationService::class);
        $wrapper->shouldReceive('isCorrect')
            ->once()
            ->andThrow(new \RuntimeException('boom'));

        $this->app->instance(WrittenAnswerEvaluationService::class, $wrapper);

        $this->assertFalse($question->isAnswerCorrect('heart answer'));
    }

    public function test_shadow_mode_persists_disagreement_with_grader_version(): void
    {
        config()->set('written_grading.shadow_mode_enabled', true);
        config()->set('written_grading.grader_primary', 'new');
        config()->set('written_grading.grader_version', 'v2.1');

        $question = new Question([
            'question_type' => Question::TYPE_WRITTEN,
            'correct_answer' => 'Heart pumps blood',
        ]);
        $question->id = 44;

        $grader = Mockery::mock(WrittenAnswerGrader::class);
        $grader->shouldReceive('evaluateQuestion')->once()->andReturn([
            'isCorrect' => false,
            'confidence' => 0.4,
            'reasonCode' => 'missing_required_concepts',
            'feedback' => 'missing',
            'diagnostics' => ['mode' => 'definition'],
            'similarity' => 0.4,
            'reason' => 'missing_required_concepts',
        ]);

        $legacy = Mockery::mock(LegacyWrittenAnswerEvaluator::class);
        $legacy->shouldReceive('evaluateQuestion')->once()->andReturn([
            'isCorrect' => true,
            'confidence' => 0.85,
            'reasonCode' => 'legacy_similarity_match',
            'feedback' => 'legacy',
            'diagnostics' => ['mode' => 'legacy'],
            'similarity' => 0.85,
            'reason' => 'legacy_similarity_match',
        ]);

        $service = new WrittenAnswerEvaluationService($grader, null, null, $legacy, app(WrittenGradingDiagnosticsService::class));
        $result = $service->evaluateQuestionAnswer($question, 'submitted answer');

        $this->assertFalse($result['isCorrect']);

        $diagnostic = Diagnostic::query()->sole();
        $this->assertSame('v2.1', $diagnostic->grader_version);
        $this->assertTrue($diagnostic->disagreement);
        $this->assertSame(44, $diagnostic->question_id);
    }

    public function test_shadow_mode_skips_persistence_when_results_agree(): void
    {
        config()->set('written_grading.shadow_mode_enabled', true);
        config()->set('written_grading.grader_primary', 'new');

        $question = new Question([
            'question_type' => Question::TYPE_WRITTEN,
            'correct_answer' => 'Heart pumps blood',
        ]);
        $question->id = 45;

        $grader = Mockery::mock(WrittenAnswerGrader::class);
        $grader->shouldReceive('evaluateQuestion')->once()->andReturn($this->correctResult('concept_match'));

        $legacy = Mockery::mock(LegacyWrittenAnswerEvaluator::class);
        $legacy->shouldReceive('evaluateQuestion')->once()->andReturn($this->correctResult('legacy_similarity_match'));

        $service = new WrittenAnswerEvaluationService($grader, null, null, $legacy, app(WrittenGradingDiagnosticsService::class));
        $service->evaluateQuestionAnswer($question, 'submitted answer');

        $this->assertSame(0, Diagnostic::query()->count());
    }

    public function test_old_primary_returns_legacy_result_without_deploy(): void
    {
        config()->set('written_grading.shadow_mode_enabled', true);
        config()->set('written_grading.grader_primary', 'old');

        $question = new Question([
            'question_type' => Question::TYPE_WRITTEN,
            'correct_answer' => 'Heart pumps blood',
        ]);
        $question->id = 46;

        $grader = Mockery::mock(WrittenAnswerGrader::class);
        $grader->shouldReceive('evaluateQuestion')->once()->andReturn($this->incorrectResult('missing_required_concepts'));

        $legacy = Mockery::mock(LegacyWrittenAnswerEvaluator::class);
        $legacy->shouldReceive('evaluateQuestion')->once()->andReturn($this->correctResult('legacy_similarity_match'));

        $service = new WrittenAnswerEvaluationService($grader, null, null, $legacy, app(WrittenGradingDiagnosticsService::class));
        $result = $service->evaluateQuestionAnswer($question, 'submitted answer');

        $this->assertTrue($result['isCorrect']);
        $this->assertSame('legacy_similarity_match', $result['reasonCode']);
        $this->assertSame(1, Diagnostic::query()->count());
    }

    public function test_wrapper_uses_configured_disagreement_threshold(): void
    {
        config()->set('written_grading.legacy_correct_disagreement_threshold', 0.03);

        $service = new WrittenAnswerEvaluationService(
            Mockery::mock(WrittenAnswerGrader::class),
            null,
            null,
            Mockery::mock(LegacyWrittenAnswerEvaluator::class),
            app(WrittenGradingDiagnosticsService::class)
        );

        $this->assertTrue($service->shouldDeleteLegacyWrapper(0.02));
        $this->assertFalse($service->shouldDeleteLegacyWrapper(0.03));
    }

    public function test_grader_error_returns_safe_result_and_persists_diagnostic(): void
    {
        config()->set('written_grading.shadow_mode_enabled', false);
        config()->set('written_grading.grader_primary', 'new');
        config()->set('written_grading.grader_version', 'v2.2');

        $question = new Question([
            'question_type' => Question::TYPE_WRITTEN,
            'correct_answer' => 'Heart pumps blood',
        ]);
        $question->id = 47;

        $grader = Mockery::mock(WrittenAnswerGrader::class);
        $grader->shouldReceive('evaluateQuestion')->once()->andThrow(new \RuntimeException('boom'));

        $service = new WrittenAnswerEvaluationService(
            $grader,
            null,
            null,
            Mockery::mock(LegacyWrittenAnswerEvaluator::class),
            app(WrittenGradingDiagnosticsService::class)
        );

        $result = $service->evaluateQuestionAnswer($question, 'submitted answer');

        $this->assertFalse($result['isCorrect']);
        $this->assertSame('grader_error', $result['reasonCode']);
        $this->assertSame(1, Diagnostic::query()->count());
        $this->assertSame('grader_error', Diagnostic::query()->firstOrFail()->reason_code);
    }

    public function test_old_primary_records_new_grader_failure_during_shadow_mode(): void
    {
        config()->set('written_grading.shadow_mode_enabled', true);
        config()->set('written_grading.grader_primary', 'old');

        $question = new Question([
            'question_type' => Question::TYPE_WRITTEN,
            'correct_answer' => 'Heart pumps blood',
        ]);
        $question->id = 48;

        $grader = Mockery::mock(WrittenAnswerGrader::class);
        $grader->shouldReceive('evaluateQuestion')->once()->andThrow(new \RuntimeException('boom'));

        $legacy = Mockery::mock(LegacyWrittenAnswerEvaluator::class);
        $legacy->shouldReceive('evaluateQuestion')->once()->andReturn($this->correctResult('legacy_similarity_match'));

        $service = new WrittenAnswerEvaluationService($grader, null, null, $legacy, app(WrittenGradingDiagnosticsService::class));
        $result = $service->evaluateQuestionAnswer($question, 'submitted answer');

        $this->assertTrue($result['isCorrect']);
        $this->assertSame('legacy_similarity_match', $result['reasonCode']);
        $this->assertSame('grader_error', Diagnostic::query()->sole()->reason_code);
    }

    private function correctResult(string $reasonCode): array
    {
        return [
            'isCorrect' => true,
            'confidence' => 0.9,
            'reasonCode' => $reasonCode,
            'feedback' => 'ok',
            'diagnostics' => [],
            'similarity' => 0.9,
            'reason' => $reasonCode,
        ];
    }

    private function incorrectResult(string $reasonCode): array
    {
        return [
            'isCorrect' => false,
            'confidence' => 0.2,
            'reasonCode' => $reasonCode,
            'feedback' => 'no',
            'diagnostics' => [],
            'similarity' => 0.2,
            'reason' => $reasonCode,
        ];
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('grading_diagnostics');
        Mockery::close();
        parent::tearDown();
    }
}
