<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\{Course, Topic, Question, User, QuizAttempt, Mistake};

class DeleteViewController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Show delete management dashboard - LETHAL ERROR HANDLING
     */
    public function index()
    {
        try {
            $cacheKey = \App\Services\QuizUltraConsolidatedCacheService::generateCacheKey('delete_index', ['user_id' => auth()->id() ?? 'guest']);
            
            $data = Cache::remember($cacheKey, 600, function () {
                $stats = [
                    'courses' => Course::count(),
                    'topics' => Topic::count(),
                    'questions' => Question::count(),
                    'users' => User::count(),
                    'quiz_attempts' => QuizAttempt::count(),
                    'mistakes' => Mistake::count()];

                return compact('stats');
            });

            return view('admin.delete-management', $data);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Delete dashboard failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            
            return view('admin.delete-management', [
                'stats' => [
                    'courses' => 0,
                    'topics' => 0,
                    'questions' => 0,
                    'users' => 0,
                    'quiz_attempts' => 0,
                    'mistakes' => 0],
                'error' => 'Failed to load dashboard statistics'
            ]);
        }
    }

    /**
     * Show courses with delete options - LETHAL ERROR HANDLING
     */
    public function showCourses()
    {
        try {
            $courses = Course::withCount(['topics', 'questions', 'users'])
                ->orderBy('name')
                ->get();

            return view('admin.delete-courses', compact('courses'));
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Show courses failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            
            return view('admin.delete-courses', [
                'courses' => collect([]),
                'error' => 'Failed to load courses'
            ]);
        }
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
        // CRITICAL SECURITY FIX: Validate all input parameters
        $validated = $request->validate([
            'course_id' => 'nullable|integer|exists:courses,id',
            'topic_id' => 'nullable|integer|exists:topics,id',
            'search' => 'nullable|string|max:255'
        ]);
        
        $query = Question::with(['topic.course'])
            ->withCount(['mistakes']);

        if (!empty($validated['course_id'])) {
            $query->whereHas('topic', function($q) use ($validated) {
                $q->where('course_id', $validated['course_id']);
            });
        }

        if (!empty($validated['topic_id'])) {
            $query->where('topic_id', $validated['topic_id']);
        }

        if (!empty($validated['search'])) {
            $query->where('question', 'like', '%' . $validated['search'] . '%');
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

        if ($request->search) {
            $validatedSearch = $request->validate(['search' => 'string|max:255'])['search'] ?? '';
            $query->where(function($q) use ($validatedSearch) {
                $q->where('name', 'like', '%' . $validatedSearch . '%')
                  ->orWhere('email', 'like', '%' . $validatedSearch . '%');
            });
        }

        if ($request->has('is_admin')) {
            $request->boolean('is_admin')
                ? $query->where('role', User::ROLE_ADMIN)
                : $query->where('role', '!=', User::ROLE_ADMIN);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.delete-users', compact('users'));
    }
}
