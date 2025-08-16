<?php

namespace App\Services;

use App\Models\User;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * ✅ REFACTORED: EnrollmentService - Business logic for user enrollments
 * Extracted from fat UserCourseController for better separation of concerns
 */
class EnrollmentService
{
    /**
     * Enroll a user in a course
     */
    public function enrollUser(int $userId, int $courseId): array
    {
        try {
            $user = User::findOrFail($userId);
            $course = Course::findOrFail($courseId);

            Log::info('✅ User and Course found:', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'course_id' => $course->id,
                'course_name' => $course->name
            ]);

            // Check if user is already enrolled using efficient cache
            $alreadyEnrolled = Cache::remember("course_enrollment_check_{$courseId}_{$userId}", 300, function() use ($courseId, $userId) { 
                return DB::table('course_user')
                    ->where('course_id', $courseId)
                    ->where('user_id', $userId)
                    ->where('is_enrolled', 1)
                    ->exists(); 
            });
            
            if ($alreadyEnrolled) {
                return [
                    'success' => false,
                    'message' => "User '{$user->name}' is already enrolled in course '{$course->name}'"
                ];
            }

            // Check if enrollment record exists but is disabled
            $existingEnrollment = DB::table('course_user')
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();

            if ($existingEnrollment) {
                Log::info('🔄 Updating existing enrollment');
                
                // Update existing enrollment to enrolled
                DB::table('course_user')
                    ->where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->update([
                        'is_enrolled' => true,
                        'enrolled_at' => now(),
                        'updated_at' => now()
                    ]);

                $message = "User '{$user->name}' re-enrolled in course '{$course->name}'";
            } else {
                Log::info('➕ Creating new enrollment');
                
                // Create new enrollment using sync method for better relationship handling
                $user->courses()->syncWithoutDetaching([
                    $courseId => [
                        'is_enrolled' => true,
                        'enrolled_at' => now()
                    ]
                ]);

                $message = "User '{$user->name}' enrolled in course '{$course->name}' successfully!";
            }

            // Clear relevant cache
            $this->clearEnrollmentCache($userId, $courseId);

            // Send welcome notification
            $this->sendWelcomeNotification($user, $course);

            Log::info('🎉 ENROLLMENT SUCCESS', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'message' => $message
            ]);

            return [
                'success' => true,
                'message' => $message
            ];

        } catch (\Exception $e) {
            Log::error('❌ Enrollment service error:', [
                'user_id' => $userId,
                'course_id' => $courseId,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to enroll user: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Revoke user access from a course
     */
    public function revokeUser(int $userId, int $courseId): array
    {
        try {
            $user = User::findOrFail($userId);
            $course = Course::findOrFail($courseId);

            // Check if enrollment exists
            $existingEnrollment = DB::table('course_user')
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();

            if (!$existingEnrollment) {
                return [
                    'success' => false,
                    'message' => "User '{$user->name}' is not enrolled in course '{$course->name}'"
                ];
            }

            // Update enrollment to revoked
            DB::table('course_user')
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->update([
                    'is_enrolled' => false,
                    'revoked_at' => now(),
                    'updated_at' => now()
                ]);

            // Clear relevant cache
            $this->clearEnrollmentCache($userId, $courseId);

            $message = "User '{$user->name}' access revoked from course '{$course->name}'";

            Log::info('🔄 REVOKE SUCCESS', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'message' => $message
            ]);

            return [
                'success' => true,
                'message' => $message
            ];

        } catch (\Exception $e) {
            Log::error('❌ Revoke service error:', [
                'user_id' => $userId,
                'course_id' => $courseId,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to revoke user access: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get enrollments based on filters
     */
    public function getEnrollments(array $filters = []): array
    {
        $query = DB::table('course_user')
            ->join('users', 'course_user.user_id', '=', 'users.id')
            ->join('courses', 'course_user.course_id', '=', 'courses.id')
            ->select([
                'course_user.*',
                'users.name as user_name',
                'users.email as user_email',
                'courses.name as course_name',
                'courses.price as course_price'
            ]);

        // Apply filters
        if (isset($filters['user_id'])) {
            $query->where('course_user.user_id', $filters['user_id']);
        }

        if (isset($filters['course_id'])) {
            $query->where('course_user.course_id', $filters['course_id']);
        }

        if (isset($filters['status'])) {
            switch ($filters['status']) {
                case 'enrolled':
                    $query->where('course_user.is_enrolled', true);
                    break;
                case 'revoked':
                    $query->where('course_user.is_enrolled', false);
                    break;
                // 'all' - no additional filter needed
            }
        }

        $enrollments = $query->orderBy('course_user.updated_at', 'desc')->get();

        return $enrollments->toArray();
    }

    /**
     * Get specific user's enrollments
     */
    public function getUserEnrollments(int $userId): array
    {
        return $this->getEnrollments(['user_id' => $userId]);
    }

    /**
     * Clear enrollment-related cache
     */
    private function clearEnrollmentCache(int $userId, int $courseId): void
    {
        $patterns = [
            "user_enrollments_{$userId}",
            "course_enrollments_{$courseId}",
            "course_enrollment_check_{$courseId}_{$userId}",
            "courses_cache_version",
            "enrollment_stats"
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }

        // Increment cache version to invalidate pattern-based caches
        $cacheVersion = Cache::get('courses_cache_version', 1);
        Cache::put('courses_cache_version', $cacheVersion + 1, 86400);
    }

    /**
     * Send welcome notification to newly enrolled user
     */
    private function sendWelcomeNotification(User $user, Course $course): void
    {
        try {
            if (class_exists('\App\Services\NotificationService')) {
                app(\App\Services\NotificationService::class)->createCourseWelcomeNotification(
                    $user,
                    $course->name
                );
                
                Log::info('Welcome notification sent', [
                    'user_id' => $user->id,
                    'course_id' => $course->id
                ]);
            }
        } catch (\Exception $e) {
            // Don't fail enrollment if notification fails
            Log::warning('Failed to send welcome notification', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}