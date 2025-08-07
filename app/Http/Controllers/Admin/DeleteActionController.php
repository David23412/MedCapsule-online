<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\DeleteRequest;
use Illuminate\Support\Facades\{DB, Log, Cache};
use App\Models\{Course, Topic, Question, User, QuizAttempt, Mistake, UserTopicProgress};
use App\Services\CacheService;
use Carbon\Carbon;

class DeleteActionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth', 'admin');
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
            $userCount = $course->users->count();
            $paymentCount = $course->payments->count();

            // LETHAL OPTIMIZATION: Extract IDs from loaded relationships
            $topicIds = $course->topics->pluck('id');
            $questionIds = $course->questions->pluck('id');

            // LETHAL OPTIMIZATION: Batch delete operations
            if ($topicIds->isNotEmpty()) {
                QuizAttempt::whereIn('topic_id', $topicIds)->delete();
                UserTopicProgress::whereIn('topic_id', $topicIds)->delete();
            }

            if ($questionIds->isNotEmpty()) {
                Mistake::whereIn('question_id', $questionIds)->delete();
                Question::whereIn('id', $questionIds)->delete();
            }
            $course->topics()->delete();
            $course->users()->detach();
            $course->payments()->delete();

            $course->clearCaches();
            $currentVersion = Cache::get('courses_cache_version', 1);
            Cache::put('courses_cache_version', $currentVersion + 1, 86400);
            Cache::forget('courses_list');
            Cache::forget('courses_with_stats');

            $course->delete();

            DB::commit();

            Log::info('Course deleted successfully', [
                'course_id' => $id,
                'course_name' => $courseName,
                'deleted_by' => auth()->id(),
                'stats' => [
                    'topics_deleted' => $topicCount,
                    'questions_deleted' => $questionCount,
                    'users_detached' => $userCount,
                    'payments_deleted' => $paymentCount
                ]
            ]);

            return redirect()->route('admin.delete.courses')
                ->with('success', "Course '{$courseName}' and all related data ({$topicCount} topics, {$questionCount} questions) have been deleted successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Course deletion failed', [
                'course_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to delete course. Error: ' . $e->getMessage());
        }
    }

    /**
     * Delete a topic with all related data
     */
    public function deleteTopic(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $topic = Topic::select('id', 'name', 'course_id')->with('course:id,name')->findOrFail($id);
            $topicName = $topic->name;
            $courseName = $topic->course->name;

            Log::warning('Topic deletion initiated', [
                'topic_id' => $id,
                'topic_name' => $topicName,
                'course_name' => $courseName,
                'deleted_by' => auth()->id(),
                'deleted_by_name' => auth()->user()->name,
                'timestamp' => Carbon::now()
            ]);

            $questionCount = $topic->questions()->count();
            $quizAttemptCount = $topic->quizAttempts()->count();

            QuizAttempt::where('topic_id', $id)->delete();

            $questionIds = $topic->questions()->pluck('id');
            Mistake::whereIn('question_id', $questionIds)->delete();

            UserTopicProgress::where('topic_id', $id)->delete();
            $topic->questions()->delete();
            $topic->delete();

            DB::commit();

            Log::info('Topic deleted successfully', [
                'topic_id' => $id,
                'topic_name' => $topicName,
                'course_name' => $courseName,
                'deleted_by' => auth()->id(),
                'stats' => [
                    'questions_deleted' => $questionCount,
                    'quiz_attempts_deleted' => $quizAttemptCount
                ]
            ]);

            return redirect()->route('admin.delete.topics')
                ->with('success', "Topic '{$topicName}' from course '{$courseName}' and all related data ({$questionCount} questions) have been deleted successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Topic deletion failed', [
                'topic_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to delete topic. Error: ' . $e->getMessage());
        }
    }

    /**
     * Delete a question with all related data
     */
    public function deleteQuestion(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $question = Question::select('id', 'question', 'topic_id')->with(['topic:id,name,course_id', 'topic.course:id,name'])->findOrFail($id);
            $questionText = substr($question->question, 0, 100) . '...';
            $topicName = $question->topic->name;
            $courseName = $question->topic->course->name;

            Log::warning('Question deletion initiated', [
                'question_id' => $id,
                'question_text' => $questionText,
                'topic_name' => $topicName,
                'course_name' => $courseName,
                'deleted_by' => auth()->id(),
                'deleted_by_name' => auth()->user()->name,
                'timestamp' => Carbon::now()
            ]);

            $mistakeCount = $question->mistakes()->count();

            $question->update(['deleted' => true]);

            $cacheService = app(CacheService::class);
            $cacheService->clearQuestionCaches($question->id, $question->topic_id);
            
            session()->flush();

            DB::commit();

            Log::info('Question marked as deleted successfully', [
                'question_id' => $id,
                'question_text' => $questionText,
                'topic_name' => $topicName,
                'course_name' => $courseName,
                'deleted_by' => auth()->id(),
                'stats' => [
                    'mistakes_count' => $mistakeCount
                ]
            ]);

            return redirect()->route('admin.delete.questions')
                ->with('success', "Question from '{$topicName}' ({$courseName}) has been marked as deleted successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Question deletion failed', [
                'question_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to delete question. Error: ' . $e->getMessage());
        }
    }

    /**
     * Delete a user with all related data
     */
    public function deleteUser(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $user = User::select('id', 'name', 'email')->findOrFail($id);
            $userName = $user->name;
            $userEmail = $user->email;

            if ($user->id === auth()->id()) {
                return redirect()->back()
                    ->with('error', 'You cannot delete your own account.');
            }

            Log::warning('User deletion initiated', [
                'user_id' => $id,
                'user_name' => $userName,
                'user_email' => $userEmail,
                'deleted_by' => auth()->id(),
                'deleted_by_name' => auth()->user()->name,
                'timestamp' => Carbon::now()
            ]);

            $quizAttemptCount = $user->quizAttempts()->count();
            $mistakeCount = $user->mistakes()->count();
            $paymentCount = $user->payments()->count();

            $user->quizAttempts()->delete();
            $user->mistakes()->delete();
            $user->userTopicProgress()->delete();
            $user->payments()->delete();
            $user->courses()->detach();
            $user->delete();

            DB::commit();

            Log::info('User deleted successfully', [
                'user_id' => $id,
                'user_name' => $userName,
                'user_email' => $userEmail,
                'deleted_by' => auth()->id(),
                'stats' => [
                    'quiz_attempts_deleted' => $quizAttemptCount,
                    'mistakes_deleted' => $mistakeCount,
                    'payments_deleted' => $paymentCount
                ]
            ]);

            return redirect()->route('admin.delete.users')
                ->with('success', "User '{$userName}' ({$userEmail}) and all related data have been deleted successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User deletion failed', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to delete user. Error: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete questions
     */
    public function bulkDeleteQuestions(Request $request)
    {
        $validated = $request->validate([
            'question_ids' => 'required|array',
            'question_ids.*' => 'integer|exists:questions,id'
        ]);
        
        $questionIds = $validated['question_ids'];
        $deletedCount = 0;

        try {
            DB::beginTransaction();

            // LETHAL FIX: Batch load all questions to eliminate N+1 queries
            $questions = Question::with(['topic.course'])
                ->whereIn('id', $questionIds)
                ->get()
                ->keyBy('id');

            $cacheService = app(CacheService::class);
            
            // Batch update all questions at once
            Question::whereIn('id', $questionIds)->update(['deleted' => true]);
            
            // Clear caches for all questions
            foreach ($questionIds as $questionId) {
                $question = $questions[$questionId] ?? null;
                if ($question) {
                    $cacheService->clearQuestionCaches($question->id, $question->topic_id);
                    $deletedCount++;
                }
            }

            session()->flush();

            DB::commit();

            Log::info('Bulk question deletion completed', [
                'question_ids' => $questionIds,
                'marked_as_deleted_count' => $deletedCount,
                'deleted_by' => auth()->id()
            ]);

            return redirect()->route('admin.delete.questions')
                ->with('success', "Successfully marked {$deletedCount} questions as deleted.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk question deletion failed', [
                'question_ids' => $questionIds,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to delete questions. Error: ' . $e->getMessage());
        }
    }
}