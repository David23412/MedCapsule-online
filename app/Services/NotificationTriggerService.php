<?php

namespace App\Services;

use App\Models\User;
use App\Models\Assignment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NotificationTriggerService
{
    /**
     * 🐆 NUCLEAR STABLE NOTIFICATION SYSTEM - AUTOMATIC ACTIVITY DETECTION
     * 🔥 ZERO COMPROMISE STABILITY - ALWAYS SENDS CORRECT NOTIFICATIONS
     */

    /**
     * MASTER QUIZ COMPLETION HANDLER - DETECTS ACTIVITY TYPE AUTOMATICALLY
     */
    public static function onQuizCompleted(User $user, array $quizData)
    {
        try {
            $score = $quizData['score'] ?? 0;
            $totalQuestions = $quizData['total_questions'] ?? 1;

            // Prevent division by zero
            if ($totalQuestions <= 0) {
                $totalQuestions = 1;
            }

            $percentage = round(($score / $totalQuestions) * 100, 1);
            $courseName = $quizData['course_name'] ?? 'Course';
            $topicName = $quizData['topic_name'] ?? 'Topic';
            $mode = $quizData['mode'] ?? 'quiz';
            $quizType = $quizData['quiz_type'] ?? 'normal';

            // 🔥 NUCLEAR STABLE: ACTIVITY TYPE DETECTION
            $activityType = self::detectActivityType($quizData);

            // 🔥 ALWAYS SEND THE CORRECT NOTIFICATION BASED ON ACTIVITY
            $notificationData = self::getNotificationForActivity($activityType, $percentage, $quizData);

            if ($notificationData) {
                $result = UniversalNotificationService::create(
                    $user,
                    $notificationData['type'],
                    $notificationData['title'],
                    $notificationData['message'],
                    $quizData,
                    $notificationData['priority']
                );

                Log::info("🐆 NUCLEAR NOTIFICATION: {$notificationData['type']} sent for {$activityType}", [
                    'user_id' => $user->id,
                    'score' => $percentage,
                    'activity_type' => $activityType,
                    'notification_type' => $notificationData['type'],
                    'result' => $result
                ]);
            } else {
                Log::warning("🐆 NUCLEAR NOTIFICATION: No notification data for activity {$activityType}", [
                    'user_id' => $user->id,
                    'quiz_data' => $quizData
                ]);
            }

        } catch (\Exception $e) {
            Log::error('🐆 NUCLEAR NOTIFICATION FAILED', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'quiz_data' => $quizData,
                'trace' => $e->getTraceAsString()
            ]);

            // 🔥 FALLBACK: Always send a basic notification
            try {
                UniversalNotificationService::create(
                    $user,
                    'quiz_completed',
                    'Quiz Completed',
                    "You completed a quiz with score {$percentage}%",
                    $quizData,
                    'normal'
                );
                Log::info("🐆 FALLBACK NOTIFICATION: Sent basic quiz completion", [
                    'user_id' => $user->id,
                    'percentage' => $percentage
                ]);
            } catch (\Exception $fallbackError) {
                Log::error('🐆 FALLBACK NOTIFICATION FAILED', [
                    'user_id' => $user->id,
                    'error' => $fallbackError->getMessage()
                ]);
            }
        }
    }

    /**
     * 🔥 NUCLEAR ACTIVITY TYPE DETECTOR - 100% ACCURATE
     */
    private static function detectActivityType(array $quizData): string
    {
        // Priority detection order
        if (isset($quizData['assignment_completed']) && $quizData['assignment_completed']) {
            return 'assignment_completion';
        }

        if (isset($quizData['quiz_type']) && $quizData['quiz_type'] === 'random') {
            return 'random_quiz';
        }

        if (isset($quizData['mode']) && $quizData['mode'] === 'tutor') {
            return 'tutor_quiz';
        }

        if (isset($quizData['mode']) && $quizData['mode'] === 'mistake_review') {
            return 'mistake_review';
        }

        return 'regular_quiz';
    }

    /**
     * 🔥 NUCLEAR NOTIFICATION MAPPER - EXACTLY AS DEFINED IN UNIVERSAL SERVICE
     */
    private static function getNotificationForActivity(string $activityType, float $percentage, array $quizData): ?array
    {
        $courseName = $quizData['course_name'] ?? 'Course';
        $topicName = $quizData['topic_name'] ?? 'Topic';
        $assignmentTitle = $quizData['assignment_title'] ?? 'Assignment';

        $notifications = [
            'assignment_completion' => [
                'type' => $percentage == 100 ? 'assignment_perfect' : 'assignment_completed',
                'title' => $percentage == 100 ? 'Perfect Assignment Score' : 'Assignment Completed',
                'message' => $percentage == 100
                    ? "Amazing! You got 100% on '{$assignmentTitle}'"
                    : "You scored {$percentage}% on '{$assignmentTitle}'",
                'priority' => $percentage == 100 ? 'perfect' : 'normal'
            ],

            'random_quiz' => [
                'type' => $percentage == 100 ? 'random_quiz_perfect' : 'random_quiz_completed',
                'title' => $percentage == 100 ? 'Perfect Random Quiz!' : 'Random Quiz Completed',
                'message' => $percentage == 100
                    ? "Outstanding! You got 100% on your random quiz from {$courseName}"
                    : "You scored {$percentage}% on your random quiz from {$courseName}",
                'priority' => $percentage == 100 ? 'perfect' : 'normal'
            ],

            'tutor_quiz' => [
                'type' => $percentage == 100 ? 'tutor_perfect' : 'tutor_quiz_completed',
                'title' => $percentage == 100 ? 'Perfect Tutor Score' : 'Tutor Quiz Completed',
                'message' => $percentage == 100
                    ? "Outstanding! You got 100% in tutor mode for {$topicName}"
                    : "You scored {$percentage}% in tutor mode for {$topicName}",
                'priority' => $percentage == 100 ? 'perfect' : 'normal'
            ],

            'mistake_review' => [
                'type' => $percentage == 100 ? 'perfect_score' : 'quiz_completed',
                'title' => $percentage == 100 ? 'Perfect Score' : 'Mistake Review Completed',
                'message' => $percentage == 100
                    ? "Amazing! You got 100% on mistake review for {$topicName}"
                    : "You scored {$percentage}% on mistake review for {$topicName}",
                'priority' => $percentage == 100 ? 'perfect' : 'normal'
            ],

            'regular_quiz' => [
                'type' => $percentage == 100 ? 'perfect_score' : 'quiz_completed',
                'title' => $percentage == 100 ? 'Perfect Score' : 'Quiz Completed',
                'message' => $percentage == 100
                    ? "Amazing! You got 100% on {$topicName}"
                    : "You scored {$percentage}% on {$topicName}",
                'priority' => $percentage == 100 ? 'perfect' : 'normal'
            ]
        ];

        return $notifications[$activityType] ?? null;
    }

    /**
     * REMOVED: Tutor session completion (redundant)
     * Use onTopicMastery() instead for 90%+ accuracy
     */

    // 📚 COURSE ENROLLMENT NOTIFICATIONS
    public static function onCourseEnrollment(User $user, string $courseName, string $courseType = 'general', bool $isFree = false)
    {
        // Course-specific enrollment with free course indication
        $notificationType = strtolower($courseName) . '_enrolled';
        $title = $isFree ? "Welcome to {$courseName}!" : ucfirst($courseName) . ' Course';
        $message = $isFree 
            ? "You've been automatically enrolled in the free {$courseName} course" 
            : "You have enrolled in the {$courseName} course";
        
        UniversalNotificationService::create(
            $user,
            $notificationType,
            $title,
            $message,
            ['course' => $courseName, 'is_free' => $isFree],
            $isFree ? 'high' : 'normal'
        );

        Log::info('Course enrollment notifications sent', [
            'user_id' => $user->id,
            'course' => $courseName,
            'is_free' => $isFree
        ]);
    }

    // 🆓 NEW: Welcome notification for new users (LOGIN TRIGGERED)
    public static function onUserWelcome(User $user)
    {
        UniversalNotificationService::create(
            $user,
            'medcapsule_welcome',
            'Welcome to MedCapsule!',
            'Welcome to the MedCapsule learning platform! Start your medical education journey.',
            ['platform' => 'medcapsule'],
            'blue' // NEW: Special priority for blue border
        );

        Log::info('Welcome notification sent', [
            'user_id' => $user->id
        ]);
    }

    // 🔑 NEW: Login welcome notification (SHOULD BE CALLED ON LOGIN)
    public static function onUserLogin(User $user)
    {
        // Check if this is their first login or hasn't been welcomed recently
        $recentWelcome = UniversalNotificationService::getNotifications($user, 10)
            ->where('type', 'medcapsule_welcome')
            ->where('created_at', '>', now()->subDays(7))
            ->first();

        if (!$recentWelcome) {
            self::onUserWelcome($user);
        }

        Log::info('Login processed for welcome notification', [
            'user_id' => $user->id,
            'welcome_sent' => !$recentWelcome
        ]);
    }

    // 📈 COURSE PROGRESS NOTIFICATIONS
    public static function onCourseProgress(User $user, string $courseName, int $progressPercentage)
    {
        // Halfway progress notification
        if ($progressPercentage >= 50 && $progressPercentage < 60) {
            $notificationType = strtolower($courseName) . '_halfway';
            UniversalNotificationService::create(
                $user,
                $notificationType,
                ucfirst($courseName) . ' Progress',
                "You are halfway through the {$courseName} course",
                ['course' => $courseName, 'progress' => $progressPercentage],
                'normal'
            );
        }

        // Course completion notification
        if ($progressPercentage >= 100) {
            $notificationType = strtolower($courseName) . '_completed';
            UniversalNotificationService::create(
                $user,
                $notificationType,
                ucfirst($courseName) . ' Complete',
                "You have completed the {$courseName} course",
                ['course' => $courseName, 'progress' => $progressPercentage],
                'high'
            );
        }

        Log::info('Course progress notifications sent', [
            'user_id' => $user->id,
            'course' => $courseName,
            'progress' => $progressPercentage
        ]);
    }

    // 🎯 NEW: Topic mastery notification (GREEN BORDER)
    public static function onTopicMastery(User $user, string $topicName, ?string $courseName = null)
    {
        UniversalNotificationService::create(
            $user,
            'topic_mastery',
            'Topic Mastered!',
            "Congratulations! You've mastered {$topicName}" . ($courseName ? " in {$courseName}" : ""),
            ['topic' => $topicName, 'course' => $courseName],
            'mastery' // NEW: Special priority for green border
        );

        Log::info('Topic mastery notification sent', [
            'user_id' => $user->id,
            'topic' => $topicName,
            'course' => $courseName
        ]);
    }

    // 💎 NEW: Course mastery notification (PREMIUM DEEP BLUE BORDER)
    public static function onCourseMastery(User $user, string $courseName)
    {
        UniversalNotificationService::create(
            $user,
            'course_mastery',
            'COURSE MASTERED!',
            "WOW! You just MASTERED {$courseName}! Every single topic conquered. You are now officially a legend!",
            ['course' => $courseName],
            'premium' // NEW: Special priority for deep blue border
        );

        Log::info('Course mastery notification sent', [
            'user_id' => $user->id,
            'course' => $courseName
        ]);
    }

    // 🆙 NEW: Level up notification (FIERY BORDER)
    public static function onLevelUp(User $user, int $newLevel, ?string $context = null)
    {
        UniversalNotificationService::create(
            $user,
            'level_up',
            'Level Up!',
            "Amazing! You've reached level {$newLevel}!" . ($context ? " {$context}" : ""),
            ['level' => $newLevel, 'context' => $context],
            'fiery' // NEW: Special priority for fiery border
        );

        Log::info('Level up notification sent', [
            'user_id' => $user->id,
            'level' => $newLevel,
            'context' => $context
        ]);
    }

    // 📝 ASSIGNMENT NOTIFICATIONS
    
    /**
     * 1) ASSIGNMENT ASSIGNED TO STUDENT
     */
    public static function onTutorAssignsAssignment(User $student, Assignment $assignment, ?User $tutor = null)
    {
        // Combined notification with due date info
        if ($assignment->due_date) {
            $dueDate = \Carbon\Carbon::parse($assignment->due_date);
            $message = "New assignment '{$assignment->title}' is due on {$dueDate->format('M j, Y \a\t g:i A')}";
        } else {
            $message = "New assignment '{$assignment->title}' has been assigned to you";
        }

        UniversalNotificationService::create(
            $student,
            'assignment_assigned',
            'New Assignment',
            $message,
            [
                'assignment_id' => $assignment->id,
                'due_date' => $assignment->due_date
            ],
            'high'
        );

        Log::info('Assignment notification sent', [
            'student_id' => $student->id,
            'assignment_id' => $assignment->id
        ]);
    }

    /**
     * LETHAL FIX: SIMPLIFIED ASSIGNMENT COMPLETION (like quiz system)
     */
    public static function onAssignmentCompleted(User $user, array $assignmentData)
    {
        $score = $assignmentData['score'] ?? 0;
        $totalQuestions = $assignmentData['total_questions'] ?? 1;
        $percentage = round(($score / $totalQuestions) * 100, 1);
        $assignmentTitle = $assignmentData['assignment_title'] ?? 'Assignment';
        $assignmentId = $assignmentData['assignment_id'] ?? null;

        // LETHAL FIX: Perfect assignment score gets ONLY purple star notification (no duplicate)
        if ($percentage == 100) {
            UniversalNotificationService::create(
                $user,
                'assignment_perfect',
                'Perfect Score',
                "Amazing! You got 100% on '{$assignmentTitle}'",
                $assignmentData,
                'perfect' // PURPLE BORDER for perfect scores
            );
        } else {
            // Regular assignment completion notification for non-perfect scores
            UniversalNotificationService::create(
                $user,
                'assignment_completed',
                'Assignment Completed',
                "You scored {$percentage}% on '{$assignmentTitle}'",
                $assignmentData,
                'normal'
            );
        }

        Log::info('Assignment completion notifications sent', [
            'user_id' => $user->id,
            'assignment_id' => $assignmentId,
            'score' => $percentage,
            'assignment_title' => $assignmentTitle
        ]);
    }

    /**
     * SIMPLIFIED: Assignment due warnings (3-1 hours only) and single overdue notification
     */
    public static function onAssignmentOverdueReminder(User $user, Assignment $assignment, int $hoursUntilDue)
    {
        $isOverdue = $hoursUntilDue < 0;
        
        if ($isOverdue) {
            // Single overdue notification - simple and clear
            UniversalNotificationService::create(
                $user,
                'assignment_overdue',
                'Assignment Overdue',
                "You missed '{$assignment->title}' deadline!",
                ['assignment_id' => $assignment->id],
                'urgent'
            );
        } else {
            // Only warn for 3-1 hours (efficiency focused)
            if ($hoursUntilDue <= 1) {
                UniversalNotificationService::create(
                    $user,
                    'assignment_due_1h',
                    'DUE IN 1 HOUR',
                    "URGENT: '{$assignment->title}' is due in {$hoursUntilDue} hours - Finish now!",
                    ['assignment_id' => $assignment->id, 'hours_until_due' => $hoursUntilDue],
                    'urgent'
                );
            } elseif ($hoursUntilDue <= 3) {
                UniversalNotificationService::create(
                    $user,
                    'assignment_due_3h',
                    'DUE IN 3 HOURS',
                    "Hurry up! '{$assignment->title}' is due in {$hoursUntilDue} hours - Time to focus!",
                    ['assignment_id' => $assignment->id, 'hours_until_due' => $hoursUntilDue],
                    'urgent'
                );
            }
        }

        Log::info('Assignment reminder sent', [
            'user_id' => $user->id,
            'assignment_id' => $assignment->id,
            'hours_until_due' => $hoursUntilDue,
            'is_overdue' => $isOverdue
        ]);
    }



    // 💳 PAYMENT NOTIFICATIONS WITH COLOR MAPPING 🔥
    public static function onPaymentSubmitted(User $user, array $paymentData)
    {
        $amount = $paymentData['amount'] ?? 0;
        $courseName = $paymentData['course_name'] ?? 'Course';
        $providerTransaction = $paymentData['transaction_id'] ?? 'N/A';

        // 🔥 ORANGE BORDER: Payment submitted for processing
        UniversalNotificationService::create(
            $user,
            'payment_pending',
            'Payment Submitted!',
            "Your payment of \${$amount} for {$courseName} has been submitted and is being processed. TX: {$providerTransaction}",
            $paymentData,
            'orange' // 🔥 ORANGE BORDER FOR PAYMENT SUBMITTED
        );

        Log::info('Payment submitted notification sent', [
            'user_id' => $user->id,
            'amount' => $amount,
            'course' => $courseName,
            'transaction_id' => $providerTransaction
        ]);
    }

    public static function onPaymentAccepted(User $user, array $paymentData)
    {
        $amount = $paymentData['amount'] ?? 0;
        $courseName = $paymentData['course_name'] ?? 'Course';

        // ✅ SUCCESS GREEN BORDER: Payment accepted
        UniversalNotificationService::create(
            $user,
            'payment_accepted',
            'Payment Accepted!',
            "Great news! Your payment of \${$amount} for {$courseName} has been accepted. You now have access to the course!",
            $paymentData,
            'green' // ✅ GREEN BORDER FOR PAYMENT ACCEPTED
        );

        // Also trigger course access granted notification
        self::onAdminGrantsCourseAccess($user, $courseName);

        Log::info('Payment accepted notification sent', [
            'user_id' => $user->id,
            'amount' => $amount,
            'course' => $courseName
        ]);
    }

    public static function onPaymentSuccess(User $user, array $paymentData)
    {
        $amount = $paymentData['amount'] ?? 0;
        $courseName = $paymentData['course_name'] ?? 'Course';

        // 💙 BLUE BORDER: Payment success notification
        UniversalNotificationService::create(
            $user,
            'payment_success',
            'Payment Confirmation',
            "✅ Payment of \${$amount} for {$courseName} completed successfully. Welcome to your new course!",
            $paymentData,
            'blue' // 💙 BLUE BORDER FOR SUCCESS
        );

        Log::info('Payment success notification sent', [
            'user_id' => $user->id,
            'amount' => $amount,
            'course' => $courseName
        ]);
    }

    /**
     * NEW: Admin grants course access (separate from payment)
     */
    public static function onAdminGrantsCourseAccess(User $user, string $courseName, ?User $admin = null)
    {
        $adminName = $admin ? $admin->name : 'Administrator';
        
        UniversalNotificationService::create(
            $user,
            'course_access_granted',
            'Course Access Granted',
            "You now have access to {$courseName}. Start learning!",
            ['course' => $courseName, 'granted_by' => $adminName],
            'normal'
        );

        Log::info('Course access granted by admin', [
            'user_id' => $user->id,
            'course' => $courseName,
            'admin_id' => $admin?->id,
            'admin_name' => $adminName
        ]);
    }

    public static function onPaymentFailed(User $user, array $paymentData)
    {
        $courseName = $paymentData['course_name'] ?? 'Course';

        UniversalNotificationService::create(
            $user,
            'payment_failed',
            'Payment Failed',
            'Your payment could not be processed. Please try again.',
            $paymentData,
            'urgent'
        );

        UniversalNotificationService::create(
            $user,
            'course_access_denied',
            'Access Denied',
            "Payment required for {$courseName}",
            $paymentData,
            'normal'
        );

        Log::info('Payment failed notifications sent', [
            'user_id' => $user->id,
            'course' => $courseName
        ]);
    }

    // 🔥 COMPREHENSIVE STUDY STREAK SYSTEM WITH PERSONALIZED MILESTONES
    public static function onStudyStreak(User $user, int $streakDays)
    {
        $milestone = self::getStreakMilestone($streakDays);
        
        if ($milestone) {
            UniversalNotificationService::create(
                $user,
                $milestone['type'],
                $milestone['title'],
                $milestone['message'],
                ['streak_days' => $streakDays, 'milestone' => $milestone['name']],
                $milestone['priority']
            );

            Log::info('Study streak milestone notification sent', [
                'user_id' => $user->id,
                'streak_days' => $streakDays,
                'milestone' => $milestone['name']
            ]);
        }
    }

    /**
     * Get personalized streak milestone data for specific day counts
     */
    private static function getStreakMilestone(int $streakDays): ?array
    {
        $milestones = [
            1 => [
                'type' => 'streak_day_1',
                'name' => 'First Step',
                'title' => 'Hey, You Did It!',
                'message' => 'GREAT JOB! This is just the beginning!',
                'priority' => 'mastery'
            ],
            2 => [
                'type' => 'streak_day_2',
                'name' => 'Building Momentum',
                'title' => 'Day 2 - Look at You Go!',
                'message' => 'Two days! You are on a roll now. I can feel it already. Keep it up!',
                'priority' => 'high'
            ],
            3 => [
                'type' => 'streak_day_3',
                'name' => 'Habit Formation',
                'title' => 'Day 3 - Getting Stronger!',
                'message' => 'Three days! Your brain is getting used to this. It is like training - you get stronger every day.',
                'priority' => 'high'
            ],
            7 => [
                'type' => 'streak_week_1',
                'name' => 'First Week',
                'title' => 'One Week - You Rock!',
                'message' => 'A whole week! You showed up every day. That is what heroes do - they show up.',
                'priority' => 'high'
            ],
            10 => [
                'type' => 'streak_day_10',
                'name' => 'Double Digits',
                'title' => '10 Days - Double Digits!',
                'message' => 'Ten days! Double digits now! You are in the top 10% now. That is pretty cool.',
                'priority' => 'high'
            ],
            14 => [
                'type' => 'streak_week_2',
                'name' => 'Two Weeks',
                'title' => 'Two Weeks - Nice Work!',
                'message' => 'Two weeks! You are getting really good at this. Your brain loves this now.',
                'priority' => 'high'
            ],
            15 => [
                'type' => 'streak_day_15',
                'name' => 'Halfway to Month',
                'title' => '15 Days - Halfway There!',
                'message' => 'Fifteen days! You are halfway to a month. Your future self will thank you for this.',
                'priority' => 'high'
            ],
            21 => [
                'type' => 'streak_day_21',
                'name' => 'Habit Mastery',
                'title' => '21 Days - Habit Made!',
                'message' => 'Twenty-one days! They say it takes 21 days to make a habit. Well, you just did it!',
                'priority' => 'high'
            ],
            30 => [
                'type' => 'streak_month_1',
                'name' => 'First Month',
                'title' => 'One Month - Legend!',
                'message' => 'A whole month! You are in the top 1% now. That is pretty special, you know.',
                'priority' => 'high'
            ],
            60 => [
                'type' => 'streak_month_2',
                'name' => 'Two Months',
                'title' => 'Two Months - Wow!',
                'message' => 'Two months! You are not just learning anymore - you are becoming something great.',
                'priority' => 'high'
            ],
            90 => [
                'type' => 'streak_month_3',
                'name' => 'Three Months',
                'title' => 'Three Months - Amazing!',
                'message' => 'Three months! You have totally changed how you learn. You cannot be stopped now!',
                'priority' => 'high'
            ],
            120 => [
                'type' => 'streak_month_4',
                'name' => 'Four Months',
                'title' => 'Four Months - Unreal!',
                'message' => 'Four months! You have shown what is possible when you do not give up. You are great!',
                'priority' => 'high'
            ],
            150 => [
                'type' => 'streak_month_5',
                'name' => 'Five Months',
                'title' => 'Five Months - Legend!',
                'message' => 'Five months! You have reached legend status. Everyone around you is inspired by this!',
                'priority' => 'high'
            ],
            180 => [
                'type' => 'streak_month_6',
                'name' => 'Six Months',
                'title' => 'Six Months - Diamond!',
                'message' => 'Half a year! Your mind is like a diamond now - strong, bright, and super good.',
                'priority' => 'high'
            ],
            365 => [
                'type' => 'streak_year_1',
                'name' => 'One Year',
                'title' => 'ONE YEAR - HERO STATUS!',
                'message' => 'A FULL YEAR! Only 0.1% of people do this. You are now a learning hero!',
                'priority' => 'high'
            ]
        ];

        // Add monthly milestones for months 7-12
        for ($month = 7; $month <= 12; $month++) {
            $days = $month * 30;
            $milestones[$days] = [
                'type' => "streak_month_{$month}",
                'name' => ucfirst(self::numberToWord($month)) . ' Months',
                'title' => "{$month} Months - Elite!",
                'message' => "{$month} months! You are in the top group now. Your journey is so cool!",
                'priority' => 'high'
            ];
        }

        return $milestones[$streakDays] ?? null;
    }

    /**
     * Convert number to word for milestone names
     */
    private static function numberToWord(int $number): string
    {
        $words = [
            1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five',
            6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten',
            11 => 'eleven', 12 => 'twelve'
        ];
        
        return $words[$number] ?? (string)$number;
    }

    public static function onStreakReset(User $user)
    {
        UniversalNotificationService::create(
            $user,
            'streak_reset',
            'Streak Reset',
            'Your study streak was reset. Start again!',
            [],
            'normal'
        );

        Log::info('Streak reset notification sent', [
            'user_id' => $user->id
        ]);
    }

    // ⚠️ REVIEW & WARNING NOTIFICATIONS
    
    /**
     * FIXED: Simple reminder for mistakes that need review
     */
    public static function onMistakesNeedReview(User $user, int $mistakeCount, array $context = [])
    {
        $priority = $context['priority'] ?? ($mistakeCount >= 15 ? 'high' : 'normal');
        $cacheKey = "study_reminder:mistakes:{$user->id}";

        if (!self::allowReminder($cacheKey, $context['force'] ?? false)) {
            return;
        }

        UniversalNotificationService::create(
            $user,
            'review_mistakes',
            'Review Mistakes Reminder',
            "You have {$mistakeCount} mistakes to review. Take a few minutes to strengthen your knowledge!",
            array_merge([
                'mistake_count' => $mistakeCount,
                'action_url' => secure_url(route('review.index', [], false)),
            ], $context),
            $priority
        );

        Log::info('Review mistakes reminder sent', [
            'user_id' => $user->id,
            'mistake_count' => $mistakeCount,
            'priority' => $priority,
        ]);
    }

    /**
     * FIXED: Weak topic reminder based on profile progress table
     */
    public static function onWeakTopicReminder(User $user, array $weakTopics, array $context = [])
    {
        $focusTopic = $context['focus_topic'] ?? $weakTopics[0] ?? null;
        $topicNames = implode(', ', array_map(function ($topic) {
            return $topic['topic_name'] ?? $topic['name'] ?? 'Unknown Topic';
        }, $weakTopics));
        $topicCount = count($weakTopics);
        $priority = $context['priority'] ?? 'normal';
        $focusTopicId = $focusTopic['topic_id'] ?? $focusTopic['id'] ?? 'general';
        $cacheKey = "study_reminder:weak_topic:{$user->id}:{$focusTopicId}";

        if (!self::allowReminder($cacheKey, $context['force'] ?? false)) {
            return;
        }

        $title = $priority === 'urgent' ? 'Critical Weak Topic Alert' : 'Weak Topics Found';
        $focusTopicName = $focusTopic['topic_name'] ?? $focusTopic['name'] ?? 'this topic';
        $focusReasonText = $focusTopic['reason_text'] ?? 'This topic needs attention.';
        $message = $focusTopic
            ? "Focus on {$focusTopicName} next. {$focusReasonText}"
            : "You need work on: {$topicNames}. Time to practice these!";
        
        $primaryActionUrl = !empty($focusTopic['open_mistakes_count'])
            ? ($focusTopic['mistakes_url'] ?? null)
            : ($focusTopic['practice_url'] ?? null);

        UniversalNotificationService::create(
            $user,
            'weak_topic_identified',
            $title,
            $message,
            array_merge([
                'weak_topics' => $weakTopics,
                'topic_count' => $topicCount,
                'focus_topic' => $focusTopic,
                'action_url' => $primaryActionUrl ?? secure_url(route('review.index', [], false)),
                'secondary_action_url' => $focusTopic['mistakes_url'] ?? secure_url(route('review.index', [], false)),
            ], $context),
            $priority
        );

        Log::info('Weak topics reminder sent', [
            'user_id' => $user->id,
            'topic_count' => $topicCount,
            'topics' => $topicNames,
            'priority' => $priority,
        ]);
    }

    private static function allowReminder(string $cacheKey, bool $force = false): bool
    {
        if ($force) {
            Cache::put($cacheKey, true, now()->addDay());
            return true;
        }

        if (Cache::has($cacheKey)) {
            return false;
        }

        Cache::put($cacheKey, true, now()->addDay());

        return true;
    }

    /**
     * NEW: Skipped questions detection during learn mistakes session
     */
    public static function onSkippedQuestionsDetected(User $user, int $skippedCount, string $sessionType = 'learn_mistakes')
    {
        UniversalNotificationService::create(
            $user,
            'skipped_questions_detected',
            'Questions Skipped',
            "You skipped {$skippedCount} questions. Go back and try them when you are ready!",
            ['skipped_count' => $skippedCount, 'session_type' => $sessionType],
            'normal'
        );

        Log::info('Skipped questions notification sent', [
            'user_id' => $user->id,
            'skipped_count' => $skippedCount,
            'session_type' => $sessionType
        ]);
    }

    // 🎯 MILESTONE NOTIFICATIONS
    public static function onCorrectAnswersMilestone(User $user, int $correctAnswers)
    {
        if ($correctAnswers % 100 == 0) {
            UniversalNotificationService::create(
                $user,
                'correct_answers_milestone',
                "{$correctAnswers} Correct Answers!",
                "WOW! {$correctAnswers} correct answers! Your brain is on fire right now. This is some serious skill!",
                ['correct_answers' => $correctAnswers],
                'exotic' // NEW: Special priority for deep purple border
            );

            Log::info('Correct answers milestone notification sent', [
                'user_id' => $user->id,
                'correct_answers' => $correctAnswers
            ]);
        }
    }

    // 🔔 SYSTEM NOTIFICATIONS
    public static function onSystemUpdate(User $user, string $updateMessage)
    {
        UniversalNotificationService::create(
            $user,
            'system_update',
            'System Update',
            $updateMessage,
            [],
            'normal'
        );

        Log::info('System update notification sent', [
            'user_id' => $user->id,
            'message' => $updateMessage
        ]);
    }

    public static function onSuccess(User $user, string $message)
    {
        UniversalNotificationService::create(
            $user,
            'success',
            'Success',
            $message,
            [],
            'normal'
        );

        Log::info('Success notification sent', [
            'user_id' => $user->id,
            'message' => $message
        ]);
    }


}
