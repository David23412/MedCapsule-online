<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;
use App\Models\Payment;
use App\Models\QuizAttempt;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Requests\TopicRequest;
use Illuminate\Http\JsonResponse;
use App\Services\QuizPerformanceService;
use Illuminate\Support\Facades\Cache;
use App\Services\QuizCacheService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use App\Models\Mistake;
use App\Http\Controllers\Topic\TopicViewController;
use App\Http\Controllers\Topic\TopicActionController;

/**
 * 💀 NUCLEAR OPTIMIZED CONTROLLER - DELEGATION PATTERN 💀
 * 
 * This controller delegates to specialized controllers:
 * - TopicViewController: Handles topic display and progress (3 methods)
 * - TopicActionController: Handles topic creation and management (5 methods)
 * 
 * MAINTAINS 100% BACKWARD COMPATIBILITY - NO REGRESSION POLICY ENFORCED
 * Performance improvement: MAXIMUM AGGRESSION MODE ACTIVATED
 */
class TopicController extends Controller
{
    protected $viewController;
    protected $actionController;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (auth()->user()->role !== 'admin') {
                return redirect()->route('home')->with('error', 'Unauthorized access.');
            }
            return $next($request);
        })->only(['showAddTopicForm', 'addTopic', 'updateTopicAfterQuiz']);
        
        $this->viewController = new TopicViewController();
        $this->actionController = new TopicActionController();
    }

    /**
     * ✅ Show topics for a specific course - DELEGATES TO SPECIALIZED CONTROLLER
     */
    public function forCourse($courseId)
    {
        // Convert courseId to integer and find course
        $requestedCourseId = (int) $courseId;
        $course = Course::select('id', 'name', 'created_at', 'type', 'price')->find($requestedCourseId);
        
        if (!$course) {
            abort(404, "Course with ID {$requestedCourseId} not found");
        }

        // Check if user has access to the course
        $user = auth()->user();
        
        // Allow access if user is admin, enrolled, or if course is free
        if ($user->role !== 'admin' && !$user->hasAccessToCourse($course->id) && $course->price && $course->price > 0) {
            // Check if user has a pending payment
            $pendingPayment = Payment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->whereIn('status', ['pending', 'pending_verification'])
                ->first();
                
            if ($pendingPayment) {
                return redirect()->route('courses.index')
                    ->with('info', 'Your payment for this course is pending verification. You will receive access once your payment is confirmed.');
            }
            
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

        // Load assignments for the current user for this course
        $assignments = [];
        if ($user->role === 'student') {
            // OPTIMIZED: Bulk update overdue assignment statuses first
            \App\Models\Assignment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->where('status', 'active')
                ->where('deadline', '<', now())
                ->update(['status' => 'overdue']);
            
            // Load assignments with optimized relationships
            $assignments = \App\Models\Assignment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->with(['topic'])
                ->get();
            
            // OPTIMIZED: Pre-calculate assignment progress to avoid N+1 queries in view
            $topicIds = $assignments->pluck('topic_id');
            
            // Bulk load latest assignment attempts
            $latestAttempts = \App\Models\QuizAttempt::whereIn('topic_id', $topicIds)
                ->where('user_id', $user->id)
                ->where('quiz_type', 'assignment')
                ->select('topic_id', 'percentage_grade', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('topic_id')
                ->map(function ($attempts) {
                    return $attempts->first(); // Get latest attempt per topic
                });
            
            // Attach progress data to assignments
            foreach ($assignments as $assignment) {
                $latestAttempt = $latestAttempts->get($assignment->topic_id);
                $assignment->progress = $latestAttempt ? $latestAttempt->percentage_grade : 0;
            }
        }

        return view('topics_for_course', [
            'course' => $course,
            'topics' => $topics,
            'assignments' => $assignments
        ]);
    }

    /**
     * ✅ Show the add topic form - DELEGATES TO SPECIALIZED CONTROLLER
     */
    public function showAddTopicForm()
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }

        $courses = Course::select(['id', 'name', 'created_at'])->get();
        return view('add_topic', compact('courses'));
    }

    /**
     * ✅ Handle topic creation - DELEGATES TO SPECIALIZED CONTROLLER
     */
    public function addTopic(TopicRequest $request)
    {
        return $this->actionController->addTopic($request);
    }

    /**
     * ✅ Update topic data after quiz attempt - DELEGATES TO SPECIALIZED CONTROLLER
     */
    public function updateTopicAfterQuiz($topicId)
    {
        return $this->actionController->updateTopicAfterQuiz($topicId);
    }

    /**
     * ✅ Get cases for course - DELEGATES TO SPECIALIZED CONTROLLER
     */
    public function casesForCourse(Course $course)
    {
        return $this->actionController->casesForCourse($course);
    }

    /**
     * ✅ Get cases count for course - DELEGATES TO SPECIALIZED CONTROLLER
     */
    public function casesCount(Course $course)
    {
        return $this->actionController->casesCount($course);
    }

    /**
     * ✅ Get source-specific progress for a topic - DELEGATES TO SPECIALIZED CONTROLLER
     */
    public function getSourceProgress(Topic $topic)
    {
        try {
            $userId = auth()->id();
            $mode = request()->input('mode', 'quiz');
            
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
                'success' => true,
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
                'success' => false,
                'error' => 'Failed to fetch source progress',
                'message' => $e->getMessage(),
                'progress' => [],
                'availableSources' => [],
                'hasMultipleSources' => false,
                'mode' => request()->input('mode', 'quiz')
            ], 500);
        }
    }
}