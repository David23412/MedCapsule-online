<?php

namespace App\Services;

use App\Models\User;
use App\Models\Course;
use App\Support\State\EnrollmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Exception;

class EnrollmentManagementService
{
    private EnrollmentService $enrollmentService;

    public function __construct(EnrollmentService $enrollmentService)
    {
        $this->enrollmentService = $enrollmentService;
    }

    /**
     * Enroll user in course
     */
    public function enrollUser(int $userId, int $courseId): array
    {
        $user = User::findOrFail($userId);
        $course = Course::findOrFail($courseId);

        // Check if enrollment already exists
        $existingEnrollment = DB::table('course_user')
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existingEnrollment) {
            // Update existing enrollment
            DB::table('course_user')
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->update([
                    'is_enrolled' => true,
                    'enrolled_at' => now(),
                    'updated_at' => now()
                ]);
        } else {
            // Create new enrollment
            DB::table('course_user')->insert([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'is_enrolled' => true,
                'enrolled_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Verify enrollment
        $enrollmentStatus = $this->enrollmentService->getEnrollmentStatus($user->id, $course->id);

        if (!$enrollmentStatus['enrolled']) {
            throw new Exception('Failed to create/update enrollment in database');
        }

        // Clear cache
        $this->clearCourseCacheForUser($user->id);

        return [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'course_id' => $course->id,
            'course_name' => $course->name,
            'is_enrolled' => true,
            'enrolled_at' => $enrollmentStatus['enrolled_at']
        ];
    }

    /**
     * Revoke user enrollment
     */
    public function revokeEnrollment(int $userId, int $courseId): void
    {
        $user = User::findOrFail($userId);
        $course = Course::findOrFail($courseId);

        // Update enrollment status
        DB::table('course_user')
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->update([
                'is_enrolled' => false,
                'enrolled_at' => null,
                'updated_at' => now()
            ]);

        // Clear cache
        $this->clearCourseCacheForUser($user->id);
    }

    /**
     * Get all enrollments
     */
    public function getAllEnrollments(): \Illuminate\Support\Collection
    {
        return DB::table('course_user')
            ->join('users', 'course_user.user_id', '=', 'users.id')
            ->join('courses', 'course_user.course_id', '=', 'courses.id')
            ->select(
                'course_user.id',
                'users.name as user_name',
                'users.email as user_email',
                'courses.name as course_name',
                'course_user.is_enrolled',
                'course_user.enrolled_at',
                'course_user.created_at'
            )
            ->orderBy('course_user.created_at', 'desc')
            ->get();
    }

    /**
     * Get user enrollments
     */
    public function getUserEnrollments(int $userId): \Illuminate\Support\Collection
    {
        return DB::table('course_user')
            ->join('courses', 'course_user.course_id', '=', 'courses.id')
            ->where('course_user.user_id', $userId)
            ->where('course_user.is_enrolled', true)
            ->select('courses.id', 'courses.name', 'course_user.enrolled_at')
            ->get();
    }

    /**
     * Clear course cache for user
     */
    private function clearCourseCacheForUser(int $userId): void
    {
        $cacheVersion = Cache::get('courses_cache_version', 1);
        
        $patterns = [
            "courses_metadata_{$userId}_all_v{$cacheVersion}",
            "courses_metadata_{$userId}_*",
            "course_*_user_{$userId}_*"
        ];
        
        foreach ($patterns as $pattern) {
            if (strpos($pattern, '*') !== false) {
                Cache::put('courses_cache_version', $cacheVersion + 1, 86400);
                break;
            } else {
                Cache::forget($pattern);
            }
        }
        
        Cache::forget('courses_list');
        Cache::forget('courses_with_stats');
    }
}