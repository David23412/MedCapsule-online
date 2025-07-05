<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class NotificationService
{
    /**
     * Create a new notification.
     */
    public function createNotification(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?array $data = null
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data ? json_encode($data) : null,
            'is_read' => false,
            'read_at' => null
        ]);
    }

    /**
     * Get unread notifications for a user.
     */
    public function getUnreadNotifications(User $user): Collection
    {
        return $user->notifications()
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all notifications for a user.
     */
    public function getAllNotifications(User $user, int $limit = 50): Collection
    {
        return $user->notifications()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(User $user): void
    {
        $user->notifications()
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);
    }

    /**
     * Create a level up notification.
     */
    public function createLevelUpNotification(User $user, int $newLevel, int $xpNeeded): Notification
    {
        // Check if we already sent a notification for this level
        $existingNotification = Notification::where('user_id', $user->id)
            ->where('type', 'level_up')
            ->whereRaw("JSON_EXTRACT(data, '$.new_level') = ?", [$newLevel])
            ->first();
            
        if ($existingNotification) {
            // Return the existing notification instead of creating a duplicate
            return $existingNotification;
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
    ): Notification {
        return $this->createNotification(
            $user->id,
            'topic_mastery',
            'Topic Mastered!',
            "You've mastered $topicName. Great work!",
            ['topic_name' => $topicName]
        );
    }

    /**
     * Create a milestone notification.
     */
    public function createMilestoneNotification(
        User $user,
        string $achievement,
        string $description
    ): Notification {
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
    ): Notification {
        $message = $mistakeCount === 1
            ? "You have 1 mistake that needs review. Let's clear it up!"
            : "You have $mistakeCount mistakes that need review. Let's clear them up!";

        return $this->createNotification(
            $user->id,
            'review_mistakes',
            'Review Your Mistakes',
            $message,
            ['mistake_count' => $mistakeCount]
        );
    }

    /**
     * Create a weak topics review reminder.
     */
    public function createWeakTopicsNotification(
        User $user,
        array $weakTopics
    ): Notification {
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
    ): Notification {
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
    ): Notification {
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
    public function createStudyStreakNotification(User $user, int $streakDays): Notification
    {
        // Create personalized titles and messages based on the streak day count
        $title = 'Study Streak!';
        $message = '';
        
        if ($streakDays == 1) {
            $message = "You've started a study streak! Come back tomorrow to keep it going!";
        } else if ($streakDays == 2) {
            $message = "Two days in a row! You're building momentum!";
        } else if ($streakDays == 3) {
            $message = "Three day streak! You're developing a great habit!";
        } else if ($streakDays >= 4 && $streakDays <= 6) {
            $message = "Impressive $streakDays day streak! You're making steady progress!";
        } else if ($streakDays == 7) {
            $title = "Week-long Streak!";
            $message = "Amazing! You've maintained your study streak for a full week!";
        } else if ($streakDays > 7 && $streakDays < 14) {
            $message = "$streakDays days and counting! Your consistency is paying off!";
        } else if ($streakDays == 14) {
            $title = "Two Week Streak!";
            $message = "Two full weeks of consistent studying! That's real dedication!";
        } else if ($streakDays > 14 && $streakDays < 30) {
            $message = "$streakDays day streak! You're becoming a study master!";
        } else if ($streakDays == 30) {
            $title = "Month-long Streak!";
            $message = "A full month of daily studying! Your commitment is incredible!";
        } else if ($streakDays > 30 && $streakDays < 60) {
            $message = "$streakDays days of consistent study! Keep up the excellent work!";
        } else if ($streakDays >= 60) {
            $title = "Legendary Streak!";
            $message = "$streakDays days of unbroken study! You're in the elite tier of dedicated learners!";
        }
        
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
    ): Notification {
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
    ): Notification {
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
    ): Notification {
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
    ): Notification {
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
    ): Notification {
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
    ): ?Notification {
        // Check if there's already a notification for this topic in the last 24 hours
        $existingNotification = Notification::where('user_id', $user->id)
            ->where('type', 'failed_topic')
            ->where('created_at', '>', now()->subHours(24))
            ->whereRaw("JSON_EXTRACT(data, '$.topic_id') = ?", [$topicId])
            ->first();
            
        if ($existingNotification) {
            // Don't create a duplicate notification
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
        
        return $notification;
    }

    /**
     * Create a notification for when a streak is reset.
     */
    public function createStreakResetNotification(User $user): Notification
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
     * Get notifications for the authenticated user with unread count
     *
     * @param int $limit Maximum number of notifications to return
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

        // Calculate unread count separately to ensure accuracy
        $unreadCount = $user->notifications()
            ->where('is_read', false)
            ->count();

        // Get notifications with prioritization of unread ones and ensure newest first
        $notifications = $user->notifications()
            ->select('*')
            ->orderByRaw('is_read ASC, created_at DESC') // Prioritize unread notifications and show newest first
            ->limit($limit)
            ->get();
            
        // Process notifications to add formatted timestamps and parse JSON data
        $notifications->transform(function ($notification) {
            // Parse JSON data if present
            if ($notification->data && is_string($notification->data)) {
                $notification->data = json_decode($notification->data);
            }
            
            // Add human-readable time
            $notification->time_ago = $this->formatTimeAgo($notification->created_at);
            
            return $notification;
        });

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
        
        return $user->notifications()
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]) > 0;
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
        
        // Only mark notifications that belong to the authenticated user
        $affected = Notification::where('user_id', $user->id)
            ->whereIn('id', $notificationIds)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);
            
        return $affected > 0;
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
            $userId = auth()->id();
            
            // Delete the notifications
            $count = Notification::whereIn('id', $notificationIds)
                ->where('user_id', $userId)
                ->delete();
                
            // Clear user notification cache
            $this->clearUserNotificationCache($userId);
            
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
    ): Notification {
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
} 