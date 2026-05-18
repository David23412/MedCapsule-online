<?php

namespace App\Services;

use App\Models\Question;

class LegacyWrittenAnswerEvaluator
{
    public function __construct(
        private TextProcessingService $textProcessor,
        private MedicalKnowledgeService $medicalKnowledge,
    ) {
    }

    public function evaluateQuestion(Question $question, string $submittedAnswer): array
    {
        return $this->evaluateAnswer(
            $submittedAnswer,
            (string) $question->correct_answer,
            $question->alternative_answers ?? []
        );
    }

    public function evaluateAnswer(
        string $submittedAnswer,
        string $correctAnswer,
        array|string|null $alternativeAnswers = []
    ): array {
        $submittedAnswer = trim($submittedAnswer);
        $correctAnswer = trim($correctAnswer);
        $alternatives = $this->normalizeAlternativeAnswers($alternativeAnswers);

        if ($submittedAnswer === '') {
            return $this->result(false, 0.0, 'legacy_empty_answer', 'No answer provided.', ['mode' => 'empty']);
        }

        if ($correctAnswer === '') {
            return $this->result(false, 0.0, 'legacy_missing_reference_answer', 'No reference answer is available for grading.', ['mode' => 'empty']);
        }

        $candidates = array_values(array_unique(array_filter([$correctAnswer, ...$alternatives])));
        $submittedNormalized = $this->normalize($submittedAnswer);
        $submittedSorted = $this->textProcessor->sortedTokenForm($submittedAnswer);
        $best = null;

        foreach ($candidates as $candidate) {
            $candidateNormalized = $this->normalize($candidate);
            $candidateSorted = $this->textProcessor->sortedTokenForm($candidate);

            if ($submittedNormalized === $candidateNormalized) {
                return $this->result(true, 1.0, 'legacy_exact_match', 'Your answer matches the expected answer.', ['mode' => 'legacy']);
            }

            if ($submittedSorted !== '' && $submittedSorted === $candidateSorted) {
                return $this->result(true, 0.96, 'legacy_order_match', 'Your answer contains the same concepts as the expected answer.', ['mode' => 'legacy']);
            }

            $overlap = $this->textProcessor->getOrderIndependentOverlap($submittedNormalized, $candidateNormalized);
            $tokenCoverage = $this->measureCoverage($submittedNormalized, $candidateNormalized);
            $confidence = min(1.0, ($overlap * 0.7) + ($tokenCoverage * 0.3));
            $isCorrect = $confidence >= 0.80 || ($overlap >= 0.72 && $tokenCoverage >= 0.65);

            $result = $this->result(
                $isCorrect,
                $confidence,
                $isCorrect ? 'legacy_similarity_match' : 'legacy_similarity_miss',
                $isCorrect ? 'Your answer is close enough to the expected answer.' : '',
                [
                    'mode' => 'legacy',
                    'overlap' => $overlap,
                    'tokenCoverage' => $tokenCoverage,
                ]
            );

            if ($best === null || $result['confidence'] > $best['confidence']) {
                $best = $result;
            }
        }

        return $best ?? $this->result(false, 0.0, 'legacy_ungradable', 'The answer could not be graded.', ['mode' => 'legacy']);
    }

    private function normalize(string $text): string
    {
        $expanded = $text;
        foreach ($this->medicalKnowledge->getAllAbbreviations() as $abbreviation => $expansion) {
            $expanded = preg_replace('/\b' . preg_quote($abbreviation, '/') . '\b/i', $expansion, $expanded) ?? $expanded;
        }

        return trim($this->textProcessor->normalizeText($expanded));
    }

    private function measureCoverage(string $submitted, string $candidate): float
    {
        $submittedTokens = $this->tokens($submitted);
        $candidateTokens = $this->tokens($candidate);

        if (empty($candidateTokens)) {
            return 0.0;
        }

        $matches = 0;
        foreach ($candidateTokens as $candidateToken) {
            foreach ($submittedTokens as $submittedToken) {
                if (
                    $submittedToken === $candidateToken
                    || $this->textProcessor->getLevenshteinSimilarity($submittedToken, $candidateToken) >= 0.80
                ) {
                    $matches++;
                    break;
                }
            }
        }

        return $matches / count($candidateTokens);
    }

    private function tokens(string $text): array
    {
        return array_values(array_filter(
            preg_split('/\s+/', $this->textProcessor->removeFillerWords($text)) ?: [],
            fn (string $token): bool => $token !== ''
        ));
    }

    private function normalizeAlternativeAnswers(array|string|null $alternatives): array
    {
        if (is_string($alternatives)) {
            $alternatives = preg_split('/\r\n|\r|\n/', $alternatives) ?: [];
        }

        if (!is_array($alternatives)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (mixed $answer): string => trim((string) $answer),
            $alternatives
        )));
    }

    private function result(bool $isCorrect, float $confidence, string $reasonCode, string $feedback, array $diagnostics): array
    {
        return [
            'isCorrect' => $isCorrect,
            'confidence' => $confidence,
            'reasonCode' => $reasonCode,
            'feedback' => $feedback,
            'diagnostics' => $diagnostics,
            'similarity' => $confidence,
            'reason' => $reasonCode,
        ];
    }
}
