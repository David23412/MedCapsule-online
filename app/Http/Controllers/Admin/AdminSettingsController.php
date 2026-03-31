<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSetting;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    /**
     * Update an admin setting.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string',
            'value' => 'required',
            'type' => 'required|in:string,boolean,integer,float,json'
        ]);

        AdminSetting::set($validated['key'], $validated['value'], $validated['type']);

        return response()->json(['success' => true, 'message' => 'Setting updated successfully']);
    }

    /**
     * Get a specific admin setting.
     */
    public function get(string $key): JsonResponse
    {
        $value = AdminSetting::get($key);
        return response()->json(['value' => $value]);
    }

    /**
     * Update fake enrolled settings for a single course.
     */
    public function updateFakeEnrolled(Request $request, Course $course): JsonResponse
    {
        $validated = $request->validate([
            'fake_enrolled_enabled' => 'required|boolean',
            'fake_enrolled_count' => 'required|integer|min:0|max:9999'
        ]);

        $course->update($validated);

        return response()->json(['success' => true, 'message' => 'Course fake enrolled settings updated']);
    }

    /**
     * Bulk update fake enrolled settings for multiple courses.
     */
    public function bulkUpdateFakeEnrolled(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'courses' => 'required|array',
            'courses.*.id' => 'required|exists:courses,id',
            'courses.*.fake_enrolled_enabled' => 'required|boolean',
            'courses.*.fake_enrolled_count' => 'required|integer|min:0|max:9999'
        ]);

        foreach ($validated['courses'] as $courseData) {
            Course::where('id', $courseData['id'])->update([
                'fake_enrolled_enabled' => $courseData['fake_enrolled_enabled'],
                'fake_enrolled_count' => $courseData['fake_enrolled_count']
            ]);
        }

        return response()->json(['success' => true, 'message' => 'All courses updated successfully']);
    }

    /**
     * Get topics for a specific course (admin API).
     */
    public function getCourseTopics(int $courseId): JsonResponse
    {
        $course = \App\Models\Course::find($courseId);

        if (!$course) {
            return response()->json(['success' => false, 'message' => 'Course not found'], 404);
        }

        $topics = \App\Models\Topic::where('course_id', $courseId)
            ->select('id', 'name')
            ->orderBy('display_order')
            ->get();

        return response()->json($topics);
    }
}
