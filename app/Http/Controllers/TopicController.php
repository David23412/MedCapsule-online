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
use App\Services\TopicProgressService;
use App\Services\StudyGamePlanService;

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
    protected TopicProgressService $topicProgressService;
    protected StudyGamePlanService $studyGamePlanService;

    public function __construct(TopicProgressService $topicProgressService, StudyGamePlanService $studyGamePlanService)
    {
        $this->middleware('auth');

        // Apply admin/tutor middleware to specific methods
        $this->middleware(function ($request, $next) {
            $user = auth()->user();

            // SECURITY FIX: Use proper role constants instead of string comparison
            // Admin has full access
            if ($user->isAdmin()) {
                return $next($request);
            }

            // Tutor has limited access - check if they have courses assigned
            if ($user->isTutor()) {
                // Check if tutor has any courses assigned (using existing course_user relationship)
                $hasCourses = $user->courses()->exists();

                if (!$hasCourses) {
                    return redirect()->route('tutor.dashboard')
                        ->with('error', 'You must be assigned to courses to manage topics.');
                }

                return $next($request);
            }

            // Deny access for other roles
            return redirect()->route('home')->with('error', 'Unauthorized access.');
        })->only(['showAddTopicForm', 'addTopic', 'updateTopicAfterQuiz']);

        $this->viewController = new TopicViewController();
        $this->actionController = new TopicActionController();
        $this->topicProgressService = $topicProgressService;
        $this->studyGamePlanService = $studyGamePlanService;
    }

    /**
     * ✅ Show topics for a specific course - DELEGATES TO SPECIALIZED CONTROLLER
     */
    public function forCourse($courseId)
    {
        // Convert courseId to integer and find course
        $requestedCourseId = (int) $courseId;
        $course = Course::query()
            ->select('id', 'name', 'created_at', 'type', 'access_type', 'price')
            ->find($requestedCourseId);
        
        if (!$course) {
            // Log the attempt for debugging
            Log::warning("Attempt to access non-existent course", [
                'requested_course_id' => $requestedCourseId,
                'user_id' => auth()->id(),
                'url' => request()->url(),
                'referer' => request()->header('referer')
            ]);
            
            // Check if there are any available courses to redirect to
            $availableCourse = Course::query()->select('id')->first();
            if ($availableCourse) {
                return redirect()->route('topics.forCourse', $availableCourse->id)
                    ->with('warning', "Course {$requestedCourseId} not found. Showing available course instead.");
            }
            
            abort(404, "Course with ID {$requestedCourseId} not found");
        }

        // Check if user has access to the course
        $user = auth()->user();

        if (!$user->isAdmin() && !$user->hasAccessToCourse($course->id)) {
            if ($user->requiresSurveyUnlockForCourse($course)) {
                return redirect($user->freeCourseSurveyRedirectFor($course));
            }

            if ($course->isSurveyUnlockable()) {
                $user->enrollInCourse($course->id, $course->defaultEnrollmentAccessLevel());
                \Illuminate\Support\Facades\Cache::forget("user_course_optimization_data_{$user->id}");
                \Illuminate\Support\Facades\Cache::forget("courses_metadata_{$user->id}_all_v1"); // Attempt to clear some course caches for UI update
            } else {
                $pendingPayment = Payment::query()
                    ->where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->where('status', 'pending')
                    ->exists();

                if ($pendingPayment) {
                    return redirect()->route('courses.index')
                        ->with('info', 'Your payment for this course is pending verification. You will receive access once your payment is confirmed.');
                }

                return redirect()->route('courses.index')
                    ->with('error', 'You need to purchase this course to access its content.');
            }
        }

        $hasFullCourseAccess = $user->hasFullAccessToCourse($course);
        $previewTopicLimit = $user->getPreviewTopicLimitForCourse($course);
        
        // Load the course with its topics, user progress, and videos
        $topics = Topic::query()
            ->where('course_id', $course->id)
            ->select([
                'id',
                'name',
                'course_id',
                'display_order',
                'case_type',
                'is_locked',
            ])
            ->orderBy('display_order')
            ->with([
                'userProgress' => function($query) use ($user) {
                    $query->where('user_id', $user->id);
                },
                'video:id,topic_id',
            ])
            ->get();

        $previewLockedTopicIds = [];
        foreach ($topics as $topic) {
            $isPreviewLocked = $previewTopicLimit !== null
                && (int) ($topic->display_order ?? 0) > $previewTopicLimit;

            $topic->is_preview_locked = $isPreviewLocked;
            $topic->is_locked = (bool) $topic->is_locked || $isPreviewLocked;

            if ($isPreviewLocked) {
                $topic->lock_reason = 'Preview access only includes the first ' . $previewTopicLimit . ' topics.';
                $previewLockedTopicIds[] = $topic->id;
            }
        }

        $this->topicProgressService->hydrateTopicsWithFreshQuizProgress($topics, $user->id);
        $gamePlan = $this->studyGamePlanService->buildForCourse($user, $course, $topics);

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
                                  ->select('id', 'assignment_id', 'user_id', 'percentage_grade', 'score', 'total_questions', 'created_at');
                                  // ✅ FIXED: Added 'total_questions' field to prevent N+1 queries
                        }
                    ])
                    ->get();

                if (!empty($previewLockedTopicIds)) {
                    $assignments = $assignments
                        ->reject(fn ($assignment) => in_array($assignment->topic_id, $previewLockedTopicIds, true))
                        ->values();
                }
                    
                // ✅ FIXED: Pre-calculate all user progress data in a single pass to prevent N+1 queries
                $assignments->each(function($assignment) use ($user) {
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
                        // Calculate overall progress across all sources for complete topics
                        $totalCorrectAnswers = $attempts->sum('score');
                        $totalQuestions = $attempts->sum('total_questions');
                        $overallPercentage = $totalQuestions > 0 ? ($totalCorrectAnswers / $totalQuestions) * 100 : 0;
                        
                        $bestAttempt = $attempts->sortByDesc('percentage_grade')->first();
                        $assignment->user_progress = [
                            'completed' => $overallPercentage >= 80,
                            'score' => $totalCorrectAnswers,
                            'percentage' => $overallPercentage,
                            'attempts_count' => $attempts->count(),
                            'best_score' => $bestAttempt->score,
                            'latest_attempt' => $attempts->sortByDesc('created_at')->first()
                        ];
                    }
                    
                    $assignment->display_status = $assignment->getStatusForUser($user);
                });
            }

        // Get available courses for random quiz modal
        if ($user->isAdmin()) {
            $availableCourses = \App\Models\Course::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        } else {
            $candidateCourses = \App\Models\Course::query()
                ->select('courses.id', 'courses.name')
                ->whereHas('users', function($subQuery) use ($user) {
                    $subQuery->where('user_id', $user->id)
                            ->where('is_enrolled', true);
                })
                ->orderBy('name')
                ->get();

            $accessLevels = $user->getCourseAccessLevels($candidateCourses->pluck('id')->all());
            $availableCourses = $candidateCourses
                ->filter(fn ($availableCourse) => ($accessLevels[$availableCourse->id] ?? null) === \App\Models\User::COURSE_ACCESS_FULL)
                ->values();
        }

        return view('topics_for_course', [
            'course' => $course,
            'topics' => $topics,
            'assignments' => $assignments,
            'availableCourses' => $availableCourses,
            'hasFullCourseAccess' => $hasFullCourseAccess,
            'previewTopicLimit' => $previewTopicLimit,
            'gamePlan' => $gamePlan,
            'currentUserView' => [
                'name' => $user->name,
                'profile_image_url' => $user->getProfileImageUrl(),
                'profile_fallback_url' => 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&size=200&background=007bff&color=ffffff',
                'level' => $user->getCurrentLevel(),
                'xp' => (int) ($user->xp ?? 0),
            ],
        ]);
    }

    /**
     * ✅ Show the add topic form - DELEGATES TO SPECIALIZED CONTROLLER
     */
    public function showAddTopicForm()
    {
        $user = auth()->user();

        // SECURITY FIX: Use proper role constants instead of string comparison
        if ($user->isAdmin()) {
            $courses = Course::select(['id', 'name', 'created_at'])->get();
        }
        // Tutor has access only to their assigned courses (using existing course_user relationship)
        elseif ($user->isTutor()) {
            $courses = $user->courses()->select(['courses.id', 'courses.name', 'courses.created_at'])->get();
        }
        else {
            abort(403, 'You do not have permission to manage topics.');
        }

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
     * ✅ Get available sources for multiple courses (for random quiz filtering)
     */
    public function getCoursesSources(Request $request)
    {
        try {
            $user = auth()->user();
            $courseIds = $request->input('course_ids', []);

            if (empty($courseIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No course IDs provided'
                ], 400);
            }

            // Get courses the user actually has access to
            $accessibleCourses = collect();
            if ($user->isAdmin()) {
                $accessibleCourses = \App\Models\Course::whereIn('id', $courseIds)->get();
            } else {
                $candidateCourses = \App\Models\Course::whereIn('id', $courseIds)
                    ->whereHas('users', function($subQuery) use ($user) {
                        $subQuery->where('user_id', $user->id)
                                ->where('is_enrolled', true);
                    })
                    ->get();

                $accessLevels = $user->getCourseAccessLevels($candidateCourses->pluck('id')->all());
                $accessibleCourses = $candidateCourses
                    ->filter(fn ($course) => ($accessLevels[$course->id] ?? null) === \App\Models\User::COURSE_ACCESS_FULL)
                    ->values();
            }

            if ($accessibleCourses->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No accessible courses found'
                ], 403);
            }

            // Get all sources from these courses
            $sources = \App\Models\Question::whereHas('topic', function($query) use ($accessibleCourses) {
                $query->whereIn('course_id', $accessibleCourses->pluck('id'));
            })
            ->whereNotNull('source')
            ->where('source', '!=', '')
            ->selectRaw('source, COUNT(*) as question_count')
            ->groupBy('source')
            ->orderBy('source')
            ->get()
            ->map(function($source) {
                return [
                    'name' => $source->source,
                    'question_count' => $source->question_count
                ];
            });

            return response()->json([
                'success' => true,
                'sources' => $sources,
                'courses_count' => $accessibleCourses->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getCoursesSources:', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? null,
                'course_ids' => $request->input('course_ids', []),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch course sources'
            ], 500);
        }
    }

    /**
     * ✅ Get source-specific progress for a topic - FIXED: Shows separate progress for each mode + SQL INJECTION PREVENTION
     */
    public function getSourceProgress(Topic $topic)
    {
        try {
            $user = auth()->user();
            $userId = auth()->id();
            // SECURITY FIX: Validate and sanitize the mode parameter to prevent SQL injection
            $mode = request()->input('mode', 'quiz');
            $allowedModes = ['quiz', 'tutor'];

            // CRITICAL: Validate mode parameter against allowed values
            if (!in_array($mode, $allowedModes)) {
                \Log::warning('SQL Injection attempt blocked in getSourceProgress', [
                    'user_id' => $userId,
                    'invalid_mode' => $mode,
                    'allowed_modes' => $allowedModes,
                    'topic_id' => $topic->id
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid mode parameter',
                    'message' => 'Mode must be either "quiz" or "tutor"'
                ], 400);
            }

            if (!$user->isAdmin() && !$user->hasAccessToCourse($topic->course_id)) {
                if ($user->requiresSurveyUnlockForCourse($topic->course_id)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Free course survey required',
                        'survey_url' => $user->freeCourseSurveyRedirectFor($topic->course_id, request()->getRequestUri()),
                    ], 423);
                }

                return response()->json([
                    'success' => false,
                    'error' => 'Access denied to this topic',
                ], 403);
            }

            if (!$user->isAdmin() && $user->isTopicPreviewLocked($topic)) {
                return response()->json([
                    'success' => false,
                    'error' => 'This topic is outside the free preview. Unlock the full course to continue.',
                ], 403);
            }

            // Get available sources and their question counts for this topic (including linked questions)
            $sourceCounts = Question::where(function($q) use ($topic) {
                    $q->where('topic_id', $topic->id)
                      ->orWhereRaw('FIND_IN_SET(?, REPLACE(extra_topic, " ", "")) > 0', [$topic->id]);
                })
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
                // SECURITY FIX: Sanitize source name to prevent XSS
                $sanitizedSource = htmlspecialchars($source, ENT_QUOTES, 'UTF-8');

                // Get data ONLY from the selected mode
                $modeData = $modeProgress['sources'][$source] ?? null;

                // Use mode-specific stats
                $completed = $modeData ? $modeData['score'] : 0;
                $attempted = $modeData ? $modeData['total'] : 0;

                $formattedProgress[$sanitizedSource] = [
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
            if (!$user->isAdmin() && !$user->hasAccessToCourse($topic->course_id)) {
                // TEMPORARY: Log the access issue for debugging

                if ($user->requiresSurveyUnlockForCourse($topic->course_id)) {
                    return redirect($user->freeCourseSurveyRedirectFor($topic->course_id));
                }

                return redirect()->route('courses.index')
                    ->with('error', 'You need to purchase this course to access its content.');
            }

            if (!$user->isAdmin() && $user->isTopicPreviewLocked($topic)) {
                return redirect()->route('topics.forCourse', $topic->course_id)
                    ->with('error', 'This video is outside the free preview. Unlock the full course to continue.');
            }
            
            // Load the topic with its video and course
            $topic->load(['video', 'course:id,name']); // Removed is_active filter since column was removed
            
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
                    'video', // Removed is_active filter since column was removed
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
