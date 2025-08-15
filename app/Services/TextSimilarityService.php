<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class TextSimilarityService
{
    private $cacheEnabled = true;
    private $cacheTtl = 1800; // 30 minutes for similarity cache
    private $fuzzyMatchThreshold = 0.85;

    /**
     * Calculate Levenshtein similarity - OPTIMIZED
     */
    public function getLevenshteinSimilarity(string $text1, string $text2): float
    {
        if ($text1 === $text2) {
            return 1.0;
        }

        $cacheKey = 'levenshtein:' . md5($text1 . '|' . $text2);
        
        return $this->getCachedOrCompute($cacheKey, function() use ($text1, $text2) {
            $len1 = strlen($text1);
            $len2 = strlen($text2);
            
            if ($len1 === 0) return $len2 === 0 ? 1.0 : 0.0;
            if ($len2 === 0) return 0.0;
            
            $distance = levenshtein($text1, $text2);
            $maxLen = max($len1, $len2);
            
            return 1.0 - ($distance / $maxLen);
        });
    }

    /**
     * Calculate Jaccard similarity - OPTIMIZED
     */
    public function getJaccardSimilarity(string $text1, string $text2): float
    {
        $words1 = array_unique(explode(' ', strtolower($text1)));
        $words2 = array_unique(explode(' ', strtolower($text2)));
        
        $intersection = count(array_intersect($words1, $words2));
        $union = count(array_unique(array_merge($words1, $words2)));
        
        return $union > 0 ? $intersection / $union : 0.0;
    }

    /**
     * Calculate keyword overlap ratio - OPTIMIZED
     */
    public function getKeywordOverlapRatio(string $text1, string $text2): float
    {
        $words1 = explode(' ', strtolower($text1));
        $words2 = explode(' ', strtolower($text2));
        
        $totalWords = count($words1);
        if ($totalWords === 0) return 0.0;
        
        $matches = 0;
        foreach ($words1 as $word) {
            if (in_array($word, $words2)) {
                $matches++;
            }
        }
        
        return $matches / $totalWords;
    }

    /**
     * Calculate conceptual similarity - SIMPLIFIED
     */
    public function getConceptualSimilarity(string $text1, string $text2): float
    {
        // Simplified conceptual similarity based on common medical concepts
        $medicalConcepts = [
            'heart' => ['cardiac', 'cardiovascular', 'myocardial'],
            'blood' => ['circulation', 'vascular', 'hemodynamic'],
            'lung' => ['pulmonary', 'respiratory', 'breathing'],
            'brain' => ['neural', 'neurological', 'cerebral'],
            'kidney' => ['renal', 'nephron', 'filtration']
        ];

        $score = 0.0;
        $totalConcepts = 0;

        foreach ($medicalConcepts as $concept => $synonyms) {
            $inText1 = stripos($text1, $concept) !== false;
            $inText2 = stripos($text2, $concept) !== false;
            
            if (!$inText1) {
                foreach ($synonyms as $synonym) {
                    if (stripos($text1, $synonym) !== false) {
                        $inText1 = true;
                        break;
                    }
                }
            }
            
            if (!$inText2) {
                foreach ($synonyms as $synonym) {
                    if (stripos($text2, $synonym) !== false) {
                        $inText2 = true;
                        break;
                    }
                }
            }
            
            if ($inText1 || $inText2) {
                $totalConcepts++;
                if ($inText1 && $inText2) {
                    $score += 1.0;
                }
            }
        }

        return $totalConcepts > 0 ? $score / $totalConcepts : 0.0;
    }

    /**
     * Calculate combined similarity - OPTIMIZED WEIGHTS
     */
    public function getCombinedSimilarity(string $text1, string $text2): float
    {
        $weights = [
            'levenshtein' => 0.3,
            'jaccard' => 0.3,
            'keyword' => 0.25,
            'conceptual' => 0.15
        ];

        $similarities = [
            'levenshtein' => $this->getLevenshteinSimilarity($text1, $text2),
            'jaccard' => $this->getJaccardSimilarity($text1, $text2),
            'keyword' => $this->getKeywordOverlapRatio($text1, $text2),
            'conceptual' => $this->getConceptualSimilarity($text1, $text2)
        ];

        $combinedScore = 0.0;
        foreach ($weights as $metric => $weight) {
            $combinedScore += $similarities[$metric] * $weight;
        }

        return min(1.0, $combinedScore);
    }

    /**
     * Set fuzzy match threshold
     */
    public function setFuzzyMatchThreshold(float $threshold): void
    {
        $this->fuzzyMatchThreshold = max(0, min(1, $threshold));
    }

    /**
     * Set cache configuration
     */
    public function setCacheConfig(bool $enabled, int $ttl = 1800): void
    {
        $this->cacheEnabled = $enabled;
        $this->cacheTtl = $ttl;
    }

    /**
     * Cache helper
     */
    private function getCachedOrCompute(string $key, \Closure $callback)
    {
        if (!$this->cacheEnabled) {
            return $callback();
        }

        return Cache::remember($key, $this->cacheTtl, $callback);
    }
}