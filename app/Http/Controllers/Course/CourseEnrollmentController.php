<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Support\State\EnrollmentService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * 🚀 PERFORMANCE BEAST: Focused Course Enrollment Controller
 * Handles: Course enrollment logic
 * Lines: ~100 (well under 200 limit)
 */
class CourseEnrollmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Enroll user in course
     */
    public function enroll(Request $request, $courseId)
    {
        try {
            $user = auth()->user();
            $course = Course::findOrFail($courseId);

            // Check if course is free
            if ($course->price > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'This course requires payment. Please use the payment system.'
                ], 400);
            }

            // Check if already enrolled
            $enrollmentService = app(EnrollmentService::class);
            if ($enrollmentService->isEnrolled($user->id, $course->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already enrolled in this course.'
                ], 400);
            }

            // Enroll user
            $user->enrollInCourse($course->id);

            // Clear caches
            $this->clearEnrollmentCaches($user->id);

            Log::info('User enrolled in free course', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'course_name' => $course->name
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully enrolled in {$course->name}!",
                'redirect_url' => route('courses.show', $course->id)
            ]);

        } catch (\Exception $e) {
            Log::error('Course enrollment failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to enroll in course. Please try again.'
            ], 500);
        }
    }

    /**
     * Clear enrollment-related caches
     */
    private function clearEnrollmentCaches($userId)
    {
        $cacheKeys = [
            "user_courses_{$userId}",
            "user_enrollments_{$userId}",
            "courses_list",
            "courses_with_stats"
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        // Clear course-specific caches
        Cache::tags(["user_{$userId}", 'courses'])->flush();
    }
}