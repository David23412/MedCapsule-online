<?php

namespace App\Http\Controllers\Admin\Delete;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\DeleteRequest;
use Illuminate\Support\Facades\{DB, Log, Cache};
use App\Models\{Course, Topic, Question, User, QuizAttempt, Mistake, UserTopicProgress};
use App\Services\Core\UltraConsolidatedCacheService;
use Carbon\Carbon;

/**
 * 🚀 PERFORMANCE BEAST: Course Deletion Controller
 * Handles: Course deletion with all related data cleanup
 * Lines: ~80 (well under 200 limit)
 */
class CourseDeleteController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Delete a course with all related data - LETHAL OPTIMIZED
     */
    public function deleteCourse(DeleteRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            // LETHAL OPTIMIZATION: Single query with eager loading
            $course = Course::select('id', 'name')
                ->with([
                    'topics:id,course_id',
                    'questions:id,topic_id', 
                    'users:id',
                    'payments:id,course_id'
                ])
                ->findOrFail($id);

            $courseName = $course->name;

            Log::warning('Course deletion initiated', [
                'course_id' => $id,
                'course_name' => $courseName,
                'deleted_by' => auth()->id(),
                'deleted_by_name' => auth()->user()->name,
                'timestamp' => Carbon::now()
            ]);

            // LETHAL OPTIMIZATION: Use loaded relationships instead of separate queries
            $topicCount = $course->topics->count();
            $questionCount = $course->questions->count();

            // NUCLEAR CASCADE DELETE: Optimized deletion order
            $this->performCascadeDelete($course);

            // NUCLEAR CACHE INVALIDATION: Clear all related caches
            $this->invalidateCourseCaches($id);

            DB::commit();

            Log::info('Course deleted successfully', [
                'course_id' => $id,
                'course_name' => $courseName,
                'topics_deleted' => $topicCount,
                'questions_deleted' => $questionCount,
                'deleted_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Course '{$courseName}' and all related data deleted successfully",
                'stats' => [
                    'topics_deleted' => $topicCount,
                    'questions_deleted' => $questionCount
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Course deletion failed', [
                'course_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete course: ' . $e->getMessage()
            ], 500);
        }
    }

    private function performCascadeDelete(Course $course): void
    {
        // Delete in optimized order to avoid foreign key constraints
        DB::table('quiz_attempts')->whereIn('topic_id', $course->topics->pluck('id'))->delete();
        DB::table('mistakes')->whereIn('question_id', $course->questions->pluck('id'))->delete();
        DB::table('user_topic_progress')->whereIn('topic_id', $course->topics->pluck('id'))->delete();
        DB::table('course_user')->where('course_id', $course->id)->delete();
        DB::table('payments')->where('course_id', $course->id)->delete();
        DB::table('questions')->whereIn('topic_id', $course->topics->pluck('id'))->delete();
        DB::table('topics')->where('course_id', $course->id)->delete();
        $course->delete();
    }

    private function invalidateCourseCaches(int $courseId): void
    {
        $cacheService = app(UltraConsolidatedCacheService::class);
        $cacheService->invalidateCourseCache($courseId);
        Cache::tags(['courses', "course_{$courseId}"])->flush();
    }
}