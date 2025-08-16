<?php

namespace App\Http\Controllers\Admin\Delete;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log, Cache};
use App\Models\{Topic, Question, QuizAttempt, Mistake, UserTopicProgress};
use App\Services\Core\UltraConsolidatedCacheService;
use Carbon\Carbon;

/**
 * 🚀 PERFORMANCE BEAST: Topic Deletion Controller
 * Handles: Topic deletion with all related data cleanup
 * Lines: ~70 (well under 200 limit)
 */
class TopicDeleteController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Delete a topic with all related data - LETHAL OPTIMIZED
     */
    public function deleteTopic(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            // LETHAL OPTIMIZATION: Single query with eager loading
            $topic = Topic::select('id', 'name', 'course_id')
                ->with([
                    'questions:id,topic_id',
                    'course:id,name'
                ])
                ->findOrFail($id);

            $topicName = $topic->name;
            $courseName = $topic->course->name ?? 'Unknown Course';

            Log::warning('Topic deletion initiated', [
                'topic_id' => $id,
                'topic_name' => $topicName,
                'course_name' => $courseName,
                'deleted_by' => auth()->id(),
                'timestamp' => Carbon::now()
            ]);

            $questionCount = $topic->questions->count();

            // NUCLEAR CASCADE DELETE: Optimized deletion order
            $this->performTopicCascadeDelete($topic);

            // NUCLEAR CACHE INVALIDATION
            $this->invalidateTopicCaches($id, $topic->course_id);

            DB::commit();

            Log::info('Topic deleted successfully', [
                'topic_id' => $id,
                'topic_name' => $topicName,
                'questions_deleted' => $questionCount,
                'deleted_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Topic '{$topicName}' and all related data deleted successfully",
                'stats' => [
                    'questions_deleted' => $questionCount
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Topic deletion failed', [
                'topic_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete topic: ' . $e->getMessage()
            ], 500);
        }
    }

    private function performTopicCascadeDelete(Topic $topic): void
    {
        // Delete in optimized order
        DB::table('quiz_attempts')->where('topic_id', $topic->id)->delete();
        DB::table('mistakes')->whereIn('question_id', $topic->questions->pluck('id'))->delete();
        DB::table('user_topic_progress')->where('topic_id', $topic->id)->delete();
        DB::table('questions')->where('topic_id', $topic->id)->delete();
        $topic->delete();
    }

    private function invalidateTopicCaches(int $topicId, int $courseId): void
    {
        $cacheService = app(UltraConsolidatedCacheService::class);
        $cacheService->invalidateTopicCache($topicId);
        Cache::tags(['topics', "topic_{$topicId}", "course_{$courseId}"])->flush();
    }
}