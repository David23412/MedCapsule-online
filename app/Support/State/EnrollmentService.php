<?php

namespace App\Support\State;

use App\Models\User;
use App\Models\Course;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Single Source of Truth for Course Enrollment State
 * 
 * Replaces split brain logic between:
 * - course_user.is_enrolled (boolean)
 * - course_user.enrolled_at (datetime) 
 * - payments.status = 'completed' (string)
 * - payments.paid_at (datetime)
 */
class EnrollmentService
{
    const CACHE_TTL = 300; // 5 minutes

    /**
     * Get definitive enrollment status for a user and course
     * 
     * @param int $userId
     * @param int $courseId
     * @return array ['enrolled' => bool, 'enrolled_at' => ?Carbon, 'method' => string]
     */
    public function getEnrollmentStatus(int $userId, int $courseId): array
    {
        \Log::debug('SSOT EnrollmentService::getEnrollmentStatus called', [
            'user_id' => $userId,
            'course_id' => $courseId,
            'called_at' => now()
        ]);
        
        $cacheKey = "enrollment_status_{$userId}_{$courseId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $courseId) {
            // Priority 1: Check completed payment (highest authority)
            $payment = Payment::where('user_id', $userId)
                ->where('course_id', $courseId)
                ->where('status', 'completed')
                ->whereNotNull('paid_at')
                ->orderBy('paid_at', 'desc')
                ->first();

            if ($payment) {
                return [
                    'enrolled' => true,
                    'enrolled_at' => $payment->paid_at,
                    'method' => 'payment',
                    'payment_id' => $payment->id
                ];
            }

            // Priority 2: Check course_user enrollment record (SSOT: only enrolled_at)
            $enrollment = DB::table('course_user')
                ->where('user_id', $userId)
                ->where('course_id', $courseId)
                ->whereNotNull('enrolled_at')
                ->first();

            if ($enrollment) {
                return [
                    'enrolled' => true,
                    'enrolled_at' => Carbon::parse($enrollment->enrolled_at),
                    'method' => 'direct',
                    'enrollment_id' => $enrollment->id
                ];
            }

            // Priority 3: Check if course is free (auto-enrollment)
            $course = Course::find($courseId);
            if ($course && ($course->price === null || $course->price <= 0)) {
                // Auto-enroll in free course
                $this->enrollUser($userId, $courseId, 'free_auto');
                
                return [
                    'enrolled' => true,
                    'enrolled_at' => now(),
                    'method' => 'free_auto'
                ];
            }

            return [
                'enrolled' => false,
                'enrolled_at' => null,
                'method' => 'none'
            ];
        });
    }

    /**
     * Check if user is enrolled (simple boolean)
     */
    public function isEnrolled(int $userId, int $courseId): bool
    {
        return $this->getEnrollmentStatus($userId, $courseId)['enrolled'];
    }

    /**
     * Get enrollment timestamp
     */
    public function getEnrollmentDate(int $userId, int $courseId): ?Carbon
    {
        return $this->getEnrollmentStatus($userId, $courseId)['enrolled_at'];
    }

    /**
     * Enroll user in course with proper state management
     */
    public function enrollUser(int $userId, int $courseId, string $method = 'manual'): bool
    {
        return DB::transaction(function () use ($userId, $courseId, $method) {
            // Insert or update enrollment record (SSOT: only enrolled_at)
            DB::table('course_user')->updateOrInsert(
                ['user_id' => $userId, 'course_id' => $courseId],
                [
                    'enrolled_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now()
                ]
            );

            // Clear cache
            $this->clearEnrollmentCache($userId, $courseId);

            logger()->info('User enrolled via EnrollmentService', [
                'user_id' => $userId,
                'course_id' => $courseId,
                'method' => $method,
                'timestamp' => now()
            ]);

            return true;
        });
    }

    /**
     * Unenroll user from course
     */
    public function unenrollUser(int $userId, int $courseId): bool
    {
        return DB::transaction(function () use ($userId, $courseId) {
            DB::table('course_user')
                ->where('user_id', $userId)
                ->where('course_id', $courseId)
                ->update([
                    'enrolled_at' => null,
                    'updated_at' => now()
                ]);

            // Clear cache
            $this->clearEnrollmentCache($userId, $courseId);

            logger()->info('User unenrolled via EnrollmentService', [
                'user_id' => $userId,
                'course_id' => $courseId,
                'timestamp' => now()
            ]);

            return true;
        });
    }

    /**
     * Process payment completion and auto-enroll
     */
    public function processPaymentEnrollment(Payment $payment): bool
    {
        if ($payment->status !== 'completed') {
            return false;
        }

        return $this->enrollUser($payment->user_id, $payment->course_id, 'payment');
    }

    /**
     * Get all enrolled courses for user
     */
    public function getUserEnrolledCourses(int $userId): array
    {
        $cacheKey = "user_enrolled_courses_{$userId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            $courses = [];
            
            // Get all potential course relationships
            $courseIds = collect();
            
            // From payments
            $paidCourses = Payment::where('user_id', $userId)
                ->where('status', 'completed')
                ->pluck('course_id');
            $courseIds = $courseIds->merge($paidCourses);
            
            // From direct enrollments (SSOT: only enrolled_at)
            $enrolledCourses = DB::table('course_user')
                ->where('user_id', $userId)
                ->whereNotNull('enrolled_at')
                ->pluck('course_id');
            $courseIds = $courseIds->merge($enrolledCourses);
            
            // From free courses (auto-enroll)
            $freeCourses = Course::where(function($query) {
                $query->where('price', 0)->orWhereNull('price');
            })->pluck('id');
            $courseIds = $courseIds->merge($freeCourses);
            
            $courseIds = $courseIds->unique();
            
            foreach ($courseIds as $courseId) {
                $status = $this->getEnrollmentStatus($userId, $courseId);
                if ($status['enrolled']) {
                    $courses[] = [
                        'course_id' => $courseId,
                        'enrolled_at' => $status['enrolled_at'],
                        'method' => $status['method']
                    ];
                }
            }
            
            return $courses;
        });
    }

    /**
     * Clear enrollment cache for user/course
     */
    public function clearEnrollmentCache(int $userId, int $courseId): void
    {
        Cache::forget("enrollment_status_{$userId}_{$courseId}");
        Cache::forget("user_enrolled_courses_{$userId}");
        Cache::forget("course_{$courseId}_enrolled_count");
        Cache::forget("course_{$courseId}_enrolled_students");
    }

    /**
     * Bulk enrollment status check
     */
    public function getBulkEnrollmentStatus(int $userId, array $courseIds): array
    {
        $results = [];
        foreach ($courseIds as $courseId) {
            $results[$courseId] = $this->getEnrollmentStatus($userId, $courseId);
        }
        return $results;
    }
}