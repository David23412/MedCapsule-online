<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class TextProcessingService
{
    protected $textNormalizationService;
    protected $textSimilarityService;
    protected $medicalDomainService;
    
    private $cacheEnabled = true;
    private $cacheTtl = 3600; // 1 hour

    public function __construct(
        TextNormalizationService $textNormalizationService = null,
        TextSimilarityService $textSimilarityService = null,
        MedicalDomainService $medicalDomainService = null
    ) {
        // Allow for dependency injection or create instances
        $this->textNormalizationService = $textNormalizationService ?: new TextNormalizationService();
        $this->textSimilarityService = $textSimilarityService ?: new TextSimilarityService();
        $this->medicalDomainService = $medicalDomainService ?: new MedicalDomainService();
    }

    /**
     * OPTIMIZED: Main text normalization method
     */
    public function normalizeText(string $text): string
    {
        if (empty($text)) {
            return '';
        }

        // Step 1: Basic normalization
        $normalized = $this->textNormalizationService->normalizeText($text);
        
        // Step 2: Expand medical abbreviations
        $normalized = $this->medicalDomainService->expandAbbreviations($normalized);
        
        // Step 3: Process medical symbols
        $normalized = $this->medicalDomainService->preprocessSymbols($normalized);
        
        // Step 4: Remove filler words
        $normalized = $this->textNormalizationService->removeFillerWords($normalized);
        
        // Step 5: Basic spelling correction
        $normalized = $this->textNormalizationService->correctSpelling($normalized);
        
        return trim($normalized);
    }

    /**
     * OPTIMIZED: Levenshtein similarity
     */
    public function getLevenshteinSimilarity(string $text1, string $text2): float
    {
        return $this->textSimilarityService->getLevenshteinSimilarity($text1, $text2);
    }

    /**
     * OPTIMIZED: Jaccard similarity
     */
    public function getJaccardSimilarity(string $text1, string $text2): float
    {
        return $this->textSimilarityService->getJaccardSimilarity($text1, $text2);
    }

    /**
     * OPTIMIZED: Keyword overlap ratio
     */
    public function getKeywordOverlapRatio(string $text1, string $text2): float
    {
        return $this->textSimilarityService->getKeywordOverlapRatio($text1, $text2);
    }

    /**
     * OPTIMIZED: Conceptual similarity
     */
    public function getConceptualSimilarity(string $text1, string $text2): float
    {
        return $this->textSimilarityService->getConceptualSimilarity($text1, $text2);
    }

    /**
     * OPTIMIZED: Combined similarity
     */
    public function getCombinedSimilarity(string $text1, string $text2): float
    {
        return $this->textSimilarityService->getCombinedSimilarity($text1, $text2);
    }

    /**
     * Remove filler words
     */
    public function removeFillerWords(string $text): string
    {
        return $this->textNormalizationService->removeFillerWords($text);
    }

    /**
     * Correct spelling
     */
    public function correctSpelling(string $text): string
    {
        return $this->textNormalizationService->correctSpelling($text);
    }

    /**
     * Set fuzzy match threshold
     */
    public function setFuzzyMatchThreshold(float $threshold): void
    {
        $this->textSimilarityService->setFuzzyMatchThreshold($threshold);
    }

    /**
     * Set cache configuration
     */
    public function setCacheConfig(bool $enabled, int $ttl = 3600): void
    {
        $this->cacheEnabled = $enabled;
        $this->cacheTtl = $ttl;
        
        // Propagate to sub-services
        $this->textNormalizationService->setCacheConfig($enabled, $ttl);
        $this->textSimilarityService->setCacheConfig($enabled, $ttl);
        $this->medicalDomainService->setCacheConfig($enabled, $ttl);
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