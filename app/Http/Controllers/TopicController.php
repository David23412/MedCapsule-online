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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use App\Models\Mistake;
use App\Http\Controllers\Topic\TopicViewController;
use App\Http\Controllers\Topic\TopicActionController;
use App\Services\TopicProgressService;
use App\Services\StudyGamePlanService;
use App\Services\StudentViewDataService;
use App\Services\TutorSourceProgressService;

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
    protected StudentViewDataService $studentViewDataService;
    protected TutorSourceProgressService $tutorSourceProgressService;

    public function __construct(
        TopicProgressService $topicProgressService,
        StudyGamePlanService $studyGamePlanService,
        StudentViewDataService $studentViewDataService,
        TutorSourceProgressService $tutorSourceProgressService
    )
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
            return redirect()->route('courses.index')->with('error', 'Unauthorized access.');
        })->only(['showAddTopicForm', 'addTopic', 'updateTopicAfterQuiz']);

        $this->viewController = new TopicViewController();
        $this->actionController = new TopicActionController();
        $this->topicProgressService = $topicProgressService;
        $this->studyGamePlanService = $studyGamePlanService;
        $this->studentViewDataService = $studentViewDataService;
        $this->tutorSourceProgressService = $tutorSourceProgressService;
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
            $availableCourse = Course::query()->select('courses.id')->first();
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
            ->where('topics.course_id', $course->id)
            ->select([
                'topics.id',
                'topics.name',
                'topics.course_id',
                'topics.display_order',
                'topics.case_type',
                'topics.is_locked',
            ])
            ->orderBy('topics.display_order')
            ->with([
                'userProgress' => function($query) use ($user) {
                    $query->where('user_topic_progress.user_id', $user->id);
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

        $topicIds = $topics->pluck('id')->map(fn ($id) => (int) $id)->all();
        $topicMistakeCounts = array_fill_keys($topicIds, 0);

        if (!empty($topicIds)) {
            $mistakeQuestions = Question::query()
                ->select(['questions.id', 'questions.topic_id', 'questions.extra_topic'])
                ->join('mistakes', 'mistakes.question_id', '=', 'questions.id')
                ->where('mistakes.user_id', $user->id)
                ->where(function ($query) use ($topicIds) {
                    foreach ($topicIds as $topicId) {
                        $query->orWhere('questions.topic_id', $topicId)
                            ->orWhereRaw('FIND_IN_SET(?, REPLACE(questions.extra_topic, " ", "")) > 0', [$topicId]);
                    }
                })
                ->distinct('questions.id')
                ->get();

            foreach ($mistakeQuestions as $mistakeQuestion) {
                $relatedTopicIds = [(int) $mistakeQuestion->topic_id];
                $extraTopicIds = array_filter(array_map('intval', explode(',', (string) ($mistakeQuestion->extra_topic ?? ''))));
                $relatedTopicIds = array_values(array_unique([...$relatedTopicIds, ...$extraTopicIds]));

                foreach ($relatedTopicIds as $relatedTopicId) {
                    if (array_key_exists($relatedTopicId, $topicMistakeCounts)) {
                        $topicMistakeCounts[$relatedTopicId]++;
                    }
                }
            }
        }

        $topics->each(function ($topic) use ($topicMistakeCounts) {
            $topic->mistakes_count = (int) ($topicMistakeCounts[$topic->id] ?? 0);
        });

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

        $topics = $topics->values();
        $topicProgressSegments = $topics
            ->take(10)
            ->values()
            ->map(function ($topic, $index) {
                $topicProgress = $topic->userProgress->first();
                $isMastered = $topicProgress && ($topicProgress->percentage_grade ?? 0) >= 80;

                return [
                    'id' => $topic->id,
                    'index' => $index,
                    'is_mastered' => $isMastered,
                ];
            });

        $masteredCount = $topics->filter(function ($topic) {
            $userProgress = $topic->userProgress->first();

            return $userProgress && ($userProgress->percentage_grade ?? 0) >= 80;
        })->count();

        $customCategoryIcons = [
            'homework' => 'fas fa-pencil-alt',
            'lab' => 'fas fa-microscope',
            'reading' => 'fas fa-book-open',
            'project' => 'fas fa-project-diagram',
            'assignment' => 'fas fa-tasks',
            'exercise' => 'fas fa-dumbbell',
            'tutorial' => 'fas fa-play-circle',
            'lecture' => 'fas fa-chalkboard-teacher',
            'exam' => 'fas fa-clipboard-check',
            'test' => 'fas fa-file-alt',
            'review' => 'fas fa-eye',
            'practice' => 'fas fa-repeat',
            'study' => 'fas fa-graduation-cap',
            'notes' => 'fas fa-sticky-note',
            'video' => 'fas fa-video',
            'audio' => 'fas fa-volume-up',
            'discussion' => 'fas fa-comments',
            'forum' => 'fas fa-comment-dots',
            'workshop' => 'fas fa-tools',
            'seminar' => 'fas fa-users',
        ];

        $topicCategoryOptions = $course->type === 'custom'
            ? $topics
                ->pluck('case_type')
                ->filter()
                ->unique()
                ->values()
                ->map(fn ($category) => [
                    'filter' => strtolower((string) $category),
                    'label' => ucfirst((string) $category),
                    'count' => $topics->where('case_type', $category)->count(),
                    'icon' => $customCategoryIcons[strtolower((string) $category)] ?? 'fas fa-tag',
                ])
            : collect([
                ['filter' => 'cases', 'label' => 'Cases', 'count' => $topics->where('case_type', 'cases')->count(), 'icon' => 'fas fa-file-alt'],
                ['filter' => 'practical', 'label' => 'Practical', 'count' => $topics->where('case_type', 'practical')->count(), 'icon' => 'fas fa-flask'],
                ['filter' => 'quiz', 'label' => 'Quiz', 'count' => $topics->where('case_type', 'quiz')->count(), 'icon' => 'fas fa-question-circle'],
            ])->filter(fn ($category) => $category['count'] > 0)->values();

        $resolvedPlannerSettings = $gamePlan['settings']['resolved_settings'] ?? [];
        $dailyProgress = $gamePlan['daily_progress'] ?? [];
        $topicsPageView = [
            'mastered_count' => $masteredCount,
            'mastered_percentage' => $topics->count() > 0 ? (int) round(($masteredCount / $topics->count()) * 100) : 0,
            'collapsed_task_dots' => collect($gamePlan['tasks'] ?? [])
                ->take(3)
                ->map(fn ($task) => !empty($task['completed']))
                ->pad(3, false)
                ->values()
                ->all(),
            'planner_focus_label' => ucwords(str_replace('_', ' ', (string) ($resolvedPlannerSettings['planner_focus'] ?? ($gamePlan['settings']['planner_focus'] ?? 'balanced')))),
            'tracked_minutes_today' => (int) ($dailyProgress['tracked_minutes'] ?? 0),
            'target_minutes_today' => (int) ($dailyProgress['target_minutes'] ?? ($resolvedPlannerSettings['allocated_minutes'] ?? 0)),
            'study_progress_percent' => (int) ($dailyProgress['progress_percent'] ?? 0),
            'planner_debug' => $gamePlan['debug'] ?? null,
            'can_view_planner_debug' => $user->isAdmin() || $user->isTutor(),
            'topic_progress_segments' => $topicProgressSegments,
            'category_options' => $topicCategoryOptions,
            'assignments_count' => $assignments->count(),
        ];

        $assignmentViewData = $assignments->mapWithKeys(function ($assignment) use ($topics, $user) {
            $topic = $topics->firstWhere('id', $assignment->topic_id);
            if (!$topic) {
                return [];
            }

            $isCompleted = $assignment->isCompletedByUser($user->id);
            $isLocked = $assignment->isLockedForUser($user->id);
            $status = $assignment->status_string;

            return [$assignment->id => [
                'topic' => $topic,
                'progress' => (float) ($assignment->user_progress['percentage'] ?? 0),
                'status' => $status,
                'quiz_url' => secure_url(route('quiz.start', ['course' => $topic->course_id, 'topic' => $topic->id, 'assignment_id' => $assignment->id], false)),
                'is_locked' => $isLocked,
                'is_completed' => $isCompleted,
                'display_state' => $isCompleted ? 'completed' : ($status === 'overdue' ? 'overdue' : 'active'),
            ]];
        });

        $topicCardData = $topics->mapWithKeys(function ($topic) {
            $userProgress = $topic->userProgress->first();
            $percentageGrade = $userProgress ? $userProgress->percentage_grade : null;
            $hasAttemptedTopic = $topic->last_attempt_date !== null || $userProgress?->last_attempt_date !== null;

            return [$topic->id => [
                'percentage_grade' => $percentageGrade,
                'has_attempted' => $hasAttemptedTopic,
                'quiz_url' => secure_url(route('quiz.start', ['course' => $topic->course_id, 'topic' => $topic->id], false)),
                'is_preview_locked' => (bool) ($topic->is_preview_locked ?? false),
            ]];
        });

        return view('topics_for_course', [
            'course' => $course,
            'topics' => $topics,
            'assignments' => $assignments,
            'availableCourses' => $availableCourses,
            'hasFullCourseAccess' => $hasFullCourseAccess,
            'previewTopicLimit' => $previewTopicLimit,
            'gamePlan' => $gamePlan,
            'topicsPageView' => $topicsPageView,
            'assignmentViewData' => $assignmentViewData,
            'topicCardData' => $topicCardData,
            'currentUserView' => $this->studentViewDataService->buildCurrentUserView($user),
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
                $query->whereIn('topics.course_id', $accessibleCourses->pluck('id'));
            })
            ->whereNotNull('questions.source')
            ->where('questions.source', '!=', '')
            ->selectRaw('questions.source, COUNT(*) as question_count')
            ->groupBy('questions.source')
            ->orderBy('questions.source')
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
            $modeProgress = $mode === 'quiz'
                ? QuizAttempt::getTopicProgress($userId, $topic->id, $mode)
                : null;
            $tutorProgressMap = $mode === 'tutor'
                ? $this->tutorSourceProgressService->getTopicProgressMap($userId, $topic)
                : [];

            // Format the response, ensuring each source has an entry even if no attempts
            $formattedProgress = [];
            $totalAvailable = 0;
            $totalCompleted = 0;

            foreach ($sourceCounts as $source => $count) {
                // SECURITY FIX: Sanitize source name to prevent XSS
                $sanitizedSource = htmlspecialchars($source, ENT_QUOTES, 'UTF-8');

                if ($mode === 'tutor') {
                    $modeData = $tutorProgressMap[$source] ?? null;
                    $attempted = (int) ($modeData['attempted'] ?? $modeData['completed'] ?? 0);
                    $correct = (int) ($modeData['correct'] ?? 0);
                    $remainingCount = (int) ($modeData['remaining_count'] ?? max(0, $count - $correct));

                    $formattedProgress[$sanitizedSource] = [
                        'completed' => $correct,
                        'attempted_count' => $attempted,
                        'correct' => $correct,
                        'total' => $count,
                        'total_questions_available' => $count,
                        'remaining_count' => $remainingCount,
                        'percentage' => $count > 0 ? round(($correct / $count) * 100, 2) : 0,
                        'accuracy_percentage' => $attempted > 0 ? round(($correct / $attempted) * 100, 2) : 0,
                        'has_attempts' => $attempted > 0,
                        'can_resume_remaining' => $remainingCount > 0,
                    ];

                    $totalCompleted += $correct;
                } else {
                    $modeData = $modeProgress['sources'][$source] ?? null;
                    $completed = $modeData ? $modeData['score'] : 0;
                    $attempted = $modeData ? $modeData['total'] : 0;

                    $formattedProgress[$sanitizedSource] = [
                        'completed' => $completed,
                        'total' => $attempted,
                        'total_questions_available' => $count,
                        'percentage' => $attempted > 0 ? round(($completed / $attempted) * 100, 2) : 0,
                        'has_attempts' => $attempted > 0
                    ];

                    $totalCompleted += $completed;
                }

                $totalAvailable += $count;
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

    /**
     * ✅ Search questions within a specific course - HIGHLY OPTIMIZED
     * Uses PHP-side fuzzy search on a cached payload to protect the DB.
     */
    public function searchCourseQuestions(Course $course, Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user || !$user->hasFullAccessToCourse($course)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search is unavailable for preview courses.',
                ], 403);
            }

            $query = trim((string) $request->input('query', ''));
            if (strlen($query) < 2) {
                return response()->json(['success' => true, 'questions' => []]);
            }

            $topicNames = \App\Models\Topic::where('course_id', $course->id)->pluck('name', 'id')->toArray();
            $courseTopicIds = array_map('intval', array_keys($topicNames));

            if (empty($courseTopicIds)) {
                return response()->json(['success' => true, 'questions' => []]);
            }

            // 🚀 SMART CACHE LAYER: Load only this course's question data with cross-field blobs
            $questions = Cache::remember("course_{$course->id}_questions_search_payload_v9", 86400, function() use ($courseTopicIds, $topicNames) {
                return Question::query()
                    ->whereIn('topic_id', $courseTopicIds)
                    ->select('id', 'question', 'correct_answer', 'explanation', 'topic_id', 'extra_topic', 'question_type', 'option_a', 'option_b', 'option_c', 'option_d')
                    ->get()
                    ->map(function($q) use ($topicNames, $courseTopicIds) {
                        $courseTopicIdLookup = array_flip($courseTopicIds);

                        $cleanQ = strip_tags($q->question);
                        $cleanE = strip_tags($q->explanation ?? '');
                        
                        // Resolve Correct Answer text
                        $actualAnswer = trim($q->correct_answer ?? '');
                        if ($q->question_type === 'multiple_choice') {
                            $letter = strtoupper(trim($q->correct_answer));
                            $optionField = 'option_' . strtolower($letter);
                            if (isset($q->{$optionField})) {
                                $actualAnswer = $letter . ': ' . $q->{$optionField};
                            }
                        }

                        // Create a normalized search blob for cross-field intelligence
                        $searchBlob = strtolower(preg_replace('/[^a-z0-9 ]/', '', $cleanQ . ' ' . $actualAnswer . ' ' . $cleanE));

                        $associatedTopicIds = collect([(int) $q->topic_id]);
                        if (!empty($q->extra_topic)) {
                            $associatedTopicIds = $associatedTopicIds->concat(
                                collect(explode(',', $q->extra_topic))
                                    ->map(fn ($id) => (int) trim($id))
                                    ->filter(fn ($id) => isset($courseTopicIdLookup[$id]))
                            );
                        }
                        
                        $topicTitles = $associatedTopicIds->unique()
                            ->map(fn($id) => $topicNames[$id] ?? null)
                            ->filter()
                            ->values()
                            ->toArray();

                        return [
                            'id' => $q->id,
                            'question' => $cleanQ,
                            'correct_answer' => $actualAnswer,
                            'explanation' => $cleanE ?: 'No explanation available.',
                            'question_type' => $q->question_type ?? 'multiple_choice',
                            'raw_correct_answer' => $q->correct_answer,
                            'alternative_answers' => $q->alternative_answers,
                            'option_a' => $q->option_a,
                            'option_b' => $q->option_b,
                            'option_c' => $q->option_c,
                            'option_d' => $q->option_d,
                            'topic_titles' => $topicTitles,
                            'search_blob' => $searchBlob,
                            'normalized_question' => strtolower(preg_replace('/[^a-z0-9 ]/', '', $cleanQ))
                        ];
                    });
            });

            // 🚀 SMART SEARCH ENGINE: Weighted Scoring Logic
            $queryClean = strtolower(preg_replace('/[^a-z0-9 ]/', '', $query));
            $queryWords = array_filter(explode(' ', $queryClean), fn($w) => strlen($w) > 1);

            if (empty($queryWords)) {
                return response()->json(['success' => true, 'questions' => []]);
            }

            $currentMistakeIds = Mistake::where('user_id', auth()->id())->pluck('question_id')->toArray();

            $results = $questions->map(function($q) use ($queryWords, $queryClean) {
                $score = 0;
                $blob = $q['search_blob'];
                $qText = $q['normalized_question'];

                // 1. Exact Phrase Priority (Highest)
                if (str_contains($qText, $queryClean)) {
                    $score += 1000;
                } elseif (str_contains($blob, $queryClean)) {
                    $score += 500;
                }

                // 2. Individual Word Weighting
                foreach ($queryWords as $word) {
                    $isMedicalTerm = strlen($word) >= 5;
                    $weight = $isMedicalTerm ? 3 : 1;

                    // Word in Question
                    if (str_contains($qText, $word)) {
                        $score += (50 * $weight);
                    } 
                    // Word in Answer/Explanation
                    elseif (str_contains($blob, $word)) {
                        $score += (15 * $weight);
                    }
                    
                    // Prefix matching (smarter partials)
                    if (str_starts_with($qText, $word) || str_contains($qText, ' ' . $word)) {
                        $score += (20 * $weight);
                    }
                }

                $q['relevance_score'] = $score;
                return $q;
            })
            ->filter(fn($q) => $q['relevance_score'] > 0)
            ->sortByDesc('relevance_score')
            ->take(15)
            ->values()
            ->map(function($q) use ($currentMistakeIds) {
                $q['is_already_mistake'] = in_array($q['id'], $currentMistakeIds);
                // Clean up payload before sending to frontend
                unset($q['search_blob']);
                unset($q['normalized_question']);
                return $q;
            });

            return response()->json([
                'success' => true,
                'questions' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Search questions error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Search failed.'], 500);
        }
    }

    /**
     * ✅ Add a question to student mistakes
     */
    public function addMistake(Question $question)
    {
        try {
            $user = auth()->user();
            
            // Safety check: is question already a mistake?
            $exists = Mistake::where('user_id', $user->id)->where('question_id', $question->id)->exists();
            if ($exists) {
                return response()->json([
                    'success' => true,
                    'message' => 'Question is already in your mistakes!'
                ]);
            }

            // Record as a failure (custom submission text to indicate manual bookmarking)
            Mistake::recordFailure($user->id, $question, "Bookmarked via Search");

            return response()->json([
                'success' => true,
                'message' => 'Question added to your mistakes!'
            ]);
        } catch (\Exception $e) {
            Log::error('Add mistake error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to add question.'], 500);
        }
    }
}
