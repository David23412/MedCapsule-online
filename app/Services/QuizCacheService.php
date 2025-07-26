<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * High-Performance Quiz Cache Service
 * Implements intelligent caching strategies for quiz generation
 */
class QuizCacheService
{
    private const CACHE_TTL_SHORT = 300;    // 5 minutes
    private const CACHE_TTL_MEDIUM = 1800;  // 30 minutes
    private const CACHE_TTL_LONG = 3600;    // 1 hour

    /**
     * Generate optimized cache key with collision resistance
     */
    public static function generateCacheKey(string $prefix, array $data): string
    {
        // Use xxh3 for ultra-fast hashing with low collision rate
        $dataHash = hash('xxh3', serialize($data));
        return "{$prefix}_{$dataHash}";
    }

    /**
     * Store quiz data with intelligent TTL selection
     */
    public static function storeQuizData(string $key, $data, ?int $ttl = null): bool
    {
        try {
            $ttl = $ttl ?? self::selectOptimalTTL($data);
            return Cache::put($key, $data, $ttl);
        } catch (\Exception $e) {
            Log::warning('Quiz cache storage failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Retrieve quiz data with fallback handling
     */
    public static function getQuizData(string $key, callable $fallback = null)
    {
        try {
            $data = Cache::get($key);
            
            if ($data === null && $fallback) {
                $data = $fallback();
                if ($data !== null) {
                    self::storeQuizData($key, $data);
                }
            }
            
            return $data;
        } catch (\Exception $e) {
            Log::warning('Quiz cache retrieval failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            
            return $fallback ? $fallback() : null;
        }
    }

    /**
     * Warm up cache for multiple topics
     */
    public static function warmupCache(array $topicIds, int $userId): void
    {
        foreach ($topicIds as $topicId) {
            $cacheKey = self::generateCacheKey('quiz_options', [
                'topic_id' => $topicId,
                'user_id' => $userId
            ]);
            
            // Mark for background warming
            Cache::put($cacheKey . '_warming', true, 60);
        }
    }

    /**
     * Clear cache for specific topic
     */
    public static function clearTopicCache(int $topicId): void
    {
        $patterns = [
            "quiz_options_{$topicId}_*",
            "quiz_gen_{$topicId}_*",
            "quiz_data_{$topicId}_*"
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Get cache statistics for monitoring
     */
    public static function getCacheStats(): array
    {
        return [
            'hit_rate' => self::calculateHitRate(),
            'memory_usage' => self::getMemoryUsage(),
            'active_keys' => self::getActiveKeyCount()
        ];
    }

    /**
     * Select optimal TTL based on data characteristics
     */
    private static function selectOptimalTTL($data): int
    {
        if (is_array($data)) {
            $size = count($data);
            
            if ($size > 100) {
                return self::CACHE_TTL_LONG;  // Large datasets cache longer
            } elseif ($size > 20) {
                return self::CACHE_TTL_MEDIUM;
            }
        }
        
        return self::CACHE_TTL_SHORT;
    }

    /**
     * Calculate cache hit rate for monitoring
     */
    private static function calculateHitRate(): float
    {
        $hits = Cache::get('cache_hits', 0);
        $misses = Cache::get('cache_misses', 0);
        $total = $hits + $misses;
        
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0.0;
    }

    /**
     * Get estimated memory usage
     */
    private static function getMemoryUsage(): string
    {
        return number_format(memory_get_usage(true) / 1024 / 1024, 2) . ' MB';
    }

    /**
     * Get count of active cache keys
     */
    private static function getActiveKeyCount(): int
    {
        // This is a simplified implementation
        // In production, you'd use Redis commands or similar
        return Cache::get('active_key_count', 0);
    }
}