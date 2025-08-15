<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class TextNormalizationService
{
    private $cacheEnabled = true;
    private $cacheTtl = 3600; // 1 hour for normalization cache

    /**
     * Normalize text for comparison - OPTIMIZED
     */
    public function normalizeText(string $text): string
    {
        if (empty($text)) {
            return '';
        }

        $cacheKey = 'normalize:' . md5($text);
        
        return $this->getCachedOrCompute($cacheKey, function() use ($text) {
            // Convert to lowercase
            $normalized = strtolower($text);
            
            // Remove extra whitespace
            $normalized = preg_replace('/\s+/', ' ', $normalized);
            
            // Remove common punctuation but keep medical symbols
            $normalized = preg_replace('/[^\w\s\-\+\(\)\/\%\°\→\↑\↓]/', '', $normalized);
            
            // Trim
            return trim($normalized);
        });
    }

    /**
     * Remove filler words - OPTIMIZED
     */
    public function removeFillerWords(string $text): string
    {
        $fillerWords = [
            'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by',
            'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did',
            'will', 'would', 'could', 'should', 'may', 'might', 'can', 'must', 'shall',
            'this', 'that', 'these', 'those', 'i', 'you', 'he', 'she', 'it', 'we', 'they',
            'my', 'your', 'his', 'her', 'its', 'our', 'their', 'me', 'him', 'her', 'us', 'them'
        ];

        $words = preg_split('/\s+/', strtolower($text), -1, PREG_SPLIT_NO_EMPTY);
        $filteredWords = array_diff($words, $fillerWords);
        
        return implode(' ', $filteredWords);
    }

    /**
     * Basic spelling correction for common medical terms
     */
    public function correctSpelling(string $text): string
    {
        $corrections = [
            'hart' => 'heart',
            'blod' => 'blood',
            'brane' => 'brain',
            'kidny' => 'kidney',
            'stomac' => 'stomach',
            'muscl' => 'muscle',
            'nervs' => 'nerves',
            'artry' => 'artery',
            'vien' => 'vein',
            'oxigen' => 'oxygen'
        ];

        foreach ($corrections as $wrong => $correct) {
            $text = str_ireplace($wrong, $correct, $text);
        }

        return $text;
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