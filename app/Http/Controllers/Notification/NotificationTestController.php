<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\UniversalNotificationService;
use Illuminate\Http\JsonResponse;

class NotificationTestController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * Create a test notification (admin only) - LETHAL CACHED
     */
    public function createTest(Request $request): JsonResponse
    {
        try {
            if (auth()->user()->role !== 'admin') {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $user = auth()->user();
            
            // LETHAL CACHING: Cache test notification templates
            $cacheKey = 'test_notification_templates_' . now()->format('Y-m-d');
            $templates = \Illuminate\Support\Facades\Cache::remember($cacheKey, 86400, function () {
                return [
                    'assignment_reminder' => [
                        'title' => 'Assignment Reminder',
                        'message' => 'You have an upcoming assignment due soon.',
                        'type' => 'reminder',
                        'priority' => 'normal'
                    ],
                    'quiz_completed' => [
                        'title' => 'Quiz Completed',
                        'message' => 'Congratulations! You have completed your quiz.',
                        'type' => 'success',
                        'priority' => 'low'
                    ],
                    'system_maintenance' => [
                        'title' => 'System Maintenance',
                        'message' => 'System will be under maintenance tonight.',
                        'type' => 'warning',
                        'priority' => 'high'
                    ]
                ];
            });

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
                'notification' => $notification,
                'available_templates' => $templates
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Test notification creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
    }

    /**
     * STRESS TEST: Generate comprehensive test notifications
     */
    public function stressTest(): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

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
                    'message' => 'Your payment of $299.99 for Premium Medical Course has been processed. You now have access to all premium content.',
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
        } catch (\Exception $e) {
            \Log::error('Method execution failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
    }
}