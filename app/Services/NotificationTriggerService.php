<?php

namespace App\Services;

use App\Models\User;
use App\Models\Assignment;
use Illuminate\Support\Facades\Log;

class NotificationTriggerService
{
    /**
     * CRITICAL NOTIFICATION SCENARIOS - COMPREHENSIVE INTEGRATION
     */

    // 🎓 ACADEMIC & LEARNING NOTIFICATIONS
    public static function onQuizCompleted(User $user, array $quizData)
    {
        $score = $quizData['score'] ?? 0;
        $totalQuestions = $quizData['total_questions'] ?? 1;
        $percentage = round(($score / $totalQuestions) * 100, 1);
        $courseName = $quizData['course_name'] ?? 'Quiz';

        // Quiz completed notification
        UniversalNotificationService::create(
            $user,
            'quiz_completed',
            'Quiz Completed',
            "You scored {$percentage}% on {$courseName}",
            $quizData,
            'normal'
        );

        // Perfect score notification
        if ($percentage == 100) {
            UniversalNotificationService::create(
                $user,
                'perfect_score',
                'Perfect Score!',
                "Amazing! You got 100% on {$courseName}",
                $quizData,
                'high'
            );
        }

        // First attempt notification (if it's their first quiz ever)
        $userQuizCount = $quizData['user_quiz_count'] ?? 0;
        if ($userQuizCount == 1) {
            UniversalNotificationService::create(
                $user,
                'first_attempt',
                'First Quiz Complete!',
                'Great job on your first quiz attempt',
                $quizData,
                'normal'
            );
        }

        Log::info('Quiz completion notifications sent', [
            'user_id' => $user->id,
            'score' => $percentage,
            'course' => $courseName
        ]);
    }

    // 📚 COURSE ENROLLMENT NOTIFICATIONS
    public static function onCourseEnrollment(User $user, string $courseName, string $courseType = 'general')
    {
        // MedCapsule enrollment (special case)
        if (strtolower($courseName) === 'medcapsule' || $courseType === 'medcapsule') {
            UniversalNotificationService::create(
                $user,
                'medcapsule_enrolled',
                'Welcome to MedCapsule!',
                'You are now enrolled in the MedCapsule learning platform',
                ['course' => $courseName],
                'high'
            );
        } else {
            // Course-specific enrollment
            $notificationType = strtolower($courseName) . '_enrolled';
            UniversalNotificationService::create(
                $user,
                $notificationType,
                ucfirst($courseName) . ' Course',
                "You have enrolled in the {$courseName} course",
                ['course' => $courseName],
                'normal'
            );
        }

        // Course welcome notification
        UniversalNotificationService::create(
            $user,
            'course_welcome',
            'Course Welcome',
            "Welcome to {$courseName}! Start your learning journey",
            ['course' => $courseName],
            'normal'
        );

        Log::info('Course enrollment notifications sent', [
            'user_id' => $user->id,
            'course' => $courseName
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

    // 📝 ASSIGNMENT NOTIFICATIONS
    public static function onAssignmentCreated(User $user, Assignment $assignment)
    {
        UniversalNotificationService::create(
            $user,
            'assignment_created',
            'New Assignment',
            "New assignment: {$assignment->title}",
            ['assignment_id' => $assignment->id],
            'normal'
        );

        Log::info('Assignment created notification sent', [
            'user_id' => $user->id,
            'assignment_id' => $assignment->id
        ]);
    }

    public static function onAssignmentDue(User $user, Assignment $assignment, int $hoursUntilDue)
    {
        $urgency = $hoursUntilDue <= 2 ? 'urgent' : ($hoursUntilDue <= 24 ? 'high' : 'normal');
        
        UniversalNotificationService::create(
            $user,
            'assignment_due',
            'Assignment Due',
            "Your {$assignment->title} assignment is due in {$hoursUntilDue} hours",
            ['assignment_id' => $assignment->id, 'hours_until_due' => $hoursUntilDue],
            $urgency
        );

        Log::info('Assignment due notification sent', [
            'user_id' => $user->id,
            'assignment_id' => $assignment->id,
            'hours_until_due' => $hoursUntilDue
        ]);
    }

    // 💳 PAYMENT NOTIFICATIONS
    public static function onPaymentSuccess(User $user, array $paymentData)
    {
        $amount = $paymentData['amount'] ?? 0;
        $courseName = $paymentData['course_name'] ?? 'Course';

        UniversalNotificationService::create(
            $user,
            'payment_success',
            'Payment Successful',
            "Payment of \${$amount} for {$courseName} was successful",
            $paymentData,
            'high'
        );

        // Course access granted
        UniversalNotificationService::create(
            $user,
            'course_access_granted',
            'Access Granted',
            "You now have access to {$courseName}",
            $paymentData,
            'normal'
        );

        Log::info('Payment success notifications sent', [
            'user_id' => $user->id,
            'amount' => $amount,
            'course' => $courseName
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
            'high'
        );

        Log::info('Payment failed notifications sent', [
            'user_id' => $user->id,
            'course' => $courseName
        ]);
    }

    // 🔥 STREAK & MILESTONE NOTIFICATIONS
    public static function onStudyStreak(User $user, int $streakDays)
    {
        if ($streakDays == 7) {
            UniversalNotificationService::create(
                $user,
                'study_streak',
                '7-Day Study Streak!',
                'Amazing! You maintained a 7-day streak',
                ['streak_days' => $streakDays],
                'high'
            );
        } elseif ($streakDays % 30 == 0) {
            UniversalNotificationService::create(
                $user,
                'streak_milestone',
                'Streak Milestone!',
                "{$streakDays}-day study streak achieved!",
                ['streak_days' => $streakDays],
                'high'
            );
        }

        Log::info('Study streak notification sent', [
            'user_id' => $user->id,
            'streak_days' => $streakDays
        ]);
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
    public static function onMistakesNeedReview(User $user, int $mistakeCount)
    {
        UniversalNotificationService::create(
            $user,
            'review_mistakes',
            'Review Mistakes',
            "You have {$mistakeCount} topics that need review",
            ['mistake_count' => $mistakeCount],
            'normal'
        );

        Log::info('Review mistakes notification sent', [
            'user_id' => $user->id,
            'mistake_count' => $mistakeCount
        ]);
    }

    public static function onWeakTopicIdentified(User $user, string $topicName)
    {
        UniversalNotificationService::create(
            $user,
            'weak_topic_identified',
            'Weak Topic',
            "{$topicName} needs more practice",
            ['topic' => $topicName],
            'normal'
        );

        Log::info('Weak topic notification sent', [
            'user_id' => $user->id,
            'topic' => $topicName
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
                'Incredible milestone reached',
                ['correct_answers' => $correctAnswers],
                'high'
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