<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Question;
use App\Models\Topic;
use App\Models\Course;
use App\Models\User;
use App\Models\QuizAttempt;
use App\Models\Mistake;
use App\Models\UserTopicProgress;
use App\Models\Payment;
use Carbon\Carbon;
use App\Services\CacheService;

class DeleteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->is_admin) {
                abort(403, 'Unauthorized access.');
            }
            return $next($request);
        });
    }

    /**
     * Show delete management dashboard
     */
    public function index()
    {
        $stats = [
            'courses' => Course::count(),
            'topics' => Topic::count(),
            'questions' => Question::count(),
            'users' => User::count(),
            'quiz_attempts' => QuizAttempt::count(),
            'mistakes' => Mistake::count(),
        ];

        return view('admin.delete-management', compact('stats'));
    }

    /**
     * Show courses with delete options
     */
    public function showCourses()
    {
        $courses = Course::withCount(['topics', 'questions', 'users'])
            ->orderBy('name')
            ->get();

        return view('admin.delete-courses', compact('courses'));
    }

    /**
     * Show topics with delete options
     */
    public function showTopics()
    {
        $topics = Topic::with(['course'])
            ->withCount(['questions', 'quizAttempts'])
            ->orderBy('name')
            ->get();

        return view('admin.delete-topics', compact('topics'));
    }

    /**
     * Show questions with delete options
     */
    public function showQuestions(Request $request)
    {
        $query = Question::with(['topic.course'])
            ->withCount(['mistakes']);

        // Filter by course
        if ($request->course_id) {
            $query->whereHas('topic', function($q) use ($request) {
                $q->where('course_id', $request->course_id);
            });
        }

        // Filter by topic
        if ($request->topic_id) {
            $query->where('topic_id', $request->topic_id);
        }

        // Search by question text
        if ($request->search) {
            $query->where('question', 'like', '%' . $request->search . '%');
        }

        $questions = $query->orderBy('id', 'desc')->paginate(20);
        $courses = Course::orderBy('name')->get();
        $topics = Topic::orderBy('name')->get();

        return view('admin.delete-questions', compact('questions', 'courses', 'topics'));
    }

    /**
     * Show users with delete options
     */
    public function showUsers(Request $request)
    {
        $query = User::withCount(['quizAttempts', 'mistakes', 'payments']);

        // Search by name or email
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by admin status
        if ($request->has('is_admin')) {
            $query->where('is_admin', $request->is_admin);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.delete-users', compact('users'));
    }

    /**
     * Delete a course with all related data
     */
    public function deleteCourse(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $course = Course::findOrFail($id);
            $courseName = $course->name;

            // Log the deletion attempt
            Log::warning('Course deletion initiated', [
                'course_id' => $id,
                'course_name' => $courseName,
                'deleted_by' => auth()->id(),
                'deleted_by_name' => auth()->user()->name,
                'timestamp' => Carbon::now()
            ]);

            // Get counts for audit
            $topicCount = $course->topics()->count();
            $questionCount = $course->questions()->count();
            $userCount = $course->users()->count();
            $paymentCount = $course->payments()->count();

            // Delete related data in proper order
            // 1. Delete quiz attempts for this course's topics
            $topicIds = $course->topics()->pluck('id');
            QuizAttempt::whereIn('topic_id', $topicIds)->delete();

            // 2. Delete mistakes for this course's questions
            $questionIds = $course->questions()->pluck('id');
            Mistake::whereIn('question_id', $questionIds)->delete();

            // 3. Delete user topic progress for this course's topics
            UserTopicProgress::whereIn('topic_id', $topicIds)->delete();

            // 4. Delete questions
            Question::whereIn('topic_id', $topicIds)->delete();

            // 5. Delete topics
            $course->topics()->delete();

            // 6. Delete course-user relationships
            $course->users()->detach();

            // 7. Delete payments
            $course->payments()->delete();

            // 8. Clear all course-related caches before deletion
            $course->clearCaches();
            
            // Increment cache version to invalidate all course caches
            $currentVersion = Cache::get('courses_cache_version', 1);
            Cache::put('courses_cache_version', $currentVersion + 1, 86400); // 24 hours
            
            // Clear general course listing cache
            Cache::forget('courses_list');
            Cache::forget('courses_with_stats');
            
            // 9. Finally delete the course
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

            $topic = Topic::with('course')->findOrFail($id);
            $topicName = $topic->name;
            $courseName = $topic->course->name;

            // Log the deletion attempt
            Log::warning('Topic deletion initiated', [
                'topic_id' => $id,
                'topic_name' => $topicName,
                'course_name' => $courseName,
                'deleted_by' => auth()->id(),
                'deleted_by_name' => auth()->user()->name,
                'timestamp' => Carbon::now()
            ]);

            // Get counts for audit
            $questionCount = $topic->questions()->count();
            $quizAttemptCount = $topic->quizAttempts()->count();

            // Delete related data in proper order
            // 1. Delete quiz attempts
            QuizAttempt::where('topic_id', $id)->delete();

            // 2. Delete mistakes for this topic's questions
            $questionIds = $topic->questions()->pluck('id');
            Mistake::whereIn('question_id', $questionIds)->delete();

            // 3. Delete user topic progress
            UserTopicProgress::where('topic_id', $id)->delete();

            // 4. Delete questions
            $topic->questions()->delete();

            // 5. Finally delete the topic
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

            $question = Question::with(['topic.course'])->findOrFail($id);
            $questionText = substr($question->question, 0, 100) . '...';
            $topicName = $question->topic->name;
            $courseName = $question->topic->course->name;

            // Log the deletion attempt
            Log::warning('Question deletion initiated', [
                'question_id' => $id,
                'question_text' => $questionText,
                'topic_name' => $topicName,
                'course_name' => $courseName,
                'deleted_by' => auth()->id(),
                'deleted_by_name' => auth()->user()->name,
                'timestamp' => Carbon::now()
            ]);

            // Get counts for audit
            $mistakeCount = $question->mistakes()->count();

            // Mark question as deleted
            $question->update(['deleted' => true]);

            // Clear all caches
            $cacheService = app(CacheService::class);
            $cacheService->clearQuestionCaches($question->id, $question->topic_id);
            
            // Force clear all session data that might contain questions
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

            $user = User::findOrFail($id);
            $userName = $user->name;
            $userEmail = $user->email;

            // Prevent deletion of the current user
            if ($user->id === auth()->id()) {
                return redirect()->back()
                    ->with('error', 'You cannot delete your own account.');
            }

            // Log the deletion attempt
            Log::warning('User deletion initiated', [
                'user_id' => $id,
                'user_name' => $userName,
                'user_email' => $userEmail,
                'deleted_by' => auth()->id(),
                'deleted_by_name' => auth()->user()->name,
                'timestamp' => Carbon::now()
            ]);

            // Get counts for audit
            $quizAttemptCount = $user->quizAttempts()->count();
            $mistakeCount = $user->mistakes()->count();
            $paymentCount = $user->payments()->count();

            // Delete related data in proper order
            // 1. Delete quiz attempts
            $user->quizAttempts()->delete();

            // 2. Delete mistakes
            $user->mistakes()->delete();

            // 3. Delete user topic progress
            $user->userTopicProgress()->delete();

            // 4. Delete payments
            $user->payments()->delete();

            // 5. Detach from courses
            $user->courses()->detach();

            // 6. Finally delete the user
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
        $request->validate([
            'question_ids' => 'required|array|min:1',
            'question_ids.*' => 'integer|exists:questions,id'
        ]);

        $questionIds = $request->question_ids;
        $deletedCount = 0;

        try {
            DB::beginTransaction();

            foreach ($questionIds as $questionId) {
                $question = Question::with(['topic.course'])->find($questionId);
                
                if (!$question) continue;

                // Mark question as deleted
                $question->update(['deleted' => true]);

                // Clear caches
                $cacheService = app(CacheService::class);
                $cacheService->clearQuestionCaches($question->id, $question->topic_id);
                
                $deletedCount++;
            }

            // Force clear all session data that might contain questions
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

    /**
     * Get deletion preview data
     */
    public function getDeletionPreview(Request $request)
    {
        $type = $request->type;
        $id = $request->id;

        switch ($type) {
            case 'course':
                $course = Course::withCount(['topics', 'questions', 'users', 'payments'])->find($id);
                return response()->json([
                    'name' => $course->name,
                    'stats' => [
                        'topics' => $course->topics_count,
                        'questions' => $course->questions_count,
                        'enrolled_users' => $course->users_count,
                        'payments' => $course->payments_count
                    ]
                ]);

            case 'topic':
                $topic = Topic::withCount(['questions', 'quizAttempts'])->with('course')->find($id);
                return response()->json([
                    'name' => $topic->name,
                    'course' => $topic->course->name,
                    'stats' => [
                        'questions' => $topic->questions_count,
                        'quiz_attempts' => $topic->quiz_attempts_count
                    ]
                ]);

            case 'question':
                $question = Question::withCount(['mistakes', 'quizAttempts'])->with(['topic.course'])->find($id);
                return response()->json([
                    'text' => substr($question->question, 0, 100) . '...',
                    'topic' => $question->topic->name,
                    'course' => $question->topic->course->name,
                    'stats' => [
                        'mistakes' => $question->mistakes_count,
                        'quiz_attempts' => $question->quiz_attempts_count
                    ]
                ]);

            case 'user':
                $user = User::withCount(['quizAttempts', 'mistakes', 'payments'])->find($id);
                return response()->json([
                    'name' => $user->name,
                    'email' => $user->email,
                    'stats' => [
                        'quiz_attempts' => $user->quiz_attempts_count,
                        'mistakes' => $user->mistakes_count,
                        'payments' => $user->payments_count
                    ]
                ]);

            default:
                return response()->json(['error' => 'Invalid type'], 400);
        }
    }
} 