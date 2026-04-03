<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * FIXED: Advanced Cache Service - Military-Grade Cache Optimization
 * Eliminates cache thrashing, implements intelligent cache warming, and provides performance monitoring
 */
class AdvancedCacheService
{
    // Cache TTL configurations for different data types
    const CACHE_TTL = [
        'user_data' => 3600,        // 1 hour - user-specific data
        'course_data' => 1800,      // 30 minutes - course information
        'payment_data' => 300,      // 5 minutes - payment processing
        'analytics' => 7200,        // 2 hours - analytics data
        'static_data' => 86400,     // 24 hours - rarely changing data
    ];

    // Cache key prefixes to prevent collisions
    const CACHE_PREFIXES = [
        'user' => 'usr',
        'course' => 'crs',
        'payment' => 'pay',
        'quiz' => 'qz',
        'topic' => 'tpc',
        'analytics' => 'anl',
        'system' => 'sys'
    ];

    // Cache warming priority levels
    const WARM_PRIORITY = [
        'critical' => 100,    // User session data, payment processing
        'high' => 75,         // Course information, user progress
        'medium' => 50,       // Analytics, reports
        'low' => 25          // Historical data, logs
    ];

    /**
     * FIXED: Generate optimized cache key with collision prevention
     */
    public static function generateCacheKey(string $type, string $identifier, array $context = []): string
    {
        $prefix = self::CACHE_PREFIXES[$type] ?? 'gen';
        $contextHash = empty($context) ? '' : '_' . hash('xxhash', serialize($context));

        return "{$prefix}_{$identifier}{$contextHash}";
    }

    /**
     * FIXED: Smart cache retrieval with intelligent fallback
     */
    public function smartGet(string $key, callable $callback, int $ttl = 3600, array $options = []): mixed
    {
        $cacheKey = $this->optimizeCacheKey($key);

        $cachedValue = $this->getCachedValueOrMiss($cacheKey);

        // Check cache first
        if (!$this->isCacheMiss($cachedValue)) {
            $this->recordCacheHit($cacheKey);
            return $cachedValue;
        }

        // Cache miss - generate fresh data
        $this->recordCacheMiss($cacheKey);
        $data = $callback();

        // Store with optimized compression if enabled
        if ($this->shouldCompress($data)) {
            $compressedData = $this->compressData($data);
            Cache::put($cacheKey, $compressedData, $ttl);
            $this->storeCompressionMetadata($cacheKey, $data);
        } else {
            Cache::put($cacheKey, $data, $ttl);
        }

        return $data;
    }

    /**
     * FIXED: Intelligent cache warming for critical data
     */
    public function warmCriticalCaches(): array
    {
        $results = [
            'warmed' => 0,
            'failed' => 0,
            'skipped' => 0
        ];

        // Warm user session caches (highest priority)
        $results = $this->mergeResults($results, $this->warmUserCaches());

        // Warm course caches (high priority)
        $results = $this->mergeResults($results, $this->warmCourseCaches());

        // Warm system caches (medium priority)
        $results = $this->mergeResults($results, $this->warmSystemCaches());

        return $results;
    }

    /**
     * FIXED: Warm user-related caches
     */
    private function warmUserCaches(): array
    {
        $results = ['warmed' => 0, 'failed' => 0, 'skipped' => 0];

        try {
            // Get active users from recent activity
            $activeUserIds = \App\Models\User::where('last_active_at', '>', now()->subHours(24))
                ->pluck('id')
                ->take(100); // Limit to prevent overload

            foreach ($activeUserIds as $userId) {
                // Warm user course enrollment cache
                $cacheKey = self::generateCacheKey('user', "courses_{$userId}");
                if ($this->isCacheMiss($this->getCachedValueOrMiss($cacheKey))) {
                    $courses = \App\Models\User::find($userId)?->courses ?? collect();
                    Cache::put($cacheKey, $courses, self::CACHE_TTL['user_data']);
                    $results['warmed']++;
                } else {
                    $results['skipped']++;
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to warm user caches', ['error' => $e->getMessage()]);
            $results['failed']++;
        }

        return $results;
    }

    /**
     * FIXED: Warm course-related caches
     */
    private function warmCourseCaches(): array
    {
        $results = ['warmed' => 0, 'failed' => 0, 'skipped' => 0];

        try {
            // Get popular courses (most enrolled)
            $popularCourseIds = \Illuminate\Support\Facades\DB::table('course_user')
                ->where('is_enrolled', true)
                ->groupBy('course_id')
                ->orderByRaw('COUNT(*) DESC')
                ->pluck('course_id')
                ->take(50);

            // Determine which courses actually need cache warming
            $courseIdsNeedingWarm = [];
            foreach ($popularCourseIds as $courseId) {
                $cacheKey = self::generateCacheKey('course', "enrolled_count_{$courseId}");
                if ($this->isCacheMiss($this->getCachedValueOrMiss($cacheKey))) {
                    $courseIdsNeedingWarm[] = $courseId;
                } else {
                    $results['skipped']++;
                }
            }

            // Single batch query instead of N queries in a loop
            if (!empty($courseIdsNeedingWarm)) {
                $enrollmentCounts = \Illuminate\Support\Facades\DB::table('course_user')
                    ->whereIn('course_id', $courseIdsNeedingWarm)
                    ->where('is_enrolled', true)
                    ->selectRaw('course_id, COUNT(*) as enrolled_count')
                    ->groupBy('course_id')
                    ->pluck('enrolled_count', 'course_id');

                foreach ($courseIdsNeedingWarm as $courseId) {
                    $cacheKey = self::generateCacheKey('course', "enrolled_count_{$courseId}");
                    $count = $enrollmentCounts->get($courseId, 0);
                    Cache::put($cacheKey, $count, self::CACHE_TTL['course_data']);
                    $results['warmed']++;
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to warm course caches', ['error' => $e->getMessage()]);
            $results['failed']++;
        }

        return $results;
    }

    /**
     * FIXED: Warm system-level caches
     */
    private function warmSystemCaches(): array
    {
        $results = ['warmed' => 0, 'failed' => 0, 'skipped' => 0];

        try {
            // Warm system statistics
            $statsCacheKey = self::generateCacheKey('system', 'stats');
            if ($this->isCacheMiss($this->getCachedValueOrMiss($statsCacheKey))) {
                $stats = [
                    'total_users' => \App\Models\User::count(),
                    'total_courses' => \App\Models\Course::count(),
                    'total_payments' => \App\Models\Payment::count(),
                    'active_users_24h' => \App\Models\User::where('last_active_at', '>', now()->subDay())->count()
                ];
                Cache::put($statsCacheKey, $stats, self::CACHE_TTL['analytics']);
                $results['warmed']++;
            } else {
                $results['skipped']++;
            }
        } catch (\Exception $e) {
            Log::error('Failed to warm system caches', ['error' => $e->getMessage()]);
            $results['failed']++;
        }

        return $results;
    }

    /**
     * FIXED: Intelligent cache invalidation with cascade support
     */
    public function invalidateCacheCascade(string $type, string $identifier, array $relatedTypes = []): int
    {
        $invalidated = 0;

        // Invalidate primary cache
        $primaryKey = self::generateCacheKey($type, $identifier);
        if (!$this->isCacheMiss($this->getCachedValueOrMiss($primaryKey))) {
            Cache::forget($primaryKey);
            $invalidated++;
        }

        // Invalidate related caches
        foreach ($relatedTypes as $relatedType => $relatedId) {
            $relatedKey = self::generateCacheKey($relatedType, $relatedId);
            if (!$this->isCacheMiss($this->getCachedValueOrMiss($relatedKey))) {
                Cache::forget($relatedKey);
                $invalidated++;
            }
        }

        // Special cascade rules
        if ($type === 'user') {
            // When user is updated, invalidate all user-related caches
            $this->invalidateUserRelatedCaches($identifier);
            $invalidated += 10; // Approximate
        } elseif ($type === 'course') {
            // When course is updated, invalidate all course-related caches
            $this->invalidateCourseRelatedCaches($identifier);
            $invalidated += 15; // Approximate
        }


        return $invalidated;
    }

    /**
     * FIXED: Invalidate all user-related caches
     */
    private function invalidateUserRelatedCaches(string $userId): void
    {
        $patterns = [
            self::generateCacheKey('user', "courses_{$userId}"),
            self::generateCacheKey('user', "payments_{$userId}"),
            self::generateCacheKey('user', "progress_{$userId}"),
            self::generateCacheKey('user', "stats_{$userId}")
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * FIXED: Invalidate all course-related caches
     */
    private function invalidateCourseRelatedCaches(string $courseId): void
    {
        $patterns = [
            self::generateCacheKey('course', "enrolled_count_{$courseId}"),
            self::generateCacheKey('course', "students_{$courseId}"),
            self::generateCacheKey('course', "payments_{$courseId}"),
            self::generateCacheKey('course', "completion_{$courseId}")
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * FIXED: Optimize cache key for better performance
     */
    private function optimizeCacheKey(string $key): string
    {
        // Remove special characters and normalize
        $optimized = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $key);

        // Ensure key length is within Redis limits
        if (strlen($optimized) > 250) {
            $optimized = substr($optimized, 0, 250) . '_' . hash('xxhash', $key);
        }

        return $optimized;
    }

    /**
     * FIXED: Record cache hit for analytics
     */
    private function recordCacheHit(string $key): void
    {
        // Increment hit counter (using atomic operations if Redis is available)
        if (class_exists('Redis')) {
            try {
                Redis::incr("cache:hits:{$key}");
            } catch (\Exception $e) {
                // Fallback to simple increment
                $hits = Cache::get("cache:hits:{$key}", 0);
                Cache::put("cache:hits:{$key}", $hits + 1, 3600);
            }
        }
    }

    /**
     * FIXED: Record cache miss for analytics
     */
    private function recordCacheMiss(string $key): void
    {
        // Increment miss counter
        if (class_exists('Redis')) {
            try {
                Redis::incr("cache:misses:{$key}");
            } catch (\Exception $e) {
                $misses = Cache::get("cache:misses:{$key}", 0);
                Cache::put("cache:misses:{$key}", $misses + 1, 3600);
            }
        }
    }

    /**
     * FIXED: Determine if data should be compressed
     */
    private function shouldCompress(mixed $data): bool
    {
        // Compress large arrays and objects
        if (is_array($data) && count($data) > 100) {
            return true;
        }

        if (is_object($data) && count(get_object_vars($data)) > 50) {
            return true;
        }

        // Compress large strings
        if (is_string($data) && strlen($data) > 10000) {
            return true;
        }

        return false;
    }

    /**
     * FIXED: Compress data for storage
     */
    private function compressData(mixed $data): string
    {
        $serialized = serialize($data);
        return gzcompress($serialized, 6); // Balance between speed and compression
    }

    /**
     * FIXED: Store compression metadata
     */
    private function storeCompressionMetadata(string $cacheKey, mixed $originalData): void
    {
        $metadata = [
            'compressed' => true,
            'original_size' => strlen(serialize($originalData)),
            'compressed_at' => now()->toISOString()
        ];

        Cache::put("{$cacheKey}:meta", $metadata, self::CACHE_TTL['static_data']);
    }

    /**
     * FIXED: Get cache performance metrics
     */
    public function getCacheMetrics(): array
    {
        $metrics = [
            'hit_rate' => $this->calculateHitRate(),
            'total_requests' => $this->getTotalCacheRequests(),
            'memory_usage' => $this->getCacheMemoryUsage(),
            'warmed_keys' => $this->getWarmedKeyCount(),
            'compression_savings' => $this->getCompressionSavings()
        ];

        return $metrics;
    }

    /**
     * FIXED: Calculate cache hit rate
     */
    private function calculateHitRate(): float
    {
        if (!class_exists('Redis')) {
            return 0.0;
        }

        try {
            $totalHits = Redis::get('cache:total_hits') ?? 0;
            $totalMisses = Redis::get('cache:total_misses') ?? 0;

            $total = $totalHits + $totalMisses;
            return $total > 0 ? ($totalHits / $total) * 100 : 0.0;
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * FIXED: Get total cache requests
     */
    private function getTotalCacheRequests(): int
    {
        if (!class_exists('Redis')) {
            return 0;
        }

        try {
            return (Redis::get('cache:total_hits') ?? 0) + (Redis::get('cache:total_misses') ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * FIXED: Get cache memory usage
     */
    private function getCacheMemoryUsage(): array
    {
        if (!class_exists('Redis')) {
            return ['used' => 0, 'available' => 0, 'percentage' => 0];
        }

        try {
            $info = Redis::info('memory');
            return [
                'used' => $info['used_memory'] ?? 0,
                'available' => $info['maxmemory'] ?? 0,
                'percentage' => $info['maxmemory'] ? (($info['used_memory'] ?? 0) / $info['maxmemory']) * 100 : 0
            ];
        } catch (\Exception $e) {
            return ['used' => 0, 'available' => 0, 'percentage' => 0];
        }
    }

    /**
     * FIXED: Get warmed key count
     */
    private function getWarmedKeyCount(): int
    {
        // Count keys with warm prefix
        $patterns = ['usr_*', 'crs_*', 'pay_*', 'qz_*', 'tpc_*', 'anl_*', 'sys_*'];
        $count = 0;

        foreach ($patterns as $pattern) {
            $keys = Cache::get($pattern, []);
            $count += count($keys);
        }

        return $count;
    }

    /**
     * FIXED: Get compression savings
     */
    private function getCompressionSavings(): array
    {
        // Calculate space saved by compression
        $compressedKeys = Cache::get('compressed_keys', []);
        $savings = ['original' => 0, 'compressed' => 0, 'saved' => 0];

        foreach ($compressedKeys as $key) {
            $metadata = Cache::get("{$key}:meta");
            if ($metadata) {
                $savings['original'] += $metadata['original_size'];
                $compressedData = Cache::get($key);
                $savings['compressed'] += strlen($compressedData);
            }
        }

        $savings['saved'] = $savings['original'] - $savings['compressed'];
        return $savings;
    }

    /**
     * FIXED: Merge results arrays
     */
    private function mergeResults(array $base, array $additional): array
    {
        return [
            'warmed' => $base['warmed'] + $additional['warmed'],
            'failed' => $base['failed'] + $additional['failed'],
            'skipped' => $base['skipped'] + $additional['skipped']
        ];
    }

    private function getCachedValueOrMiss(string $key): mixed
    {
        return Cache::get($key, $this->cacheMissSentinel());
    }

    private function isCacheMiss(mixed $value): bool
    {
        return $value === $this->cacheMissSentinel();
    }

    private function cacheMissSentinel(): \stdClass
    {
        static $sentinel = null;
        if ($sentinel === null) {
            $sentinel = new \stdClass();
        }

        return $sentinel;
    }

    /**
     * FIXED: Emergency cache clear for system maintenance
     */
    public function emergencyCacheClear(): array
    {
        $cleared = 0;
        $failed = 0;

        try {
            // Clear all cache tags/prefixes
            $prefixes = array_values(self::CACHE_PREFIXES);

            foreach ($prefixes as $prefix) {
                $pattern = $prefix . '*';
                // Use Redis SCAN if available
                if (class_exists('Redis')) {
                    $iterator = null;
                    while ($keys = Redis::scan($iterator, $pattern, 100)) {
                        foreach ($keys as $key) {
                            if (Redis::del($key)) {
                                $cleared++;
                            } else {
                                $failed++;
                            }
                        }
                    }
                } else {
                    Cache::flush(); // Fallback for non-Redis
                    break;
                }
            }
        } catch (\Exception $e) {
            Log::error('Emergency cache clear failed', ['error' => $e->getMessage()]);
            $failed++;
        }

        return ['cleared' => $cleared, 'failed' => $failed];
    }
}
