<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UniversalNotificationService;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get notifications for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $limit = $request->get('limit', 20);
        
        $notifications = UniversalNotificationService::getNotifications($user, $limit);
        $unreadCount = UniversalNotificationService::getUnreadCount($user);
        
        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Request $request): JsonResponse
    {
        $request->validate([
            'notification_id' => 'required|string'
        ]);

        $user = auth()->user();
        $notificationId = $request->get('notification_id');
        
        $success = UniversalNotificationService::markAsRead($user, $notificationId);
        $unreadCount = UniversalNotificationService::getUnreadCount($user);
        
        return response()->json([
            'success' => $success,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = auth()->user();
        
        $success = UniversalNotificationService::markAllAsRead($user);
        
        return response()->json([
            'success' => $success,
            'unread_count' => 0
        ]);
    }

    /**
     * Get notification statistics
     */
    public function stats(): JsonResponse
    {
        $user = auth()->user();
        $stats = UniversalNotificationService::getStats($user);
        
        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Clean up expired notifications
     */
    public function cleanup(): JsonResponse
    {
        $user = auth()->user();
        $removedCount = UniversalNotificationService::cleanupExpiredNotifications($user);
        
        return response()->json([
            'success' => true,
            'removed_count' => $removedCount
        ]);
    }

    /**
     * Create a test notification (admin only)
     */
    public function createTest(Request $request): JsonResponse
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'type' => 'required|string',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:500',
            'priority' => 'sometimes|string|in:urgent,high,normal,low'
        ]);

        $user = auth()->user();
        $notification = UniversalNotificationService::create(
            $user,
            $request->get('type'),
            $request->get('title'),
            $request->get('message'),
            [],
            $request->get('priority', 'normal')
        );

        return response()->json([
            'success' => true,
            'notification' => $notification
        ]);
    }

    /**
     * STRESS TEST: Generate comprehensive test notifications
     */
    public function stressTest(): JsonResponse
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // STRESS TEST DATA - ALL NOTIFICATION TYPES WITH ICONS
        $testNotifications = [
            [
                'type' => 'course_welcome',
                'title' => 'Welcome to Advanced Cardiology!',
                'message' => 'You have been successfully enrolled in Advanced Cardiology. Start your journey with comprehensive cardiac care education.',
                'priority' => 'high'
            ],
            [
                'type' => 'quiz_completed',
                'title' => 'Quiz Completed Successfully',
                'message' => 'You scored 95% on Cardiovascular Physiology Quiz. Excellent work! Keep up the momentum.',
                'priority' => 'normal'
            ],
            [
                'type' => 'achievement',
                'title' => 'Achievement Unlocked: Quiz Master',
                'message' => 'Congratulations! You have completed 50 quizzes with an average score of 90%. You are now a certified Quiz Master!',
                'priority' => 'high'
            ],
            [
                'type' => 'streak_milestone',
                'title' => '🔥 30-Day Study Streak!',
                'message' => 'Amazing dedication! You have maintained a 30-day study streak. Your consistency is paying off with improved performance.',
                'priority' => 'high'
            ],
            [
                'type' => 'assignment_due',
                'title' => 'Assignment Due Tomorrow',
                'message' => 'Your Pathophysiology Case Study assignment is due tomorrow at 11:59 PM. Make sure to submit it on time.',
                'priority' => 'urgent'
            ],
            [
                'type' => 'grade_released',
                'title' => 'New Grade Available',
                'message' => 'Your grade for Pharmacology Midterm has been released. You scored 88% - Great job on this challenging exam!',
                'priority' => 'normal'
            ],
            [
                'type' => 'system_update',
                'title' => 'System Maintenance Complete',
                'message' => 'Our scheduled maintenance is complete. New features include improved quiz analytics and enhanced study tracking.',
                'priority' => 'low'
            ],
            [
                'type' => 'payment_success',
                'title' => 'Payment Processed Successfully',
                'message' => 'Your payment of $299.99 for Premium Medical Course Bundle has been processed. You now have access to all premium content.',
                'priority' => 'normal'
            ],
            [
                'type' => 'payment_failed',
                'title' => 'Payment Failed - Action Required',
                'message' => 'Your payment for Advanced Neurology Course could not be processed. Please update your payment method to continue.',
                'priority' => 'urgent'
            ],
            [
                'type' => 'course_access_granted',
                'title' => 'Course Access Granted',
                'message' => 'You now have access to Emergency Medicine Protocols. This premium course includes 200+ scenarios and case studies.',
                'priority' => 'normal'
            ],
            [
                'type' => 'course_access_denied',
                'title' => 'Course Access Pending',
                'message' => 'Your request for Surgical Techniques Course is under review. You will be notified once the review is complete.',
                'priority' => 'normal'
            ],
            [
                'type' => 'reminder',
                'title' => 'Study Session Reminder',
                'message' => 'Don\'t forget your scheduled study session for Anatomy & Physiology at 3:00 PM today. Stay consistent with your learning!',
                'priority' => 'normal'
            ],
            [
                'type' => 'announcement',
                'title' => 'New Feature: AI Study Assistant',
                'message' => 'Introducing our new AI-powered study assistant! Get personalized study recommendations and instant answers to your medical questions.',
                'priority' => 'high'
            ],
            [
                'type' => 'error',
                'title' => 'Sync Error Resolved',
                'message' => 'We detected and resolved a sync error with your study progress. All your data has been successfully recovered.',
                'priority' => 'normal'
            ],
            [
                'type' => 'success',
                'title' => 'Profile Updated Successfully',
                'message' => 'Your profile information has been updated successfully. Your new specialization preferences have been saved.',
                'priority' => 'low'
            ],
            [
                'type' => 'warning',
                'title' => 'Low Quiz Performance Alert',
                'message' => 'Your recent quiz scores have dropped below 70%. Consider reviewing the study materials or reaching out for help.',
                'priority' => 'urgent'
            ],
            [
                'type' => 'info',
                'title' => 'Study Tips: Effective Note-Taking',
                'message' => 'Learn how to take effective notes during lectures with our new study guide. Improve retention and exam performance.',
                'priority' => 'low'
            ]
        ];

        $createdNotifications = [];
        
        foreach ($testNotifications as $notificationData) {
            $notification = UniversalNotificationService::create(
                $user,
                $notificationData['type'],
                $notificationData['title'],
                $notificationData['message'],
                ['stress_test' => true],
                $notificationData['priority']
            );
            
            $createdNotifications[] = $notification;
        }

        return response()->json([
            'success' => true,
            'message' => 'Stress test completed successfully',
            'notifications_created' => count($createdNotifications),
            'types_tested' => array_unique(array_column($testNotifications, 'type')),
            'priorities_tested' => array_unique(array_column($testNotifications, 'priority')),
            'notifications' => $createdNotifications
        ]);
    }

    /**
     * Clear all test notifications
     */
    public function clearTestNotifications(): JsonResponse
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Clear all notifications for the user (this is a test method)
        $cacheKey = "user_notifications_{$user->id}";
        \Illuminate\Support\Facades\Cache::forget($cacheKey);
        \Illuminate\Support\Facades\Cache::put("user_unread_count_{$user->id}", 0, 86400);

        return response()->json([
            'success' => true,
            'message' => 'All test notifications cleared'
        ]);
    }
    
    /**
     * AGGRESSIVE CLEANUP - Delete all viewed notifications immediately
     * Called when user opens notification dropdown (after 1 minute)
     */
    public function aggressiveCleanup(): JsonResponse
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $deletedCount = UniversalNotificationService::aggressiveDeleteViewed($user);
        
        return response()->json([
            'success' => true,
            'deleted_count' => $deletedCount,
            'message' => 'All viewed notifications deleted for database optimization'
        ]);
    }
    
    /**
     * Delete specific notification immediately
     */
    public function deleteNotification(Request $request): JsonResponse
    {
        $request->validate([
            'notification_id' => 'required|string'
        ]);

        $user = auth()->user();
        $notificationId = $request->get('notification_id');
        
        $success = UniversalNotificationService::deleteNotification($user, $notificationId);
        
        return response()->json([
            'success' => $success,
            'message' => 'Notification deleted for database optimization'
        ]);
    }
    
    /**
     * ULTRA AGGRESSIVE CLEANUP - Delete old read notifications
     */
    public function ultraCleanup(): JsonResponse
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $deletedCount = UniversalNotificationService::ultraAggressiveCleanup($user, 1);
        
        return response()->json([
            'success' => true,
            'deleted_count' => $deletedCount,
            'message' => 'Ultra aggressive cleanup completed for maximum database optimization'
        ]);
    }
}