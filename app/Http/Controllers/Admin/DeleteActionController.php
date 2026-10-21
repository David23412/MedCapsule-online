<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\DeleteRequest;
use Illuminate\Support\Facades\{DB, Log, Cache};
use App\Models\{Course, Topic, Question, User, QuizAttempt, Mistake, UserTopicProgress};
use App\Services\Core\UltraConsolidatedCacheService;
use Carbon\Carbon;

class DeleteActionController extends Controller
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
                    'questions:questions.id,topic_id,extra_topic',
                    'users:users.id',
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
                foreach ($course->questions as $question) {
                    $question->delete();
                }
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

            UserTopicProgress::where('topic_id', $id)->delete();
            foreach ($topic->questions()->get() as $question) {
                $question->delete();
            }
            $topic->delete();

            DB::commit();


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

            $question = Question::select('id', 'question', 'topic_id', 'extra_topic')->with(['topic:id,name,course_id', 'topic.course:id,name'])->findOrFail($id);
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

            $question->delete();

            $cacheService = app(UltraConsolidatedCacheService::class);
            $cacheService->clearQuestionCaches($question->id, $question->topic_id);


            DB::commit();


            return redirect()->route('admin.delete.questions')
                ->with('success', "Question from '{$topicName}' ({$courseName}) has been deleted successfully.");

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
                DB::rollBack();

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You cannot delete your own account.',
                    ], 422);
                }

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

            $message = "User '{$userName}' ({$userEmail}) and all related data have been deleted successfully.";

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'user_id' => (int) $id,
                ]);
            }

            return redirect()->route('admin.delete.users')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User deletion failed', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete user. Error: ' . $e->getMessage(),
                ], 500);
            }

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
            'question_ids' => 'required|array|min:1|max:500',
            'question_ids.*' => 'required|integer|distinct|exists:questions,id'
        ]);

        $questionIds = $validated['question_ids'];
        $deletedCount = 0;

        try {
            DB::beginTransaction();

            $questions = Question::whereIn('id', $questionIds)->lockForUpdate()->get();
            // Model deletion runs the same cache invalidation as the inline question editor.
            foreach ($questions as $question) {
                $question->delete();
                $deletedCount++;
            }

            DB::commit();


            return redirect()->route('admin.delete.questions')
                ->with('success', "Successfully deleted {$deletedCount} questions.");

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
