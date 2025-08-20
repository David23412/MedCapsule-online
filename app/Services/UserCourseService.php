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
                ['enrolled_at' => now(), 'updated_at' => now()]
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
                    'is_enrolled' => true,
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
                    DB::raw('COALESCE(course_user.enrolled_at IS NOT NULL, false) as is_enrolled')
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
                    'course_user.created_at'
                )
                ->orderBy('course_user.created_at', 'desc')
                ->get();

            return ['success' => true, 'enrollments' => $enrollments];
        } catch (\Exception $e) {
            Log::error('Failed to get enrollments in service: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load enrollment data.'];
        }
    }

    public function revokeUserAccess(User $user, Course $course): array
    {
        try {
            $enrollment = DB::table('course_user')
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();

            if (!$enrollment) {
                return ['success' => false, 'message' => 'User is not enrolled in this course.'];
            }

            $isEnrolled = !is_null($enrollment->enrolled_at);
            $newStatus = !$isEnrolled;

            DB::table('course_user')
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->update([
                    'enrolled_at' => $newStatus ? now() : null,
                    'updated_at' => now()
                ]);

            $this->clearCourseCacheForUser($user);

            $message = $newStatus
                ? "Access granted to {$user->name} for {$course->name}."
                : "Access revoked for {$user->name} from {$course->name}.";

            return ['success' => true, 'message' => $message, 'is_enrolled' => $newStatus];
        } catch (\Exception $e) {
            Log::error('Access revocation failed in service: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to revoke access.'];
        }
    }

    /**
     * Clear course cache for a specific user.
     *
     * @param User $user
     */
    private function clearCourseCacheForUser(User $user): void
    {
        // Using tagged cache for efficient invalidation
        Cache::tags(["courses_user_{$user->id}"])->flush();
        Log::info("Cache cleared for user {$user->id}");
    }
}
