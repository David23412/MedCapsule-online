<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class UniversalNotificationService
{
    /**
     * BEAUTIFUL NOTIFICATION ICON MAPPING - RESTORED FROM ORIGINAL DESIGN
     */
    const NOTIFICATION_ICONS = [
        // 🎓 ACADEMIC & LEARNING
        'course_welcome' => 'fas fa-graduation-cap',
        'quiz_completed' => 'fas fa-check-circle',
        'topic_mastery' => 'fas fa-graduation-cap',
        'level_up' => 'fas fa-level-up-alt',
        'assignment_due' => 'fas fa-clock',
        'grade_released' => 'fas fa-star',
        
        // 🏆 ACHIEVEMENTS & MILESTONES
        'achievement' => 'fas fa-trophy',
        'streak_milestone' => 'fas fa-fire',
        'study_streak' => 'fas fa-fire',
        'correct_answers_milestone' => 'fas fa-trophy',
        'quiz_mastered' => 'fas fa-graduation-cap',
        
        // ⚡ ENERGY & PROGRESS (LIGHTNING YELLOW ONE!)
        'level_up' => 'fas fa-bolt',
        'energy_boost' => 'fas fa-bolt',
        'power_up' => 'fas fa-bolt',
        'momentum' => 'fas fa-bolt',
        
        // 🔥 STREAKS & MOTIVATION
        'streak_reset' => 'fas fa-fire',
        'streak_milestone' => 'fas fa-fire',
        'study_streak' => 'fas fa-fire',
        'burning_streak' => 'fas fa-fire',
        
        // ⚠️ WARNINGS & REVIEWS
        'review_mistakes' => 'fas fa-exclamation-triangle',
        'weak_topic_identified' => 'fas fa-exclamation-circle',
        'skipped_questions_detected' => 'fas fa-forward',
        'topic_needs_review' => 'fas fa-exclamation-triangle',
        'review_reminder' => 'fas fa-clock',
        
        // 💳 PAYMENT & ACCESS
        'payment_success' => 'fas fa-credit-card',
        'payment_failed' => 'fas fa-exclamation-triangle',
        'course_access_granted' => 'fas fa-unlock',
        'course_access_denied' => 'fas fa-lock',
        
        // 🔔 SYSTEM & GENERAL
        'system_update' => 'fas fa-bell',
        'announcement' => 'fas fa-bullhorn',
        'reminder' => 'fas fa-bell',
        'info' => 'fas fa-info-circle',
        'success' => 'fas fa-check',
        'warning' => 'fas fa-exclamation-triangle',
        'error' => 'fas fa-exclamation-circle',
        'test' => 'fas fa-bell',
        
        // 🎯 SPECIAL ACHIEVEMENTS
        'perfect_score' => 'fas fa-star',
        'first_attempt' => 'fas fa-medal',
        'speed_demon' => 'fas fa-tachometer-alt',
        'consistency_king' => 'fas fa-crown',
        'knowledge_master' => 'fas fa-brain',
        
        // 📚 SUBJECT SPECIFIC
        'anatomy_mastered' => 'fas fa-heartbeat',
        'cardiology_completed' => 'fas fa-heart',
        'pharmacology_quiz' => 'fas fa-pills',
        'physiology_quiz' => 'fas fa-dna',
        
        // 🎨 CUSTOM TYPES
        'milestone_reached' => 'fas fa-flag-checkered',
        'goal_achieved' => 'fas fa-target',
        'breakthrough' => 'fas fa-lightbulb',
        'inspiration' => 'fas fa-lightbulb'
    ];

    /**
     * Notification priority levels
     */
    const PRIORITY_URGENT = 'urgent';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_LOW = 'low';

    /**
     * Create a new notification for a user
     */
    public static function create(
        User $user, 
        string $type, 
        string $title, 
        string $message, 
        array $data = [], 
        string $priority = self::PRIORITY_NORMAL
    ): array {
        // DUPLICATE PREVENTION: Check for similar notifications in the last 5 minutes
        $cacheKey = "user_notifications_{$user->id}";
        $existingNotifications = Cache::get($cacheKey, []);
        
        $fiveMinutesAgo = now()->subMinutes(5);
        foreach ($existingNotifications as $existing) {
            $existingTime = \Carbon\Carbon::parse($existing['created_at']);
            if ($existingTime->greaterThan($fiveMinutesAgo) && 
                $existing['type'] === $type && 
                $existing['title'] === $title) {
                // Duplicate found within 5 minutes - return existing notification
                Log::info("Duplicate notification prevented", [
                    'user_id' => $user->id,
                    'type' => $type,
                    'title' => $title
                ]);
                return $existing;
            }
        }

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

        // DISABLED: AUTO-CLEANUP BEFORE ADDING NEW NOTIFICATION
        // self::performAggressiveCleanup($user);
        
        // Store notification in cache
        $notifications = $existingNotifications;
        array_unshift($notifications, $notification);
        
        // NO LIMIT: Keep all notifications - NO AUTO-DELETE
        // $notifications = array_slice($notifications, 0, 20);
        
        Cache::put($cacheKey, $notifications, 86400 * 30); // 30 days

        // Update unread count
        self::updateUnreadCount($user);

        Log::info("New notification created", [
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title
        ]);

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
     * Get all notifications for a user - DISABLED AUTO-CLEANUP
     */
    public static function getNotifications(User $user, int $limit = 100): Collection
    {
        // DISABLED: AGGRESSIVE AUTO-CLEANUP ON EVERY ACCESS
        // self::performAggressiveCleanup($user);
        
        $cacheKey = "user_notifications_{$user->id}";
        $notifications = Cache::get($cacheKey, []);
        
        // Filter out expired notifications (should be minimal after cleanup)
        $notifications = array_filter($notifications, function($notification) {
            return now()->lt(\Carbon\Carbon::parse($notification['expires_at']));
        });
        
        // NO LIMIT: Return all notifications
        return collect($notifications)->take($limit);
    }

    /**
     * Get unread notifications count - DISABLED AUTO-CLEANUP
     */
    public static function getUnreadCount(User $user): int
    {
        // DISABLED: AGGRESSIVE AUTO-CLEANUP ON EVERY ACCESS
        // self::performAggressiveCleanup($user);
        
        $count = Cache::get("user_unread_count_{$user->id}", 0);
        // NO LIMIT: Show actual unread count
        return $count;
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
        Cache::put("user_unread_count_{$user->id}", 0, 86400 * 30);
        
        return true;
    }

    /**
     * Delete a specific notification
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
        
        return true;
    }

    /**
     * Update unread count for a user
     */
    private static function updateUnreadCount(User $user): void
    {
        $cacheKey = "user_notifications_{$user->id}";
        $notifications = Cache::get($cacheKey, []);
        
        $unreadCount = 0;
        foreach ($notifications as $notification) {
            if (!$notification['read']) {
                $unreadCount++;
            }
        }
        
        Cache::put("user_unread_count_{$user->id}", $unreadCount, 86400 * 30);
    }

    /**
     * Clean up expired notifications - DISABLED AGGRESSIVE CLEANUP
     */
    public static function cleanupExpiredNotifications(User $user): int
    {
        $cacheKey = "user_notifications_{$user->id}";
        $notifications = Cache::get($cacheKey, []);
        
        $validNotifications = array_filter($notifications, function($notification) {
            return now()->lt($notification['expires_at']);
        });
        
        // DISABLED: FORCE LIMIT TO 5 NOTIFICATIONS MAXIMUM - AUTO DELETE EXCESS
        // $validNotifications = array_slice($validNotifications, 0, 5);
        
        $removedCount = count($notifications) - count($validNotifications);
        
        if ($removedCount > 0) {
            Cache::put($cacheKey, array_values($validNotifications), 86400 * 30);
            self::updateUnreadCount($user);
            
            // Log cleanup for monitoring
            \Log::info("Cleaned up {$removedCount} expired notifications for user {$user->id}");
        }
        
        return $removedCount;
    }
    
    /**
     * Force cleanup to ensure notification limit - DISABLED AUTO-CALLED ON EVERY ACCESS
     * DISABLED PERFORMANCE OPTIMIZATION
     */
    public static function enforceLimit(User $user): void
    {
        // DISABLED: Auto-enforcement of limits
        // $cacheKey = "user_notifications_{$user->id}";
        // $notifications = Cache::get($cacheKey, []);
        
        // if (count($notifications) > 5) {
        //     // Keep only the 5 most recent notifications - DELETE THE REST
        //     $deletedCount = count($notifications) - 5;
        //     $notifications = array_slice($notifications, 0, 5);
        //     Cache::put($cacheKey, $notifications, 86400 * 30);
        //     self::updateUnreadCount($user);
        //     
        //     // Log auto-deletion for monitoring
        //     \Log::info("PERFORMANCE: Auto-deleted {$deletedCount} excess notifications for user {$user->id}");
        // }
    }
    
    /**
     * AGGRESSIVE AUTO-CLEANUP - DISABLED: Called on every notification access
     * DISABLED: Ensures database stays lean and fast
     */
    public static function performAggressiveCleanup(User $user): void
    {
        // DISABLED: All aggressive cleanup functionality
        // // 1. Clean expired notifications
        // self::cleanupExpiredNotifications($user);
        
        // // 2. Enforce hard limit of 5
        // self::enforceLimit($user);
        
        // // 3. Remove old read notifications beyond 3 days to save space
        // $cacheKey = "user_notifications_{$user->id}";
        // $notifications = Cache::get($cacheKey, []);
        
        // $cleanedNotifications = array_filter($notifications, function($notification) {
        //     // Keep unread notifications always
        //     if (!$notification['read']) {
        //         return true;
        //     }
        //     
        //     // Remove read notifications older than 3 days
        //     $createdAt = \Carbon\Carbon::parse($notification['created_at']);
        //     return $createdAt->gt(now()->subDays(3));
        // });
        
        // // Still enforce 5 notification limit after cleanup
        // $cleanedNotifications = array_slice($cleanedNotifications, 0, 5);
        
        // if (count($cleanedNotifications) !== count($notifications)) {
        //     Cache::put($cacheKey, array_values($cleanedNotifications), 86400 * 30);
        //     self::updateUnreadCount($user);
        //     
        //     $removedCount = count($notifications) - count($cleanedNotifications);
        //     \Log::info("AGGRESSIVE CLEANUP: Removed {$removedCount} old read notifications for user {$user->id}");
        // }
    }

    /**
     * AGGRESSIVE DELETE - DISABLED: Remove all viewed notifications immediately
     * DISABLED: Called when user opens notification dropdown (after 1 minute)
     */
    public static function aggressiveDeleteViewed(User $user): int
    {
        // DISABLED: Aggressive deletion
        // $cacheKey = "user_notifications_{$user->id}";
        // $notifications = Cache::get($cacheKey, []);
        
        // $initialCount = count($notifications);
        
        // // DELETE ALL NOTIFICATIONS - AGGRESSIVE DATABASE OPTIMIZATION
        // Cache::forget($cacheKey);
        // Cache::forget("user_unread_count_{$user->id}");
        
        // Log::info("AGGRESSIVE DELETE: Removed ALL {$initialCount} notifications for user {$user->id} for database optimization");
        
        // return $initialCount;
        
        return 0; // No notifications deleted
    }
    
    /**
     * ULTRA AGGRESSIVE - DISABLED: Delete all notifications older than X minutes after being read
     */
    public static function ultraAggressiveCleanup(User $user, int $minutesAfterRead = 1): int
    {
        // DISABLED: Ultra aggressive cleanup
        // $cacheKey = "user_notifications_{$user->id}";
        // $notifications = Cache::get($cacheKey, []);
        
        // $cutoffTime = now()->subMinutes($minutesAfterRead);
        // $initialCount = count($notifications);
        
        // // Keep only unread notifications OR very recent read notifications
        // $filteredNotifications = array_filter($notifications, function($notification) use ($cutoffTime) {
        //     // Always keep unread notifications
        //     if (!$notification['read']) {
        //         return true;
        //     }
        //     
        //     // For read notifications, only keep if read very recently
        //     $createdAt = \Carbon\Carbon::parse($notification['created_at']);
        //     return $createdAt->gt($cutoffTime);
        // });
        
        // // Still enforce 5 notification limit
        // $filteredNotifications = array_slice($filteredNotifications, 0, 5);
        
        // $deletedCount = $initialCount - count($filteredNotifications);
        
        // if ($deletedCount > 0) {
        //     Cache::put($cacheKey, array_values($filteredNotifications), 86400 * 30);
        //     self::updateUnreadCount($user);
        //     
        //     Log::info("ULTRA AGGRESSIVE: Deleted {$deletedCount} old read notifications for user {$user->id}");
        // }
        
        // return $deletedCount;
        
        return 0; // No notifications deleted
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