<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserActivityLogService
{
    /**
     * Log a user activity
     */
    public function log(string $action, array $data = [], ?User $user = null): UserActivityLog
    {
        $user = $user ?? Auth::user();
        
        if (!$user) {
            // Create a log entry without user_id for anonymous users
            return UserActivityLog::create([
                'action' => $action,
                'data' => $data,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        return UserActivityLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log a page view
     */
    public function logPageView(string $page, array $data = [], ?User $user = null): UserActivityLog
    {
        return $this->log('page_view', array_merge($data, ['page' => $page]), $user);
    }

    /**
     * Log a quiz attempt
     */
    public function logQuizAttempt(array $data, ?User $user = null): UserActivityLog
    {
        return $this->log('quiz_attempt', $data, $user);
    }

    /**
     * Log a course enrollment
     */
    public function logCourseEnrollment(int $courseId, array $data = [], ?User $user = null): UserActivityLog
    {
        return $this->log('course_enroll', array_merge($data, ['course_id' => $courseId]), $user);
    }

    /**
     * Log a course view
     */
    public function logCourseView(int $courseId, array $data = [], ?User $user = null): UserActivityLog
    {
        return $this->log('course_view', array_merge($data, ['course_id' => $courseId]), $user);
    }

    /**
     * Log a topic view
     */
    public function logTopicView(int $topicId, array $data = [], ?User $user = null): UserActivityLog
    {
        return $this->log('topic_view', array_merge($data, ['topic_id' => $topicId]), $user);
    }

    /**
     * Log a payment action
     */
    public function logPaymentAction(string $action, int $paymentId, array $data = [], ?User $user = null): UserActivityLog
    {
        return $this->log('payment_' . $action, array_merge($data, ['payment_id' => $paymentId]), $user);
    }

    /**
     * Get recent activity for a user
     */
    public function getRecentActivity(int $userId, int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return UserActivityLog::where('user_id', $userId)
            ->recent($days)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get activity by action type
     */
    public function getActivityByAction(int $userId, string $action, int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return UserActivityLog::where('user_id', $userId)
            ->byAction($action)
            ->recent($days)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get page views for a user
     */
    public function getPageViews(int $userId, int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return UserActivityLog::where('user_id', $userId)
            ->pageViews()
            ->recent($days)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get quiz attempts for a user
     */
    public function getQuizAttempts(int $userId, int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return UserActivityLog::where('user_id', $userId)
            ->quizAttempts()
            ->recent($days)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get course enrollments for a user
     */
    public function getCourseEnrollments(int $userId, int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return UserActivityLog::where('user_id', $userId)
            ->courseEnrollments()
            ->recent($days)
            ->orderBy('created_at', 'desc')
            ->get();
    }
} 