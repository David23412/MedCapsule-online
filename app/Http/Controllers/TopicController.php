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
use Illuminate\Support\Facades\Auth;
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
            // Log the attempt for debugging
            Log::warning("Attempt to access non-existent course", [
                'requested_course_id' => $requestedCourseId,
                'user_id' => auth()->id(),
                'url' => request()->url(),
                'referer' => request()->header('referer')
            ]);
            
            // Check if there are any available courses to redirect to
            $availableCourse = Course::first();
            if ($availableCourse) {
                return redirect()->route('topics.forCourse', $availableCourse->id)
                    ->with('warning', "Course {$requestedCourseId} not found. Showing available course instead.");
            }
            
            abort(404, "Course with ID {$requestedCourseId} not found");
        }

        // Check if user has access to the course
        $user = auth()->user();
        
        // Allow access if user is admin, enrolled, or if course is free
        if ($user->role !== 'admin' && !$user->hasAccessToCourse($course->id) && $course->price && $course->price > 0) {
            // Check if user has a pending payment
            $pendingPayment = Payment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->where('status', 'pending')
                ->first();
                
            if ($pendingPayment) {
                return redirect()->route('courses.index')
                    ->with('info', 'Your payment for this course is pending verification. You will receive access once your payment is confirmed.');
            }
            
            return redirect()->route('courses.show', $course->id)
                ->with('error', 'You need to purchase this course to access its content.');
        }
        
        // Load the course with its topics, user progress, and videos
        $topics = Topic::where('course_id', $course->id)
            ->orderBy('display_order')
            ->with([
                'userProgress' => function($query) use ($user) {
                    $query->where('user_id', $user->id);
                },
                'video' => function($query) {
                    $query->where('is_active', true);
                }
            ])
            ->get();

        // Load assignments only for students, using integer role constants.
        $assignments = collect();
        if ($user->role === \App\Models\User::ROLE_STUDENT) {
            // Students only see assignments specifically assigned to them via pivot table
            $assignments = \App\Models\Assignment::where('course_id', $course->id)
                ->whereHas('assignedUsers', function($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->with([
                    'topic:id,name,course_id',
                    'attempts' => function($query) use ($user) {
                        $query->where('user_id', $user->id)
                              ->select('id', 'assignment_id', 'user_id', 'percentage_grade', 'score', 'created_at');
                    }
                ])
                ->get();
                
            // Attach dynamic progress for current user using eager loaded data
            foreach ($assignments as $assignment) {
                // Use eager loaded attempts instead of making new queries
                $attempts = $assignment->attempts;
                
                if ($attempts->isEmpty()) {
                    $assignment->user_progress = [
                        'completed' => false,
                        'score' => 0,
                        'percentage' => 0,
                        'attempts_count' => 0,
                        'best_score' => 0,
                        'latest_attempt' => null
                    ];
                } else {
                    $bestAttempt = $attempts->sortByDesc('percentage_grade')->first();
                    $assignment->user_progress = [
                        'completed' => $bestAttempt->percentage_grade >= 80,
                        'score' => $bestAttempt->score,
                        'percentage' => $bestAttempt->percentage_grade,
                        'attempts_count' => $attempts->count(),
                        'best_score' => $bestAttempt->score,
                        'latest_attempt' => $attempts->sortByDesc('created_at')->first()
                    ];
                }
                
                $assignment->display_status = $assignment->getStatusForUser(Auth::user());
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
     * ✅ Get source-specific progress for a topic - FIXED: Shows separate progress for each mode
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
            
            // FIXED: Get progress ONLY for the selected mode (quiz OR tutor, not both)
            $modeProgress = QuizAttempt::getTopicProgress($userId, $topic->id, $mode);
            
            // Format the response, ensuring each source has an entry even if no attempts
            $formattedProgress = [];
            $totalAvailable = 0;
            $totalCompleted = 0;
            
            foreach ($sourceCounts as $source => $count) {
                // Get data ONLY from the selected mode
                $modeData = $modeProgress['sources'][$source] ?? null;
                
                // Use mode-specific stats
                $completed = $modeData ? $modeData['score'] : 0;
                $attempted = $modeData ? $modeData['total'] : 0;
                
                $formattedProgress[$source] = [
                    'completed' => $completed,
                    'total' => $attempted,
                    'total_questions_available' => $count,
                    'percentage' => $attempted > 0 ? round(($completed / $attempted) * 100, 2) : 0,
                    'has_attempts' => $attempted > 0  // CRITICAL: Flag based on actual attempts in this mode
                ];
                
                $totalAvailable += $count;
                $totalCompleted += $completed;
            }

            // Add overall progress (only for quiz mode, tutor mode has no overall)
            if ($mode === 'quiz') {
                $overallAttempted = $modeProgress['overall']['total'];
                $overallCompleted = $modeProgress['overall']['score'];
                
                $formattedProgress['all'] = [
                    'completed' => $overallCompleted,
                    'total' => $overallAttempted,
                    'total_questions_available' => $totalAvailable,
                    'percentage' => $modeProgress['overall']['percentage'],
                    'has_attempts' => $overallAttempted > 0
                ];
            } else {
                // Tutor mode: no overall progress
                $formattedProgress['all'] = [
                    'completed' => 0,
                    'total' => 0,
                    'total_questions_available' => $totalAvailable,
                    'percentage' => 0,
                    'has_attempts' => false
                ];
            }
            
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

    /**
     * ✅ Show video for a specific topic
     */
    public function showVideo(Topic $topic)
    {
        try {
            $user = auth()->user();
            
            // Check if user has access to the topic's course
            if ($user->role !== 'admin' && !$user->hasAccessToCourse($topic->course_id)) {
                return redirect()->route('courses.show', $topic->course_id)
                    ->with('error', 'You need to purchase this course to access its content.');
            }
            
            // Load the topic with its video and course
            $topic->load(['video' => function($query) {
                $query->where('is_active', true);
            }, 'course:id,name']);
            
            // Check if topic has an active video
            if (!$topic->video) {
                return redirect()->route('topics.forCourse', $topic->course_id)
                    ->with('error', 'No video available for this topic.');
            }
            
            // Get all topics for the course to show in sidebar
            $allTopics = Topic::where('course_id', $topic->course_id)
                ->orderBy('display_order')
                ->with([
                    'userProgress' => function($query) use ($user) {
                        $query->where('user_id', $user->id);
                    },
                    'video' => function($query) {
                        $query->where('is_active', true);
                    },
                    'videoProgress' => function($query) use ($user) {
                        $query->where('user_id', $user->id);
                    }
                ])
                ->get();

            // Return video view
            return view('video', [
                'currentTopic' => $topic,
                'video' => $topic->video,
                'course' => $topic->course,
                'topics' => $allTopics
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in showVideo:', [
                'error' => $e->getMessage(),
                'topic_id' => $topic->id,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('courses.index')
                ->with('error', 'Unable to load video. Please try again.');
        }
    }
}