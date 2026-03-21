<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use App\Models\Payment;
use App\Notifications\PaymentRequiresAttention;
use Illuminate\Support\Facades\Notification as FacadeNotification;
use Illuminate\Support\Facades\Log;
use App\Services\UniversalNotificationService;

class NotificationService
{
    // Notification Types
    const ASSIGNMENT_COMPLETED = 'assignment_completed';
    const STUDENT_COMPLETED_ASSIGNMENT = 'student_completed_assignment';
    const ASSIGNMENT_OVERDUE = 'assignment_overdue';
    const ASSIGNMENT_OVERDUE_TUTOR = 'assignment_overdue_tutor';
    const ASSIGNMENT_REMINDER = 'assignment_reminder';

    /**
     * Create a new notification.
     */
    public function createNotification(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?array $data = null
    ): array {
        // Use UniversalNotificationService instead of database
        return UniversalNotificationService::create(
            User::find($userId),
            $type,
            $title,
            $message,
            $data ?? []
        );
    }

    /**
     * Get unread notifications for a user.
     */
    public function getUnreadNotifications(User $user): Collection
    {
        return UniversalNotificationService::getNotifications($user)
            ->where('read', false);
    }

    /**
     * Get all notifications for a user.
     */
    public function getAllNotifications(User $user, int $limit = 50): Collection
    {
        return UniversalNotificationService::getNotifications($user, $limit);
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(User $user): void
    {
        UniversalNotificationService::markAllAsRead($user);
    }

    /**
     * Create assignment-related notifications
     */
    public function createAssignmentNotification(
        int $userId,
        string $type,
        string $title,
        string $message,
        array $assignmentData = []
    ): array {
        return $this->createNotification(
            $userId,
            $type,
            $title,
            $message,
            array_merge(['category' => 'assignment'], $assignmentData)
        );
    }

    /**
     * Notify student about new assignment
     */
    public function notifyAssignmentCreated(\App\Models\Assignment $assignment): array
    {
        return $this->createAssignmentNotification(
            $assignment->user_id,
            'assignment_created',
            'New Assignment: ' . $assignment->title,
            "You have a new assignment for {$assignment->topic->name}. Due: {$assignment->deadline->format('M j, Y g:i A')}",
            [
                'assignment_id' => $assignment->id,
                'topic_name' => $assignment->topic->name,
                'course_name' => $assignment->course->name,
                'deadline' => $assignment->deadline->toISOString()
            ]
        );
    }

    /**
     * Notify student about deadline change
     */
    public function notifyDeadlineChanged(\App\Models\Assignment $assignment, \Carbon\Carbon $oldDeadline): array
    {
        return $this->createAssignmentNotification(
            $assignment->user_id,
            'assignment_deadline_changed',
            'Assignment Deadline Updated',
            "The deadline for '{$assignment->title}' has been changed from {$oldDeadline->format('M j, Y g:i A')} to {$assignment->deadline->format('M j, Y g:i A')}",
            [
                'assignment_id' => $assignment->id,
                'old_deadline' => $oldDeadline->toISOString(),
                'new_deadline' => $assignment->deadline->toISOString()
            ]
        );
    }

    /**
     * Notify about assignment completion
     */
    public function notifyAssignmentCompleted(\App\Models\Assignment $assignment, User $user, $attempt): array
    {
        $notifications = [];

        // Notify student
        $notifications['student'] = $this->createAssignmentNotification(
            $user->id,
            self::ASSIGNMENT_COMPLETED,
            'Assignment Completed!',
            "Great job! You've completed '{$assignment->title}'" . ($attempt->percentage_grade ? " with a score of {$attempt->percentage_grade}%" : ""),
            [
                'assignment_id' => $assignment->id,
                'score' => $attempt->percentage_grade,
                'completed_at' => $attempt->completed_at->toISOString()
            ]
        );

        // Notify tutor if one is assigned to the course
        if ($assignment->course && $assignment->course->tutor) {
            $notifications['tutor'] = $this->createAssignmentNotification(
                $assignment->course->tutor->id,
                self::STUDENT_COMPLETED_ASSIGNMENT,
                'Student Completed Assignment',
                "{$user->name} has completed '{$assignment->title}'" . ($attempt->percentage_grade ? " with a score of {$attempt->percentage_grade}%" : ""),
                [
                    'assignment_id' => $assignment->id,
                    'student_name' => $user->name,
                    'score' => $attempt->percentage_grade,
                    'completed_at' => $attempt->completed_at->toISOString(),
                    'action_url' => route('assignments.show', $assignment->id)
                ]
            );
        }

        return $notifications;
    }

    /**
     * Send deadline reminder notifications
     */
    public function sendDeadlineReminders(): int
    {
        $sentCount = 0;
        
        // Get assignments due within 24 hours that haven't been reminded
        $allAssignments = \App\Models\Assignment::with(['assignedUsers', 'topic', 'course'])
            ->where('deadline', '<=', now()->addHours(24))
            ->where('deadline', '>', now())
            ->whereDoesntHave('notifications', function($query) {
                $query->where('type', 'assignment_reminder')
                      ->where('created_at', '>=', now()->subHours(12));
            })
            ->get();
            
        foreach ($allAssignments as $assignment) {
            // Send notification to each assigned user if their status is active
            foreach ($assignment->assignedUsers as $user) {
                if ($assignment->getStatusForUser($user) !== \App\Models\Assignment::STATUS_ACTIVE) {
                    continue;
                }

                try {
                    $this->createAssignmentNotification(
                        $user->id,
                        self::ASSIGNMENT_REMINDER,
                        'Assignment Due Soon!',
                        "Reminder: '{$assignment->title}' is due {$assignment->deadline->diffForHumans()}",
                        [
                            'assignment_id' => $assignment->id,
                            'deadline' => $assignment->deadline->toISOString(),
                            'time_remaining' => $assignment->getTimeRemaining(),
                            'action_url' => route('student.assignments.show', $assignment->id)
                        ]
                    );
                    $sentCount++;
                } catch (\Exception $e) {
                    Log::error('Failed to send assignment reminder', [
                        'assignment_id' => $assignment->id,
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return $sentCount;
    }

    /**
     * Send overdue assignment notifications
     */
    public function sendOverdueNotifications(): int
    {
        $sentCount = 0;

        $assignments = \App\Models\Assignment::where('deadline', '<', now())
            ->where('deadline', '>', now()->subDays(7)) // Only check for recently overdue
            ->with(['assignedUsers', 'course.tutor'])
            ->get();

        foreach ($assignments as $assignment) {
            foreach ($assignment->assignedUsers as $user) {
                // Check if the assignment is locked because it's overdue for this specific user
                $isOverdue = $assignment->getStatusForUser($user) === \App\Models\Assignment::STATUS_LOCKED && 
                             $assignment->getLockReasonForUser($user) === 'overdue';

                if ($isOverdue) {
                    // Check if a notification was sent recently using the UniversalNotificationService's logic
                    $notifications = UniversalNotificationService::getNotifications($user, 100); // Check recent notifications
                    $recentNotification = $notifications->first(function ($notification) use ($assignment) {
                        return $notification['type'] === self::ASSIGNMENT_OVERDUE &&
                               isset($notification['data']['assignment_id']) &&
                               $notification['data']['assignment_id'] === $assignment->id &&
                               Carbon::parse($notification['timestamp'])->gte(now()->subHours(24));
                    });

                    if (!$recentNotification) {
                        $this->createAssignmentNotification(
                            $user->id,
                            self::ASSIGNMENT_OVERDUE,
                            'Assignment Overdue',
                            "'{$assignment->title}' is now overdue. Please complete it as soon as possible or request an extension.",
                            [
                                'assignment_id' => $assignment->id,
                                'overdue_by' => $assignment->deadline->diffForHumans(),
                                'action_url' => route('student.assignments.show', $assignment->id)
                            ]
                        );
                        $sentCount++;
                    }
                }
            }
        }

        return $sentCount;
    }

    /**
     * Create a level up notification.
     */
    public function createLevelUpNotification(User $user, int $newLevel, int $xpNeeded): array
    {
        // Lightweight duplicate guard for same level notification.
        $dedupeKey = "level_up_notif_{$user->id}_{$newLevel}";
        $cacheMiss = new \stdClass();
        $cachedNotification = Cache::get($dedupeKey, $cacheMiss);
        if ($cachedNotification !== $cacheMiss) {
            return $cachedNotification;
        }
        
        $notification = $this->createNotification(
            $user->id,
            'level_up',
            'Level Up!',
            "Congratulations! You've reached level $newLevel",
            [
                'new_level' => $newLevel,
                'xp_needed_for_next' => $xpNeeded,
                'total_xp' => $user->calculateXP(),
                'correct_answers' => $user->correct_answers_count,
                'accuracy' => $user->total_questions_attempted > 0 
                    ? round(($user->correct_answers_count / $user->total_questions_attempted) * 100, 1)
                    : 0
            ]
        );

        Cache::put($dedupeKey, $notification, now()->addDays(30));
        
        // Clear notification cache to ensure immediate visibility
        $this->clearUserNotificationCache($user->id);
        
        return $notification;
    }

    /**
     * Create a topic mastery notification.
     */
    public function createTopicMasteryNotification(
        User $user,
        string $topicName
    ): array {
        $notification = $this->createNotification(
            $user->id,
            'topic_mastery',
            'Quiz Mastered!',
            "You've mastered the quiz for $topicName. Keep practicing with all sources to achieve full topic mastery!",
            ['topic_name' => $topicName]
        );
        
        return $notification;
    }

    /**
     * Create a milestone notification.
     */
    public function createMilestoneNotification(
        User $user,
        string $achievement,
        string $description
    ): array {
        return $this->createNotification(
            $user->id,
            'milestone',
            $achievement,
            $description,
            ['achievement' => $achievement]
        );
    }

    /**
     * Create a review reminder notification for mistakes.
     */
    public function createReviewMistakesNotification(
        User $user,
        int $mistakeCount
    ): array {
        $message = $mistakeCount === 1
            ? "You have 1 mistake that needs review. Let's clear it up!"
            : "You have $mistakeCount mistakes that need review. Let's clear them up!";

        $notification = $this->createNotification(
            $user->id,
            'review_mistakes',
            'Review Your Mistakes',
            $message,
            ['mistake_count' => $mistakeCount]
        );
        
        return $notification;
    }

    /**
     * Create a weak topics review reminder.
     */
    public function createWeakTopicsNotification(
        User $user,
        array $weakTopics
    ): array {
        $topicCount = count($weakTopics);
        $message = $topicCount === 1
            ? "You should review {$weakTopics[0]} to improve your performance."
            : "You have $topicCount weak topics that need attention. Focus on improving these areas!";

        return $this->createNotification(
            $user->id,
            'weak_topics',
            'Weak Topics Identified',
            $message,
            ['topics' => $weakTopics]
        );
    }

    /**
     * Create a leaderboard rank notification.
     */
    public function createLeaderboardRankNotification(
        User $user,
        int $oldRank,
        int $newRank
    ): array {
        $message = $newRank < $oldRank
            ? "You moved up from rank #$oldRank to #$newRank on the leaderboard! Keep it up!"
            : "Your leaderboard position has changed from #$oldRank to #$newRank.";

        return $this->createNotification(
            $user->id,
            'leaderboard_rank',
            'Leaderboard Update',
            $message,
            [
                'old_rank' => $oldRank,
                'new_rank' => $newRank
            ]
        );
    }

    /**
     * Create a performance notification.
     */
    public function createPerformanceNotification(
        User $user,
        float $accuracy,
        string $timeframe = 'week'
    ): array {
        $message = $accuracy >= 80
            ? "Your accuracy this $timeframe was $accuracy%! Excellent performance!"
            : "Your accuracy this $timeframe was $accuracy%. Keep practicing to improve!";

        return $this->createNotification(
            $user->id,
            'performance',
            'Performance Update',
            $message,
            [
                'accuracy' => $accuracy,
                'timeframe' => $timeframe
            ]
        );
    }

    /**
     * Create a study streak notification.
     */
    public function createStudyStreakNotification(User $user, int $streakDays): array
    {
        $title = "{$streakDays}-Day Study Streak!";
        $message = "Congratulations! You've maintained a {$streakDays}-day study streak. Keep up the excellent work!";
        
        $notification = $this->createNotification(
            $user->id,
            'study_streak',
            $title,
            $message,
            ['streak_days' => $streakDays]
        );
        
        // Clear notification cache to ensure immediate visibility
        $this->clearUserNotificationCache($user->id);
        
        return $notification;
    }

    /**
     * Create a review reminder notification.
     */
    public function createReviewReminderNotification(
        User $user,
        array $topicsToReview
    ): array {
        $topicCount = count($topicsToReview);
        $message = $topicCount === 1
            ? "Time to review {$topicsToReview[0]}! Keep your knowledge fresh."
            : "You have $topicCount topics that need review. Don't let your knowledge fade!";

        return $this->createNotification(
            $user->id,
            'review_reminder',
            'Review Reminder',
            $message,
            ['topics' => $topicsToReview]
        );
    }

    /**
     * Create a course progress notification.
     */
    public function createCourseProgressNotification(
        User $user,
        string $courseName,
        float $progressPercentage
    ): array {
        return $this->createNotification(
            $user->id,
            'course_progress',
            'Course Progress Update',
            "You're making great progress in $courseName! You're $progressPercentage% through the course.",
            [
                'course_name' => $courseName,
                'progress_percentage' => $progressPercentage
            ]
        );
    }

    /**
     * Create a welcome notification when a user enrolls in a course.
     */
    public function createCourseWelcomeNotification(
        User $user,
        string $courseName
    ): array {
        // Also create universal notification
        UniversalNotificationService::createCourseWelcomeNotification($user, $courseName);
        
        $notification = $this->createNotification(
            $user->id,
            'course_welcome',
            "Welcome to $courseName!",
            "You've been enrolled in $courseName. Start your journey now and explore the materials!",
            [
                'course_name' => $courseName
            ]
        );
        
        // Clear notification cache to ensure immediate visibility
        $this->clearUserNotificationCache($user->id);
        
        return $notification;
    }

    /**
     * Create a milestone achievement notification for answering 100 questions correctly.
     */
    public function createCorrectAnswersMilestoneNotification(
        User $user,
        int $correctAnswersCount
    ): array {
        // Format the message based on the milestone
        $milestone = floor($correctAnswersCount / 100) * 100;
        
        $notification = $this->createNotification(
            $user->id,
            'correct_answers_milestone',
            "You've reached $milestone correct answers!",
            "Congratulations! You've correctly answered $milestone questions. Your knowledge is growing stronger!",
            [
                'milestone' => $milestone,
                'correct_answers_count' => $correctAnswersCount
            ]
        );
        
        // Clear notification cache to ensure immediate visibility
        $this->clearUserNotificationCache($user->id);
        
        return $notification;
    }

    /**
     * Create a notification for when a weak topic is identified and added to review.
     */
    public function createWeakTopicAddedNotification(
        User $user,
        string $topicName
    ): array {
        $notification = $this->createNotification(
            $user->id,
            'weak_topic_added',
            'Topic Needs Review',
            "We've identified '$topicName' as a topic that needs your attention. It's been added to your review list.",
            [
                'topic_name' => $topicName
            ]
        );
        
        // Clear notification cache to ensure immediate visibility
        $this->clearUserNotificationCache($user->id);
        
        return $notification;
    }

    /**
     * Create a notification for a failed topic, with duplication prevention
     * 
     * @param User $user
     * @param string $topicName
     * @param int $topicId
     * @param float $score
     * @return Notification|null Returns null if duplicate
     */
    public function createFailedTopicNotification(
        User $user, 
        string $topicName, 
        int $topicId, 
        float $score
    ): ?array {
        $dedupeKey = "failed_topic_notif_{$user->id}_{$topicId}";
        $cacheMiss = new \stdClass();
        if (Cache::get($dedupeKey, $cacheMiss) !== $cacheMiss) {
            return null;
        }
        
        // Format the score as a percentage
        $scorePercentage = number_format($score, 0);
        
        $notification = $this->createNotification(
            $user->id,
            'failed_topic',
            'Topic Needs Review',
            "You scored $scorePercentage% on '$topicName'. Visit your profile to review this topic.",
            [
                'topic_id' => $topicId,
                'topic_name' => $topicName,
                'score' => $score
            ]
        );
        
        // Clear notification cache to ensure immediate visibility
        $this->clearUserNotificationCache($user->id);

        Cache::put($dedupeKey, true, now()->addHours(24));
        
        return $notification;
    }

    /**
     * Create a notification for when a streak is reset.
     */
    public function createStreakResetNotification(User $user): array
    {
        $notification = $this->createNotification(
            $user->id,
            'streak_reset',
            'Streak Reset',
            "Your study streak has been reset. Don't worry, today is a new opportunity to begin again!",
            ['previous_streak' => $user->study_streak_days]
        );
        
        // Clear notification cache to ensure immediate visibility
        $this->clearUserNotificationCache($user->id);
        
        return $notification;
    }

    /**
     * Helper method to clear notification cache for a user
     */
    private function clearUserNotificationCache(int $userId): void
    {
        // Clear any existing cache keys for this user's notifications
        Cache::forget("user_notifications_{$userId}_20");
        Cache::forget("user_notifications_{$userId}_50");
    }

    /**
     * Get user notifications with optimized single query
     * OPTIMIZED: Reduced from 2 queries to 1 query using subquery
     *
     * @param int $limit
     * @return array
     */
    public function getUserNotifications(int $limit = 20): array
    {
        $user = Auth::user();
        if (!$user) {
            return [
                'notifications' => collect(),
                'unread_count' => 0
            ];
        }

        $notifications = UniversalNotificationService::getNotifications($user, $limit)
            ->map(function ($notification) {
                $createdAt = $notification['created_at'] ?? $notification['timestamp'] ?? now();
                $notification['time_ago'] = $this->formatTimeAgo($createdAt);
                return $notification;
            });

        $unreadCount = $notifications->where('read', false)->count();

        return [
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ];
    }
    
    /**
     * Format a timestamp into a human-readable "time ago" string
     *
     * @param string|\Carbon\Carbon $timestamp
     * @return string
     */
    protected function formatTimeAgo($timestamp): string
    {
        if (!$timestamp instanceof Carbon) {
            $timestamp = Carbon::parse($timestamp);
        }
        
        $now = Carbon::now();
        $diff = $timestamp->diffInSeconds($now);
        
        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes == 1 ? '1 minute ago' : "$minutes minutes ago";
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours == 1 ? '1 hour ago' : "$hours hours ago";
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days == 1 ? 'Yesterday' : "$days days ago";
        } else {
            return $timestamp->format('M j');
        }
    }

    /**
     * Mark all notifications as read for the authenticated user
     *
     * @return bool
     */
    public function markAllAsReadForCurrentUser(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return UniversalNotificationService::markAllAsRead($user);
    }

    /**
     * Mark specific notifications as read
     *
     * @param array $notificationIds
     * @return bool
     */
    public function markAsRead(array $notificationIds): bool
    {
        if (empty($notificationIds)) {
            return false;
        }
        
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        $affected = false;
        foreach ($notificationIds as $notificationId) {
            $affected = UniversalNotificationService::markAsRead($user, (string) $notificationId) || $affected;
        }

        return $affected;
    }

    /**
     * Delete notifications by IDs.
     * 
     * @param array $notificationIds
     * @return bool
     */
    public function deleteNotifications(array $notificationIds): bool
    {
        try {
            // Make sure the user can only delete their own notifications
            $user = auth()->user();
            if (!$user) {
                return false;
            }

            $count = 0;
            foreach ($notificationIds as $notificationId) {
                if (UniversalNotificationService::deleteNotification($user, (string) $notificationId)) {
                    $count++;
                }
            }
                
            // Clear user notification cache
            $this->clearUserNotificationCache($user->id);
            
            return $count > 0;
        } catch (\Exception $e) {
            // Log error if needed
            return false;
        }
    }

    /**
     * Create a notification for successful payment.
     *
     * @param User $user
     * @param string $courseName
     * @param float $amount
     * @return Notification
     */
    public function createPaymentSuccessNotification(
        User $user,
        string $courseName,
        float $amount
    ): array {
        $notification = $this->createNotification(
            $user->id,
            'payment_success',
            'Payment Successful',
            "Your payment of {$amount} EGP for {$courseName} has been processed successfully. You now have access to the course.",
            [
                'course_name' => $courseName,
                'amount' => $amount
            ]
        );
        
        // Clear notification cache to ensure immediate visibility
        $this->clearUserNotificationCache($user->id);
        
        return $notification;
    }

    /**
     * Send a notification to all admins about a payment that requires attention.
     *
     * @param Payment $payment
     * @param string $reason
     * @param string $priority
     * @param array $data
     * @return bool
     */
    public function notifyAdminsAboutPayment(Payment $payment, string $reason, string $priority = 'medium', array $data = [])
    {
        try {
            // Get all admin users
            $admins = User::query()
                ->where('role', User::ROLE_ADMIN)
                ->get();
            
            if ($admins->isEmpty()) {
                Log::warning('No admin users found to notify about payment', [
                    'payment_id' => $payment->id,
                    'reason' => $reason
                ]);
                return false;
            }
            
            // Create notification
            $notification = new PaymentRequiresAttention($payment, $reason, $priority, $data);
            
            // Send notification to all admins
            FacadeNotification::send($admins, $notification);
            
            Log::info('Payment notification sent to admins', [
                'payment_id' => $payment->id,
                'reason' => $reason,
                'admin_count' => $admins->count(),
                'priority' => $priority
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to notify admins about payment', [
                'payment_id' => $payment->id,
                'reason' => $reason,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
    
    /**
     * Notify admins about a payment that requires manual verification.
     *
     * @param Payment $payment
     * @param float $confidenceScore
     * @return bool
     */
    public function notifyAboutPaymentVerification(Payment $payment, float $confidenceScore = 0)
    {
        $priority = 'medium';
        
        // Determine priority based on confidence score
        if ($confidenceScore >= 40 && $confidenceScore < 70) {
            $priority = 'medium';
            $reason = 'Payment requires verification (medium confidence: ' . round($confidenceScore) . '%)';
        } else {
            $priority = 'high';
            $reason = 'Payment requires verification (low confidence: ' . round($confidenceScore) . '%)';
        }
        
        $data = [
            'confidence_score' => $confidenceScore,
            'transaction_id' => $payment->payment_data['transaction_id'] ?? null,
            'payment_method' => $payment->payment_data['payment_method_used'] ?? null,
            'has_proof' => isset($payment->payment_data['proof_path'])
        ];
        
        return $this->notifyAdminsAboutPayment($payment, $reason, $priority, $data);
    }
    
    /**
     * Notify admins about payments with exceeded retry attempts.
     *
     * @param Payment $payment
     * @param int $attempts
     * @param string $lastError
     * @return bool
     */
    public function notifyAboutExceededRetryAttempts(Payment $payment, int $attempts, string $lastError)
    {
        $reason = "Payment processing failed after {$attempts} attempts";
        $data = [
            'attempts' => $attempts,
            'last_error' => $lastError,
            'transaction_id' => $payment->payment_data['transaction_id'] ?? null
        ];
        
        return $this->notifyAdminsAboutPayment($payment, $reason, 'high', $data);
    }
    
    /**
     * Create a daily digest notification for pending payments.
     *
     * @param int $pendingCount
     * @param int $highPriorityCount
     * @return bool
     */
    public function createDailyPaymentDigest(int $pendingCount, int $highPriorityCount)
    {
        if ($pendingCount === 0) {
            return true; // No notifications needed
        }
        
        try {
            // Get all admin users
            $admins = User::query()
                ->where('role', User::ROLE_ADMIN)
                ->get();
            
            if ($admins->isEmpty()) {
                Log::warning('No admin users found for daily payment digest');
                return false;
            }
            
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\DailyPaymentDigest($pendingCount, $highPriorityCount));
            }
            
            Log::info('Daily payment digest sent to admins', [
                'pending_count' => $pendingCount,
                'high_priority_count' => $highPriorityCount,
                'admin_count' => $admins->count()
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send daily payment digest', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
    
    /**
     * Mark notifications related to a payment as read.
     *
     * @param Payment $payment
     * @param User $admin
     * @return int Number of notifications marked as read
     */
    public function markPaymentNotificationsAsRead(Payment $payment, User $admin)
    {
        try {
            if (!Schema::hasTable('notifications')) {
                return 0;
            }

            $count = 0;
            
            // Find all unread notifications for this admin related to this payment
            $notifications = $admin->unreadNotifications()
                ->where('type', PaymentRequiresAttention::class)
                ->where('data->payment_id', $payment->id)
                ->get();
            
            // Mark each notification as read
            foreach ($notifications as $notification) {
                $notification->markAsRead();
                $count++;
            }
            
            return $count;
        } catch (\Exception $e) {
            Log::error('Failed to mark payment notifications as read', [
                'payment_id' => $payment->id,
                'admin_id' => $admin->id,
                'error' => $e->getMessage()
            ]);
            
            return 0;
        }
    }
}
