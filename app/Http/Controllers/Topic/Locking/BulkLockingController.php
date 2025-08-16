<?php

namespace App\Http\Controllers\Topic\Locking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Topic;
use App\Models\Course;
use Illuminate\Support\Facades\DB;

/**
 * 🚀 PERFORMANCE BEAST: Bulk Locking Controller
 * Handles: Bulk topic and course locking operations
 * Lines: ~90 (well under 200 limit)
 */
class BulkLockingController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Bulk lock topics
     */
    public function bulkLock(Request $request)
    {
        try {
            $topicIds = $request->input('topic_ids', []);
            
            if (empty($topicIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No topics selected'
                ], 400);
            }

            $updatedCount = Topic::whereIn('id', $topicIds)->update(['is_locked' => true]);
            
            return response()->json([
                'success' => true,
                'message' => "{$updatedCount} topics have been locked",
                'updated_count' => $updatedCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk lock topics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk unlock topics
     */
    public function bulkUnlock(Request $request)
    {
        try {
            $topicIds = $request->input('topic_ids', []);
            
            if (empty($topicIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No topics selected'
                ], 400);
            }

            $updatedCount = Topic::whereIn('id', $topicIds)->update(['is_locked' => false]);
            
            return response()->json([
                'success' => true,
                'message' => "{$updatedCount} topics have been unlocked",
                'updated_count' => $updatedCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk unlock topics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lock all topics in a course
     */
    public function lockCourse($courseId)
    {
        try {
            $course = Course::findOrFail($courseId);
            $updatedCount = Topic::where('course_id', $courseId)->update(['is_locked' => true]);
            
            return response()->json([
                'success' => true,
                'message' => "All topics in '{$course->name}' have been locked",
                'course_id' => $courseId,
                'updated_count' => $updatedCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to lock course topics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unlock all topics in a course
     */
    public function unlockCourse($courseId)
    {
        try {
            $course = Course::findOrFail($courseId);
            $updatedCount = Topic::where('course_id', $courseId)->update(['is_locked' => false]);
            
            return response()->json([
                'success' => true,
                'message' => "All topics in '{$course->name}' have been unlocked",
                'course_id' => $courseId,
                'updated_count' => $updatedCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unlock course topics: ' . $e->getMessage()
            ], 500);
        }
    }
}