<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * BULLETPROOF Cache Invalidation Service
 * Automatically clears relevant caches when data changes
 */
class CacheInvalidationService
{
    /**
     * BULLETPROOF: Invalidate home page cache when courses change
     */
    public function invalidateHomePageCache(): bool
    {
        try {
            $homePageService = app(HomePageService::class);
            return $homePageService->invalidateCache();
        } catch (\Exception $e) {
            Log::error('Failed to invalidate home page cache via service', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * BULLETPROOF: Invalidate all major caches (use sparingly)
     */
    public function invalidateAllCaches(): bool
    {
        try {
            $cacheKeys = [
                // Home page caches
                'home_data_v4_bulletproof_2025',
                'home_statistics_v4_bulletproof_2025',
                'home_featured_courses_v4_bulletproof_2025',
                'home_health_check_v4_bulletproof_2025',

                // Legacy caches
                'home_statistics_nuclear_v3',
                'featured_courses_nuclear_v3',

                // Course listing caches
                'courses_list',
                'courses_with_stats',
                'courses_cache_version',

                // User-specific caches (these will be cleared naturally)
                // 'course_*', 'user_*' - handled by individual model events
            ];

            $cleared = 0;
            foreach ($cacheKeys as $key) {
                if (Cache::forget($key)) {
                    $cleared++;
                }
            }


            return true;
        } catch (\Exception $e) {
            Log::error('Failed to invalidate all caches', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * BULLETPROOF: Force refresh home page data
     */
    public function forceHomePageRefresh(): array
    {
        try {
            $homePageService = app(HomePageService::class);
            return $homePageService->forceRefresh();
        } catch (\Exception $e) {
            Log::error('Failed to force home page refresh', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * BULLETPROOF: Health check for cache system
     */
    public function checkCacheHealth(): array
    {
        try {
            $homePageService = app(HomePageService::class);

            // Get cache health info
            $cacheKey = 'home_health_check_v4_bulletproof_2025';
            $health = Cache::get($cacheKey);

            $isHealthy = $health &&
                        isset($health['version']) &&
                        $health['version'] === 'v4_bulletproof_2025' &&
                        isset($health['last_updated']) &&
                        $health['last_updated']->diffInMinutes(now()) < 5;

            return [
                'healthy' => $isHealthy,
                'last_updated' => $health['last_updated'] ?? null,
                'version' => $health['version'] ?? null,
                'data_integrity' => $health['data_integrity'] ?? false,
                'cache_driver' => config('cache.default'),
                'timestamp' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];
        }
    }
}
