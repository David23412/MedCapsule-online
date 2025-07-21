<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UniversalNotificationService;
use Illuminate\Http\JsonResponse;

class NotificationTestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * STRESS TEST: Generate comprehensive test notifications
     */
    public function generateStressTestData(): JsonResponse
    {
        $user = auth()->user();
        
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Admin access required'], 403);
        }

        $notifications = [];
        
        // Test all notification types with different priorities
        $testData = [
            [
                'type' => 'course_welcome',
                'title' => 'Welcome to Advanced Cardiology',
                'message' => 'You have been successfully enrolled in Advanced Cardiology. Start your journey with comprehensive cardiac care education.',
                'priority' => 'high'
            ],
            [
                'type' => 'quiz_completed',
                'title' => 'Quiz Completed: Pharmacology Basics',
                'message' => 'Great job! You scored 95% on the Pharmacology Basics quiz. Your understanding of drug interactions is excellent.',
                'priority' => 'normal'
            ],
            [
                'type' => 'achievement',
                'title' => 'Achievement Unlocked: Study Streak Master',
                'message' => 'Congratulations! You\'ve maintained a 30-day study streak. Your dedication is paying off!',
                'priority' => 'high'
            ],
            [
                'type' => 'streak_milestone',
                'title' => '🔥 15-Day Study Streak!',
                'message' => 'Amazing consistency! You\'ve studied for 15 days straight. Keep up the excellent work!',
                'priority' => 'high'
            ],
            [
                'type' => 'assignment_due',
                'title' => 'Assignment Due Tomorrow',
                'message' => 'Your Anatomy Case Study assignment is due tomorrow at 11:59 PM. Don\'t forget to submit!',
                'priority' => 'urgent'
            ],
            [
                'type' => 'grade_released',
                'title' => 'Grade Released: Pathophysiology Exam',
                'message' => 'Your grade for the Pathophysiology midterm exam has been released. Check your progress dashboard.',
                'priority' => 'normal'
            ],
            [
                'type' => 'system_update',
                'title' => 'System Maintenance Scheduled',
                'message' => 'MedCapsule will undergo scheduled maintenance on Sunday from 2:00 AM to 4:00 AM EST.',
                'priority' => 'low'
            ],
            [
                'type' => 'payment_success',
                'title' => 'Payment Successful',
                'message' => 'Your payment of $299.99 for the Premium Medical Package has been processed successfully.',
                'priority' => 'high'
            ],
            [
                'type' => 'payment_failed',
                'title' => 'Payment Failed',
                'message' => 'We couldn\'t process your payment for the Advanced Neurology course. Please update your payment method.',
                'priority' => 'urgent'
            ],
            [
                'type' => 'course_access_granted',
                'title' => 'Access Granted: Emergency Medicine',
                'message' => 'You now have access to the Emergency Medicine course. Start learning critical care procedures.',
                'priority' => 'high'
            ],
            [
                'type' => 'course_access_denied',
                'title' => 'Access Request Denied',
                'message' => 'Your request for access to the Surgical Procedures course has been denied. Contact your administrator.',
                'priority' => 'normal'
            ],
            [
                'type' => 'reminder',
                'title' => 'Study Reminder',
                'message' => 'Don\'t forget to review your flashcards for tomorrow\'s Microbiology quiz. You\'ve got this!',
                'priority' => 'normal'
            ],
            [
                'type' => 'announcement',
                'title' => 'New Feature: AI Study Assistant',
                'message' => 'Introducing our new AI-powered study assistant! Get personalized study recommendations and instant answers.',
                'priority' => 'normal'
            ],
            [
                'type' => 'error',
                'title' => 'Sync Error Resolved',
                'message' => 'The synchronization error with your study progress has been resolved. All data is now up to date.',
                'priority' => 'normal'
            ],
            [
                'type' => 'success',
                'title' => 'Profile Updated Successfully',
                'message' => 'Your profile information has been updated successfully. Changes are now live.',
                'priority' => 'low'
            ],
            [
                'type' => 'warning',
                'title' => 'Low Quiz Performance Alert',
                'message' => 'Your recent quiz scores have been below average. Consider reviewing the study materials.',
                'priority' => 'urgent'
            ],
            [
                'type' => 'info',
                'title' => 'New Study Materials Available',
                'message' => 'New study materials for Clinical Diagnostics have been added to your course library.',
                'priority' => 'low'
            ]
        ];

        // Generate notifications with random delays to simulate real-world timing
        foreach ($testData as $data) {
            $notification = UniversalNotificationService::create(
                $user,
                $data['type'],
                $data['title'],
                $data['message'],
                ['test_data' => true],
                $data['priority']
            );
            
            $notifications[] = $notification;
        }

        // Generate some additional notifications with edge cases
        $edgeCases = [
            [
                'type' => 'course_welcome',
                'title' => 'Very Long Title That Should Test The Notification System\'s Ability To Handle Extended Text Content Without Breaking The Layout',
                'message' => 'This is an extremely long message designed to test how the notification system handles lengthy content. It should wrap properly and maintain readability while not breaking the overall design of the notification dropdown. The system should gracefully handle this edge case scenario.',
                'priority' => 'normal'
            ],
            [
                'type' => 'achievement',
                'title' => 'Short',
                'message' => 'Brief.',
                'priority' => 'high'
            ],
            [
                'type' => 'error',
                'title' => 'Special Characters Test: áéíóú ñ ç 中文 العربية русский',
                'message' => 'Testing unicode support: 🎉🔥💯⭐🏆📚💊🩺 and symbols: @#$%^&*()_+-=[]{}|;:,.<>?',
                'priority' => 'urgent'
            ]
        ];

        foreach ($edgeCases as $data) {
            $notification = UniversalNotificationService::create(
                $user,
                $data['type'],
                $data['title'],
                $data['message'],
                ['test_data' => true, 'edge_case' => true],
                $data['priority']
            );
            
            $notifications[] = $notification;
        }

        return response()->json([
            'success' => true,
            'message' => 'Stress test data generated successfully',
            'notifications_created' => count($notifications),
            'types_tested' => array_unique(array_column($testData, 'type')),
            'priorities_tested' => array_unique(array_column($testData, 'priority'))
        ]);
    }

    /**
     * STRESS TEST: Generate high volume notifications
     */
    public function generateHighVolumeTest(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Admin access required'], 403);
        }

        $count = $request->get('count', 50);
        $count = min($count, 100); // Limit to prevent abuse

        $notifications = [];
        $types = ['course_welcome', 'quiz_completed', 'achievement', 'streak_milestone', 'assignment_due'];
        $priorities = ['low', 'normal', 'high', 'urgent'];

        for ($i = 1; $i <= $count; $i++) {
            $type = $types[array_rand($types)];
            $priority = $priorities[array_rand($priorities)];
            
            $notification = UniversalNotificationService::create(
                $user,
                $type,
                "High Volume Test Notification #{$i}",
                "This is test notification number {$i} generated for high volume stress testing of the notification system.",
                ['test_data' => true, 'volume_test' => true, 'sequence' => $i],
                $priority
            );
            
            $notifications[] = $notification;
        }

        return response()->json([
            'success' => true,
            'message' => "Generated {$count} high volume test notifications",
            'notifications_created' => count($notifications)
        ]);
    }

    /**
     * STRESS TEST: Test notification performance
     */
    public function performanceTest(): JsonResponse
    {
        $user = auth()->user();
        
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Admin access required'], 403);
        }

        $startTime = microtime(true);

        // Test notification retrieval performance
        $notifications = UniversalNotificationService::getNotifications($user, 50);
        $retrievalTime = microtime(true) - $startTime;

        // Test unread count performance
        $countStartTime = microtime(true);
        $unreadCount = UniversalNotificationService::getUnreadCount($user);
        $countTime = microtime(true) - $countStartTime;

        // Test stats performance
        $statsStartTime = microtime(true);
        $stats = UniversalNotificationService::getStats($user);
        $statsTime = microtime(true) - $statsStartTime;

        return response()->json([
            'success' => true,
            'performance_metrics' => [
                'notification_retrieval_time' => round($retrievalTime * 1000, 2) . 'ms',
                'unread_count_time' => round($countTime * 1000, 2) . 'ms',
                'stats_calculation_time' => round($statsTime * 1000, 2) . 'ms',
                'total_notifications' => $notifications->count(),
                'unread_count' => $unreadCount,
                'stats' => $stats
            ]
        ]);
    }

    /**
     * STRESS TEST: Clear all test notifications
     */
    public function clearTestData(): JsonResponse
    {
        $user = auth()->user();
        
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Admin access required'], 403);
        }

        // Clear test notifications from cache
        $cacheKey = "user_notifications_{$user->id}";
        $notifications = \Illuminate\Support\Facades\Cache::get($cacheKey, []);
        
        $testNotifications = array_filter($notifications, function($notification) {
            return isset($notification['data']['test_data']) && $notification['data']['test_data'] === true;
        });

        $cleanNotifications = array_filter($notifications, function($notification) {
            return !isset($notification['data']['test_data']) || $notification['data']['test_data'] !== true;
        });

        \Illuminate\Support\Facades\Cache::put($cacheKey, array_values($cleanNotifications), 86400 * 30);
        
        // Update unread count
        $unreadCount = collect($cleanNotifications)->where('read', false)->count();
        \Illuminate\Support\Facades\Cache::put("user_unread_count_{$user->id}", $unreadCount, 86400);

        return response()->json([
            'success' => true,
            'message' => 'Test notifications cleared successfully',
            'cleared_count' => count($testNotifications),
            'remaining_count' => count($cleanNotifications)
        ]);
    }
}