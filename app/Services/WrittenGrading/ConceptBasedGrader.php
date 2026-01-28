<?php

namespace App\Services\WrittenGrading;

use Illuminate\Support\Str;

/**
 * Concept-based written answer grader.
 *
 * This is a first, non-invasive implementation of the "ultimate" grading core:
 * - Scores answers by which rubric concepts are expressed.
 * - Applies explicit penalties for known misconceptions.
 * - Produces a rich breakdown for downstream feedback generators.
 *
 * NOTE: This implementation intentionally uses simple string heuristics for
 *       matching phrases. It is structured so that more advanced semantic
 *       matching (embeddings, entailment models, etc.) can be dropped in
 *       later without changing the public API or the grading pipeline.
 */
class ConceptBasedGrader
{
    /**
     * Grade a single written answer against a concept rubric.
     *
     * @param string        $answer
     * @param ConceptRubric $rubric
     * @return array<string, mixed>  Structured grading result
     */
    public function grade(string $answer, ConceptRubric $rubric): array
    {
        $normalizedAnswer = $this->normalizeText($answer);

        // Score concepts
        $conceptResults = [];
        $totalWeight    = 0.0;
        $coveredWeight  = 0.0;

        foreach ($rubric->concepts as $concept) {
            $weight = (float)($concept['weight'] ?? 0.0);
            $role   = $concept['role'] ?? 'core';
            $conceptId = $concept['id'] ?? '';

            // Aggregate total weight only over non-zero entries
            if ($weight > 0) {
                $totalWeight += $weight;
            }

            $matched = $this->matchConcept($normalizedAnswer, $concept);

            if ($matched && $weight > 0) {
                $coveredWeight += $weight;
            }

            $conceptResults[$conceptId] = [
                'id'          => $conceptId,
                'role'        => $role,
                'weight'      => $weight,
                'description' => $concept['description'] ?? '',
                'matched'     => $matched,
            ];
        }

        // Avoid division by zero for edge cases
        $coverageFraction = $totalWeight > 0 ? ($coveredWeight / $totalWeight) : 0.0;

        // Apply misconception penalties
        $penalties           = [];
        $totalPenaltyFraction = 0.0;

        foreach ($rubric->misconceptions as $misconception) {
            if ($this->matchMisconception($normalizedAnswer, $misconception)) {
                $penalty = max(0.0, min(1.0, (float)($misconception['penalty'] ?? 0.0)));
                $totalPenaltyFraction += $penalty;

                $penalties[] = [
                    'id'       => $misconception['id'] ?? '',
                    'penalty'  => $penalty,
                    'message'  => $misconception['message'] ?? '',
                ];
            }
        }

        // Clamp penalty to at most full score
        $totalPenaltyFraction = min(1.0, $totalPenaltyFraction);

        // Compute raw score before clamping to [0, maxScore]
        $rawScore = $rubric->maxScore * max(0.0, $coverageFraction - $totalPenaltyFraction);

        // Build lists of missing / covered concepts (for feedback)
        $coveredConcepts = [];
        $missingConcepts = [];

        foreach ($conceptResults as $result) {
            if ($result['matched']) {
                $coveredConcepts[] = $result;
            } else {
                $missingConcepts[] = $result;
            }
        }

        // Compose high-level feedback
        $feedback = $this->buildFeedback($coveredConcepts, $missingConcepts, $penalties);

        return [
            'score'             => round($rawScore, 1),
            'max_score'         => $rubric->maxScore,
            'coverage_fraction' => $coverageFraction,
            'penalty_fraction'  => $totalPenaltyFraction,
            'concepts'          => $conceptResults,
            'covered_concepts'  => $coveredConcepts,
            'missing_concepts'  => $missingConcepts,
            'penalties'         => $penalties,
            'feedback'          => $feedback,
        ];
    }

    /**
     * Lightweight normalization – lowercasing and collapsing whitespace.
     *
     * A more advanced pipeline can be injected later (lemmatization, synonyms, etc.).
     */
    private function normalizeText(string $text): string
    {
        $text = Str::of($text)
            ->lower()
            ->replaceMatches('/\s+/u', ' ')
            ->trim()
            ->toString();

        return $text;
    }

    /**
     * Determine if a concept is present in the student's answer.
     *
     * Current implementation:
     * - Checks if any of the configured phrases appear as substrings.
     * - Designed so that this can be upgraded to semantic entailment later.
     *
     * @param string               $normalizedAnswer
     * @param array<string, mixed> $concept
     */
    private function matchConcept(string $normalizedAnswer, array $concept): bool
    {
        $phrases = $concept['phrases'] ?? [];

        foreach ($phrases as $phrase) {
            $phrase = Str::of($phrase)->lower()->trim()->toString();
            if ($phrase === '') {
                continue;
            }

            if (Str::contains($normalizedAnswer, $phrase)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a known misconception is present.
     *
     * @param string               $normalizedAnswer
     * @param array<string, mixed> $misconception
     */
    private function matchMisconception(string $normalizedAnswer, array $misconception): bool
    {
        $patterns = $misconception['patterns'] ?? [];

        foreach ($patterns as $pattern) {
            $pattern = Str::of($pattern)->lower()->trim()->toString();
            if ($pattern === '') {
                continue;
            }

            if (Str::contains($normalizedAnswer, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build human-readable feedback that acknowledges strengths and gaps.
     *
     * @param array<int, array<string, mixed>> $covered
     * @param array<int, array<string, mixed>> $missing
     * @param array<int, array<string, mixed>> $penalties
     */
    private function buildFeedback(array $covered, array $missing, array $penalties): string
    {
        $parts = [];

        if (!empty($covered)) {
            $coveredSnippets = array_slice(array_map(
                fn ($c) => $c['description'] ?: $c['id'],
                $covered
            ), 0, 3);

            $parts[] = 'You correctly mentioned: ' . implode('; ', $coveredSnippets) . '.';
        }

        if (!empty($missing)) {
            $missingCore = array_filter($missing, fn ($c) => $c['role'] === 'core');
            $missingList = !empty($missingCore) ? $missingCore : $missing;

            $missingSnippets = array_slice(array_map(
                fn ($c) => $c['description'] ?: $c['id'],
                $missingList
            ), 0, 3);

            $parts[] = 'To reach full marks, you should also include: ' . implode('; ', $missingSnippets) . '.';
        }

        foreach ($penalties as $p) {
            if (!empty($p['message'])) {
                $parts[] = $p['message'];
            }
        }

        if (empty($parts)) {
            return 'No specific feedback available for this answer.';
        }

        return implode(' ', $parts);
    }
}


