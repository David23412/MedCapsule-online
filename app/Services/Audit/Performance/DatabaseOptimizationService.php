<?php

namespace App\Services\Audit\Performance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Database Performance Optimization Service
 * Provides methods to optimize database queries and connections
 */
class DatabaseOptimizationService
{
    /**
     * Optimize database connection settings
     */
    public function optimizeConnections(): void
    {
        // Set optimal MySQL settings
        DB::statement("SET SESSION query_cache_type = ON");
        DB::statement("SET SESSION query_cache_size = 67108864"); // 64MB
        DB::statement("SET SESSION innodb_buffer_pool_size = 134217728"); // 128MB
    }
    
    /**
     * Enable query result caching
     */
    public function enableQueryCaching(): void
    {
        // Cache frequently used queries
        $this->cacheUserCounts();
        $this->cacheSystemSettings();
        $this->cacheNavigationData();
    }
    
    /**
     * Cache user statistics
     */
    private function cacheUserCounts(): void
    {
        Cache::remember('user_counts', 300, function () {
            return [
                'total_users' => DB::table('users')->count(),
                'active_users' => DB::table('users')->where('last_login_at', '>', now()->subDays(30))->count(),
                'new_users_today' => DB::table('users')->whereDate('created_at', today())->count(),
            ];
        });
    }
    
    /**
     * Cache system settings
     */
    private function cacheSystemSettings(): void
    {
        Cache::remember('system_settings', 3600, function () {
            return DB::table('settings')->pluck('value', 'key')->toArray();
        });
    }
    
    /**
     * Cache navigation data
     */
    private function cacheNavigationData(): void
    {
        Cache::remember('navigation_menu', 1800, function () {
            return DB::table('menu_items')
                ->where('active', true)
                ->orderBy('sort_order')
                ->get();
        });
    }
    
    /**
     * Optimize slow queries with indexes
     */
    public function optimizeSlowQueries(): array
    {
        $optimizations = [];
        
        // Add indexes for common queries
        $indexQueries = [
            "CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)",
            "CREATE INDEX IF NOT EXISTS idx_users_last_login ON users(last_login_at)",
            "CREATE INDEX IF NOT EXISTS idx_sessions_user_id ON sessions(user_id)",
            "CREATE INDEX IF NOT EXISTS idx_cache_key ON cache(key)",
        ];
        
        foreach ($indexQueries as $query) {
            try {
                DB::statement($query);
                $optimizations[] = "Index created: " . substr($query, strpos($query, 'idx_'));
            } catch (\Exception $e) {
                // Index might already exist
            }
        }
        
        return $optimizations;
    }
}