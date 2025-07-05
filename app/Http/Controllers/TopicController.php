<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;
use App\Models\Payment;
use App\Models\QuizAttempt;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use App\Models\Mistake;
use App\Models\SpacedRepetition;
use Illuminate\Support\Facades\Auth;
use App\Models\SourceProgress;

class TopicController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->is_admin) {
                return redirect()->route('home')->with('error', 'Unauthorized access.');
            }
            return $next($request);
        })->only(['showAddTopicForm', 'addTopic', 'updateTopicAfterQuiz']); // Only apply to admin routes
    }

    /**
     * ✅ Show topics for a specific course.
     */
    public function forCourse(Course $course)
    {
        // Check if user has access to the course
        $user = auth()->user();
        
        if (!$user->is_admin && !$user->hasAccessToCourse($course->id)) {
            // Check if user has a pending payment
            $pendingPayment = Payment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->whereIn('status', ['pending', 'pending_verification'])
                ->first();
                
            if ($pendingPayment) {
                // Redirect with message about pending payment
                return redirect()->route('courses.index')
                    ->with('info', 'Your payment for this course is pending verification. You will receive access once your payment is confirmed.');
            }
            
            // No pending payment, redirect to payment page
            return redirect()->route('courses.show', $course->id)
                ->with('error', 'You need to purchase this course to access its content.');
        }
        
        // Load the course with its topics and user progress
        $topics = Topic::where('course_id', $course->id)
            ->orderBy('display_order')
            ->with(['userProgress' => function($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->get();

        // Log the topics being loaded
        Log::info('Loading topics for course', [
            'course_id' => $course->id,
            'topics' => $topics->map(function($topic) {
                return [
                    'topic_id' => $topic->id,
                    'course_id' => $topic->course_id,
                    'name' => $topic->name
                ];
            })
        ]);
        
        return view('topics_for_course', [
            'course' => $course,
            'topics' => $topics
        ]);
    }

    /**
     * ✅ Show the add topic form.
     */
    public function showAddTopicForm()
    {
        if (!auth()->user()->is_admin) {
            abort(403);
        }

        $courses = Course::all(); // Fetch all courses
        return view('add_topic', compact('courses'));
    }

    /**
     * ✅ Handle topic creation.
     */
    public function addTopic(Request $request)
    {
        if (!auth()->user()->is_admin) {
            abort(403);
        }

        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'case_type' => 'required|string|in:quiz,cases,practical'
        ]);

        // Set default display_order
        $maxOrder = Topic::where('course_id', $validated['course_id'])->max('display_order') ?? 0;

        $topic = Topic::create([
            'course_id' => $validated['course_id'],
            'name' => $validated['name'],
            'description' => $request->description,
            'case_type' => $validated['case_type'],
            'display_order' => $maxOrder + 1
        ]);

        return redirect()->back()->with('success', 'Topic added successfully!');
    }

    /**
     * ✅ Update topic data after quiz attempt.
     */
    public function updateTopicAfterQuiz($topicId)
    {
        $topic = Topic::findOrFail($topicId);

        // Get total number of questions
        $totalQuestions = Question::where('topic_id', $topicId)->count();
        if ($totalQuestions === 0) {
            return;
        }

        // Count incorrect answers using spaced_repetitions
        $incorrectAnswers = SpacedRepetition::whereHas('question', function ($query) use ($topicId) {
            $query->where('topic_id', $topicId);
        })
        ->whereColumn('submitted_answer', '!=', 'questions.correct_answer')
        ->count();

        // Calculate correct answers
        $correctAnswers = $totalQuestions - $incorrectAnswers;

        // Calculate percentage grade
        $percentageGrade = ($correctAnswers / $totalQuestions) * 100;

        // Update topic details
        $topic->update([
            'percentage_grade' => $percentageGrade,
            'last_attempt_date' => Carbon::now()
        ]);
    }

    public function casesForCourse(Course $course)
    {
        return $course->topics()
            ->where('name', 'like', '% cases')
            ->get();
    }

    public function casesCount(Course $course)
    {
        return $course->topics()
            ->where('name', 'like', '% cases')
            ->count();
    }

    /**
     * Get source-specific progress for a topic
     */
    public function getSourceProgress(Topic $topic)
    {
        try {
        $userId = auth()->id();
        $mode = request()->input('mode', 'quiz'); // Get mode from request, default to quiz
        
        // Get available sources and their question counts for this topic
        $sourceCounts = Question::where('topic_id', $topic->id)
            ->whereNotNull('source')
            ->selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->pluck('count', 'source')
            ->toArray();
        
        // Get progress for each source using QuizAttempt
        $progress = QuizAttempt::getTopicProgress($userId, $topic->id, $mode);
        
        // Format the response, ensuring each source has an entry even if no attempts
        $formattedProgress = [];
        $totalAvailable = 0;
        $totalCompleted = 0;
        
        foreach ($sourceCounts as $source => $count) {
                $sourceData = $progress['sources'][$source] ?? null;
            $completed = $sourceData ? $sourceData['score'] : 0;
            $total = $sourceData ? $sourceData['total'] : 0;
            
            $formattedProgress[$source] = [
                'completed' => $completed,
                'total' => $total,
                'total_questions_available' => $count,
                'percentage' => $total > 0 ? round(($completed / $total) * 100, 2) : 0
            ];
            
            $totalAvailable += $count;
            $totalCompleted += $completed;
        }

            // Add overall progress
            $formattedProgress['all'] = [
                'completed' => $progress['overall']['score'],
                'total' => $progress['overall']['total'],
                'total_questions_available' => $totalAvailable,
                'percentage' => $progress['overall']['percentage']
            ];
        
        // Add metadata about available sources
        $response = [
            'progress' => $formattedProgress,
            'availableSources' => array_keys($sourceCounts),
            'hasMultipleSources' => count($sourceCounts) > 1,
            'mode' => $mode
        ];
        
        return response()->json($response);
            
        } catch (\Exception $e) {
            Log::error('Error in getSourceProgress:', [
                'error' => $e->getMessage(),
                'topic_id' => $topic->id,
                'user_id' => $userId ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to fetch source progress',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}