<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\Eloquent\Model;

class AdvancedCacheService
{
    /**
     * Cache TTL configurations
     */
    const CACHE_TTL = [
        'user_data' => 3600,        // 1 hour
        'quiz_data' => 1800,        // 30 minutes
        'course_data' => 7200,      // 2 hours
        'analytics' => 300,         // 5 minutes
        'session_data' => 1800,     // 30 minutes
        'search_results' => 600,    // 10 minutes
        'api_responses' => 300,     // 5 minutes
        'static_content' => 86400,  // 24 hours
        'computed_data' => 3600,    // 1 hour
    ];

    /**
     * Cache tags for better organization
     */
    const CACHE_TAGS = [
        'users',
        'quizzes',
        'courses',
        'analytics',
        'sessions',
        'search',
        'api',
        'static',
        'computed'
    ];

    /**
     * Cache warming priorities
     */
    const WARM_UP_PRIORITIES = [
        'critical' => ['user_data', 'session_data'],
        'high' => ['quiz_data', 'course_data'],
        'medium' => ['analytics', 'search_results'],
        'low' => ['static_content', 'computed_data']
    ];

    /**
     * Intelligent cache key generation
     */
    public static function generateCacheKey(string $type, array $params = []): string
    {
        $key = "advanced:{$type}:";

        // Sort parameters for consistent key generation
        ksort($params);

        foreach ($params as $param => $value) {
            $key .= "{$param}=" . (is_array($value) ? json_encode($value) : $value) . ":";
        }

        return rtrim($key, ':');
    }

    /**
     * Smart cache retrieval with fallback
     */
    public static function smartGet(string $key, callable $callback, int $ttl = null, array $tags = []): mixed
    {
        // Try to get from cache first
        $cached = self::getFromCache($key, $tags);

        if ($cached !== null) {
            Log::info('Cache hit', ['key' => $key]);
            return $cached;
        }

        // Cache miss - execute callback
        Log::info('Cache miss', ['key' => $key]);

        try {
            $data = $callback();

            // Store in cache
            self::storeInCache($key, $data, $ttl, $tags);

            return $data;
        } catch (\Exception $e) {
            Log::error('Cache callback failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            // Return fallback data if available
            return self::getFallbackData($key);
        }
    }

    /**
     * Multi-level cache retrieval
     */
    public static function multiLevelGet(string $key, callable $callback, array $options = []): mixed
    {
        $levels = $options['levels'] ?? ['redis', 'file', 'database'];

        foreach ($levels as $level) {
            $data = self::getFromLevel($key, $level);

            if ($data !== null) {
                Log::info("Cache hit on level: {$level}", ['key' => $key]);
                return $data;
            }
        }

        // All levels missed - execute callback
        Log::info('All cache levels missed', ['key' => $key]);

        try {
            $data = $callback();

            // Store in all levels
            foreach ($levels as $level) {
                self::storeInLevel($key, $data, $level, $options);
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('Multi-level cache callback failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            return self::getFallbackData($key);
        }
    }

    /**
     * Intelligent cache invalidation
     */
    public static function intelligentInvalidate(string $pattern, array $tags = []): bool
    {
        try {
            // Invalidate by tags first (more efficient)
            if (!empty($tags)) {
                Cache::tags($tags)->flush();
                Log::info('Invalidated cache by tags', ['tags' => $tags]);
            }

            // Invalidate by pattern
            if ($pattern) {
                $keys = self::getKeysByPattern($pattern);
                foreach ($keys as $key) {
                    Cache::forget($key);
                }
                Log::info('Invalidated cache by pattern', [
                    'pattern' => $pattern,
                    'keys_count' => count($keys)
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Cache invalidation failed', [
                'pattern' => $pattern,
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Cache warming for frequently accessed data
     */
    public static function warmUpCache(array $priorities = null): array
    {
        $priorities = $priorities ?? self::WARM_UP_PRIORITIES;
        $results = [];

        foreach ($priorities as $priority => $types) {
            $results[$priority] = [];

            foreach ($types as $type) {
                try {
                    $count = self::warmUpType($type);
                    $results[$priority][$type] = $count;
                    Log::info("Warmed up cache for type: {$type}", [
                        'priority' => $priority,
                        'count' => $count
                    ]);
                } catch (\Exception $e) {
                    Log::error("Failed to warm up cache for type: {$type}", [
                        'priority' => $priority,
                        'error' => $e->getMessage()
                    ]);
                    $results[$priority][$type] = 0;
                }
            }
        }

        return $results;
    }

    /**
     * Predictive caching based on user behavior
     */
    public static function predictiveCache(string $userId, array $behaviorData): void
    {
        try {
            // Analyze user behavior patterns
            $patterns = self::analyzeBehaviorPatterns($behaviorData);

            // Pre-cache likely future requests
            foreach ($patterns as $pattern) {
                $predictedKey = self::generateCacheKey('predicted', [
                    'user_id' => $userId,
                    'pattern' => $pattern
                ]);

                // Cache with shorter TTL for predictions
                Cache::put($predictedKey, $pattern, 300); // 5 minutes
            }

            Log::info('Predictive cache generated', [
                'user_id' => $userId,
                'patterns_count' => count($patterns)
            ]);
        } catch (\Exception $e) {
            Log::error('Predictive caching failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Cache performance monitoring
     */
    public static function getCachePerformance(): array
    {
        try {
            $stats = [];

            // Hit rate calculation
            $hits = Cache::get('cache_hits', 0);
            $misses = Cache::get('cache_misses', 0);
            $total = $hits + $misses;

            $stats['hit_rate'] = $total > 0 ? round(($hits / $total) * 100, 2) : 0;
            $stats['hits'] = $hits;
            $stats['misses'] = $misses;

            // Memory usage (if using Redis)
            if (Redis::connection()) {
                $info = Redis::info('memory');
                $stats['memory_used'] = $info['used_memory_human'] ?? 'N/A';
                $stats['memory_peak'] = $info['used_memory_peak_human'] ?? 'N/A';
            }

            // Cache size by type
            foreach (self::CACHE_TTL as $type => $ttl) {
                $pattern = "advanced:{$type}:*";
                $keys = self::getKeysByPattern($pattern);
                $stats['size_by_type'][$type] = count($keys);
            }

            return $stats;
        } catch (\Exception $e) {
            Log::error('Cache performance monitoring failed', [
                'error' => $e->getMessage()
            ]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Smart cache compression for large data
     */
    public static function compressAndCache(string $key, mixed $data, int $ttl = null, array $tags = []): bool
    {
        try {
            // Check if data is large enough to compress
            $serialized = serialize($data);
            $originalSize = strlen($serialized);

            if ($originalSize > 1024) { // Compress if > 1KB
                $compressed = gzcompress($serialized);
                $compressedSize = strlen($compressed);

                if ($compressedSize < $originalSize) {
                    // Store compressed data with flag
                    $cacheData = [
                        'compressed' => true,
                        'data' => base64_encode($compressed),
                        'original_size' => $originalSize,
                        'compressed_size' => $compressedSize
                    ];
                } else {
                    // Compression not effective
                    $cacheData = [
                        'compressed' => false,
                        'data' => $data
                    ];
                }
            } else {
                // No compression needed
                $cacheData = [
                    'compressed' => false,
                    'data' => $data
                ];
            }

            return self::storeInCache($key, $cacheData, $ttl, $tags);
        } catch (\Exception $e) {
            Log::error('Cache compression failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Smart cache retrieval with decompression
     */
    public static function getAndDecompress(string $key, array $tags = []): mixed
    {
        try {
            $cached = self::getFromCache($key, $tags);

            if ($cached === null) {
                return null;
            }

            if (is_array($cached) && isset($cached['compressed'])) {
                if ($cached['compressed']) {
                    $compressed = base64_decode($cached['data']);
                    $serialized = gzuncompress($compressed);
                    return unserialize($serialized);
                } else {
                    return $cached['data'];
                }
            }

            return $cached;
        } catch (\Exception $e) {
            Log::error('Cache decompression failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Private helper methods
     */
    private static function getFromCache(string $key, array $tags = []): mixed
    {
        try {
            if (!empty($tags)) {
                return Cache::tags($tags)->get($key);
            }
            return Cache::get($key);
        } catch (\Exception $e) {
            Log::error('Cache retrieval failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private static function storeInCache(string $key, mixed $data, int $ttl = null, array $tags = []): bool
    {
        try {
            $ttl = $ttl ?? 3600; // Default 1 hour

            if (!empty($tags)) {
                Cache::tags($tags)->put($key, $data, $ttl);
            } else {
                Cache::put($key, $data, $ttl);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Cache storage failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private static function getFromLevel(string $key, string $level): mixed
    {
        return match($level) {
            'redis' => Redis::get($key),
            'file' => Cache::store('file')->get($key),
            'database' => Cache::store('database')->get($key),
            default => null
        };
    }

    private static function storeInLevel(string $key, mixed $data, string $level, array $options = []): void
    {
        $ttl = $options['ttl'] ?? 3600;

        match($level) {
            'redis' => Redis::setex($key, $ttl, serialize($data)),
            'file' => Cache::store('file')->put($key, $data, $ttl),
            'database' => Cache::store('database')->put($key, $data, $ttl),
            default => null
        };
    }

    private static function getKeysByPattern(string $pattern): array
    {
        try {
            if (Redis::connection()) {
                return Redis::keys($pattern);
            }
            return [];
        } catch (\Exception $e) {
            Log::error('Pattern key retrieval failed', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    private static function getFallbackData(string $key): mixed
    {
        // Implement fallback data logic based on key type
        if (str_contains($key, 'user_data')) {
            return ['fallback' => true, 'message' => 'User data temporarily unavailable'];
        }

        return null;
    }

    private static function analyzeBehaviorPatterns(array $behaviorData): array
    {
        // Simple pattern analysis - can be enhanced with ML
        $patterns = [];

        if (isset($behaviorData['frequent_quizzes'])) {
            $patterns[] = 'quiz_access';
        }

        if (isset($behaviorData['frequent_courses'])) {
            $patterns[] = 'course_access';
        }

        return $patterns;
    }

    private static function warmUpType(string $type): int
    {
        // Implement type-specific warming logic
        $count = 0;

        switch ($type) {
            case 'user_data':
                // Warm up frequently accessed user data
                $count = self::warmUpUserData();
                break;
            case 'quiz_data':
                // Warm up popular quiz data
                $count = self::warmUpQuizData();
                break;
            case 'course_data':
                // Warm up course data
                $count = self::warmUpCourseData();
                break;
        }

        return $count;
    }

    private static function warmUpUserData(): int
    {
        // Implementation for warming up user data
        return 0;
    }

    private static function warmUpQuizData(): int
    {
        // Implementation for warming up quiz data
        return 0;
    }

    private static function warmUpCourseData(): int
    {
        // Implementation for warming up course data
        return 0;
    }
}