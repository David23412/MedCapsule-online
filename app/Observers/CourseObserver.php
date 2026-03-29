<?php

namespace App\Observers;

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourseObserver
{
    /**
     * Handle the Course "created" event.
     */
    public function created(Course $course): void
    {
        $this->clearAllCourseCaches();

    }

    /**
     * Handle the Course "updated" event.
     * 🎯 SIMPLE LOGIC: Price 0 = Auto-enroll all, Price > 0 = Revoke all enrollments
     */
    public function updated(Course $course): void
    {
        $this->clearAllCourseCaches();

        // 🎯 PRICE CHANGE DETECTED - APPLY SIMPLE LOGIC
        if ($course->isDirty('price')) {
            $newPrice = $course->price;


            if ($newPrice != 0 && $newPrice !== null) {
                // 💰 PRICE NOW > 0: REVOKE ALL ENROLLMENTS
                $this->revokeAllEnrollmentsFromCourse($course);
            }
        }

    }

    /**
     * Handle the Course "deleted" event.
     */
    public function deleted(Course $course): void
    {
        $this->clearAllCourseCaches();
    }

    /**
     * Handle the Course "restored" event.
     */
    public function restored(Course $course): void
    {
        $this->clearAllCourseCaches();
    }

    /**
     * Handle the Course "force deleted" event.
     */
    public function forceDeleted(Course $course): void
    {
        $this->clearAllCourseCaches();
    }

    /**
     * Clear all course-related caches AGGRESSIVELY
     */
    private function clearAllCourseCaches(): void
    {
        // Increment cache version to invalidate ALL versioned caches
        $currentVersion = Cache::get('courses_cache_version', 1);
        Cache::put('courses_cache_version', $currentVersion + 1, 86400);

        // Clear specific cache keys
        $cacheKeys = [
            'courses_list',
            'courses_with_stats',
            'courses_metadata_guest_all',
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        // For extreme cases, clear everything (nuclear option)
        if (config('app.env') === 'local') {
            Cache::flush();
        }
    }

    /**
     * Check if a course is free (price = 0 or null)
     */
    private function isFreeCourse(Course $course): bool
    {
        return $course->price === 0.0 || $course->price === null;
    }

    /**
     * 🎯 SIMPLE: Revoke ALL enrollments from course (used for price changes)
     */
    private function revokeAllEnrollmentsFromCourse(Course $course): void
    {
        DB::table('course_user')
            ->where('course_id', $course->id)
            ->update([
                'is_enrolled' => false,
                'updated_at' => now()
            ]);
    }

    /**
     * 🔥 NUCLEAR SECURITY: Revoke ALL ENROLLMENTS from paid course EXCEPT admins
     * This is the ultimate protection against paid course enrollment hacks
     */
    private function nuclearRevokeAllEnrollmentsFromPaidCourse(Course $course): void
    {
        try {
            // 🔥 GET STUDENT COUNT BEFORE REVOKATION (for logging)
            $adminUserIds = User::where('role', 3)->pluck('id')->toArray(); // ROLE_STUDENT = 3
            $studentEnrollmentCount = DB::table('course_user')
                ->where('course_id', $course->id)
                ->where('is_enrolled', true)
                ->whereNotIn('user_id', $adminUserIds)
                ->count();

            if ($studentEnrollmentCount > 0) {
                \Illuminate\Support\Facades\Log::warning('🔥 NUCLEAR REVOKATION: Revoking ALL student enrollments in PAID course', [
                    'course_id' => $course->id,
                    'course_name' => $course->name,
                    'course_price' => $course->price,
                    'student_enrollments_to_revoke' => $studentEnrollmentCount,
                    'admin_users_preserved' => count($adminUserIds)
                ]);

                // 🔥 NUCLEAR ATTACK: REVOKE ALL NON-ADMIN ENROLLMENTS
                DB::table('course_user')
                    ->where('course_id', $course->id)
                    ->where('is_enrolled', true)
                    ->whereNotIn('user_id', $adminUserIds) // ✅ PRESERVE ADMINS
                    ->update([
                        'is_enrolled' => false,
                        'updated_at' => now()
                    ]);

                \Illuminate\Support\Facades\Log::error('✅ NUCLEAR REVOKATION COMPLETE: ALL students revoked from PAID course', [
                    'course_id' => $course->id,
                    'course_name' => $course->name,
                    'enrollments_revoked' => $studentEnrollmentCount
                ]);
            } else {
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::critical('💥 NUCLEAR FAILURE: Failed to revoke enrollments from PAID course', [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // This is critical - fail if we can't secure the course
        }
    }

    /**
     * 🎉 NUCLEAR ENROLLMENT: Auto-enroll ALL NON-ADMIN users in FREE course
     * Only for when paid courses become free
     */
    private function nuclearAutoEnrollAllUsersInFreeCourse(Course $course): void
    {
        try {
            // Get NON-ADMIN users only (students and tutors)
            $userIds = User::whereNotIn('role', [1]) // Exclude admins (ROLE_ADMIN = 1)
                           ->pluck('id')
                           ->toArray();

            if (empty($userIds)) {
                return;
            }

            $enrolledCount = 0;
            $batchSize = 100;
            $batches = array_chunk($userIds, $batchSize);

            foreach ($batches as $batch) {
                $enrollmentData = [];
                foreach ($batch as $userId) {
                    $enrollmentData[] = [
                        'user_id' => $userId,
                        'course_id' => $course->id,
                        'is_enrolled' => true,
                        'enrolled_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }

                // Bulk enroll
                foreach ($enrollmentData as $enrollment) {
                    DB::table('course_user')->updateOrInsert(
                        [
                            'user_id' => $enrollment['user_id'],
                            'course_id' => $enrollment['course_id']
                        ],
                        $enrollment
                    );
                    $enrolledCount++;
                }
            }


        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to auto-enroll users in free course', [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'error' => $e->getMessage()
            ]);
        }
    }

}
