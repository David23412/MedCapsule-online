<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserCourseRequest;
use App\Models\User;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 💀 NUCLEAR OPTIMIZED COURSE ENROLLMENT CONTROLLER 💀
 * Handles enrollment operations with F-16 RAPTOR SPEED
 */
class CourseEnrollmentController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->check()) {
                return redirect()->route('login')->with('error', 'Please login to continue.');
            }
            if (!optional(auth()->user())->isAdmin()) {
                return redirect()->route('home')->with('error', 'Access denied. Only admins can manage enrollments.');
            }
            return $next($request);
        });
    }

    /**
     * Enroll user in course
     */
    public function enroll(UserCourseRequest $request)
    {
        try {
            $validated = $request->validated();
            
            // LETHAL EAGER LOADING: Load user and course with comprehensive data
            $user = User::select('id', 'name', 'email', 'role', 'created_at')
                ->with([
                    'courses:id,name,price',
                    'courses.topics:id,course_id,name',
                    'payments:id,user_id,course_id,status,amount,created_at',
                    'quizAttempts:id,user_id,topic_id,score,created_at',
                    'assignments:id,user_id,status,created_at'
                ])
                ->findOrFail($validated['user_id']);
                
            $course = Course::select('id', 'name', 'price', 'description')
                ->with([
                    'topics:id,course_id,name',
                    'topics.questions:id,topic_id,difficulty',
                    'users:id,name,email,role',
                    'payments:id,user_id,course_id,status,amount',
                    'payments:id,course_id,user_id,status'
                ])
                ->withCount(['topics', 'users', 'questions'])
                ->findOrFail($validated['course_id']);

            // Check if already enrolled
            if ($user->courses()->where('course_id', $course->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is already enrolled in this course.'
                ], 400);
            }

            // Enroll user
            $user->courses()->attach($course->id, [
                'enrolled_at' => $validated['enrollment_date'] ?? now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Clear relevant caches
            Cache::forget("user_courses_{$user->id}");
            Cache::forget("course_students_{$course->id}");
            Cache::forget("course_enrollment_stats");

            Log::info('User enrolled in course', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "User {$user->name} enrolled in {$course->name} successfully."
            ]);

        } catch (\Exception $e) {
            Log::error('Course enrollment failed', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to enroll user in course.'
            ], 500);
        }
    }
}
