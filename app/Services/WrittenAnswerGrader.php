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
            $question->alternative_answers ?? [],
            $question
        );
    }

    public function evaluateAnswer(
        string $submittedAnswer,
        string $correctAnswer,
        array|string|null $alternativeAnswers = [],
        ?Question $question = null
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

        $candidates = array_values(array_unique(array_filter([$correctAnswer, ...$alternatives], fn (string $answer): bool => $answer !== '')));
        $submittedContext = $this->normalizeInput($submittedAnswer);

        $best = null;

        foreach ($candidates as $candidate) {
            $candidateContext = $this->normalizeInput($candidate);
            $rubric = $this->buildRubric($candidate, $candidateContext['normalized'], $question);
            $result = $this->gradeCandidate($submittedAnswer, $submittedContext, $candidate, $candidateContext, $rubric);

            if ($best === null
                || ($result['isCorrect'] && !$best['isCorrect'])
                || ($result['isCorrect'] === $best['isCorrect'] && $result['confidence'] > $best['confidence'])
            ) {
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
        $assertedAnswer = $this->extractAssertedContrast($submittedAnswer, $candidateContext);
        if ($assertedAnswer !== null) {
            $submittedAnswer = $assertedAnswer;
            $submittedContext = $this->normalizeInput($assertedAnswer);
        }

        if (preg_match('/[\p{L}\p{N}]/u', $submittedContext['normalized']) !== 1
            || preg_match('/[\p{L}\p{N}]/u', $candidateContext['normalized']) !== 1
        ) {
            return $this->buildResult(false, 0.0, 'empty_answer', 'No meaningful answer provided.', ['mode' => $rubric['mode']]);
        }

        if ($submittedContext['normalized'] === $candidateContext['normalized']) {
            return $this->buildResult(true, 1.0, 'normalized_exact_match', 'Your answer matches the expected answer.', [
                'mode' => $rubric['mode'],
                'rubric' => $rubric,
            ]);
        }

        $guard = $this->applyHardGuards($submittedContext['normalized'], $candidateContext['normalized'], $rubric);
        if ($guard !== null) {
            return $guard;
        }

        if ($submittedContext['sortedTokens'] !== '' && $submittedContext['sortedTokens'] === $candidateContext['sortedTokens']) {
            return $this->buildResult(true, 0.99, 'order_independent_match', 'Your answer contains the same concepts as the expected answer.', [
                'mode' => $rubric['mode'],
                'rubric' => $rubric,
            ]);
        }

        if (!in_array($rubric['mode'], ['numerical', 'comparison'], true)) {
            $dynamicAcceptance = $this->gradeDynamicAcceptableAnswer(
                $submittedAnswer,
                $submittedContext,
                $candidateAnswer,
                $candidateContext,
                $rubric
            );
            if ($dynamicAcceptance !== null) {
                return $dynamicAcceptance;
            }
        }

        return match ($rubric['mode']) {
            'numerical' => $this->gradeNumericalAnswer($submittedContext, $candidateContext, $rubric),
            'enumeration' => $this->gradeEnumerationAnswer($submittedContext, $rubric),
            'comparison' => $this->gradeComparisonAnswer($submittedContext, $rubric),
            default => $this->gradeConceptAnswer($submittedContext, $candidateContext, $rubric),
        };
    }

    private function buildRubric(string $rawAnswer, string $normalizedAnswer, ?Question $question = null): array
    {
        $questionText = trim((string) ($question?->question ?? ''));
        $promptMode = $this->detectPromptMode($questionText);
        $requiredItemCount = $this->inferRequiredItemCount($questionText);
        $blankCount = $this->countAnswerBlanks($questionText);
        $mode = $this->detectAnswerMode($this->normalizeForShapeDetection($rawAnswer));

        if ($promptMode === 'list') {
            $mode = 'enumeration';
        }

        $rubric = match ($mode) {
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
            default => $this->buildConceptRubric($mode, $normalizedAnswer, $rawAnswer),
        };

        $rubric['prompt_mode'] = $promptMode;
        $rubric['question_text'] = $questionText;
        $rubric['required_item_count'] = $requiredItemCount;
        $rubric['blank_count'] = $blankCount;
        $rubric['raw_answer'] = $rawAnswer;

        return $rubric;
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

    private function buildConceptRubric(string $mode, string $normalizedAnswer, ?string $rawAnswer = null): array
    {
        $required = $this->extractRequiredConcepts($normalizedAnswer, $rawAnswer);
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

        if (!$isCorrect && $orderOverlap >= 0.75 && $requiredCoverage >= 0.74) {
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

    private function gradeDynamicAcceptableAnswer(
        string $submittedAnswer,
        array $submittedContext,
        string $candidateAnswer,
        array $candidateContext,
        array $rubric
    ): ?array {
        $submittedTokens = $submittedContext['tokens'];
        $candidateTokens = $candidateContext['tokens'];

        if (empty($submittedTokens) || empty($candidateTokens)) {
            return null;
        }

        $coverage = $this->measureBidirectionalTokenCoverage($submittedTokens, $candidateTokens);
        $promptMode = (string) ($rubric['prompt_mode'] ?? 'general');
        $requiredItemCount = $rubric['required_item_count'] ?? null;
        $blankCount = (int) ($rubric['blank_count'] ?? 0);
        $correctItems = $this->extractAcceptableAnswerItems($candidateAnswer, true);
        $submittedItems = $this->extractAcceptableAnswerItems($submittedAnswer, false);
        if ($promptMode === 'list' && count($correctItems) > 1
            && !array_filter($correctItems, fn (string $item): bool => preg_match('/\band\b/i', $item) === 1)
            && max(array_map(fn (string $item): int => count($this->extractMeaningfulTokens($item)), $correctItems)) <= 2
        ) {
            $submittedItems = $this->extractAcceptableAnswerItems(
                preg_replace('/\s+and\s+/i', '; ', $submittedAnswer) ?? $submittedAnswer, false
            );
        }
        $itemMatch = $this->matchAnswerItems($submittedItems, $correctItems);
        $correctLooksLikeList = $this->looksLikeListReference($candidateAnswer, $correctItems);
        $hasReferenceAlternatives = $this->hasReferenceAlternativeMarkup($candidateAnswer);

        $diagnostics = [
            'mode' => 'dynamic',
            'promptMode' => $promptMode,
            'requiredItemCount' => $requiredItemCount,
            'blankCount' => $blankCount,
            'tokenCoverage' => $coverage,
            'itemMatch' => $itemMatch,
            'correctItemCount' => count($correctItems),
            'submittedItemCount' => count($submittedItems),
        ];

        if (($promptMode === 'list' || ($requiredItemCount !== null && $blankCount > 1)) && !empty($correctItems)) {
            $required = $requiredItemCount ?? max(1, (int) ceil(count($correctItems) * $this->cfg('enumeration_coverage_threshold', 0.70)));
            $required = max(1, min((int) $required, count($correctItems)));

            if ($itemMatch['matched'] >= $required) {
                return $this->buildResult(
                    true,
                    max(0.86, min(0.98, $itemMatch['score'])),
                    $promptMode === 'list' ? 'prompt_required_items_match' : 'prompt_blank_items_match',
                    'Your answer includes the required item(s).',
                    $diagnostics
                );
            }

            $nearRequired = max(1, $required - 1);
            if ($required > 1 && $itemMatch['matched'] >= $nearRequired
                && $itemMatch['matched'] / $required >= $this->cfg('minimum_requested_item_coverage', 0.75)
                && $coverage['precision'] >= 0.72) {
                return $this->buildResult(
                    true,
                    max(0.74, min(0.88, ($itemMatch['score'] + $coverage['precision']) / 2)),
                    'prompt_most_required_items_match',
                    'Your answer covers enough of the required concepts.',
                    $diagnostics
                );
            }

            if ($requiredItemCount !== null) {
                return $this->buildResult(false, min(0.69, $itemMatch['matched'] / $required),
                    'required_items_missing',
                    "This question asks for {$required} distinct items; your answer matches {$itemMatch['matched']}.",
                    $diagnostics);
            }
        }

        if ($promptMode === 'blank' || $blankCount > 0) {
            $required = $requiredItemCount ?? max(1, $blankCount);

            if ($required <= 1 && $itemMatch['matched'] >= 1) {
                return $this->buildResult(
                    true,
                    max(0.82, min(0.95, $itemMatch['score'])),
                    'blank_short_answer_match',
                    'Your answer matches the expected blank.',
                    $diagnostics
                );
            }

            if ($required <= 1 && $this->submittedIsAcceptableShortAnswer($coverage, $submittedTokens, $candidateTokens, $candidateAnswer)) {
                return $this->buildResult(
                    true,
                    max(0.82, min(0.95, ($coverage['precision'] * 0.65) + ($coverage['recall'] * 0.35))),
                    'blank_short_answer_match',
                    'Your answer matches the expected blank.',
                    $diagnostics
                );
            }
        }

        if ($promptMode !== 'list'
            && $hasReferenceAlternatives
            && $this->submittedIsAcceptableShortAnswer($coverage, $submittedTokens, $candidateTokens, $candidateAnswer)
        ) {
            return $this->buildResult(
                true,
                max(0.80, min(0.94, ($coverage['precision'] * 0.70) + ($coverage['recall'] * 0.30))),
                'reference_alternative_match',
                'Your answer matches an acceptable form of the reference answer.',
                $diagnostics
            );
        }

        if (!$correctLooksLikeList && count($submittedTokens) >= 2) {
            if ($coverage['precision'] >= 0.90 && $coverage['recall'] >= 0.50) {
                return $this->buildResult(
                    true,
                    max(0.78, min(0.93, ($coverage['precision'] * 0.55) + ($coverage['recall'] * 0.45))),
                    'submitted_concepts_covered',
                    'Your answer covers the central expected concept.',
                    $diagnostics
                );
            }

            if ($coverage['precision'] >= 0.80 && $coverage['recall'] >= 0.70) {
                return $this->buildResult(
                    true,
                    max(0.76, min(0.92, $coverage['f1'])),
                    'strong_concept_overlap',
                    'Your answer covers the required concepts.',
                    $diagnostics
                );
            }
        }

        return null;
    }

    private function submittedIsAcceptableShortAnswer(array $coverage, array $submittedTokens, array $candidateTokens, string $candidateAnswer): bool
    {
        if (empty($submittedTokens)) {
            return false;
        }

        if ($coverage['precision'] < 0.88) {
            return false;
        }

        if ($coverage['recall'] >= 0.50) {
            return true;
        }

        if (count($submittedTokens) >= 2 && $this->hasReferenceAlternativeMarkup($candidateAnswer)) {
            return true;
        }

        return count($submittedTokens) === 1
            && count($candidateTokens) <= 3
            && ($coverage['bestScore'] ?? 0.0) >= 0.92;
    }

    private function measureBidirectionalTokenCoverage(array $submittedTokens, array $candidateTokens): array
    {
        $matches = $this->matchTokenLists($submittedTokens, $candidateTokens);
        $precision = count($submittedTokens) > 0 ? $matches['matched'] / count($submittedTokens) : 0.0;
        $recall = count($candidateTokens) > 0 ? $matches['matched'] / count($candidateTokens) : 0.0;
        $f1 = ($precision + $recall) > 0 ? (2 * $precision * $recall) / ($precision + $recall) : 0.0;

        return [
            'precision' => $precision,
            'recall' => $recall,
            'f1' => $f1,
            'matchedSubmittedTokens' => $matches['matched'],
            'matchedCandidateTokens' => $matches['matched'],
            'bestScore' => $matches['bestScore'],
        ];
    }

    private function matchTokenLists(array $leftTokens, array $rightTokens): array
    {
        $edges = [];
        $bestScore = 0.0;
        foreach ($leftTokens as $leftIndex => $left) {
            foreach ($rightTokens as $rightIndex => $right) {
                $score = $this->dynamicTokenSimilarity($left, $right);
                $bestScore = max($bestScore, $score);
                if ($score >= $this->dynamicTokenMatchThreshold($left, $right)) {
                    $edges[$leftIndex][] = $rightIndex;
                }
            }
        }

        // Find a one-to-one assignment. A greedy match can consume the only
        // option for a later token and make the grade depend on word order.
        $owners = [];
        $assign = function (int $leftIndex, array &$visited) use (&$assign, &$owners, $edges): bool {
            foreach ($edges[$leftIndex] ?? [] as $rightIndex) {
                if (isset($visited[$rightIndex])) {
                    continue;
                }
                $visited[$rightIndex] = true;
                if (!isset($owners[$rightIndex]) || $assign($owners[$rightIndex], $visited)) {
                    $owners[$rightIndex] = $leftIndex;
                    return true;
                }
            }
            return false;
        };
        foreach (array_keys($leftTokens) as $leftIndex) {
            $visited = [];
            $assign($leftIndex, $visited);
        }

        return ['matched' => count($owners), 'bestScore' => $bestScore];
    }

    private function dynamicTokenSimilarity(string $left, string $right): float
    {
        if ($left === $right) {
            return 1.0;
        }

        foreach ($this->safeEquivalentTokenGroups() as $group) {
            if (in_array($left, $group, true) && in_array($right, $group, true)) {
                return 0.96;
            }
        }

        // Tokens are already normalized. Avoid running the full medical dictionary
        // for every pair, and count a single adjacent letter swap as one typo.
        $length = max(strlen($left), strlen($right));
        $distance = levenshtein($left, $right);
        if ($distance === 1 && strlen($left) !== strlen($right) && min(strlen($left), strlen($right)) >= 4) {
            return 0.90;
        }
        if (strlen($left) === strlen($right) && $distance === 2) {
            $different = [];
            for ($i = 0; $i < $length; $i++) {
                if ($left[$i] !== $right[$i]) {
                    $different[] = $i;
                }
            }
            if (count($different) === 2 && $different[1] === $different[0] + 1
                && $left[$different[0]] === $right[$different[1]]
                && $left[$different[1]] === $right[$different[0]]
            ) {
                return 0.95;
            }
        }

        return $length > 0 ? 1.0 - ($distance / $length) : 0.0;
    }

    private function dynamicTokenMatchThreshold(string $left, string $right): float
    {
        if (preg_match('/\d/', $left . $right)) {
            return 1.0;
        }

        $shortest = min(mb_strlen($left), mb_strlen($right));

        if ($shortest <= 4) {
            return 0.90;
        }

        if ($shortest === 5) {
            return 0.86;
        }

        return $this->cfg('typo_similarity_threshold', 0.80);
    }

    private function extractAcceptableAnswerItems(string $text, bool $reference): array
    {
        $clean = $this->stripReferenceNoise($text);

        // Parenthetical wording qualifies the surrounding list item; treating it
        // as a separate item can incorrectly increase the number of required
        // concepts (for example, "mouth widely open (obtuse angle)").
        $clean = str_replace(['(', ')'], ' ', $clean);
        $clean = preg_replace('/\b\d+\s*[\.)]\s*/', '; ', $clean) ?? $clean;
        if ($reference) {
            $clean = preg_replace('/\s*\/\s*/', '; ', $clean) ?? $clean;
        }
        $clean = str_replace(["\r\n", "\r", "\n"], '; ', $clean);

        if (!$reference) {
            $clean = preg_replace('/(?<=[A-Za-z])\.\s+(?=[A-Za-z0-9])/', '; ', $clean) ?? $clean;
        }

        $parts = preg_split('/\s*;\s*|\s*,\s*/', $clean) ?: [];

        if ($reference && count($parts) <= 1 && preg_match('/\s+\band\b\s+/i', $clean) === 1 && count($this->extractMeaningfulTokens($clean)) <= 6) {
            $parts = preg_split('/\s+\band\b\s+/i', $clean) ?: $parts;
        }

        $items = [];
        foreach ($parts as $part) {
            $item = $this->cleanAnswerItem((string) $part, $reference);
            if ($item !== '') {
                $items[] = $item;
            }
        }

        if (empty($items)) {
            $fallback = $this->cleanAnswerItem($clean, $reference);
            if ($fallback !== '') {
                $items[] = $fallback;
            }
        }

        return array_values(array_unique($items));
    }

    private function cleanAnswerItem(string $item, bool $reference): string
    {
        $item = trim($item);

        if ($reference) {
            $item = preg_replace(
                '/^.+?\b(?:includes?|contain(?:s)?|consists?\s+of|comprises?|are|is|include)\b\s+/i',
                '',
                $item
            ) ?? $item;
        }

        $item = preg_replace('/\b(?:other\s+acceptable|acceptable)\b\s*:?\s*/i', '', $item) ?? $item;
        $item = trim($item, " \t\n\r\0\x0B,.;:-");

        return $item;
    }

    private function stripReferenceNoise(string $text): string
    {
        $text = preg_replace('/\bMNEMONIC\b\s*:.*$/is', '', $text) ?? $text;
        $text = preg_replace('/\bOther\s+acceptable\s*:/i', '; ', $text) ?? $text;

        return trim($text);
    }

    private function matchAnswerItems(array $submittedItems, array $correctItems): array
    {
        $used = [];
        $matched = 0;
        $scores = [];

        foreach ($submittedItems as $submittedItem) {
            $bestIndex = null;
            $bestScore = 0.0;

            foreach ($correctItems as $index => $correctItem) {
                if (isset($used[$index])) {
                    continue;
                }

                $score = $this->scoreAnswerItemMatch($submittedItem, $correctItem);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestIndex = $index;
                }
            }

            if ($bestIndex !== null && $bestScore >= 0.72) {
                $used[$bestIndex] = true;
                $matched++;
                $scores[] = $bestScore;
            }
        }

        return [
            'matched' => $matched,
            'score' => !empty($scores) ? array_sum($scores) / count($scores) : 0.0,
            'scores' => $scores,
        ];
    }

    private function scoreAnswerItemMatch(string $submittedItem, string $correctItem): float
    {
        $submittedTokens = $this->extractMeaningfulTokens($this->normalizeForComparison($submittedItem));
        $correctTokens = $this->extractMeaningfulTokens($this->normalizeForComparison($correctItem));

        if (empty($submittedTokens) || empty($correctTokens)) {
            return 0.0;
        }

        $coverage = $this->measureBidirectionalTokenCoverage($submittedTokens, $correctTokens);
        $submittedNormalized = implode(' ', $submittedTokens);
        $correctNormalized = implode(' ', $correctTokens);
        $containsPhrase = $submittedNormalized !== ''
            && $correctNormalized !== ''
            && (str_contains($submittedNormalized, $correctNormalized) || str_contains($correctNormalized, $submittedNormalized));

        $score = max($coverage['f1'], min(0.96, $coverage['precision']));

        // A student may provide the required phrase with useful extra context
        // (for example, "lower lip is curled outside"). In that direction,
        // recall—not precision—shows that the required item is fully covered.
        if ($containsPhrase && max($coverage['precision'], $coverage['recall']) >= 0.80) {
            $score = max($score, 0.92);
        }

        return $score;
    }

    private function looksLikeListReference(string $answer, array $items): bool
    {
        if (count($items) >= 3) {
            return true;
        }

        return preg_match('/\b\d+\s*[\.)]|[;\n]|,\s*[^,]+,\s*/', $answer) === 1;
    }

    private function hasReferenceAlternativeMarkup(string $answer): bool
    {
        return str_contains($answer, '(')
            || str_contains($answer, '/')
            || preg_match('/\b(?:or|other acceptable)\b/i', $answer) === 1;
    }

    private function applyHardGuards(string $submitted, string $correct, array $rubric): ?array
    {
        if ($this->hasNegationMismatch($submitted, $correct) || $this->hasCriticalTermMismatch($submitted, $correct)) {
            return $this->buildResult(false, 0.20, 'contradiction_detected', 'Your answer changes an essential part of the expected meaning.', ['mode' => $rubric['mode']]);
        }

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
        $text = $this->normalizeNegationWording($text);
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

    private function detectPromptMode(string $questionText): string
    {
        $question = mb_strtolower(trim($questionText));

        if ($question === '') {
            return 'general';
        }

        if (preg_match('/\b(list|mention|enumerate|name|give|state|write)\b/i', $question) === 1) {
            return 'list';
        }

        if ($this->countAnswerBlanks($questionText) > 0
            || preg_match('/\b(complete|fill\s+in|space|blank)\b/i', $question) === 1
        ) {
            return 'blank';
        }

        if (preg_match('/\b(define|what\s+is|what\s+are)\b/i', $question) === 1) {
            return 'definition';
        }

        return 'general';
    }

    private function inferRequiredItemCount(string $questionText): ?int
    {
        $question = mb_strtolower($questionText);
        $numberWords = [
            'one' => 1,
            'two' => 2,
            'three' => 3,
            'four' => 4,
            'five' => 5,
            'six' => 6,
            'seven' => 7,
            'eight' => 8,
            'nine' => 9,
            'ten' => 10,
        ];

        if (preg_match('/\b(?:list|mention|enumerate|name|give|state|write)\s+(?:any\s+)?(\d{1,2})\b/i', $question, $match) === 1) {
            return max(1, (int) $match[1]);
        }

        foreach ($numberWords as $word => $value) {
            if (preg_match('/\b(?:list|mention|enumerate|name|give|state|write)\s+(?:any\s+)?' . preg_quote($word, '/') . '\b/i', $question) === 1) {
                return $value;
            }
        }

        if (preg_match('/\((?:list\s*)?(\d{1,2})\)/i', $question, $match) === 1) {
            return max(1, (int) $match[1]);
        }

        $blankCount = $this->countAnswerBlanks($questionText);
        if ($blankCount > 0) {
            return $blankCount;
        }

        return null;
    }

    private function countAnswerBlanks(string $questionText): int
    {
        preg_match_all('/_{2,}|-{3,}|\.{3,}/', $questionText, $matches);

        return count($matches[0]);
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

    private function extractRequiredConcepts(string $normalizedAnswer, ?string $rawAnswer = null): array
    {
        $rawForSegmentation = $rawAnswer ?? $normalizedAnswer;

        $tokens = $this->extractMeaningfulTokens($normalizedAnswer);

        // Try to segment on raw commas first (before normalization strips them).
        $segments = preg_split('/,\s*/', $rawForSegmentation) ?: [];
        $segments = array_values(array_filter(array_map('trim', $segments)));

        // Only use segmentation if we got 2+ meaningful segments.
        if (count($segments) >= 2) {
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
        }

        // Fall back to token-count-based grouping.
        if (count($tokens) < (int) $this->cfg('short_concept_group_token_limit', 6)) {
            return !empty($tokens)
                ? [['label' => $normalizedAnswer, 'tokens' => $tokens]]
                : [];
        }

        $segmentsBySemicolon = preg_split('/,|;/', $normalizedAnswer) ?: [];
        $concepts = [];

        foreach ($segmentsBySemicolon as $segment) {
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
            default => 0.75,
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

        // Additional non-semantic "bridge" words that should never contribute to concept matching.
        $bridgeWords = array_flip([
            'through', 'throughout', 'within', 'without', 'between', 'among',
            'because', 'therefore', 'however', 'although', 'whereas', 'while',
            'during', 'before', 'after', 'about', 'above', 'below', 'under',
            'along', 'around', 'across', 'against', 'amongst', 'beside',
            'beyond', 'toward', 'towards', 'upon', 'from', 'into', 'onto',
        ]);

        return array_values(array_unique(array_filter($tokens, function (string $token) use ($optionalDescriptors, $bridgeWords): bool {
            if ($token === '') {
                return false;
            }

            if (isset($optionalDescriptors[$token])) {
                return false;
            }

            if (isset($bridgeWords[$token])) {
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

    private function hasNegationMismatch(string $submitted, string $correct): bool
    {
        // Keep negation out of the filler-word shortcuts. "Not only" is emphasis.
        $pattern = "/\b(?:no|not|never|without|cannot|absence|absent|[a-z]+n't)\b/u";
        $submitted = preg_replace('/\bnot only\b/', '', $submitted) ?? $submitted;
        $correct = preg_replace('/\bnot only\b/', '', $correct) ?? $correct;

        $submittedNegative = preg_match($pattern, $submitted) === 1;
        $correctNegative = preg_match($pattern, $correct) === 1;
        if ($submittedNegative !== $correctNegative) {
            return true;
        }
        if (!$submittedNegative) {
            return false;
        }

        // Compare the concepts in each negative clause, not just the presence of "not".
        $submittedNegated = $this->negatedTokens($submitted);
        $correctNegated = $this->negatedTokens($correct);
        $submittedTokens = $this->extractMeaningfulTokens($submitted);
        $correctTokens = $this->extractMeaningfulTokens($correct);
        foreach ($submittedTokens as $submittedToken) {
            foreach ($correctTokens as $correctToken) {
                if ($this->wordsAreSimilar($submittedToken, $correctToken)
                    && (in_array($submittedToken, $submittedNegated, true) !== in_array($correctToken, $correctNegated, true))) {
                    return true;
                }
            }
        }
        return false;
    }

    private function normalizeNegationWording(string $text): string
    {
        $text = str_replace(["’", "‘"], "'", $text);
        $text = preg_replace("/\bcan't\b/i", 'cannot', $text) ?? $text;
        $text = preg_replace("/\bwon't\b/i", 'will not', $text) ?? $text;
        $text = preg_replace("/\b([a-z]+)n't\b/i", '$1 not', $text) ?? $text;
        // Normalize absence phrasing within a clause without erasing its subject.
        $text = preg_replace('/\bnot present\b/i', 'absent', $text) ?? $text;
        $text = preg_replace('/\babsence of\b/i', 'no', $text) ?? $text;
        $text = preg_replace('/(^|[.;,]|\band\b|\bbut\b)\s*([\p{L} -]+?)\s+(?:is|are|was|were)\s+absent\b/iu', '$1 no $2', $text) ?? $text;
        return $text;
    }

    private function negatedTokens(string $text): array
    {
        $tokens = [];
        foreach (preg_split('/[.;]|\b(?:and|but|whereas|while)\b/u', $text) ?: [] as $clause) {
            if (preg_match('/\b(?:no|not|never|without|cannot|absence|absent)\b\s*(.*)/u', $clause, $match)) {
                $tokens = array_merge($tokens, $this->extractMeaningfulTokens($match[1]));
            }
        }
        return array_values(array_unique($tokens));
    }

    private function extractAssertedContrast(string $answer, array $candidateContext): ?string
    {
        if (preg_match('/^(.+?),\s*(?:not|rather than)\s+(.+?)[.!]?$/iu', trim($answer), $match)) {
            [$asserted, $excluded] = [$match[1], $match[2]];
        } elseif (preg_match('/^not\s+(.+?)\s+but\s+(.+?)[.!]?$/iu', trim($answer), $match)) {
            [$asserted, $excluded] = [$match[2], $match[1]];
        } else {
            return null;
        }

        $assertedContext = $this->normalizeInput($asserted);
        $excludedContext = $this->normalizeInput($excluded);
        $coverage = $this->measureBidirectionalTokenCoverage($assertedContext['tokens'], $candidateContext['tokens']);
        $excludedCoverage = $this->measureBidirectionalTokenCoverage($excludedContext['tokens'], $candidateContext['tokens']);
        // Only discard an explicitly rejected alternative when the asserted answer
        // covers the whole reference and the rejected alternative shares no concepts.
        return $coverage['precision'] >= 1.0 && $coverage['recall'] >= 1.0
            && $excludedCoverage['matchedCandidateTokens'] === 0
            ? $asserted : null;
    }

    private function hasCriticalTermMismatch(string $submitted, string $correct): bool
    {
        $pairs = [
            ['ileum', 'ilium'], ['ureter', 'urethra'],
            ['afferent', 'efferent'], ['abduction', 'adduction'],
            ['positive', 'negative'], ['systole', 'diastole'],
        ];
        foreach ($pairs as [$left, $right]) {
            if (($this->containsWholeTerm($submitted, $left) && $this->containsWholeTerm($correct, $right)
                    && !$this->containsWholeTerm($correct, $left))
                || ($this->containsWholeTerm($submitted, $right) && $this->containsWholeTerm($correct, $left)
                    && !$this->containsWholeTerm($correct, $right))
            ) {
                return true;
            }
        }
        preg_match_all('/\b(hyper|hypo)([a-z]+)\b/', $submitted, $terms, PREG_SET_ORDER);
        foreach ($terms as $term) {
            $opposite = ($term[1] === 'hyper' ? 'hypo' : 'hyper') . $term[2];
            if ($this->containsWholeTerm($correct, $opposite) && !$this->containsWholeTerm($correct, $term[0])) {
                return true;
            }
        }

        return false;
    }

    private function hasDirectionalContradiction(string $submitted, string $correct): bool
    {
        if ($this->hasTargetDirectionMismatch($submitted, $correct)) {
            return true;
        }

        $pairs = [
            ['increase', 'decrease'],
            ['increases', 'decreases'],
            ['high', 'low'],
            ['sympathetic', 'parasympathetic'],
            ['anterior', 'posterior'],
            ['superior', 'inferior'],
        ];

        foreach ($pairs as [$left, $right]) {
            $submittedHasLeft = $this->containsWholeTerm($submitted, $left);
            $submittedHasRight = $this->containsWholeTerm($submitted, $right);
            $correctHasLeft = $this->containsWholeTerm($correct, $left);
            $correctHasRight = $this->containsWholeTerm($correct, $right);

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

    private function hasTargetDirectionMismatch(string $submitted, string $correct): bool
    {
        $targets = [
            'heart rate' => ['heart rate', 'pulse'],
            'digestion' => ['digestion', 'peristalsis'],
            'blood pressure' => ['blood pressure'],
        ];

        foreach ($targets as $targetPhrases) {
            $submittedDirection = $this->directionForTarget($submitted, $targetPhrases);
            $correctDirection = $this->directionForTarget($correct, $targetPhrases);

            if ($submittedDirection !== null
                && $correctDirection !== null
                && $submittedDirection !== $correctDirection
            ) {
                return true;
            }
        }

        return false;
    }

    private function directionForTarget(string $text, array $targetPhrases): ?string
    {
        $increaseTerms = ['increase', 'increases', 'increased', 'raise', 'raises', 'raised', 'elevate', 'elevates', 'elevated', 'promote', 'promotes', 'stimulate', 'stimulates'];
        $decreaseTerms = ['decrease', 'decreases', 'decreased', 'lower', 'lowers', 'lowered', 'reduce', 'reduces', 'reduced', 'suppress', 'suppresses', 'inhibit', 'inhibits'];

        foreach ($targetPhrases as $targetPhrase) {
            foreach ($increaseTerms as $term) {
                if ($this->termAppliesToTarget($text, $term, $targetPhrase)) {
                    return 'increase';
                }
            }

            foreach ($decreaseTerms as $term) {
                if ($this->termAppliesToTarget($text, $term, $targetPhrase)) {
                    return 'decrease';
                }
            }
        }

        return null;
    }

    private function termAppliesToTarget(string $text, string $term, string $targetPhrase): bool
    {
        $term = preg_quote($term, '/');
        $target = preg_quote($targetPhrase, '/');

        return preg_match('/\b' . $term . '\b(?:\s+(?!(?:and|but|while|whereas)\b)\w+){0,3}?\s+\b' . $target . '\b/i', $text) === 1
            || preg_match('/\b' . $target . '\b(?:\s+(?!(?:and|but|while|whereas)\b)\w+){0,3}?\s+\b' . $term . '\b/i', $text) === 1;
    }

    private function wordsAreSimilar(string $left, string $right): bool
    {
        if ($left === $right) {
            return true;
        }

        return $this->dynamicTokenSimilarity($left, $right)
            >= $this->dynamicTokenMatchThreshold($left, $right);
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
            $item = preg_replace(
                '/^.+?\b(?:includes?|contain(?:s)?|consists?\s+of|comprises?|are|is)\b\s+/i',
                '',
                trim($item)
            ) ?? trim($item);

            return rtrim(trim(preg_replace('/[()]/', '', $item)), ',.;');
        }, array_filter($items, fn (string $item): bool => strlen(trim($item)) > 1)));
    }

    private function extractNumericalValues(string $text): array
    {
        $values = [];

        if (preg_match_all('/(\d+(?:[\.,]\d+)?)\s*[\-–]\s*(\d+(?:[\.,]\d+)?)(?:\s*([a-zA-Zµ°]+(?:\/[a-zA-Z]+)?))?/u', $text, $matches)) {
            foreach ($matches[0] as $index => $match) {
                $values[] = [
                    'type' => 'range',
                    'min' => (float) str_replace(',', '.', $matches[1][$index]),
                    'max' => (float) str_replace(',', '.', $matches[2][$index]),
                    'text' => $match,
                    'unit' => $this->normalizeUnit($matches[3][$index] ?? ''),
                ];
            }
        }

        if (preg_match_all('/(\d+(?:[\.,]\d+)?)\s*([a-zA-Zµ°]+(?:\/[a-zA-Z]+)?)?/', $text, $matches)) {
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
        if ($reference['unit'] !== '' && $candidate['unit'] !== '' && $reference['unit'] !== $candidate['unit']) {
            return 0.0;
        }

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
            $decoded = json_decode($alternatives, true);

            if (is_array($decoded)) {
                $alternatives = $decoded;
            } else {
                $alternatives = preg_split('/\r\n|\r|\n|\s*\|\s*/', $alternatives) ?: [];
            }
        }

        if (!is_array($alternatives)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (mixed $answer): string => trim((string) $answer),
            $alternatives
        ), fn (string $answer): bool => $answer !== ''));
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
            ['pump', 'pumps', 'pumped'],
            ['circulate', 'circulates', 'circulated'],
            ['propel', 'propels'],
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
