<?php

namespace App\Services\Audit\Performance;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * Advanced Caching Service
 * Implements sophisticated caching strategies for maximum performance
 */
class AdvancedCachingService
{
    /**
     * Cache with tags for easy invalidation
     */
    public function cacheWithTags(string $key, array $tags, $data, int $ttl = 3600)
    {
        return Cache::tags($tags)->put($key, $data, $ttl);
    }
    
    /**
     * Implement cache warming for critical data
     */
    public function warmCache(): void
    {
        $this->warmUserCache();
        $this->warmSystemCache();
        $this->warmContentCache();
    }
    
    /**
     * Warm user-related cache
     */
    private function warmUserCache(): void
    {
        // Pre-cache user statistics
        Cache::put('stats:users:total', \DB::table('users')->count(), 1800);
        Cache::put('stats:users:active', \DB::table('users')->where('last_login_at', '>', now()->subDays(7))->count(), 1800);
        
        // Pre-cache user roles and permissions
        $roles = \DB::table('roles')->with('permissions')->get();
        Cache::put('system:roles', $roles, 3600);
    }
    
    /**
     * Warm system cache
     */
    private function warmSystemCache(): void
    {
        // Pre-cache system settings
        $settings = \DB::table('settings')->pluck('value', 'key');
        Cache::put('system:settings', $settings, 7200);
        
        // Pre-cache configuration
        Cache::put('system:config', config()->all(), 3600);
    }
    
    /**
     * Warm content cache
     */
    private function warmContentCache(): void
    {
        // Pre-cache navigation menu
        $menu = \DB::table('menu_items')->where('active', true)->orderBy('sort_order')->get();
        Cache::put('content:navigation', $menu, 3600);
        
        // Pre-cache frequently accessed content
        $popularContent = \DB::table('content')->where('status', 'published')->orderBy('views', 'desc')->limit(50)->get();
        Cache::put('content:popular', $popularContent, 1800);
    }
    
    /**
     * Implement intelligent cache invalidation
     */
    public function invalidateRelatedCache(string $type, $id = null): void
    {
        switch ($type) {
            case 'user':
                Cache::tags(['users', 'stats'])->flush();
                break;
            case 'content':
                Cache::tags(['content', 'navigation'])->flush();
                break;
            case 'system':
                Cache::tags(['system', 'config'])->flush();
                break;
        }
    }
    
    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        $redis = Redis::connection();
        
        return [
            'total_keys' => $redis->dbsize(),
            'memory_usage' => $redis->info('memory')['used_memory_human'] ?? 'N/A',
            'hit_rate' => $this->calculateHitRate(),
            'cache_size' => $this->getCacheSize(),
        ];
    }
    
    /**
     * Calculate cache hit rate
     */
    private function calculateHitRate(): float
    {
        $hits = Cache::get('cache_stats:hits', 0);
        $misses = Cache::get('cache_stats:misses', 0);
        
        if ($hits + $misses === 0) {
            return 0;
        }
        
        return round(($hits / ($hits + $misses)) * 100, 2);
    }
    
    /**
     * Get total cache size
     */
    private function getCacheSize(): string
    {
        try {
            $redis = Redis::connection();
            $info = $redis->info('memory');
            return $info['used_memory_human'] ?? 'N/A';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }
}