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
        return view('admin.delete-management', ['stats' => [
            'courses' => Course::count(), 'topics' => Topic::count(), 'questions' => Question::count(),
            'users' => User::count(), 'quiz_attempts' => QuizAttempt::count(), 'mistakes' => Mistake::count(),
        ]]);
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
            ->paginate(50);

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
        $courses = Course::orderBy('name')->get(['id', 'name']);
        $topics = !empty($validated['course_id'])
            ? Topic::where('course_id', $validated['course_id'])->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('admin.delete-questions', compact('questions', 'courses', 'topics'));
    }

    /**
     * Show users with delete options
     */
    public function showUsers(Request $request)
    {
        $validated = $request->validate(['search' => 'nullable|string|max:255', 'is_admin' => 'nullable|boolean']);
        $query = User::withCount(['quizAttempts', 'mistakes', 'payments']);

        if (!empty($validated['search'])) {
            $validatedSearch = $validated['search'];
            $query->where(function($q) use ($validatedSearch) {
                $q->where('name', 'like', '%' . $validatedSearch . '%')
                  ->orWhere('email', 'like', '%' . $validatedSearch . '%');
            });
        }

        if (isset($validated['is_admin'])) {
            $request->boolean('is_admin')
                ? $query->where('role', User::ROLE_ADMIN)
                : $query->where('role', '!=', User::ROLE_ADMIN);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.delete-users', compact('users'));
    }
}
