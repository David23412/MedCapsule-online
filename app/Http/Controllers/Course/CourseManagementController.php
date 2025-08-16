<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * 🚀 PERFORMANCE BEAST: Focused Course Management Controller
 * Handles: Course creation and management
 * Lines: ~150 (under 200 limit)
 */
class CourseManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Show add course form
     */
    public function showAddCourseForm()
    {
        return view('admin.add-course');
    }

    /**
     * Add new course
     */
    public function addCourse(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:courses,name',
                'description' => 'required|string|max:1000',
                'price' => 'nullable|numeric|min:0|max:99999.99'
            ]);

            // Create course
            $course = Course::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'price' => $validated['price'] ?? 0
            ]);

            // Clear all course caches
            $this->clearAllCourseCaches();

            Log::info('New course created', [
                'course_id' => $course->id,
                'name' => $course->name,
                'price' => $course->price,
                'admin_user' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Course created successfully!',
                'course' => [
                    'id' => $course->id,
                    'name' => $course->name,
                    'description' => $course->description,
                    'price' => $course->price,
                    'created_at' => $course->created_at
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Course creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create course. Please try again.'
            ], 500);
        }
    }

    /**
     * Clear all course-related caches
     */
    private function clearAllCourseCaches()
    {
        $cacheKeys = [
            'courses_list',
            'courses_with_stats',
            'courses_metadata_all',
            'free_courses_list'
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        // Clear all course-related cache tags
        Cache::tags(['courses', 'enrollments'])->flush();

        // Increment cache version to invalidate versioned caches
        $currentVersion = Cache::get('courses_cache_version', 1);
        Cache::put('courses_cache_version', $currentVersion + 1, 86400);

        Log::info('All course caches cleared', [
            'cache_keys_cleared' => count($cacheKeys),
            'new_cache_version' => $currentVersion + 1
        ]);
    }
}