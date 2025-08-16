<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * ✅ REFACTORED: CourseManagementService - Business logic for course management
 * Extracted from fat CourseController for better separation of concerns
 */
class CourseManagementService
{
    /**
     * Create a new course
     */
    public function createCourse(array $validated, Request $request): array
    {
        try {
            DB::beginTransaction();

            // Handle image upload or URL
            $imageUrl = $this->handleImageUpload($request, $validated);

            $course = Course::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'image' => $imageUrl,
                'color' => $validated['color'],
                'title_color' => $validated['title_color'],
                'academic_year' => $validated['academic_year'],
                'price' => $validated['price'] ?? 0,
                'currency' => 'USD' // Default currency
            ]);

            // Clear all course caches for instant updates
            $this->clearAllCourseCaches();

            DB::commit();

            Log::info('Course created successfully', [
                'course_id' => $course->id,
                'name' => $course->name,
                'created_by' => auth()->id()
            ]);

            return [
                'success' => true,
                'message' => "Course '{$course->name}' created successfully!",
                'course' => $course
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create course', [
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            return [
                'success' => false,
                'message' => 'Failed to create course: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update an existing course
     */
    public function updateCourse(Course $course, array $validated, Request $request): array
    {
        try {
            DB::beginTransaction();

            // Handle image upload or URL
            $imageUrl = $this->handleImageUpload($request, $validated, $course->image);

            $course->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'image' => $imageUrl,
                'color' => $validated['color'],
                'title_color' => $validated['title_color'],
                'academic_year' => $validated['academic_year'],
                'price' => $validated['price'] ?? 0,
            ]);

            // Clear all course caches for instant updates
            $this->clearAllCourseCaches();

            DB::commit();

            Log::info('Course updated successfully', [
                'course_id' => $course->id,
                'name' => $course->name,
                'updated_by' => auth()->id()
            ]);

            return [
                'success' => true,
                'message' => "Course '{$course->name}' updated successfully!",
                'course' => $course
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update course', [
                'course_id' => $course->id,
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update course: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete a course
     */
    public function deleteCourse(Course $course): array
    {
        try {
            DB::beginTransaction();

            $courseName = $course->name;
            $courseId = $course->id;

            // Check if course has enrollments
            $enrollmentCount = $course->enrolledUsers()->count();
            if ($enrollmentCount > 0) {
                return [
                    'success' => false,
                    'message' => "Cannot delete course '{$courseName}' because it has {$enrollmentCount} enrolled users."
                ];
            }

            // Check if course has topics
            $topicCount = $course->topics()->count();
            if ($topicCount > 0) {
                return [
                    'success' => false,
                    'message' => "Cannot delete course '{$courseName}' because it has {$topicCount} topics. Please delete topics first."
                ];
            }

            // Delete course image if it's stored locally
            if ($course->image && str_starts_with($course->image, '/storage/')) {
                $imagePath = str_replace('/storage/', '', $course->image);
                if (Storage::disk('public')->exists($imagePath)) {
                    Storage::disk('public')->delete($imagePath);
                }
            }

            $course->delete();

            // Clear all course caches
            $this->clearAllCourseCaches();

            DB::commit();

            Log::info('Course deleted successfully', [
                'course_id' => $courseId,
                'name' => $courseName,
                'deleted_by' => auth()->id()
            ]);

            return [
                'success' => true,
                'message' => "Course '{$courseName}' deleted successfully!"
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete course', [
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to delete course: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Handle image upload or URL processing
     */
    private function handleImageUpload(Request $request, array $validated, ?string $existingImage = null): ?string
    {
        // Handle image upload or URL
        $imageUrl = $existingImage;

        if ($request->hasFile('image_file')) {
            // Delete old image if it exists and is stored locally
            if ($existingImage && str_starts_with($existingImage, '/storage/')) {
                $oldImagePath = str_replace('/storage/', '', $existingImage);
                if (Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                }
            }

            // Store uploaded file
            $file = $request->file('image_file');
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_' . uniqid() . '.' . $extension;
            $path = $file->storeAs('course-images', $filename, 'public');
            $imageUrl = '/storage/' . $path; // Use relative path

        } elseif (isset($validated['image_url']) && $validated['image_url']) {
            // Use provided URL
            $imageUrl = $validated['image_url'];
        }

        return $imageUrl;
    }

    /**
     * Clear all course-related caches
     */
    private function clearAllCourseCaches(): void
    {
        $patterns = [
            'courses_metadata_*',
            'courses_cache_version',
            'course_enrollment_check_*',
            'course_*'
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                // For wildcard patterns, increment cache version to invalidate all
                $cacheVersion = Cache::get('courses_cache_version', 1);
                Cache::put('courses_cache_version', $cacheVersion + 1, 86400);
            } else {
                Cache::forget($pattern);
            }
        }

        Log::info('Cleared all course caches for instant updates');
    }

    /**
     * Get course statistics
     */
    public function getCourseStatistics(): array
    {
        return Cache::remember('course_statistics', 300, function () {
            return [
                'total_courses' => Course::count(),
                'published_courses' => Course::whereNotNull('created_at')->count(),
                'total_enrollments' => DB::table('course_user')->where('is_enrolled', true)->count(),
                'total_topics' => DB::table('topics')->count(),
                'average_price' => Course::where('price', '>', 0)->avg('price') ?? 0,
            ];
        });
    }

    /**
     * Search courses by criteria
     */
    public function searchCourses(array $criteria): \Illuminate\Database\Eloquent\Collection
    {
        $query = Course::query();

        if (isset($criteria['name'])) {
            $query->where('name', 'like', '%' . $criteria['name'] . '%');
        }

        if (isset($criteria['academic_year'])) {
            $query->where('academic_year', $criteria['academic_year']);
        }

        if (isset($criteria['price_min'])) {
            $query->where('price', '>=', $criteria['price_min']);
        }

        if (isset($criteria['price_max'])) {
            $query->where('price', '<=', $criteria['price_max']);
        }

        return $query->with(['topics' => function($q) {
            $q->select('id', 'course_id', 'name');
        }])->get();
    }
}