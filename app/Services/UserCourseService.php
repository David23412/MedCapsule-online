<?php

namespace App\Services;

use App\Models\User;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UserCourseService
{
    /**
     * Enroll a user in a course.
     *
     * @param User $user
     * @param Course $course
     * @return array
     */
    public function enrollUser(User $user, Course $course): array
    {
        try {
            DB::table('course_user')->updateOrInsert(
                ['user_id' => $user->id, 'course_id' => $course->id],
                [
                    'is_enrolled' => true,
                    'access_level' => User::COURSE_ACCESS_FULL,
                    'enrolled_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $this->clearCourseCacheForUser($user);

            Log::info('ENROLLMENT COMPLETED SUCCESSFULLY', [
                'user_id' => $user->id,
                'course_id' => $course->id,
            ]);

            return [
                'success' => true,
                'message' => "User {$user->name} enrolled in {$course->name} successfully.",
                'enrollment' => [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'course_id' => $course->id,
                    'course_name' => $course->name,

                    'enrolled_at' => now()->toDateTimeString(),
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Course enrollment failed in service: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to enroll user in course.'];
        }
    }

    /**
     * Revoke a user's access to a course.
     *
     * @param User $user
     * @param Course $course
     * @return array
     */
    public function getCourseEnrollmentStatusForUser(int $userId): array
    {
        try {
            $courses = DB::table('courses')
                ->leftJoin('course_user', function ($join) use ($userId) {
                    $join->on('courses.id', '=', 'course_user.course_id')
                        ->where('course_user.user_id', '=', $userId);
                })
                ->select(
                    'courses.id',
                    'courses.name',
                    'courses.price',
                    DB::raw('COALESCE(course_user.is_enrolled, false) as is_enrolled')
                )
                ->orderBy('courses.name')
                ->get();

            return ['success' => true, 'courses' => $courses];
        } catch (\Exception $e) {
            Log::error('Failed to get user enrollments in service: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load user enrollment data.'];
        }
    }

    public function getEnrollmentDataForAjax(): array
    {
        try {
            // FIXED: Show ALL enrollments (both active and revoked) for complete activity history
            $enrollments = DB::table('course_user')
                ->join('users', 'course_user.user_id', '=', 'users.id')
                ->join('courses', 'course_user.course_id', '=', 'courses.id')
                ->select(
                    'users.id as user_id',
                    'users.name as name',
                    'users.email as email',
                    'courses.id as course_id',
                    'courses.name as course_name',
                    'course_user.enrolled_at',
                    'course_user.created_at',
                    'course_user.is_enrolled'
                )
                ->orderBy('course_user.updated_at', 'desc')
                ->limit(50)
                ->get();

            return ['success' => true, 'enrollments' => $enrollments];
        } catch (\Exception $e) {
            Log::error('Failed to get enrollments in service: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load enrollment data.'];
        }
    }

    /**
     * Revoke/Grant user access to a course - ULTRA-OPTIMIZED
     * Single atomic operation with proper status handling
     *
     * @param User $user
     * @param Course $course
     * @return array
     */
    public function revokeUserAccess(User $user, Course $course): array
    {
        try {
            // 🔥 SINGLE ATOMIC OPERATION 🔥
            // Check if user is already enrolled
            $existingEnrollment = DB::table('course_user')
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->where('is_enrolled', true)
                ->first();

            if ($existingEnrollment) {
                // User is enrolled, revoke access by setting is_enrolled to false
                DB::table('course_user')
                    ->where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->update(['is_enrolled' => false, 'updated_at' => now()]);

                $isEnrolled = false;
                $message = "Access revoked for {$user->name} from {$course->name}.";
            } else {
                // User is not enrolled, grant access
                DB::table('course_user')->updateOrInsert(
                    ['user_id' => $user->id, 'course_id' => $course->id],
                    [
                        'is_enrolled' => true,
                        'access_level' => User::COURSE_ACCESS_FULL,
                        'enrolled_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $isEnrolled = true;
                $message = "Access granted to {$user->name} for {$course->name}.";
            }

            $this->clearCourseCacheForUser($user);

            Log::info('ACCESS STATUS TOGGLED', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'new_status' => $isEnrolled ? 'enrolled' : 'revoked'
            ]);

            return [
                'success' => true,
                'message' => $message,
                'is_enrolled' => $isEnrolled
            ];
        } catch (\Exception $e) {
            Log::error('Access toggle failed in service: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to toggle access.'];
        }
    }

    /**
     * Clear course cache for a specific user.
     *
     * @param User $user
     */
    private function clearCourseCacheForUser(User $user): void
    {
        // 🔥 CRITICAL FIX: Clear BOTH cache types used by different controllers

        // 1. Clear tagged cache (used by some controllers)
        try {
            Cache::tags(["courses_user_{$user->id}"])->flush();
        } catch (\Exception $e) {
            // Tagged cache might not be supported, continue with regular cache
            Log::warning("Tagged cache not supported, falling back to regular cache");
        }

        // 2. Clear regular cache keys used by CourseController
        Cache::forget("user_course_optimization_data_{$user->id}");
        Cache::forget("courses_metadata_{$user->id}_guest_all_v1");

        // 3. Clear versioned caches by incrementing version
        $currentVersion = Cache::get('courses_cache_version', 1);
        Cache::put('courses_cache_version', $currentVersion + 1, 86400);

        // 4. Clear pattern-based caches
        $this->clearPatternBasedCaches($user->id);

        Log::info("Cache cleared for user {$user->id} - Both tagged and regular cache");
    }

    /**
     * Clear pattern-based caches for user.
     *
     * @param int $userId
     */
    private function clearPatternBasedCaches(int $userId): void
    {
        // Common cache patterns to clear
        $patterns = [
            "courses_metadata_{$userId}_*",
            "course_*_enrolled_{$userId}",
            "course_*_user_{$userId}",
            "courses_list",
            "courses_with_stats"
        ];

        foreach ($patterns as $pattern) {
            try {
                Cache::forget($pattern);
            } catch (\Exception $e) {
                // Ignore cache errors
            }
        }
    }
}
