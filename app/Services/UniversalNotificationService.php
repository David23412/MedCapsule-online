<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class UniversalNotificationService
{
    /**
     * Notification icon mapping for different types
     */
    const NOTIFICATION_ICONS = [
        'course_welcome' => 'fas fa-graduation-cap',
        'quiz_completed' => 'fas fa-check-circle',
        'achievement' => 'fas fa-trophy',
        'streak_milestone' => 'fas fa-fire',
        'assignment_due' => 'fas fa-clock',
        'grade_released' => 'fas fa-star',
        'system_update' => 'fas fa-bell',
        'payment_success' => 'fas fa-credit-card',
        'payment_failed' => 'fas fa-exclamation-triangle',
        'course_access_granted' => 'fas fa-unlock',
        'course_access_denied' => 'fas fa-lock',
        'reminder' => 'fas fa-reminder',
        'announcement' => 'fas fa-bullhorn',
        'error' => 'fas fa-exclamation-circle',
        'success' => 'fas fa-check',
        'warning' => 'fas fa-exclamation-triangle',
        'info' => 'fas fa-info-circle'
    ];

    /**
     * Notification priority levels
     */
    const PRIORITY_URGENT = 'urgent';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_LOW = 'low';

    /**
     * Create a new notification
     */
    public static function create(
        User $user,
        string $type,
        string $title,
        string $message,
        array $data = [],
        string $priority = self::PRIORITY_NORMAL
    ): array {
        $notification = [
            'id' => uniqid('notif_', true),
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'icon' => self::getIcon($type),
            'priority' => $priority,
            'data' => $data,
            'read' => false,
            'created_at' => now()->toISOString(),
            'expires_at' => now()->addDays(30)->toISOString()
        ];

        // AUTO-CLEANUP BEFORE ADDING NEW NOTIFICATION
        self::performAggressiveCleanup($user);
        
        // Store notification in cache
        $cacheKey = "user_notifications_{$user->id}";
        $notifications = Cache::get($cacheKey, []);
        array_unshift($notifications, $notification);
        
        // HARD LIMIT: Keep only last 5 notifications - PREVENT SYSTEM OVERLOAD
        $notifications = array_slice($notifications, 0, 5);
        
        Cache::put($cacheKey, $notifications, 86400 * 30); // 30 days

        // Update unread count
        self::updateUnreadCount($user);

        return $notification;
    }

    /**
     * Get icon for notification type
     */
    public static function getIcon(string $type): string
    {
        return self::NOTIFICATION_ICONS[$type] ?? 'fas fa-bell';
    }

    /**
     * Get all notifications for a user - HARD LIMITED TO 5 WITH AUTO-CLEANUP
     */
    public static function getNotifications(User $user, int $limit = 5): Collection
    {
        // AGGRESSIVE AUTO-CLEANUP ON EVERY ACCESS - KEEPS SYSTEM FAST
        self::performAggressiveCleanup($user);
        
        $cacheKey = "user_notifications_{$user->id}";
        $notifications = Cache::get($cacheKey, []);
        
        // Filter out expired notifications (should be minimal after cleanup)
        $notifications = array_filter($notifications, function($notification) {
            return now()->lt($notification['expires_at']);
        });
        
        // FORCE LIMIT TO 5 MAXIMUM - NEVER MORE
        return collect($notifications)->take(5);
    }

    /**
     * Get unread notifications count - HARD LIMITED TO 5 WITH AUTO-CLEANUP
     */
    public static function getUnreadCount(User $user): int
    {
        // AGGRESSIVE AUTO-CLEANUP ON EVERY ACCESS - KEEPS SYSTEM FAST
        self::performAggressiveCleanup($user);
        
        $count = Cache::get("user_unread_count_{$user->id}", 0);
        // FORCE LIMIT TO 5 MAXIMUM - NEVER SHOW MORE
        return min($count, 5);
    }

    /**
     * Mark notification as read
     */
    public static function markAsRead(User $user, string $notificationId): bool
    {
        $cacheKey = "user_notifications_{$user->id}";
        $notifications = Cache::get($cacheKey, []);
        
        foreach ($notifications as &$notification) {
            if ($notification['id'] === $notificationId) {
                $notification['read'] = true;
                break;
            }
        }
        
        Cache::put($cacheKey, $notifications, 86400 * 30);
        self::updateUnreadCount($user);
        
        return true;
    }

    /**
     * Mark all notifications as read
     */
    public static function markAllAsRead(User $user): bool
    {
        $cacheKey = "user_notifications_{$user->id}";
        $notifications = Cache::get($cacheKey, []);
        
        foreach ($notifications as &$notification) {
            $notification['read'] = true;
        }
        
        Cache::put($cacheKey, $notifications, 86400 * 30);
        Cache::put("user_unread_count_{$user->id}", 0, 86400);
        
        return true;
    }

    /**
     * Update unread count for user - HARD LIMITED TO 5
     */
    private static function updateUnreadCount(User $user): void
    {
        $notifications = self::getNotifications($user, 5);
        $unreadCount = $notifications->where('read', false)->count();
        
        // FORCE LIMIT TO 5 MAXIMUM
        $unreadCount = min($unreadCount, 5);
        
        Cache::put("user_unread_count_{$user->id}", $unreadCount, 86400);
    }

    /**
     * Create course welcome notification
     */
    public static function createCourseWelcomeNotification(User $user, string $courseName): array
    {
        return self::create(
            $user,
            'course_welcome',
            'Welcome to ' . $courseName,
            'You have been successfully enrolled. Start learning now!',
            ['course_name' => $courseName],
            self::PRIORITY_HIGH
        );
    }

    /**
     * Create quiz completion notification
     */
    public static function createQuizCompletionNotification(
        User $user,
        string $topicName,
        int $score,
        int $total,
        float $percentage
    ): array {
        $message = "You scored {$score}/{$total} ({$percentage}%) on {$topicName}";
        $priority = $percentage >= 90 ? self::PRIORITY_HIGH : self::PRIORITY_NORMAL;
        
        return self::create(
            $user,
            'quiz_completed',
            'Quiz Completed!',
            $message,
            [
                'topic_name' => $topicName,
                'score' => $score,
                'total' => $total,
                'percentage' => $percentage
            ],
            $priority
        );
    }

    /**
     * Create achievement notification
     */
    public static function createAchievementNotification(
        User $user,
        string $achievementName,
        string $description
    ): array {
        return self::create(
            $user,
            'achievement',
            'Achievement Unlocked!',
            $achievementName . ': ' . $description,
            ['achievement' => $achievementName],
            self::PRIORITY_HIGH
        );
    }

    /**
     * Create streak milestone notification
     */
    public static function createStreakMilestoneNotification(User $user, int $streakDays): array
    {
        return self::create(
            $user,
            'streak_milestone',
            'Streak Milestone!',
            "Congratulations! You've maintained a {$streakDays}-day study streak!",
            ['streak_days' => $streakDays],
            self::PRIORITY_HIGH
        );
    }

    /**
     * Create payment success notification
     */
    public static function createPaymentSuccessNotification(
        User $user,
        string $courseName,
        float $amount,
        string $currency
    ): array {
        return self::create(
            $user,
            'payment_success',
            'Payment Successful',
            "Payment of {$amount} {$currency} for {$courseName} was successful. You now have access!",
            [
                'course_name' => $courseName,
                'amount' => $amount,
                'currency' => $currency
            ],
            self::PRIORITY_HIGH
        );
    }

    /**
     * Create system announcement
     */
    public static function createSystemAnnouncement(
        User $user,
        string $title,
        string $message,
        string $priority = self::PRIORITY_NORMAL
    ): array {
        return self::create(
            $user,
            'announcement',
            $title,
            $message,
            [],
            $priority
        );
    }

    /**
     * Broadcast notification to multiple users
     */
    public static function broadcast(
        Collection $users,
        string $type,
        string $title,
        string $message,
        array $data = [],
        string $priority = self::PRIORITY_NORMAL
    ): int {
        $count = 0;
        
        foreach ($users as $user) {
            self::create($user, $type, $title, $message, $data, $priority);
            $count++;
        }
        
        return $count;
    }

    /**
     * Clean up expired notifications and enforce 5 notification limit
     * AUTO-DELETION SYSTEM FOR MAXIMUM EFFICIENCY
     */
    public static function cleanupExpiredNotifications(User $user): int
    {
        $cacheKey = "user_notifications_{$user->id}";
        $notifications = Cache::get($cacheKey, []);
        
        $validNotifications = array_filter($notifications, function($notification) {
            return now()->lt($notification['expires_at']);
        });
        
        // FORCE LIMIT TO 5 NOTIFICATIONS MAXIMUM - AUTO DELETE EXCESS
        $validNotifications = array_slice($validNotifications, 0, 5);
        
        $removedCount = count($notifications) - count($validNotifications);
        
        if ($removedCount > 0) {
            Cache::put($cacheKey, array_values($validNotifications), 86400 * 30);
            self::updateUnreadCount($user);
            
            // Log cleanup for monitoring
            \Log::info("Auto-deleted {$removedCount} notifications for user {$user->id} to maintain 5-notification limit");
        }
        
        return $removedCount;
    }
    
    /**
     * Force cleanup to ensure 5 notification limit - AUTO CALLED ON EVERY ACCESS
     * CRITICAL PERFORMANCE OPTIMIZATION
     */
    public static function enforceLimit(User $user): void
    {
        $cacheKey = "user_notifications_{$user->id}";
        $notifications = Cache::get($cacheKey, []);
        
        if (count($notifications) > 5) {
            // Keep only the 5 most recent notifications - DELETE THE REST
            $deletedCount = count($notifications) - 5;
            $notifications = array_slice($notifications, 0, 5);
            Cache::put($cacheKey, $notifications, 86400 * 30);
            self::updateUnreadCount($user);
            
            // Log auto-deletion for monitoring
            \Log::info("PERFORMANCE: Auto-deleted {$deletedCount} excess notifications for user {$user->id}");
        }
    }
    
    /**
     * AGGRESSIVE AUTO-CLEANUP - Called on every notification access
     * Ensures database stays lean and fast
     */
    public static function performAggressiveCleanup(User $user): void
    {
        // 1. Clean expired notifications
        self::cleanupExpiredNotifications($user);
        
        // 2. Enforce hard limit of 5
        self::enforceLimit($user);
        
        // 3. Remove old read notifications beyond 3 days to save space
        $cacheKey = "user_notifications_{$user->id}";
        $notifications = Cache::get($cacheKey, []);
        
        $cleanedNotifications = array_filter($notifications, function($notification) {
            // Keep unread notifications always
            if (!$notification['read']) {
                return true;
            }
            
            // Remove read notifications older than 3 days
            $createdAt = \Carbon\Carbon::parse($notification['created_at']);
            return $createdAt->gt(now()->subDays(3));
        });
        
        // Still enforce 5 notification limit after cleanup
        $cleanedNotifications = array_slice($cleanedNotifications, 0, 5);
        
        if (count($cleanedNotifications) !== count($notifications)) {
            Cache::put($cacheKey, array_values($cleanedNotifications), 86400 * 30);
            self::updateUnreadCount($user);
            
            $removedCount = count($notifications) - count($cleanedNotifications);
            \Log::info("AGGRESSIVE CLEANUP: Removed {$removedCount} old read notifications for user {$user->id}");
        }
    }

    /**
     * AGGRESSIVE DELETE - Remove all viewed notifications immediately
     * Called when user opens notification dropdown (after 1 minute)
     */
    public static function aggressiveDeleteViewed(User $user): int
    {
        $cacheKey = "user_notifications_{$user->id}";
        $notifications = Cache::get($cacheKey, []);
        
        $initialCount = count($notifications);
        
        // DELETE ALL NOTIFICATIONS - AGGRESSIVE DATABASE OPTIMIZATION
        Cache::forget($cacheKey);
        Cache::forget("user_unread_count_{$user->id}");
        
        Log::info("AGGRESSIVE DELETE: Removed ALL {$initialCount} notifications for user {$user->id} for database optimization");
        
        return $initialCount;
    }
    
    /**
     * Delete specific notification immediately
     */
    public static function deleteNotification(User $user, string $notificationId): bool
    {
        $cacheKey = "user_notifications_{$user->id}";
        $notifications = Cache::get($cacheKey, []);
        
        $notifications = array_filter($notifications, function($notification) use ($notificationId) {
            return $notification['id'] !== $notificationId;
        });
        
        Cache::put($cacheKey, array_values($notifications), 86400 * 30);
        self::updateUnreadCount($user);
        
        Log::info("AGGRESSIVE DELETE: Removed notification {$notificationId} for user {$user->id}");
        
        return true;
    }
    
    /**
     * ULTRA AGGRESSIVE - Delete all notifications older than X minutes after being read
     */
    public static function ultraAggressiveCleanup(User $user, int $minutesAfterRead = 1): int
    {
        $cacheKey = "user_notifications_{$user->id}";
        $notifications = Cache::get($cacheKey, []);
        
        $cutoffTime = now()->subMinutes($minutesAfterRead);
        $initialCount = count($notifications);
        
        // Keep only unread notifications OR very recent read notifications
        $filteredNotifications = array_filter($notifications, function($notification) use ($cutoffTime) {
            // Always keep unread notifications
            if (!$notification['read']) {
                return true;
            }
            
            // For read notifications, only keep if read very recently
            $createdAt = \Carbon\Carbon::parse($notification['created_at']);
            return $createdAt->gt($cutoffTime);
        });
        
        // Still enforce 5 notification limit
        $filteredNotifications = array_slice($filteredNotifications, 0, 5);
        
        $deletedCount = $initialCount - count($filteredNotifications);
        
        if ($deletedCount > 0) {
            Cache::put($cacheKey, array_values($filteredNotifications), 86400 * 30);
            self::updateUnreadCount($user);
            
            Log::info("ULTRA AGGRESSIVE: Deleted {$deletedCount} old read notifications for user {$user->id}");
        }
        
        return $deletedCount;
    }
    
    /**
     * Get notification statistics
     */
    public static function getStats(User $user): array
    {
        $notifications = self::getNotifications($user, 100);
        
        return [
            'total' => $notifications->count(),
            'unread' => $notifications->where('read', false)->count(),
            'urgent' => $notifications->where('priority', self::PRIORITY_URGENT)->count(),
            'high' => $notifications->where('priority', self::PRIORITY_HIGH)->count(),
            'by_type' => $notifications->groupBy('type')->map->count()->toArray()
        ];
    }
}