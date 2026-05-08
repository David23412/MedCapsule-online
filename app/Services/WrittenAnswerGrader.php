<?php

namespace App\Services;

use App\Models\Question;

class WrittenAnswerGrader
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
            return $this->buildResult(
                false,
                0.0,
                'empty_answer',
                'No answer provided.',
                ['mode' => 'empty']
            );
        }

        if ($correctAnswer === '') {
            return $this->buildResult(
                false,
                0.0,
                'missing_reference_answer',
                'No reference answer is available for grading.',
                ['mode' => 'empty']
            );
        }

        $candidates = array_values(array_unique(array_filter([$correctAnswer, ...$alternatives])));
        $submittedContext = $this->normalizeInput($submittedAnswer);

        $best = null;

        foreach ($candidates as $candidate) {
            $candidateContext = $this->normalizeInput($candidate);
            $rubric = $this->buildRubric($candidate, $candidateContext['normalized']);
            $result = $this->gradeCandidate($submittedAnswer, $submittedContext, $candidate, $candidateContext, $rubric);

            if ($best === null || $result['confidence'] > $best['confidence']) {
                $best = $result;
            }

            if ($result['isCorrect'] && $result['confidence'] >= 0.98) {
                return $result;
            }
        }

        return $best ?? $this->buildResult(
            false,
            0.0,
            'ungradable',
            'The answer could not be graded.',
            ['mode' => 'unknown']
        );
    }

    public function isCorrect(Question $question, string $submittedAnswer): bool
    {
        return $this->evaluateQuestion($question, $submittedAnswer)['isCorrect'];
    }

    private function gradeCandidate(
        string $submittedAnswer,
        array $submittedContext,
        string $candidateAnswer,
        array $candidateContext,
        array $rubric
    ): array {
        if ($submittedContext['normalized'] === $candidateContext['normalized']) {
            return $this->buildResult(true, 1.0, 'normalized_exact_match', 'Your answer matches the expected answer.', [
                'mode' => $rubric['mode'],
                'rubric' => $rubric,
            ]);
        }

        if ($submittedContext['sortedTokens'] !== '' && $submittedContext['sortedTokens'] === $candidateContext['sortedTokens']) {
            return $this->buildResult(true, 0.99, 'order_independent_match', 'Your answer contains the same concepts as the expected answer.', [
                'mode' => $rubric['mode'],
                'rubric' => $rubric,
            ]);
        }

        $guard = $this->applyHardGuards($submittedContext['normalized'], $candidateContext['normalized'], $rubric);
        if ($guard !== null) {
            return $guard;
        }

        return match ($rubric['mode']) {
            'numerical' => $this->gradeNumericalAnswer($submittedContext, $candidateContext, $rubric),
            'enumeration' => $this->gradeEnumerationAnswer($submittedContext, $rubric),
            'comparison' => $this->gradeComparisonAnswer($submittedContext, $rubric),
            default => $this->gradeConceptAnswer($submittedContext, $candidateContext, $rubric),
        };
    }

    private function buildRubric(string $rawAnswer, string $normalizedAnswer): array
    {
        $mode = $this->detectAnswerMode($this->normalizeForShapeDetection($rawAnswer));

        return match ($mode) {
            'numerical' => [
                'mode' => 'numerical',
                'required' => [],
                'optional' => [],
                'numbers' => $this->extractNumericalValues($rawAnswer),
                'anchor_terms' => $this->extractAnchorTerms($normalizedAnswer),
            ],
            'enumeration' => [
                'mode' => 'enumeration',
                'required' => array_map(
                    fn (string $item) => $this->buildConceptGroup($item),
                    $this->extractEnumerationItems($rawAnswer)
                ),
                'optional' => [],
                'anchor_terms' => $this->extractAnchorTerms($normalizedAnswer),
            ],
            'comparison' => $this->buildComparisonRubric($rawAnswer, $normalizedAnswer),
            default => $this->buildConceptRubric($mode, $normalizedAnswer),
        };
    }

    private function buildComparisonRubric(string $rawAnswer, string $normalizedAnswer): array
    {
        $pair = $this->extractComparisonSides($rawAnswer);

        if ($pair === null) {
            return $this->buildConceptRubric('definition', $normalizedAnswer);
        }

        return [
            'mode' => 'comparison',
            'required' => [
                $this->buildConceptGroup($pair['sideA']),
                $this->buildConceptGroup($pair['sideB']),
            ],
            'optional' => [],
            'anchor_terms' => $this->extractAnchorTerms($normalizedAnswer),
            'sides' => [$pair['sideA'], $pair['sideB']],
        ];
    }

    private function buildConceptRubric(string $mode, string $normalizedAnswer): array
    {
        $required = $this->extractRequiredConcepts($normalizedAnswer);
        $optional = $this->extractOptionalConcepts($normalizedAnswer, $required);

        return [
            'mode' => $mode,
            'required' => $required,
            'optional' => $optional,
            'anchor_terms' => $this->extractAnchorTerms($normalizedAnswer),
        ];
    }

    private function gradeConceptAnswer(array $submittedContext, array $candidateContext, array $rubric): array
    {
        $requiredCoverage = $this->measureConceptCoverage($submittedContext['normalized'], $rubric['required']);
        $optionalCoverage = $this->measureConceptCoverage($submittedContext['normalized'], $rubric['optional']);
        $orderOverlap = $this->textProcessor->getOrderIndependentOverlap(
            $submittedContext['normalized'],
            $candidateContext['normalized']
        );
        $requiredWeight = $this->cfg('required_concept_weight', 0.70);
        $overlapWeight = $this->cfg('order_overlap_weight', 0.10);
        $optionalWeight = max(0.0, 1.0 - $requiredWeight - $overlapWeight);

        $confidence = min(
            1.0,
            ($requiredCoverage * $requiredWeight)
            + ($optionalCoverage * $optionalWeight)
            + ($orderOverlap * $overlapWeight)
        );

        $isCorrect = $requiredCoverage >= $this->cfg('required_concept_threshold', 0.74)
            && $confidence >= $this->cfg('pass_threshold', 0.72);

        if (!$isCorrect && $rubric['mode'] === 'definition') {
            $isCorrect = $requiredCoverage >= 0.55 && $confidence >= 0.60;
        }

        if (!$isCorrect && $orderOverlap >= 0.70 && $requiredCoverage >= 0.45) {
            $isCorrect = true;
        }

        $missing = $this->missingConceptLabels($submittedContext['normalized'], $rubric['required']);
        $feedback = $isCorrect
            ? 'Your answer covers the required concepts.'
            : $this->buildMissingConceptFeedback($missing);

        if ($isCorrect) {
            $confidence = max($confidence, 0.75);
        }

        return $this->buildResult(
            $isCorrect,
            $confidence,
            $isCorrect ? 'concept_match' : 'missing_required_concepts',
            $feedback,
            [
                'mode' => $rubric['mode'],
                'rubric' => $rubric,
                'requiredCoverage' => $requiredCoverage,
                'optionalCoverage' => $optionalCoverage,
                'orderOverlap' => $orderOverlap,
                'missing' => $missing,
            ]
        );
    }

    private function gradeEnumerationAnswer(array $submittedContext, array $rubric): array
    {
        $coverage = $this->measureConceptCoverage($submittedContext['normalized'], $rubric['required']);
        $threshold = $this->cfg('enumeration_coverage_threshold', 0.70);
        $isCorrect = $coverage >= $threshold;
        $missing = $this->missingConceptLabels($submittedContext['normalized'], $rubric['required']);

        return $this->buildResult(
            $isCorrect,
            $coverage,
            $isCorrect ? 'enumeration_match' : 'enumeration_incomplete',
            $isCorrect
                ? 'You identified the required items.'
                : $this->buildMissingConceptFeedback($missing),
            [
                'mode' => 'enumeration',
                'rubric' => $rubric,
                'requiredCoverage' => $coverage,
                'missing' => $missing,
            ]
        );
    }

    private function gradeComparisonAnswer(array $submittedContext, array $rubric): array
    {
        $required = $rubric['required'];
        $sideScores = array_map(
            fn (array $group) => $this->measureConceptCoverage($submittedContext['normalized'], [$group]),
            $required
        );

        $threshold = $this->cfg('comparison_side_threshold', 0.70);
        $allSidesCovered = !empty($sideScores) && min($sideScores) >= $threshold;
        $confidence = !empty($sideScores) ? array_sum($sideScores) / count($sideScores) : 0.0;

        return $this->buildResult(
            $allSidesCovered,
            $confidence,
            $allSidesCovered ? 'comparison_match' : 'comparison_incomplete',
            $allSidesCovered
                ? 'Your answer addresses both sides of the comparison.'
                : 'Your answer does not fully cover both sides of the comparison.',
            [
                'mode' => 'comparison',
                'rubric' => $rubric,
                'sideScores' => $sideScores,
            ]
        );
    }

    private function gradeNumericalAnswer(array $submittedContext, array $candidateContext, array $rubric): array
    {
        $numberMatch = $this->compareNumericalValues(
            $this->extractNumericalValues($submittedContext['raw']),
            $rubric['numbers']
        );
        $anchorCoverage = $this->measureTokenSetCoverage($submittedContext['tokens'], $rubric['anchor_terms']);
        $confidence = min(1.0, ($numberMatch['score'] * 0.85) + ($anchorCoverage * 0.15));
        $isCorrect = ($numberMatch['score'] >= 0.95 && $numberMatch['total_matches'] === count($rubric['numbers']))
            || ($numberMatch['score'] >= 0.90 && $anchorCoverage >= 0.50);

        return $this->buildResult(
            $isCorrect,
            $confidence,
            $isCorrect ? 'numerical_match' : 'numerical_mismatch',
            $isCorrect
                ? 'Your numerical answer matches the expected value.'
                : 'The numerical value or unit does not match the expected answer.',
            [
                'mode' => 'numerical',
                'rubric' => $rubric,
                'numberMatch' => $numberMatch,
                'anchorCoverage' => $anchorCoverage,
                'candidateNormalized' => $candidateContext['normalized'],
            ]
        );
    }

    private function applyHardGuards(string $submitted, string $correct, array $rubric): ?array
    {
        foreach ($this->medicalKnowledge->getHardGuards() as $guardDefinition) {
            $guard = $this->applyConfiguredHardGuard($submitted, $correct, $guardDefinition);
            if ($guard !== null) {
                return $guard;
            }
        }

        if ($this->detectLocationMismatch($submitted, $correct)) {
            return $this->buildResult(
                false,
                0.20,
                'location_mismatch',
                'Your answer places the process in the wrong location.',
                ['mode' => $rubric['mode'], 'rubric' => $rubric]
            );
        }

        if ($this->hasKeyNounDivergence($submitted, $correct)) {
            return $this->buildResult(
                false,
                0.20,
                'key_noun_divergence',
                'Your answer uses a different key concept than the expected answer.',
                ['mode' => $rubric['mode'], 'rubric' => $rubric]
            );
        }

        if ($this->hasDirectionalContradiction($submitted, $correct)) {
            return $this->buildResult(
                false,
                0.20,
                'contradiction_detected',
                'Your answer contradicts the expected concept.',
                ['mode' => $rubric['mode'], 'rubric' => $rubric]
            );
        }

        return null;
    }

    private function applyConfiguredHardGuard(string $submitted, string $correct, array $guardDefinition): ?array
    {
        if (preg_match($guardDefinition['question_pattern'] ?? '//', $correct) !== 1) {
            return null;
        }

        $requiredPresence = $guardDefinition['required_presence'] ?? [];
        $forbiddenPresence = $guardDefinition['forbidden_presence'] ?? [];

        $mentionsRequired = $this->containsAllTerms($submitted, $requiredPresence);
        $mentionsForbidden = $this->containsAnyTerm($submitted, $forbiddenPresence);

        if ($mentionsForbidden && !$mentionsRequired) {
            return $this->buildResult(
                false,
                0.15,
                $guardDefinition['wrong_division_code'] ?? 'wrong_division',
                $guardDefinition['wrong_division_feedback'] ?? 'Your answer refers to the wrong division.',
                ['mode' => 'definition', 'guard' => $guardDefinition['name'] ?? null]
            );
        }

        $positiveMatched = $this->phraseGroupSatisfied($submitted, $guardDefinition['positive_phrases'] ?? []);
        $negativeMatched = $this->containsPhraseGroup($submitted, $guardDefinition['negative_phrases'] ?? []);

        if ($negativeMatched && !$positiveMatched) {
            return $this->buildResult(
                false,
                0.15,
                $guardDefinition['reversal_code'] ?? 'guard_reversal',
                $guardDefinition['reversal_feedback'] ?? 'Your answer reverses the expected effects.',
                ['mode' => 'definition', 'guard' => $guardDefinition['name'] ?? null]
            );
        }

        if ($positiveMatched && !$negativeMatched) {
            return $this->buildResult(
                true,
                0.92,
                $guardDefinition['match_code'] ?? 'guard_match',
                $guardDefinition['match_feedback'] ?? 'Your answer correctly captures the expected effects.',
                ['mode' => 'definition', 'guard' => $guardDefinition['name'] ?? null]
            );
        }

        return null;
    }

    private function normalizeInput(string $text): array
    {
        $normalized = $this->normalizeForComparison($text);

        return [
            'raw' => $text,
            'normalized' => $normalized,
            'tokens' => $this->extractMeaningfulTokens($normalized),
            'sortedTokens' => $this->textProcessor->sortedTokenForm($text),
        ];
    }

    private function normalizeForComparison(string $text): string
    {
        $text = $this->preprocessSymbols($text);
        $text = $this->expandKnownAbbreviations($text);
        $text = $this->textProcessor->normalizeText($text);

        return trim($this->normalizeSemanticPhrases($text));
    }

    private function normalizeForShapeDetection(string $text): string
    {
        $text = $this->preprocessSymbols($text);
        $text = $this->expandKnownAbbreviations($text);
        $text = mb_strtolower(trim($text));

        return preg_replace('/\s+/', ' ', $text) ?? $text;
    }

    private function preprocessSymbols(string $answer): string
    {
        $patterns = [
            '/(\d+)\/(\d+)/' => 'systolic $1 diastolic $2',
            '/HR\s*=?\s*(\d+)/i' => 'heart rate $1',
            '/(\d+[\.,]?\d*)\s*mmol\/L/i' => '$1 millimoles per liter',
            '/(\d+[\.,]?\d*)\s*mg\/dL/i' => '$1 milligrams per deciliter',
            '/α/' => 'alpha',
            '/β/' => 'beta',
            '/γ/' => 'gamma',
            '/δ/' => 'delta',
            '/μ/' => 'mu',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $answer = preg_replace($pattern, $replacement, $answer) ?? $answer;
        }

        return $answer;
    }

    private function expandKnownAbbreviations(string $text): string
    {
        $abbreviations = $this->medicalKnowledge->getAllAbbreviations();
        uksort($abbreviations, fn (string $a, string $b) => strlen($b) <=> strlen($a));

        foreach ($abbreviations as $abbreviation => $expansion) {
            $text = preg_replace('/\b' . preg_quote($abbreviation, '/') . '\b/i', $expansion, $text) ?? $text;
        }

        return $text;
    }

    private function detectAnswerMode(string $answer): string
    {
        if ($this->isNumericalValueAnswer($answer)) {
            return 'numerical';
        }

        if ($this->isEnumerationAnswer($answer)) {
            return 'enumeration';
        }

        if ($this->isComparisonAnswer($answer)) {
            return 'comparison';
        }

        if ($this->isDefinitionStyle($answer)) {
            return 'definition';
        }

        return 'exact';
    }

    private function isComparisonAnswer(string $answer): bool
    {
        $markers = [' vs ', ' vs. ', ' versus ', ' compared to ', ' unlike ', ' whereas ', ' while ', ' in contrast '];
        $lower = strtolower($answer);

        foreach ($markers as $marker) {
            if (str_contains($lower, $marker)) {
                return true;
            }
        }

        return false;
    }

    private function isDefinitionStyle(string $answer): bool
    {
        return (bool) preg_match('/\b(is|are|refers to|means|defined as|consists of|composed of)\b/i', $answer);
    }

    private function isEnumerationAnswer(string $answer): bool
    {
        if (preg_match('/\d[\)\.]\s+.+\d[\)\.]\s+/s', $answer) === 1) {
            return true;
        }

        if (preg_match('/^[-*•]\s+.+[-*•]\s+/m', $answer) === 1) {
            return true;
        }

        if (substr_count($answer, ',') < 2 || strlen($answer) >= 300) {
            return false;
        }

        $parts = array_values(array_filter(array_map('trim', preg_split('/,\s*/', $answer) ?: [])));
        if (count($parts) < 3) {
            return false;
        }

        $wordCounts = array_map(function (string $part): int {
            return count(array_filter(preg_split('/\s+/', $part) ?: []));
        }, $parts);

        $averageWords = array_sum($wordCounts) / max(count($wordCounts), 1);

        return $averageWords >= $this->cfg('enumeration_min_average_item_words', 3.0);
    }

    private function isNumericalValueAnswer(string $answer): bool
    {
        $patterns = [
            '/\d+\s*\/\s*\d+/',
            '/\d+[\.,]\d+/',
            '/\d+\s*(mmhg|bpm|kg|g|mg|ml|l|%|mmol\/l|mg\/dl)\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $answer) === 1) {
                return true;
            }
        }

        return false;
    }

    private function extractComparisonSides(string $answer): ?array
    {
        $separators = [
            'compared to', 'in contrast to', 'on the other hand', 'versus',
            'whereas', 'however', 'while', 'although', 'unlike', ' vs. ', ' vs ',
        ];

        $lower = strtolower($answer);
        foreach ($separators as $separator) {
            $position = strpos($lower, $separator);
            if ($position === false) {
                continue;
            }

            $sideA = trim(substr($answer, 0, $position));
            $sideB = trim(substr($answer, $position + strlen($separator)));

            if ($sideA !== '' && $sideB !== '') {
                return ['sideA' => $sideA, 'sideB' => $sideB];
            }
        }

        return null;
    }

    private function extractRequiredConcepts(string $normalizedAnswer): array
    {
        $tokens = $this->extractMeaningfulTokens($normalizedAnswer);
        if (count($tokens) < (int) $this->cfg('short_concept_group_token_limit', 6)) {
            return !empty($tokens)
                ? [['label' => $normalizedAnswer, 'tokens' => $tokens]]
                : [];
        }

        $segments = preg_split('/,|;/', $normalizedAnswer) ?: [];
        $concepts = [];

        foreach ($segments as $segment) {
            $group = $this->buildConceptGroup($segment);
            if (!empty($group['tokens'])) {
                $concepts[] = $group;
            }
        }

        if (!empty($concepts)) {
            return $this->deduplicateConceptGroups($concepts);
        }

        return !empty($tokens)
            ? [['label' => implode(' ', $tokens), 'tokens' => $tokens]]
            : [];
    }

    private function extractOptionalConcepts(string $normalizedAnswer, array $required): array
    {
        $requiredTokens = [];
        foreach ($required as $group) {
            foreach ($group['tokens'] as $token) {
                $requiredTokens[$token] = true;
            }
        }

        $optionalDescriptors = array_flip($this->medicalKnowledge->getOptionalDescriptors());
        $optionalTokens = array_values(array_filter(
            $this->extractMeaningfulTokens($normalizedAnswer),
            fn (string $token) => !isset($requiredTokens[$token]) && !isset($optionalDescriptors[$token])
        ));

        return !empty($optionalTokens)
            ? [['label' => implode(' ', $optionalTokens), 'tokens' => $optionalTokens]]
            : [];
    }

    private function buildConceptGroup(string $text): array
    {
        $normalized = $this->normalizeSemanticPhrases($this->textProcessor->normalizeText($text));
        $tokens = $this->extractMeaningfulTokens($normalized);

        return [
            'label' => trim($text),
            'tokens' => $tokens,
        ];
    }

    private function deduplicateConceptGroups(array $groups): array
    {
        $seen = [];
        $deduped = [];

        foreach ($groups as $group) {
            $key = implode('|', $group['tokens']);
            if ($key === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $deduped[] = $group;
        }

        return $deduped;
    }

    private function measureConceptCoverage(string $submittedNormalized, array $conceptGroups): float
    {
        if (empty($conceptGroups)) {
            return 1.0;
        }

        $matched = 0;
        foreach ($conceptGroups as $group) {
            if ($this->matchesConceptGroup($submittedNormalized, $group)) {
                $matched++;
            }
        }

        return $matched / count($conceptGroups);
    }

    private function matchesConceptGroup(string $submittedNormalized, array $group): bool
    {
        $tokens = $group['tokens'] ?? [];
        if (empty($tokens)) {
            return false;
        }

        $submittedTokens = $this->extractMeaningfulTokens($submittedNormalized);
        $matched = 0;

        foreach ($tokens as $token) {
            foreach ($submittedTokens as $submittedToken) {
                if ($submittedToken === $token || $this->wordsAreSimilar($submittedToken, $token)) {
                    $matched++;
                    break;
                }
            }
        }

        $requiredRatio = match (count($tokens)) {
            1 => 1.0,
            2 => 0.75,
            default => 0.6,
        };

        return ($matched / count($tokens)) >= $requiredRatio;
    }

    private function missingConceptLabels(string $submittedNormalized, array $groups): array
    {
        $missing = [];

        foreach ($groups as $group) {
            if (!$this->matchesConceptGroup($submittedNormalized, $group)) {
                $missing[] = $group['label'];
            }
        }

        return $missing;
    }

    private function buildMissingConceptFeedback(array $missing): string
    {
        if (empty($missing)) {
            return 'Your answer does not contain enough of the required concepts.';
        }

        return 'Your answer is missing or incomplete on: ' . implode(', ', array_slice($missing, 0, 3)) . '.';
    }

    private function measureTokenSetCoverage(array $submittedTokens, array $requiredTokens): float
    {
        if (empty($requiredTokens)) {
            return 1.0;
        }

        $matched = 0;
        foreach ($requiredTokens as $token) {
            foreach ($submittedTokens as $submittedToken) {
                if ($submittedToken === $token || $this->wordsAreSimilar($submittedToken, $token)) {
                    $matched++;
                    break;
                }
            }
        }

        return $matched / count($requiredTokens);
    }

    private function extractMeaningfulTokens(string $text): array
    {
        $filtered = $this->textProcessor->removeFillerWords($text);
        $tokens = preg_split('/\s+/', trim($filtered)) ?: [];
        $optionalDescriptors = array_flip($this->medicalKnowledge->getOptionalDescriptors());

        return array_values(array_unique(array_filter($tokens, function (string $token) use ($optionalDescriptors): bool {
            if ($token === '') {
                return false;
            }

            if (isset($optionalDescriptors[$token])) {
                return false;
            }

            if (preg_match('/\d/', $token)) {
                return true;
            }

            return mb_strlen($token) >= 3;
        })));
    }

    private function extractAnchorTerms(string $normalizedAnswer): array
    {
        $highPriorityGroups = $this->medicalKnowledge->getHighPriorityConceptGroups();
        $tokens = $this->extractMeaningfulTokens($normalizedAnswer);
        $anchors = [];

        foreach ($tokens as $token) {
            foreach ($highPriorityGroups as $group) {
                if (in_array($token, $group, true)) {
                    $anchors[] = $token;
                    continue 2;
                }
            }

            if (mb_strlen($token) >= 6) {
                $anchors[] = $token;
            }
        }

        return array_values(array_unique($anchors));
    }

    private function detectLocationMismatch(string $submitted, string $correct): bool
    {
        foreach ($this->medicalKnowledge->getProcessLocationMismatchRules() as $process => $locations) {
            if (!str_contains($correct, $process) || !str_contains($submitted, $process)) {
                continue;
            }

            foreach ($locations as $wrongLocation) {
                if (str_contains($submitted, $wrongLocation)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasKeyNounDivergence(string $submitted, string $correct): bool
    {
        $keyNouns = [
            'heart', 'liver', 'lung', 'kidney', 'brain', 'stomach', 'spleen', 'pancreas',
            'intestine', 'bladder', 'esophagus', 'gallbladder', 'uterus', 'ovary', 'testis',
            'cardiovascular', 'respiratory', 'nervous', 'digestive', 'endocrine', 'urinary',
            'immune', 'musculoskeletal', 'integumentary', 'lymphatic', 'reproductive',
            'digestion', 'gas exchange', 'filtration', 'absorption', 'secretion',
            'excretion', 'respiration', 'circulation', 'immunity', 'metabolism',
            'epithelial', 'connective', 'muscle', 'nervous tissue',
        ];

        $submittedNouns = [];
        $correctNouns = [];

        foreach ($keyNouns as $noun) {
            if (str_contains($submitted, $noun)) {
                $submittedNouns[] = $noun;
            }
            if (str_contains($correct, $noun)) {
                $correctNouns[] = $noun;
            }
        }

        if (empty($submittedNouns) || empty($correctNouns)) {
            return false;
        }

        return !empty(array_diff($submittedNouns, $correctNouns))
            && !empty(array_diff($correctNouns, $submittedNouns));
    }

    private function containsAnyPhrase(string $text, array $phrases): bool
    {
        foreach ($phrases as $phrase) {
            if (str_contains($text, $phrase)) {
                return true;
            }
        }

        return false;
    }

    private function hasDirectionalContradiction(string $submitted, string $correct): bool
    {
        $pairs = [
            ['increase', 'decrease'],
            ['increases', 'decreases'],
            ['high', 'low'],
            ['sympathetic', 'parasympathetic'],
            ['anterior', 'posterior'],
            ['superior', 'inferior'],
        ];

        foreach ($pairs as [$left, $right]) {
            $submittedHasLeft = str_contains($submitted, $left);
            $submittedHasRight = str_contains($submitted, $right);
            $correctHasLeft = str_contains($correct, $left);
            $correctHasRight = str_contains($correct, $right);

            $submittedMixed = $submittedHasLeft && $submittedHasRight;
            $correctMixed = $correctHasLeft && $correctHasRight;

            if (($submittedMixed || $correctMixed)) {
                continue;
            }

            if (($submittedHasLeft && $correctHasRight) || ($submittedHasRight && $correctHasLeft)) {
                return true;
            }
        }

        return false;
    }

    private function wordsAreSimilar(string $left, string $right): bool
    {
        if ($left === $right) {
            return true;
        }

        foreach ($this->safeEquivalentTokenGroups() as $group) {
            if (in_array($left, $group, true) && in_array($right, $group, true)) {
                return true;
            }
        }

        return $this->textProcessor->getLevenshteinSimilarity($left, $right)
            >= $this->cfg('typo_similarity_threshold', 0.80);
    }

    private function normalizeSemanticPhrases(string $text): string
    {
        $replacements = [
            'greater in number' => 'much greater',
            'larger in number' => 'much greater',
            'more numerous' => 'much greater',
            'increased number' => 'much greater',
            'move freely' => 'free to move',
            'moves freely' => 'free to move',
            'freely move' => 'free to move',
            'free movement' => 'free to move',
            'infectivity is persistent' => 'infectivity may persist',
            'infectivity persists' => 'infectivity may persist',
            'persistent infectivity' => 'infectivity may persist',
        ];

        foreach ($replacements as $from => $to) {
            $text = str_replace($from, $to, $text);
        }

        return $text;
    }

    private function containsAllTerms(string $text, array $terms): bool
    {
        foreach ($terms as $term) {
            if (!$this->containsWholeTerm($text, $term)) {
                return false;
            }
        }

        return !empty($terms);
    }

    private function containsAnyTerm(string $text, array $terms): bool
    {
        foreach ($terms as $term) {
            if ($this->containsWholeTerm($text, $term)) {
                return true;
            }
        }

        return false;
    }

    private function phraseGroupSatisfied(string $text, array $phraseGroups): bool
    {
        if (empty($phraseGroups)) {
            return false;
        }

        foreach ($phraseGroups as $phrases) {
            if (!$this->containsAnyPhrase($text, $phrases)) {
                return false;
            }
        }

        return true;
    }

    private function containsPhraseGroup(string $text, array $phraseGroups): bool
    {
        foreach ($phraseGroups as $phrases) {
            if ($this->containsAnyPhrase($text, $phrases)) {
                return true;
            }
        }

        return false;
    }

    private function containsWholeTerm(string $text, string $term): bool
    {
        return preg_match('/\b' . preg_quote($term, '/') . '\b/i', $text) === 1;
    }

    private function extractEnumerationItems(string $text): array
    {
        $items = [];

        if (preg_match_all('/\d[\)\.]\s+([^0-9\n]+)/', $text, $matches)) {
            $items = array_map('trim', $matches[1]);
        } elseif (preg_match_all('/^[-*•]\s+(.+)$/m', $text, $matches)) {
            $items = array_map('trim', $matches[1]);
        } else {
            $parts = preg_split('/,\s*|\band\b|\bor\b/i', $text) ?: [];
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $items[] = $part;
                }
            }
        }

        return array_values(array_map(function (string $item): string {
            $item = trim(preg_replace('/\s*\(.*?\)\s*/', ' ', $item) ?? $item);
            $item = preg_replace(
                '/^.+?\b(?:includes?|contain(?:s)?|consists?\s+of|comprises?|are|is)\b\s+/i',
                '',
                $item
            ) ?? $item;

            return rtrim($item, ',.;');
        }, array_filter($items, fn (string $item): bool => strlen(trim($item)) > 1)));
    }

    private function extractNumericalValues(string $text): array
    {
        $values = [];

        if (preg_match_all('/(\d+[\.,]\d+)\s*[\-\–]\s*(\d+[\.,]\d+)/', $text, $matches)) {
            foreach ($matches[0] as $index => $match) {
                $values[] = [
                    'type' => 'range',
                    'min' => (float) str_replace(',', '.', $matches[1][$index]),
                    'max' => (float) str_replace(',', '.', $matches[2][$index]),
                    'text' => $match,
                    'unit' => $this->extractUnit($match),
                ];
            }
        }

        if (preg_match_all('/(\d+(?:[\.,]\d+)?)\s*([a-zA-Zµ°\/]+)?/', $text, $matches)) {
            foreach ($matches[0] as $index => $match) {
                $alreadyCaptured = false;
                foreach ($values as $value) {
                    if (str_contains($value['text'], $match)) {
                        $alreadyCaptured = true;
                        break;
                    }
                }

                if ($alreadyCaptured) {
                    continue;
                }

                $values[] = [
                    'type' => 'single',
                    'value' => (float) str_replace(',', '.', $matches[1][$index]),
                    'text' => $match,
                    'unit' => $this->normalizeUnit(trim($matches[2][$index] ?? '')),
                ];
            }
        }

        return $values;
    }

    private function compareNumericalValues(array $submittedValues, array $correctValues): array
    {
        if (empty($correctValues)) {
            return ['score' => 0.0, 'matches' => [], 'total_matches' => 0];
        }

        if (empty($submittedValues)) {
            return ['score' => 0.0, 'matches' => [], 'total_matches' => 0];
        }

        $matches = [];
        $totalScore = 0.0;

        foreach ($correctValues as $correctValue) {
            $best = ['score' => 0.0, 'submitted' => null];

            foreach ($submittedValues as $submittedValue) {
                $score = $this->calculateNumericalSimilarity($correctValue, $submittedValue);
                if ($score > $best['score']) {
                    $best = ['score' => $score, 'submitted' => $submittedValue];
                }
            }

            if ($best['score'] > 0.7) {
                $matches[] = $best;
                $totalScore += $best['score'];
            }
        }

        return [
            'score' => count($matches) > 0 ? $totalScore / count($matches) : 0.0,
            'matches' => $matches,
            'total_matches' => count($matches),
        ];
    }

    private function calculateNumericalSimilarity(array $reference, array $candidate): float
    {
        $unitBonus = ($reference['unit'] !== '' && $reference['unit'] === $candidate['unit']) ? 0.05 : 0.0;

        if ($reference['type'] === 'single' && $candidate['type'] === 'single') {
            $difference = abs($reference['value'] - $candidate['value']);
            $baseline = max(abs((float) $reference['value']), 1.0);
            $relativeDifference = $difference / $baseline;

            if ($relativeDifference <= 0.01) {
                return min(1.0, 1.0 + $unitBonus);
            }
            if ($relativeDifference <= 0.03) {
                return min(1.0, 0.9 + $unitBonus);
            }
            if ($relativeDifference <= 0.05) {
                return min(1.0, 0.75 + $unitBonus);
            }

            return $unitBonus;
        }

        if ($reference['type'] === 'range' && $candidate['type'] === 'range') {
            $overlap = min($reference['max'], $candidate['max']) - max($reference['min'], $candidate['min']);
            $totalSpan = max($reference['max'], $candidate['max']) - min($reference['min'], $candidate['min']);

            return $totalSpan > 0 ? min(1.0, max(0.0, $overlap / $totalSpan) + $unitBonus) : $unitBonus;
        }

        if ($reference['type'] === 'range' && $candidate['type'] === 'single') {
            if ($candidate['value'] >= $reference['min'] && $candidate['value'] <= $reference['max']) {
                return min(1.0, 0.9 + $unitBonus);
            }
        }

        return $unitBonus;
    }

    private function extractUnit(string $text): string
    {
        return preg_match('/[\d\.\-\–]+\s*(.+)$/', $text, $matches) === 1
            ? trim($matches[1])
            : '';
    }

    private function normalizeUnit(string $unit): string
    {
        $units = [
            'g/dl' => 'g/dl',
            'mg/dl' => 'mg/dl',
            'mmol/l' => 'mmol/l',
            'mmhg' => 'mmhg',
            '°c' => 'celsius',
            '°f' => 'fahrenheit',
        ];

        $unit = strtolower(trim($unit));

        return $units[$unit] ?? $unit;
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

    private function buildResult(bool $isCorrect, float $confidence, string $reasonCode, string $feedback, array $diagnostics): array
    {
        return [
            'isCorrect' => $isCorrect,
            'confidence' => $confidence,
            'reasonCode' => $reasonCode,
            'feedback' => $feedback,
            'diagnostics' => $diagnostics,
            // Backward-compatible aliases for existing callers/tests.
            'similarity' => $confidence,
            'reason' => $reasonCode,
        ];
    }

    private function safeEquivalentTokenGroups(): array
    {
        return [
            ['pump', 'pumps', 'circulate', 'circulates', 'propel', 'propels'],
            ['organ', 'structure'],
            ['increase', 'increases', 'raise', 'raises', 'elevate', 'elevates'],
            ['decrease', 'decreases', 'lower', 'lowers', 'reduce', 'reduces'],
            ['through', 'throughout'],
            ['renal', 'kidney'],
        ];
    }

    private function cfg(string $key, float|int $default): float
    {
        try {
            if (function_exists('config')) {
                $value = config('written_grading.' . $key);
                if ($value !== null) {
                    return (float) $value;
                }
            }
        } catch (\Throwable) {
            // Fall back to defaults for plain PHPUnit tests without a Laravel container.
        }

        return (float) $default;
    }
}
