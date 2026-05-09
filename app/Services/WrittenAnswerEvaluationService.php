<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Support\Facades\Log;

class WrittenAnswerEvaluationService
{
    public function __construct(
        private WrittenAnswerGrader $grader,
        ?TextProcessingService $textProcessor = null,
        ?MedicalKnowledgeService $medicalKnowledge = null,
        private ?LegacyWrittenAnswerEvaluator $legacyEvaluator = null,
        private ?WrittenGradingDiagnosticsService $diagnostics = null,
    ) {
        // Backward-compatibility: old callers passed TextProcessingService as the first arg.
        if ($textProcessor !== null) {
            $this->grader = new WrittenAnswerGrader(
                $textProcessor,
                $medicalKnowledge ?? app(MedicalKnowledgeService::class),
            );

            $this->legacyEvaluator ??= new LegacyWrittenAnswerEvaluator(
                $textProcessor,
                $medicalKnowledge ?? app(MedicalKnowledgeService::class),
            );
        } else {
            $this->legacyEvaluator ??= function_exists('app') ? app(LegacyWrittenAnswerEvaluator::class) : null;
        }

        $this->diagnostics ??= function_exists('app') ? app(WrittenGradingDiagnosticsService::class) : null;
    }

    public function setCacheConfig(bool $enabled, int $ttl = 86400): void
    {
        // Compatibility no-op.
    }

    public function evaluateQuestionAnswer(Question $question, string $submittedAnswer): array
    {
        $safeSubmittedAnswer = trim($submittedAnswer);

        if ($safeSubmittedAnswer === '') {
            return $this->safeErrorResult('empty_answer', 'No answer provided.');
        }

        return $this->evaluateWithRollout(
            fn () => $this->grader->evaluateQuestion($question, $safeSubmittedAnswer),
            fn () => $this->legacyEvaluator?->evaluateQuestion($question, $safeSubmittedAnswer) ?? $this->safeErrorResult('legacy_unavailable', 'Legacy evaluator unavailable.'),
            $safeSubmittedAnswer,
            $question->id ? (int) $question->id : null,
            null,
        );
    }

    public function evaluateAnswer(
        string $submittedAnswer,
        string $correctAnswer,
        array $alternativeAnswers = [],
        float $similarityThreshold = 0.0
    ): array {
        $safeSubmittedAnswer = trim($submittedAnswer);

        if ($safeSubmittedAnswer === '') {
            return $this->safeErrorResult('empty_answer', 'No answer provided.');
        }

        return $this->evaluateWithRollout(
            fn () => $this->grader->evaluateAnswer($safeSubmittedAnswer, $correctAnswer, $alternativeAnswers),
            fn () => $this->legacyEvaluator?->evaluateAnswer($safeSubmittedAnswer, $correctAnswer, $alternativeAnswers) ?? $this->safeErrorResult('legacy_unavailable', 'Legacy evaluator unavailable.'),
            $safeSubmittedAnswer,
            null,
            null,
        );
    }

    public function isCorrect(Question $question, string $submittedAnswer): bool
    {
        return $this->evaluateQuestionAnswer($question, $submittedAnswer)['isCorrect'];
    }

    public function shouldDeleteLegacyWrapper(float $legacyCorrectDisagreementRate): bool
    {
        return $this->diagnostics?->shouldDeleteLegacyWrapper($legacyCorrectDisagreementRate)
            ?? ($legacyCorrectDisagreementRate < $this->cfg('legacy_correct_disagreement_threshold', 0.03));
    }

    private function evaluateWithRollout(
        callable $newEvaluator,
        callable $oldEvaluator,
        string $submittedAnswer,
        ?int $questionId,
        ?int $userId
    ): array {
        $primary = $this->primaryGrader();
        $shadowModeEnabled = $this->shadowModeEnabled();

        $newResult = null;
        $oldResult = null;

        try {
            if ($primary === 'old') {
                $oldResult = $oldEvaluator();
                if ($shadowModeEnabled) {
                    $newResult = $this->captureSecondaryResult($newEvaluator, $questionId, $userId, $submittedAnswer, 'new');
                    $this->recordDisagreementIfNeeded($questionId, $userId, $submittedAnswer, $oldResult, $newResult);
                }

                return $oldResult;
            }

            $newResult = $newEvaluator();

            if ($shadowModeEnabled) {
                $oldResult = $this->captureSecondaryResult($oldEvaluator, $questionId, $userId, $submittedAnswer, 'old');
                $this->recordDisagreementIfNeeded($questionId, $userId, $submittedAnswer, $oldResult, $newResult);
            }

            return $newResult;
        } catch (\Throwable $e) {
            $result = $this->safeErrorResult('grader_error', 'We could not grade this written answer right now.');
            $this->recordError($questionId, $userId, $submittedAnswer, 'grader_error', [
                'primary' => $primary,
                'exception' => $e->getMessage(),
                'legacy_result' => $oldResult,
                'canonical_result' => $newResult,
            ]);

            Log::error('Written grading failed', [
                'question_id' => $questionId,
                'user_id' => $userId,
                'primary' => $primary,
                'error' => $e->getMessage(),
            ]);

            return $result;
        }
    }

    private function captureSecondaryResult(
        callable $evaluator,
        ?int $questionId,
        ?int $userId,
        string $submittedAnswer,
        string $evaluatorName
    ): ?array {
        try {
            return $evaluator();
        } catch (\Throwable $e) {
            $this->recordError($questionId, $userId, $submittedAnswer, 'grader_error', [
                'secondary' => $evaluatorName,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function recordDisagreementIfNeeded(?int $questionId, ?int $userId, string $submittedAnswer, ?array $oldResult, ?array $newResult): void
    {
        if ($oldResult === null || $newResult === null) {
            return;
        }

        if (($oldResult['isCorrect'] ?? false) === ($newResult['isCorrect'] ?? false)) {
            return;
        }

        $this->diagnostics?->recordDisagreement($questionId, $userId, $submittedAnswer, $oldResult, $newResult, [
            'primary' => $this->primaryGrader(),
        ]);
    }

    private function recordError(?int $questionId, ?int $userId, string $submittedAnswer, string $reasonCode, array $context): void
    {
        $this->diagnostics?->recordError($questionId, $userId, $submittedAnswer, $reasonCode, $context);
    }

    private function safeErrorResult(string $reasonCode, string $feedback): array
    {
        return [
            'isCorrect' => false,
            'confidence' => 0.0,
            'reasonCode' => $reasonCode,
            'feedback' => $feedback,
            'diagnostics' => [],
            'similarity' => 0.0,
            'reason' => $reasonCode,
        ];
    }

    private function primaryGrader(): string
    {
        return $this->cfgString('grader_primary', 'new') === 'old' ? 'old' : 'new';
    }

    private function shadowModeEnabled(): bool
    {
        return $this->cfgBool('shadow_mode_enabled', false);
    }

    private function cfgBool(string $key, bool $default): bool
    {
        try {
            if (function_exists('config')) {
                $value = config('written_grading.' . $key);
                if ($value !== null) {
                    return (bool) $value;
                }
            }
        } catch (\Throwable) {
            // Fall through to default.
        }

        return $default;
    }

    private function cfgString(string $key, string $default): string
    {
        try {
            if (function_exists('config')) {
                $value = config('written_grading.' . $key);
                if ($value !== null) {
                    return (string) $value;
                }
            }
        } catch (\Throwable) {
            // Fall through to default.
        }

        return $default;
    }

    private function cfg(string $key, float $default): float
    {
        try {
            if (function_exists('config')) {
                $value = config('written_grading.' . $key);
                if ($value !== null) {
                    return (float) $value;
                }
            }
        } catch (\Throwable) {
            // Fall through to default.
        }

        return $default;
    }
}
