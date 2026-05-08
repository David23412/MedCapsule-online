<?php

namespace App\Services;

use App\Models\Grading\Diagnostic;
use Illuminate\Support\Facades\Log;

class WrittenGradingDiagnosticsService
{
    public function recordDisagreement(
        ?int $questionId,
        ?int $userId,
        string $submittedAnswer,
        array $legacyResult,
        array $canonicalResult,
        array $context = []
    ): void {
        $this->persist([
            'grader_version' => $this->graderVersion(),
            'question_id' => $questionId,
            'user_id' => $userId,
            'submitted_answer_hash' => hash('sha256', trim($submittedAnswer)),
            'old_result' => (bool) ($legacyResult['isCorrect'] ?? false),
            'new_result' => (bool) ($canonicalResult['isCorrect'] ?? false),
            'disagreement' => (bool) (($legacyResult['isCorrect'] ?? false) !== ($canonicalResult['isCorrect'] ?? false)),
            'reason_code' => (string) ($canonicalResult['reasonCode'] ?? $legacyResult['reasonCode'] ?? 'shadow_disagreement'),
            'diagnostics' => [
                'context' => $context,
                'legacy' => $this->compactResult($legacyResult),
                'canonical' => $this->compactResult($canonicalResult),
            ],
        ]);
    }

    public function recordError(
        ?int $questionId,
        ?int $userId,
        string $submittedAnswer,
        string $reasonCode,
        array $context = []
    ): void {
        $this->persist([
            'grader_version' => $this->graderVersion(),
            'question_id' => $questionId,
            'user_id' => $userId,
            'submitted_answer_hash' => hash('sha256', trim($submittedAnswer)),
            'old_result' => $context['old_result'] ?? null,
            'new_result' => $context['new_result'] ?? null,
            'disagreement' => (bool) ($context['disagreement'] ?? false),
            'reason_code' => $reasonCode,
            'diagnostics' => $context,
        ]);
    }

    public function shouldDeleteLegacyWrapper(float $legacyCorrectDisagreementRate): bool
    {
        return $legacyCorrectDisagreementRate < $this->cfg('legacy_correct_disagreement_threshold', 0.03);
    }

    private function persist(array $payload): void
    {
        try {
            Diagnostic::query()->create($payload);
        } catch (\Throwable $e) {
            Log::warning('Failed to persist written grading diagnostics', [
                'reason_code' => $payload['reason_code'] ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function compactResult(array $result): array
    {
        return [
            'isCorrect' => (bool) ($result['isCorrect'] ?? false),
            'confidence' => (float) ($result['confidence'] ?? 0.0),
            'reasonCode' => (string) ($result['reasonCode'] ?? 'unknown'),
            'feedback' => (string) ($result['feedback'] ?? ''),
            'diagnostics' => $result['diagnostics'] ?? [],
        ];
    }

    private function graderVersion(): string
    {
        try {
            if (function_exists('config')) {
                return (string) (config('written_grading.grader_version') ?? 'v2.0');
            }
        } catch (\Throwable) {
            // Fall through to default.
        }

        return 'v2.0';
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
