<?php
// app/Services/Grading/Cache/IntelligentCacheManager.php

namespace App\Services\Grading\Cache;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\Log;

/**
 * AI-Ready Intelligent Cache Manager
 * Multi-level caching with predictive preloading for AI integration
 *
 * Performance Targets:
 * - L1 (APCu): <1ms access time
 * - L2 (Redis): <5ms access time
 * - L3 (File): <50ms access time
 * - Overall: <10ms average response time
 */
class IntelligentCacheManager
{
    private Cache $cache;
    private array $cacheLevels = ['l1' => 'apcu', 'l2' => 'redis', 'l3' => 'file'];
    private array $performanceMetrics = [];
    private array $predictiveCache = [];

    // Cache hit tracking for AI learning
    private array $hitPatterns = [];
    private array $missPatterns = [];

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
        $this->initializeCacheLevels();
    }

    /**
     * Initialize cache levels and check availability
     */
    private function initializeCacheLevels(): void
    {
        // Check APCu availability (L1)
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            unset($this->cacheLevels['l1']);
            Log::warning('APCu not available, falling back to Redis/File cache only');
        }

        // Check Redis connectivity (L2)
        try {
            $this->cache->put('test_connection', 'test', 1);
        } catch (\Exception $e) {
            unset($this->cacheLevels['l2']);
            Log::warning('Redis not available, falling back to File cache only', [
                'error' => $e->getMessage()
            ]);
        }

    }

    /**
     * Intelligent cache retrieval with fallback chain
     * AI-Ready: Logs patterns for predictive caching
     */
    public function remember(string $key, int $ttl, callable $callback)
    {
        $startTime = microtime(true);
        $cacheKey = $this->buildCacheKey($key);

        // L1: APCu (microseconds)
        if ($this->isLevelEnabled('l1')) {
            $result = $this->getFromLevel('l1', $cacheKey);
            if ($result !== null) {
                $this->recordPerformance('l1_hit', microtime(true) - $startTime);
                $this->trackHitPattern($key, 'l1');
                return $result;
            }
        }

        // L2: Redis (milliseconds)
        if ($this->isLevelEnabled('l2')) {
            $result = $this->getFromLevel('l2', $cacheKey);
            if ($result !== null) {
                // Promote to L1 for faster future access
                $this->setLevel('l1', $cacheKey, $result, $ttl);
                $this->recordPerformance('l2_hit', microtime(true) - $startTime);
                $this->trackHitPattern($key, 'l2');
                return $result;
            }
        }

        // L3: File (seconds - last resort)
        if ($this->isLevelEnabled('l3')) {
            $result = $this->getFromLevel('l3', $cacheKey);
            if ($result !== null) {
                // Promote to higher levels
                $this->setLevel('l2', $cacheKey, $result, $ttl);
                $this->setLevel('l1', $cacheKey, $result, $ttl);
                $this->recordPerformance('l3_hit', microtime(true) - $startTime);
                $this->trackHitPattern($key, 'l3');
                return $result;
            }
        }

        // Compute and cache at all levels
        $result = $callback();
        $this->setAllLevels($cacheKey, $result, $ttl);

        // AI-Ready: Log for pattern analysis
        $this->logCacheMiss($key, microtime(true) - $startTime);
        $this->trackMissPattern($key);

        return $result;
    }

    /**
     * Get value from specific cache level
     */
    private function getFromLevel(string $level, string $key)
    {
        try {
            switch ($level) {
                case 'l1':
                    if ($this->isLevelEnabled('l1') && function_exists('apcu_fetch')) {
                        return apcu_fetch($key);
                    }
                    return null;

                case 'l2':
                    if ($this->isLevelEnabled('l2')) {
                        return $this->cache->get($key);
                    }
                    return null;

                case 'l3':
                    if ($this->isLevelEnabled('l3')) {
                        return $this->getFileCache($key);
                    }
                    return null;

                default:
                    return null;
            }
        } catch (\Exception $e) {
            Log::warning("Cache level {$level} failed", [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Set value in specific cache level
     */
    private function setLevel(string $level, string $key, $value, int $ttl): void
    {
        try {
            switch ($level) {
                case 'l1':
                    if ($this->isLevelEnabled('l1') && function_exists('apcu_store')) {
                        apcu_store($key, $value, $ttl);
                    }
                    break;

                case 'l2':
                    if ($this->isLevelEnabled('l2')) {
                        $this->cache->put($key, $value, $ttl / 60); // Convert to minutes
                    }
                    break;

                case 'l3':
                    if ($this->isLevelEnabled('l3')) {
                        $this->setFileCache($key, $value, $ttl);
                    }
                    break;
            }
        } catch (\Exception $e) {
            Log::warning("Failed to set cache level {$level}", [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Set value in all available cache levels
     */
    private function setAllLevels(string $key, $value, int $ttl): void
    {
        foreach (array_keys($this->cacheLevels) as $level) {
            if ($this->isLevelEnabled($level)) {
                $this->setLevel($level, $key, $value, $ttl);
            }
        }
    }

    /**
     * Check if a specific cache level is enabled
     */
    private function isLevelEnabled(string $level): bool
    {
        return isset($this->cacheLevels[$level]);
    }

    /**
     * Check if key exists in any cache level
     */
    private function hasAnyLevel(string $key): bool
    {
        foreach (array_keys($this->cacheLevels) as $level) {
            if ($this->isLevelEnabled($level) && $this->getFromLevel($level, $key) !== null) {
                return true;
            }
        }
        return false;
    }

    /**
     * File-based cache implementation (L3)
     */
    private function getFileCache(string $key): mixed
    {
        $file = storage_path("cache/grading/{$key}.cache");

        if (file_exists($file)) {
            $fileTime = filemtime($file);
            $currentTime = time();

            // Check if file is expired
            if (($currentTime - $fileTime) > 3600) { // 1 hour TTL
                unlink($file); // Delete expired file
                return null;
            }

            $content = file_get_contents($file);
            return unserialize($content);
        }

        return null;
    }

    /**
     * Set file-based cache
     */
    private function setFileCache(string $key, $value, int $ttl): void
    {
        $dir = storage_path('cache/grading');

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $file = storage_path("cache/grading/{$key}.cache");
        file_put_contents($file, serialize($value));
    }

    /**
     * Build standardized cache key
     */
    private function buildCacheKey(string $key): string
    {
        return 'grading:' . md5($key);
    }

    /**
     * Record performance metrics for AI learning
     */
    private function recordPerformance(string $metric, float $time): void
    {
        if (!isset($this->performanceMetrics[$metric])) {
            $this->performanceMetrics[$metric] = ['count' => 0, 'total_time' => 0, 'avg_time' => 0];
        }

        $this->performanceMetrics[$metric]['count']++;
        $this->performanceMetrics[$metric]['total_time'] += $time;
        $this->performanceMetrics[$metric]['avg_time'] =
            $this->performanceMetrics[$metric]['total_time'] / $this->performanceMetrics[$metric]['count'];

        // Log slow queries for performance monitoring
        if ($time > 100) { // 100ms threshold
            Log::warning('Slow cache operation detected', [
                'metric' => $metric,
                'time' => $time,
                'threshold' => 100
            ]);
        }
    }

    /**
     * Track hit patterns for AI predictive caching
     */
    private function trackHitPattern(string $key, string $level): void
    {
        $pattern = $this->extractPattern($key);

        if (!isset($this->hitPatterns[$pattern])) {
            $this->hitPatterns[$pattern] = ['l1' => 0, 'l2' => 0, 'l3' => 0];
        }

        $this->hitPatterns[$pattern][$level]++;

        // AI-Ready: If pattern is frequently accessed, consider preloading
        if (array_sum($this->hitPatterns[$pattern]) > 10) {
            $this->considerPreloading($pattern);
        }
    }

    /**
     * Track miss patterns for AI learning
     */
    private function trackMissPattern(string $key): void
    {
        $pattern = $this->extractPattern($key);

        if (!isset($this->missPatterns[$pattern])) {
            $this->missPatterns[$pattern] = 0;
        }

        $this->missPatterns[$pattern]++;

        // Log frequent misses for analysis
        if ($this->missPatterns[$pattern] > 5) {
        }
    }

    /**
     * Extract pattern from cache key for AI analysis
     */
    private function extractPattern(string $key): string
    {
        // Extract pattern type from key (e.g., "similarity", "pattern", "normalize")
        if (str_contains($key, 'similarity')) {
            return 'similarity_calculation';
        } elseif (str_contains($key, 'pattern')) {
            return 'pattern_matching';
        } elseif (str_contains($key, 'normalize')) {
            return 'text_normalization';
        } elseif (str_contains($key, 'tfidf')) {
            return 'tfidf_vectorization';
        } else {
            return 'other';
        }
    }

    /**
     * Consider preloading frequently accessed patterns
     */
    private function considerPreloading(string $pattern): void
    {
        if (isset($this->predictiveCache[$pattern])) {
            return; // Already considering preloading
        }

        $this->predictiveCache[$pattern] = true;


        // In a full implementation, this would trigger background preloading
        // For now, we just log the pattern for analysis
    }

    /**
     * Log cache miss for AI training data
     */
    private function logCacheMiss(string $key, float $time): void
    {
    }

    /**
     * Get performance metrics for monitoring
     */
    public function getPerformanceMetrics(): array
    {
        $totalHits = 0;
        $totalMisses = 0;

        // Calculate hit rates
        foreach ($this->performanceMetrics as $metric => $data) {
            if (str_contains($metric, '_hit')) {
                $totalHits += $data['count'];
            }
        }

        foreach ($this->missPatterns as $pattern => $misses) {
            $totalMisses += $misses;
        }

        return array_merge($this->performanceMetrics, [
            'total_hits' => $totalHits,
            'total_misses' => $totalMisses,
            'overall_hit_rate' => $totalHits + $totalMisses > 0 ?
                ($totalHits / ($totalHits + $totalMisses)) * 100 : 0,
            'hit_patterns' => $this->hitPatterns,
            'miss_patterns' => $this->missPatterns,
            'available_levels' => array_keys($this->cacheLevels)
        ]);
    }

    /**
     * Clear all cache levels (for maintenance/testing)
     */
    public function clearAll(): void
    {
        // Clear L1 (APCu)
        if ($this->isLevelEnabled('l1')) {
            apcu_clear_cache();
        }

        // Clear L2 (Redis)
        if ($this->isLevelEnabled('l2')) {
            $this->cache->flush();
        }

        // Clear L3 (File)
        if ($this->isLevelEnabled('l3')) {
            $dir = storage_path('cache/grading');
            if (is_dir($dir)) {
                array_map('unlink', glob("$dir/*.cache"));
            }
        }

        // Reset metrics
        $this->performanceMetrics = [];
        $this->hitPatterns = [];
        $this->missPatterns = [];

    }

    /**
     * Get cache statistics for dashboard
     */
    public function getStatistics(): array
    {
        $stats = [
            'cache_levels' => count($this->cacheLevels),
            'total_cache_operations' => 0,
            'cache_hit_rate' => 0,
            'most_used_patterns' => [],
            'performance_trends' => []
        ];

        // Calculate totals
        foreach ($this->performanceMetrics as $metric => $data) {
            $stats['total_cache_operations'] += $data['count'];
        }

        // Find most used patterns
        arsort($this->hitPatterns);
        $stats['most_used_patterns'] = array_slice($this->hitPatterns, 0, 5, true);

        // Calculate hit rate
        $totalHits = 0;
        $totalMisses = array_sum($this->missPatterns);

        foreach ($this->performanceMetrics as $metric => $data) {
            if (str_contains($metric, '_hit')) {
                $totalHits += $data['count'];
            }
        }

        $stats['cache_hit_rate'] = ($totalHits + $totalMisses) > 0 ?
            round(($totalHits / ($totalHits + $totalMisses)) * 100, 2) : 0;

        return $stats;
    }
}
