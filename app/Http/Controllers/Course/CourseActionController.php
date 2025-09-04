<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseRequest;
use App\Models\Course;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * LETHAL OPTIMIZED COURSE ACTION CONTROLLER
 * Handles course enrollment and management operations
 */
class CourseActionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
        
        // Admin-only routes
        $this->middleware(function ($request, $next) {
            if (!auth()->user() || auth()->user()->role !== 'admin') {
                return redirect()->route('home')->with('error', 'Unauthorized access.');
            }
            return $next($request);
        });
    }

    /**
     * Enroll the current user in the selected course
     */
    public function enroll(Request $request, $courseId)
    {
        // TODO: Create FormRequest class for validation
        $user = Auth::user();
        $course = Course::findOrFail($courseId);

        // Check if user is already enrolled
        $existingEnrollment = $user->courses()
            ->where('course_id', $courseId)
            ->whereNotNull('enrolled_at')
            ->exists();

        if ($existingEnrollment) {
            return redirect()->route('topics.forCourse', $courseId)
                ->with('info', 'You are already enrolled in this course.');
        }

        // Check if course requires payment
        if ($course->price > 0) {
            // Check for completed payment
            $hasValidPayment = $user->payments()
                ->where('course_id', $courseId)
                ->where('status', 'completed')
                ->exists();

            if (!$hasValidPayment) {
                return redirect()->route('payment.options', $courseId)
                    ->with('info', 'Payment is required to enroll in this course.');
            }
        }

        // Enroll the user
        $user->courses()->attach($courseId, [
            'enrolled_at' => now()
        ]);

        // Clear relevant caches
        $this->clearEnrollmentCaches($user->id, $courseId);
        
        // Send welcome notification
        try {
            app(NotificationService::class)->createCourseWelcomeNotification(
                $user,
                $course->name
            );
        } catch (\Exception $e) {
            // Don't fail enrollment if notification fails
            \Log::warning('Failed to send welcome notification', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);
        }

        return redirect()->route('topics.forCourse', $courseId)
            ->with('success', "Successfully enrolled in {$course->name}!");
    }

    /**
     * Add a new course (admin only)
     */
    public function addCourse(CourseRequest $request)
    {
        $validated = $request->validated();

        try {
            $course = Course::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'price' => $validated['price'],
                'created_by' => auth()->id()
            ]);

            // Clear course-related caches
            $this->clearCourseCaches();

            Log::info('New course created', [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'created_by' => auth()->id()
            ]);

            return redirect()->route('admin.courses')
                ->with('success', 'Course "' . $course->name . '" created successfully!');
                
        } catch (\Exception $e) {
            \Log::error('Failed to create course', [
                'error' => $e->getMessage(),
                'validated_data' => $validated,
                'user_id' => auth()->id()
            ]);

            return back()->withInput()
                ->with('error', 'Failed to create course. Please try again.');
        }
    }

    /**
     * Clear enrollment-related caches
     */
    private function clearEnrollmentCaches($userId, $courseId): void
    {
        $patterns = [
            "courses_metadata_{$userId}_*",
            "user_courses_{$userId}",
            "course_enrollment_{$courseId}_*"
        ];
        
        // For Laravel cache stores that support pattern deletion
        try {
            foreach ($patterns as $pattern) {
                Cache::forget($pattern);
            }
        } catch (\Exception $e) {
            // Fallback: clear entire cache if pattern deletion fails
            Cache::flush();
        }
    }

    /**
     * Clear course-related caches
     */
    private function clearCourseCaches(): void
    {
        Cache::forget('courses_cache_version');
        Cache::increment('courses_cache_version');
        
        // Clear specific course caches
        $patterns = [
            'courses_metadata_*',
            'course_preview_*',
            'admin_courses_*'
        ];
        
        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }
}
