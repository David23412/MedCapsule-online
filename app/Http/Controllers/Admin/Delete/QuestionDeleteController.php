<?php

namespace App\Http\Controllers\Admin\Delete;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log, Cache};
use App\Models\{Question, QuizAttempt, Mistake};
use App\Services\Core\UltraConsolidatedCacheService;
use Carbon\Carbon;

/**
 * 🚀 PERFORMANCE BEAST: Question Deletion Controller
 * Handles: Question deletion and bulk operations
 * Lines: ~90 (well under 200 limit)
 */
class QuestionDeleteController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Delete a single question - LETHAL OPTIMIZED
     */
    public function deleteQuestion(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $question = Question::select('id', 'question_text', 'topic_id')
                ->with('topic:id,name')
                ->findOrFail($id);

            $questionText = substr($question->question_text, 0, 50) . '...';
            $topicName = $question->topic->name ?? 'Unknown Topic';

            Log::warning('Question deletion initiated', [
                'question_id' => $id,
                'question_preview' => $questionText,
                'topic_name' => $topicName,
                'deleted_by' => auth()->id(),
                'timestamp' => Carbon::now()
            ]);

            // NUCLEAR CASCADE DELETE
            $this->performQuestionCascadeDelete($question);

            // NUCLEAR CACHE INVALIDATION
            $this->invalidateQuestionCaches($id, $question->topic_id);

            DB::commit();

            Log::info('Question deleted successfully', [
                'question_id' => $id,
                'deleted_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Question deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Question deletion failed', [
                'question_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete question: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete questions - NUCLEAR OPTIMIZED
     */
    public function bulkDeleteQuestions(Request $request)
    {
        $request->validate([
            'question_ids' => 'required|array|min:1',
            'question_ids.*' => 'integer|exists:questions,id'
        ]);

        try {
            DB::beginTransaction();

            $questionIds = $request->question_ids;
            $deletedCount = 0;

            Log::warning('Bulk question deletion initiated', [
                'question_count' => count($questionIds),
                'question_ids' => $questionIds,
                'deleted_by' => auth()->id(),
                'timestamp' => Carbon::now()
            ]);

            // NUCLEAR BULK DELETE: Optimized batch operations
            DB::table('quiz_attempts')->whereIn('question_id', $questionIds)->delete();
            DB::table('mistakes')->whereIn('question_id', $questionIds)->delete();
            $deletedCount = DB::table('questions')->whereIn('id', $questionIds)->delete();

            // NUCLEAR CACHE INVALIDATION: Clear all related caches
            $this->invalidateBulkQuestionCaches($questionIds);

            DB::commit();

            Log::info('Bulk question deletion completed', [
                'questions_deleted' => $deletedCount,
                'deleted_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$deletedCount} questions deleted successfully",
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Bulk question deletion failed', [
                'error' => $e->getMessage(),
                'question_ids' => $request->question_ids ?? []
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete questions: ' . $e->getMessage()
            ], 500);
        }
    }

    private function performQuestionCascadeDelete(Question $question): void
    {
        DB::table('quiz_attempts')->where('question_id', $question->id)->delete();
        DB::table('mistakes')->where('question_id', $question->id)->delete();
        $question->delete();
    }

    private function invalidateQuestionCaches(int $questionId, int $topicId): void
    {
        $cacheService = app(UltraConsolidatedCacheService::class);
        $cacheService->invalidateQuestionCache($questionId);
        Cache::tags(['questions', "question_{$questionId}", "topic_{$topicId}"])->flush();
    }

    private function invalidateBulkQuestionCaches(array $questionIds): void
    {
        $cacheService = app(UltraConsolidatedCacheService::class);
        foreach ($questionIds as $questionId) {
            $cacheService->invalidateQuestionCache($questionId);
        }
        Cache::tags(['questions'])->flush();
    }
}