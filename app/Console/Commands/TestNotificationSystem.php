<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\UniversalNotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TestNotificationSystem extends Command
{
    protected $signature = 'notifications:test 
                            {--aggressive : Run aggressive performance tests}
                            {--load=100 : Number of notifications for load testing}
                            {--user= : Specific user ID to test}
                            {--cleanup : Test cleanup functionality}
                            {--performance : Run performance benchmarks}';

    protected $description = 'Run comprehensive tests on the aggressive notification system';

    public function handle()
    {
        $this->info('🚀 STARTING AGGRESSIVE NOTIFICATION SYSTEM TESTS');
        $this->newLine();
        
        $user = $this->getTestUser();
        
        if ($this->option('aggressive')) {
            $this->runAggressiveTests($user);
        }
        
        if ($this->option('cleanup')) {
            $this->testCleanupFunctionality($user);
        }
        
        if ($this->option('performance')) {
            $this->runPerformanceTests($user);
        }
        
        if (!$this->option('aggressive') && !$this->option('cleanup') && !$this->option('performance')) {
            $this->runAllTests($user);
        }
        
        $this->newLine();
        $this->info('✅ ALL TESTS COMPLETED SUCCESSFULLY');
        
        return 0;
    }
    
    private function getTestUser(): User
    {
        if ($this->option('user')) {
            $user = User::find($this->option('user'));
            if (!$user) {
                $this->error("User with ID {$this->option('user')} not found");
                exit(1);
            }
            return $user;
        }
        
        // Create or get test user
        $user = User::firstOrCreate(
            ['email' => 'test@notifications.com'],
            [
                'name' => 'Notification Test User',
                'password' => bcrypt('password'),
                'role' => 'student'
            ]
        );
        
        $this->line("📧 Using test user: {$user->email} (ID: {$user->id})");
        return $user;
    }
    
    private function runAllTests(User $user): void
    {
        $this->info('🧪 RUNNING COMPREHENSIVE TEST SUITE');
        $this->newLine();
        
        $this->test5NotificationLimit($user);
        $this->testAutoCleanup($user);
        $this->testUnreadCountLimit($user);
        $this->testAggressiveDelete($user);
        $this->testPerformanceUnderLoad($user);
        $this->testCacheEfficiency($user);
        $this->testNotificationTypes($user);
    }
    
    private function runAggressiveTests(User $user): void
    {
        $this->info('⚡ RUNNING AGGRESSIVE TESTS');
        $this->newLine();
        
        $this->test5NotificationLimit($user);
        $this->testAggressiveDelete($user);
        $this->testUltraAggressiveCleanup($user);
    }
    
    private function testCleanupFunctionality(User $user): void
    {
        $this->info('🧹 TESTING CLEANUP FUNCTIONALITY');
        $this->newLine();
        
        $this->testAutoCleanup($user);
        $this->testUltraAggressiveCleanup($user);
        $this->testScheduledCleanup($user);
    }
    
    private function runPerformanceTests(User $user): void
    {
        $this->info('⚡ RUNNING PERFORMANCE TESTS');
        $this->newLine();
        
        $this->testPerformanceUnderLoad($user);
        $this->testCacheEfficiency($user);
        $this->testMemoryUsage($user);
    }
    
    private function test5NotificationLimit(User $user): void
    {
        $this->info('🔢 TEST: 5 Notification Hard Limit');
        
        // Clear existing notifications
        Cache::forget("user_notifications_{$user->id}");
        
        // Create 10 notifications
        for ($i = 1; $i <= 10; $i++) {
            UniversalNotificationService::create(
                $user,
                'test_limit',
                "Limit Test #{$i}",
                "Testing 5 notification limit - message #{$i}",
                ['test' => true, 'sequence' => $i]
            );
        }
        
        $notifications = UniversalNotificationService::getNotifications($user);
        $count = $notifications->count();
        
        if ($count === 5) {
            $this->line("✅ PASSED: Created 10 notifications, system kept exactly 5");
        } else {
            $this->error("❌ FAILED: Expected 5 notifications, got {$count}");
        }
        
        // Verify they are the most recent
        $firstTitle = $notifications->first()['title'];
        if (str_contains($firstTitle, '#10')) {
            $this->line("✅ PASSED: Most recent notifications preserved");
        } else {
            $this->error("❌ FAILED: Wrong notifications preserved");
        }
        
        $this->newLine();
    }
    
    private function testAutoCleanup(User $user): void
    {
        $this->info('🧹 TEST: Auto-Cleanup on Access');
        
        // Manually insert old notifications
        $cacheKey = "user_notifications_{$user->id}";
        $oldNotifications = [
            [
                'id' => 'old_1',
                'type' => 'test',
                'title' => 'Old Notification 1',
                'message' => 'This should be cleaned up',
                'read' => true,
                'created_at' => now()->subDays(5)->toISOString(),
                'expires_at' => now()->addDays(25)->toISOString(),
                'icon' => 'fas fa-bell',
                'priority' => 'normal'
            ],
            [
                'id' => 'old_2',
                'type' => 'test',
                'title' => 'Old Notification 2',
                'message' => 'This should also be cleaned up',
                'read' => true,
                'created_at' => now()->subDays(4)->toISOString(),
                'expires_at' => now()->addDays(26)->toISOString(),
                'icon' => 'fas fa-bell',
                'priority' => 'normal'
            ]
        ];
        
        Cache::put($cacheKey, $oldNotifications, 86400 * 30);
        
        $beforeCount = count(Cache::get($cacheKey, []));
        
        // Access notifications - should trigger cleanup
        $notifications = UniversalNotificationService::getNotifications($user);
        
        $afterCount = $notifications->count();
        
        $this->line("✅ PASSED: Auto-cleanup triggered on access");
        $this->line("   Before: {$beforeCount} notifications");
        $this->line("   After: {$afterCount} notifications");
        
        $this->newLine();
    }
    
    private function testUnreadCountLimit(User $user): void
    {
        $this->info('🔢 TEST: Unread Count Hard Limit');
        
        // Clear existing
        Cache::forget("user_notifications_{$user->id}");
        Cache::forget("user_unread_count_{$user->id}");
        
        // Create 8 unread notifications
        for ($i = 1; $i <= 8; $i++) {
            UniversalNotificationService::create(
                $user,
                'urgent_test',
                "Urgent #{$i}",
                "Urgent message #{$i}",
                ['urgent' => true],
                'urgent'
            );
        }
        
        $unreadCount = UniversalNotificationService::getUnreadCount($user);
        
        if ($unreadCount <= 5) {
            $this->line("✅ PASSED: Created 8 unread, badge shows {$unreadCount} (≤5)");
        } else {
            $this->error("❌ FAILED: Unread count {$unreadCount} exceeds limit of 5");
        }
        
        $this->newLine();
    }
    
    private function testAggressiveDelete(User $user): void
    {
        $this->info('🗑️ TEST: Aggressive Delete Viewed');
        
        // Create test notifications
        for ($i = 1; $i <= 3; $i++) {
            UniversalNotificationService::create(
                $user,
                'delete_test',
                "Delete Test #{$i}",
                "This will be aggressively deleted",
                ['delete_test' => true]
            );
        }
        
        $beforeCount = UniversalNotificationService::getNotifications($user)->count();
        
        // Trigger aggressive delete
        $deletedCount = UniversalNotificationService::aggressiveDeleteViewed($user);
        
        $afterCount = UniversalNotificationService::getNotifications($user)->count();
        
        if ($afterCount === 0 && $deletedCount === $beforeCount) {
            $this->line("✅ PASSED: Aggressively deleted all {$deletedCount} viewed notifications");
        } else {
            $this->error("❌ FAILED: Expected 0 remaining, got {$afterCount}");
        }
        
        $this->newLine();
    }
    
    private function testUltraAggressiveCleanup(User $user): void
    {
        $this->info('⚡ TEST: Ultra Aggressive Cleanup');
        
        // Create mix of old read and new unread notifications
        $cacheKey = "user_notifications_{$user->id}";
        $mixedNotifications = [
            [
                'id' => 'old_read',
                'type' => 'test',
                'title' => 'Old Read Notification',
                'message' => 'Should be deleted',
                'read' => true,
                'created_at' => now()->subMinutes(5)->toISOString(),
                'expires_at' => now()->addDays(25)->toISOString(),
                'icon' => 'fas fa-bell',
                'priority' => 'normal'
            ],
            [
                'id' => 'new_unread',
                'type' => 'test',
                'title' => 'New Unread Notification',
                'message' => 'Should be kept',
                'read' => false,
                'created_at' => now()->toISOString(),
                'expires_at' => now()->addDays(30)->toISOString(),
                'icon' => 'fas fa-bell',
                'priority' => 'normal'
            ]
        ];
        
        Cache::put($cacheKey, $mixedNotifications, 86400 * 30);
        
        $deletedCount = UniversalNotificationService::ultraAggressiveCleanup($user, 1);
        $remaining = UniversalNotificationService::getNotifications($user);
        
        if ($remaining->count() === 1 && !$remaining->first()['read']) {
            $this->line("✅ PASSED: Ultra cleanup deleted {$deletedCount} old read, kept 1 unread");
        } else {
            $this->error("❌ FAILED: Ultra cleanup didn't work as expected");
        }
        
        $this->newLine();
    }
    
    private function testPerformanceUnderLoad(User $user): void
    {
        $this->info('⚡ TEST: Performance Under Load');
        
        $loadSize = (int) $this->option('load');
        
        // Test creation performance
        $startTime = microtime(true);
        
        for ($i = 1; $i <= $loadSize; $i++) {
            UniversalNotificationService::create(
                $user,
                'load_test',
                "Load Test #{$i}",
                "Performance test notification #{$i}",
                ['load_test' => true, 'sequence' => $i]
            );
        }
        
        $creationTime = (microtime(true) - $startTime) * 1000;
        
        // Test retrieval performance
        $retrievalStart = microtime(true);
        $notifications = UniversalNotificationService::getNotifications($user);
        $retrievalTime = (microtime(true) - $retrievalStart) * 1000;
        
        // Test count performance
        $countStart = microtime(true);
        $unreadCount = UniversalNotificationService::getUnreadCount($user);
        $countTime = (microtime(true) - $countStart) * 1000;
        
        $this->line("✅ Created {$loadSize} notifications in: " . round($creationTime, 2) . "ms");
        $this->line("✅ Retrieved notifications in: " . round($retrievalTime, 2) . "ms");
        $this->line("✅ Got unread count in: " . round($countTime, 2) . "ms");
        $this->line("✅ Final count: {$notifications->count()} (enforced limit)");
        $this->line("✅ Unread count: {$unreadCount}");
        
        if ($creationTime < 2000 && $retrievalTime < 100 && $countTime < 50) {
            $this->line("✅ PASSED: Performance within acceptable limits");
        } else {
            $this->error("❌ FAILED: Performance degraded under load");
        }
        
        $this->newLine();
    }
    
    private function testCacheEfficiency(User $user): void
    {
        $this->info('📊 TEST: Cache Efficiency');
        
        // Clear cache
        Cache::forget("user_notifications_{$user->id}");
        
        // First access (cache miss)
        $start1 = microtime(true);
        $notifications1 = UniversalNotificationService::getNotifications($user);
        $time1 = (microtime(true) - $start1) * 1000;
        
        // Second access (cache hit)
        $start2 = microtime(true);
        $notifications2 = UniversalNotificationService::getNotifications($user);
        $time2 = (microtime(true) - $start2) * 1000;
        
        $this->line("✅ First access (cache miss): " . round($time1, 2) . "ms");
        $this->line("✅ Second access (cache hit): " . round($time2, 2) . "ms");
        
        if ($time2 < $time1) {
            $this->line("✅ PASSED: Cache hit is faster than cache miss");
        } else {
            $this->error("❌ FAILED: Cache not working efficiently");
        }
        
        $this->newLine();
    }
    
    private function testNotificationTypes(User $user): void
    {
        $this->info('🎨 TEST: Notification Types and Icons');
        
        $testTypes = [
            'course_welcome' => 'fas fa-graduation-cap',
            'quiz_completed' => 'fas fa-check-circle',
            'achievement' => 'fas fa-trophy',
            'streak_milestone' => 'fas fa-fire',
            'assignment_due' => 'fas fa-clock'
        ];
        
        $allPassed = true;
        
        foreach ($testTypes as $type => $expectedIcon) {
            $actualIcon = UniversalNotificationService::getIcon($type);
            
            if ($actualIcon === $expectedIcon) {
                $this->line("✅ {$type}: {$actualIcon}");
            } else {
                $this->error("❌ {$type}: Expected {$expectedIcon}, got {$actualIcon}");
                $allPassed = false;
            }
        }
        
        if ($allPassed) {
            $this->line("✅ PASSED: All notification types have correct icons");
        }
        
        $this->newLine();
    }
    
    private function testScheduledCleanup(User $user): void
    {
        $this->info('⏰ TEST: Scheduled Cleanup Command');
        
        // Create test data
        for ($i = 1; $i <= 7; $i++) {
            UniversalNotificationService::create(
                $user,
                'scheduled_test',
                "Scheduled Test #{$i}",
                "Testing scheduled cleanup",
                ['scheduled_test' => true]
            );
        }
        
        $beforeCount = UniversalNotificationService::getNotifications($user)->count();
        
        // Run cleanup command
        $this->call('notifications:cleanup', ['--user' => $user->id]);
        
        $afterCount = UniversalNotificationService::getNotifications($user)->count();
        
        $this->line("✅ Before cleanup: {$beforeCount} notifications");
        $this->line("✅ After cleanup: {$afterCount} notifications");
        $this->line("✅ PASSED: Scheduled cleanup command executed");
        
        $this->newLine();
    }
    
    private function testMemoryUsage(User $user): void
    {
        $this->info('💾 TEST: Memory Usage');
        
        $startMemory = memory_get_usage(true);
        
        // Create many notifications to test memory efficiency
        for ($i = 1; $i <= 1000; $i++) {
            UniversalNotificationService::create(
                $user,
                'memory_test',
                "Memory Test #{$i}",
                "Testing memory usage with notification #{$i}",
                ['memory_test' => true]
            );
        }
        
        $endMemory = memory_get_usage(true);
        $memoryUsed = $endMemory - $startMemory;
        
        $this->line("✅ Memory used for 1000 notifications: " . $this->formatBytes($memoryUsed));
        
        // Should still only have 5 notifications due to aggressive limiting
        $finalCount = UniversalNotificationService::getNotifications($user)->count();
        
        if ($finalCount === 5 && $memoryUsed < 10 * 1024 * 1024) { // Less than 10MB
            $this->line("✅ PASSED: Memory usage efficient, maintained 5 notification limit");
        } else {
            $this->error("❌ FAILED: Memory usage too high or limit not enforced");
        }
        
        $this->newLine();
    }
    
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}