<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\UniversalNotificationService;
use Illuminate\Support\Facades\Cache;

class CleanupNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notifications:cleanup 
                            {--all : Clean up notifications for all users}
                            {--user= : Clean up notifications for specific user ID}
                            {--force : Force aggressive cleanup}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up expired notifications and enforce limits for optimal performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Starting notification cleanup...');
        
        $totalCleaned = 0;
        $totalUsers = 0;
        
        if ($this->option('user')) {
            // Clean specific user
            $user = User::find($this->option('user'));
            if (!$user) {
                $this->error("User with ID {$this->option('user')} not found");
                return 1;
            }
            
            $cleaned = $this->cleanupUserNotifications($user);
            $this->info("✅ Cleaned {$cleaned} notifications for user {$user->id}");
            
        } elseif ($this->option('all')) {
            // Clean all users
            $this->info('🔄 Cleaning notifications for all users...');
            
            User::chunk(100, function ($users) use (&$totalCleaned, &$totalUsers) {
                foreach ($users as $user) {
                    $cleaned = $this->cleanupUserNotifications($user);
                    $totalCleaned += $cleaned;
                    $totalUsers++;
                    
                    if ($cleaned > 0) {
                        $this->line("  User {$user->id}: {$cleaned} notifications cleaned");
                    }
                }
            });
            
            $this->info("✅ Processed {$totalUsers} users, cleaned {$totalCleaned} total notifications");
            
        } else {
            // Clean users with cached notifications only
            $this->info('🎯 Cleaning notifications for active users only...');
            
            $cacheKeys = $this->getCachedNotificationKeys();
            
            foreach ($cacheKeys as $cacheKey) {
                $userId = str_replace('user_notifications_', '', $cacheKey);
                $user = User::find($userId);
                
                if ($user) {
                    $cleaned = $this->cleanupUserNotifications($user);
                    $totalCleaned += $cleaned;
                    $totalUsers++;
                    
                    if ($cleaned > 0) {
                        $this->line("  User {$user->id}: {$cleaned} notifications cleaned");
                    }
                }
            }
            
            $this->info("✅ Processed {$totalUsers} active users, cleaned {$totalCleaned} total notifications");
        }
        
        // Performance optimization stats
        $this->newLine();
        $this->info('📊 Performance Optimization Complete:');
        $this->line("  • Total notifications cleaned: {$totalCleaned}");
        $this->line("  • Users processed: {$totalUsers}");
        $this->line("  • System performance: OPTIMIZED ⚡");
        
        return 0;
    }
    
    /**
     * Clean up notifications for a specific user
     */
    private function cleanupUserNotifications(User $user): int
    {
        $initialCount = $this->getUserNotificationCount($user);
        
        if ($this->option('force')) {
            // Force aggressive cleanup
            UniversalNotificationService::performAggressiveCleanup($user);
        } else {
            // Standard cleanup
            UniversalNotificationService::cleanupExpiredNotifications($user);
            UniversalNotificationService::enforceLimit($user);
        }
        
        $finalCount = $this->getUserNotificationCount($user);
        
        return max(0, $initialCount - $finalCount);
    }
    
    /**
     * Get current notification count for user
     */
    private function getUserNotificationCount(User $user): int
    {
        $cacheKey = "user_notifications_{$user->id}";
        $notifications = Cache::get($cacheKey, []);
        return count($notifications);
    }
    
    /**
     * Get all cached notification keys
     */
    private function getCachedNotificationKeys(): array
    {
        // This is a simplified approach - in production you might want to use Redis SCAN
        // or maintain a separate index of active notification cache keys
        $keys = [];
        
        // For demonstration, we'll check recent users
        $recentUsers = User::where('updated_at', '>', now()->subDays(7))->pluck('id');
        
        foreach ($recentUsers as $userId) {
            $cacheKey = "user_notifications_{$userId}";
            if (Cache::has($cacheKey)) {
                $keys[] = $cacheKey;
            }
        }
        
        return $keys;
    }
}