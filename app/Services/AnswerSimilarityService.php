<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class AnswerSimilarityService
{
    protected $textSimilarityService;
    protected $patternMatcher;
    private $cacheEnabled = true;
    private $cacheTtl = 3600; // 1 hour for similarity calculations

    public function __construct(TextSimilarityService $textSimilarityService, PatternMatcherService $patternMatcher)
    {
        $this->textSimilarityService = $textSimilarityService;
        $this->patternMatcher = $patternMatcher;
    }

    /**
     * Calculate comprehensive similarity metrics
     */
    public function calculateSimilarityMetrics(string $submittedAnswer, string $correctAnswer): array
    {
        // Normalize both answers
        $normalizedSubmitted = $this->textProcessor->normalizeText($submittedAnswer);
        $normalizedCorrect = $this->textProcessor->normalizeText($correctAnswer);

        return [
            'levenshtein' => $this->calculateLevenshteinSimilarity($normalizedSubmitted, $normalizedCorrect),
            'jaccard' => $this->calculateJaccardSimilarity($normalizedSubmitted, $normalizedCorrect),
            'keyword' => $this->calculateKeywordSimilarity($normalizedSubmitted, $normalizedCorrect),
            'pattern' => $this->calculatePatternSimilarity($normalizedSubmitted, $normalizedCorrect),
            'conceptual' => $this->calculateConceptualSimilarity($normalizedSubmitted, $normalizedCorrect),
        ];
    }

    /**
     * Calculate Levenshtein similarity
     */
    private function calculateLevenshteinSimilarity(string $normalizedSubmitted, string $normalizedCorrect): float
    {
        return $this->textSimilarityService->getLevenshteinSimilarity($normalizedSubmitted, $normalizedCorrect);
    }

    /**
     * Calculate Jaccard similarity
     */
    private function calculateJaccardSimilarity(string $normalizedSubmitted, string $normalizedCorrect): float
    {
        return $this->textSimilarityService->getJaccardSimilarity($normalizedSubmitted, $normalizedCorrect);
    }

    /**
     * Calculate keyword similarity
     */
    private function calculateKeywordSimilarity(string $normalizedSubmitted, string $normalizedCorrect): float
    {
        return $this->textSimilarityService->getKeywordOverlapRatio($normalizedSubmitted, $normalizedCorrect);
    }

    /**
     * Calculate pattern similarity
     */
    private function calculatePatternSimilarity(string $normalizedSubmitted, string $normalizedCorrect): array
    {
        return $this->getPatternMatches($normalizedSubmitted, $normalizedCorrect);
    }

    /**
     * Calculate conceptual similarity
     */
    private function calculateConceptualSimilarity(string $normalizedSubmitted, string $normalizedCorrect): float
    {
        return $this->textSimilarityService->getConceptualSimilarity($normalizedSubmitted, $normalizedCorrect);
    }

    /**
     * Get pattern matches
     */
    private function getPatternMatches(string $normalizedSubmitted, string $normalizedCorrect): array
    {
        try {
            $patterns = [
                'medical_terminology' => $this->patternMatcher->matchesPattern('medical_terminology', $normalizedSubmitted),
                'anatomical_references' => $this->patternMatcher->matchesPattern('anatomical_references', $normalizedSubmitted),
                'physiological_processes' => $this->patternMatcher->matchesPattern('physiological_processes', $normalizedSubmitted),
                'pathological_conditions' => $this->patternMatcher->matchesPattern('pathological_conditions', $normalizedSubmitted),
            ];

            $correctPatterns = [
                'medical_terminology' => $this->patternMatcher->matchesPattern('medical_terminology', $normalizedCorrect),
                'anatomical_references' => $this->patternMatcher->matchesPattern('anatomical_references', $normalizedCorrect),
                'physiological_processes' => $this->patternMatcher->matchesPattern('physiological_processes', $normalizedCorrect),
                'pathological_conditions' => $this->patternMatcher->matchesPattern('pathological_conditions', $normalizedCorrect),
            ];

            $matchCount = 0;
            $totalPatterns = count($patterns);

            foreach ($patterns as $key => $matched) {
                if ($matched && $correctPatterns[$key]) {
                    $matchCount++;
                }
            }

            return [
                'score' => $totalPatterns > 0 ? $matchCount / $totalPatterns : 0,
                'matches' => $patterns,
                'correct_patterns' => $correctPatterns,
            ];
        } catch (\Exception $e) {
            return [
                'score' => 0,
                'matches' => [],
                'correct_patterns' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check for sympathetic effects match
     */
    public function isSympatheticEffectsMatch(string $submittedAnswer, string $correctAnswer): bool
    {
        return $this->patternMatcher->matchesPattern('sympathetic_effects', $submittedAnswer) && 
               $this->patternMatcher->matchesPattern('sympathetic_effects', $correctAnswer);
    }

    /**
     * Check for parasympathetic effects match
     */
    public function isParasympatheticEffectsMatch(string $submittedAnswer, string $correctAnswer): bool
    {
        return $this->patternMatcher->matchesPattern('parasympathetic_effects', $submittedAnswer) && 
               $this->patternMatcher->matchesPattern('parasympathetic_effects', $correctAnswer);
    }

    /**
     * Set cache configuration
     */
    public function setCacheConfig(bool $enabled, int $ttl = 3600): void
    {
        $this->cacheEnabled = $enabled;
        $this->cacheTtl = $ttl;
    }

    /**
     * Cache helper method
     */
    private function getCachedOrCompute(string $key, \Closure $callback)
    {
        if (!$this->cacheEnabled) {
            return $callback();
        }

        return Cache::remember($key, $this->cacheTtl, $callback);
    }
}