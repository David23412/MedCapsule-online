<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Quiz Ultra Consolidated Cache Service
 * Handles advanced caching operations for quiz-related data
 */
class QuizUltraConsolidatedCacheService
{
    private const DEFAULT_TTL = 3600; // 1 hour
    private const MAX_CACHE_SIZE = 1000;
    
    /**
     * Generate a cache key based on operation and parameters
     */
    public static function generateCacheKey(string $operation, array $parameters = []): string
    {
        $key = 'quiz_' . $operation;
        
        if (!empty($parameters)) {
            // Sort parameters to ensure consistent key generation
            ksort($parameters);
            $key .= '_' . md5(serialize($parameters));
        }
        
        return $key;
    }
    
    /**
     * Store quiz data in cache
     */
    public static function storeQuizData(string $key, $data, ?int $ttl = null): bool
    {
        try {
            $ttl = $ttl ?? self::DEFAULT_TTL;
            
            // Compress data if it's large
            if (is_string($data) && strlen($data) > 1024) {
                $data = gzcompress($data, 6);
            }
            
            return Cache::put($key, $data, $ttl);
        } catch (\Exception $e) {
            Log::error('Failed to store quiz data in cache', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get quiz data from cache
     */
    public static function getQuizData(string $key, ?callable $fallback = null)
    {
        try {
            $data = Cache::get($key);
            
            if ($data !== null) {
                // Decompress data if it was compressed
                if (is_string($data) && substr($data, 0, 2) === "\x1f\x8b") {
                    $data = gzuncompress($data);
                }
                return $data;
            }
            
            // Execute fallback if provided
            if ($fallback) {
                $data = $fallback();
                self::storeQuizData($key, $data);
                return $data;
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Failed to retrieve quiz data from cache', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            
            // Execute fallback if available
            if ($fallback) {
                return $fallback();
            }
            
            return null;
        }
    }
    
    /**
     * Remember data with fallback
     */
    public static function remember(string $key, int $ttl, callable $callback)
    {
        return Cache::remember($key, $ttl, $callback);
    }
    
    /**
     * Clear cache for a specific topic
     */
    public static function clearTopicCache(int $topicId): bool
    {
        try {
            $pattern = "quiz_*_topic_{$topicId}_*";
            $keys = Cache::get($pattern);
            
            if ($keys) {
                foreach ($keys as $key) {
                    Cache::forget($key);
                }
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear topic cache', [
                'topic_id' => $topicId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Warm up cache for specific topics
     */
    public static function warmupCache(array $topicIds, int $userId): bool
    {
        try {
            foreach ($topicIds as $topicId) {
                $key = self::generateCacheKey('warmup', [
                    'topic_id' => $topicId,
                    'user_id' => $userId
                ]);
                
                // Store a placeholder to indicate cache warming
                Cache::put($key, 'warming', 300);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to warm up cache', [
                'topic_ids' => $topicIds,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get cache statistics
     */
    public static function getCacheStats(): array
    {
        try {
            return [
                'total_keys' => Cache::get('cache_stats_total', 0),
                'hit_rate' => Cache::get('cache_stats_hit_rate', 0.0),
                'memory_usage' => Cache::get('cache_stats_memory', 0),
                'last_updated' => Cache::get('cache_stats_updated', now())
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get cache stats', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Clear all quiz-related cache
     */
    public static function clearAllQuizCache(): bool
    {
        try {
            $pattern = 'quiz_*';
            $keys = Cache::get($pattern);
            
            if ($keys) {
                foreach ($keys as $key) {
                    Cache::forget($key);
                }
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear all quiz cache', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Check if cache is available
     */
    public static function isAvailable(): bool
    {
        try {
            $testKey = 'cache_test_' . uniqid();
            Cache::put($testKey, 'test', 1);
            $result = Cache::get($testKey) === 'test';
            Cache::forget($testKey);
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }
}
