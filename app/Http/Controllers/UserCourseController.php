<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnrollRequest;
use App\Http\Requests\RevokeRequest;
use App\Http\Requests\GetPaymentsRequest;
use App\Http\Requests\ToggleAdminRequest;
use App\Http\Requests\SearchUsersRequest;
use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Services\UserCourseService;
use App\Services\FreeCourseSurveyService;
use App\Services\PaymentService;
use App\Services\UserService;
use App\Services\TopicLockingService;
use App\Services\Learning\SchedulerCalibrationCoverageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\UpdatePaymentSettingsRequest;
use Illuminate\Http\Request;

class UserCourseController extends Controller
{
    protected $userCourseService;
    protected $paymentService;
    protected $userService;
    protected $freeCourseSurveyService;
    protected $analyticsService;
    protected $topicLockingService;

    public function __construct(
        UserCourseService $userCourseService, 
        PaymentService $paymentService, 
        UserService $userService, 
        FreeCourseSurveyService $freeCourseSurveyService,
        \App\Services\StudentAnalyticsService $analyticsService,
        TopicLockingService $topicLockingService
    ) {
        $this->userCourseService = $userCourseService;
        $this->paymentService = $paymentService;
        $this->userService = $userService;
        $this->freeCourseSurveyService = $freeCourseSurveyService;
        $this->analyticsService = $analyticsService;
        $this->topicLockingService = $topicLockingService;
    }

    public function index()
    {
        $data = $this->userService->getManageAccessPageData(15); // Default 15 users per page
        $surveyInsights = $this->freeCourseSurveyService->adminInsights();
        $schedulerCalibration = app(SchedulerCalibrationCoverageService::class)->buildDashboard();

        $dbStatus = 'Online';
        try {
            \DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $dbStatus = 'Offline';
        }

        if (!$data['success']) {
            return view('admin.manage-access', [
                'surveyInsights' => $surveyInsights,
                'schedulerCalibration' => $schedulerCalibration,
                'dbStatus' => $dbStatus,
            ])->with('error', $data['message']);
        }

        return view('admin.manage-access', [
            'users' => $data['users'],
            'courses' => $data['courses'],
            'enrolledUsers' => $data['enrolledUsers'],
            'courseStats' => $data['courseStats'],
            'userStats' => $data['userStats'],
            'surveyInsights' => $surveyInsights,
            'schedulerCalibration' => $schedulerCalibration,
            'dbStatus' => $dbStatus,
        ]);
    }

    public function enroll(EnrollRequest $request)
    {
        $user = User::findOrFail($request->validated('user_id'));
        $course = Course::findOrFail($request->validated('course_id'));

        $result = $this->userCourseService->enrollUser($user, $course);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    public function revoke(RevokeRequest $request)
    {
        $user = User::findOrFail($request->validated('user_id'));
        $course = Course::findOrFail($request->validated('course_id'));

        $result = $this->userCourseService->revokeUserAccess($user, $course);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function courseSubjects(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
        ]);

        $subjects = Topic::query()
            ->where('course_id', $validated['course_id'])
            ->whereNotNull('subject')
            ->where('subject', '!=', '')
            ->selectRaw('subject, COUNT(*) as total_topics, SUM(CASE WHEN is_locked = 1 THEN 1 ELSE 0 END) as locked_topics')
            ->groupBy('subject')
            ->orderBy('subject')
            ->get()
            ->map(fn ($row) => [
                'subject' => (string) $row->subject,
                'total_topics' => (int) $row->total_topics,
                'locked_topics' => (int) $row->locked_topics,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'subjects' => $subjects,
        ]);
    }

    public function lockSubject(Request $request)
    {
        return $this->updateSubjectLockState($request, true);
    }

    public function unlockSubject(Request $request)
    {
        return $this->updateSubjectLockState($request, false);
    }

    private function updateSubjectLockState(Request $request, bool $locked)
    {
        $validated = $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'subject' => 'required|string|max:100',
        ]);

        try {
            $course = Course::findOrFail($validated['course_id']);
            $updatedCount = $locked
                ? $this->topicLockingService->lockSubject($course, $validated['subject'], auth()->user())
                : $this->topicLockingService->unlockSubject($course, $validated['subject'], auth()->user());

            return response()->json([
                'success' => true,
                'message' => ($locked ? 'Locked' : 'Unlocked') . " {$updatedCount} topic(s) for {$validated['subject']}.",
                'updated_count' => $updatedCount,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (\Throwable $e) {
            Log::error('Subject lock update failed', [
                'course_id' => $validated['course_id'] ?? null,
                'subject' => $validated['subject'] ?? null,
                'locked' => $locked,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update subject lock state.',
            ], 500);
        }
    }



    public function toggleMentorship(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'action' => 'required|in:grant,revoke'
        ]);

        $user = User::findOrFail($request->user_id);
        
        try {
            if ($request->action === 'grant') {
                // Check if already mentored
                $exists = DB::table('tutor_student_relationships')
                    ->where('user_id', $user->id)
                    ->exists();
                
                if (!$exists) {
                    // Assign the current admin as the mentor
                    DB::table('tutor_student_relationships')->insert([
                        'tutor_id' => auth()->id(),
                        'user_id' => $user->id,
                        'assigned_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
                
                return response()->json([
                    'success' => true,
                    'message' => "{$user->name} has been enrolled in the Mentorship Program."
                ]);
            } else {
                DB::table('tutor_student_relationships')
                    ->where('user_id', $user->id)
                    ->delete();
                
                return response()->json([
                    'success' => true,
                    'message' => "{$user->name} has been removed from the Mentorship Program."
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update mentorship status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function searchUsers(SearchUsersRequest $request)
    {
        // FIXED: Handle null query by converting to empty string
        $query = $request->validated('query') ?? '';
        $result = $this->userService->searchUsers($query);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function userCourses($userId)
    {
        $result = $this->userService->getUserCourses((int)$userId);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    public function toggleAdmin(ToggleAdminRequest $request)
    {
        $user = User::findOrFail($request->validated('user_id'));
        $result = $this->userService->toggleAdminRole($user, auth()->user());

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function securityUsers(Request $request)
    {
        $limit = min(100, max(1, (int) $request->input('limit', 50)));
        $result = $this->userService->getSecurityWatchlist($limit);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    public function clearSecurityLock(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $result = $this->userService->clearSecurityLock($user, auth()->user());

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function payments(GetPaymentsRequest $request)
    {
        try {
            $validated = $request->validated();
            $result = $this->paymentService->getPaginatedPayments(
                $validated['search'] ?? null,
                $validated['page'] ?? 1,
                $validated['per_page'] ?? 20
            );

            return response()->json($result, $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            \Log::error('Payments endpoint error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading payments.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updatePaymentSettings(UpdatePaymentSettingsRequest $request)
    {
        $result = $this->paymentService->updatePaymentSettings($request->validated('settings'));

        return response()->json($result, $result['success'] ? 200 : 500);
    }
    
    /**
     * Get enrollment data for AJAX refresh
     */
    public function getEnrollments(\Illuminate\Http\Request $request)
    {
        $result = $this->userCourseService->getEnrollmentDataForAjax();

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Get user enrollments for course dropdown
     * FIXED: Use UserService method instead of UserCourseService
     */
    public function getUserEnrollments(\Illuminate\Http\Request $request)
    {
        $userId = $request->input('user_id');

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'User ID is required.'], 400);
        }

        // FIXED: Use UserService which has the proper method
        $result = $this->userService->getCourseEnrollmentStatusForUser((int)$userId);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Paginated written (textarea) survey answers for admin AJAX.
     */
    public function surveyWrittenAnswers(Request $request)
    {
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(50, max(1, (int) $request->input('per_page', 20)));

        return response()->json($this->freeCourseSurveyService->getWrittenAnswers($page, $perPage));
    }

    /**
     * Paginated list of all student survey responses for admin AJAX.
     */
    public function surveyStudentResponses(Request $request)
    {
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(50, max(1, (int) $request->input('per_page', 25)));
        $search = $request->input('search');

        return response()->json($this->freeCourseSurveyService->getStudentResponses($page, $perPage, $search));
    }

    /**
     * Single student's full survey response detail for admin AJAX.
     */
    public function surveyStudentDetail(int $userId)
    {
        $detail = $this->freeCourseSurveyService->getStudentDetail($userId);

        if (!$detail) {
            return response()->json(['error' => 'No survey response found for this student.'], 404);
        }

        return response()->json($detail);
    }

    /**
     * Get paginated last-active users for admin AJAX.
     */
    public function getLastActiveUsers(Request $request)
    {
        $page    = max(1, (int) $request->input('page', 1));
        $perPage = min(50, max(1, (int) $request->input('per_page', 20)));
        $search  = $request->input('search');

        $result = $this->userService->getLastActiveUsers($page, $perPage, $search);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Return real-time system health metrics for the admin dashboard.
     */
    public function getSystemStats()
    {
        $result = $this->userService->getSystemStats();

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Get students in the mentorship program with their performance metrics for admin.
     */
    public function getMentorshipData()
    {
        try {
            // 1. Fetch Students who are in the mentorship program
            // Criteria: They must exist in the tutor_student_relationships table
            $students = User::where('role', User::ROLE_STUDENT)
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('tutor_student_relationships')
                        ->whereColumn('tutor_student_relationships.user_id', 'users.id');
                })
                ->with([
                    'quizAttempts' => function ($q) {
                        $q->select(['quiz_attempts.id', 'quiz_attempts.user_id', 'quiz_attempts.percentage_grade', 'quiz_attempts.created_at'])
                          ->orderBy('quiz_attempts.created_at', 'desc');
                    }
                ])
                ->get();

            // 2. Fetch recent planner outcomes for all these students
            $studentIds = $students->pluck('id');
            $allOutcomes = DB::table('planner_task_outcomes')
                ->whereIn('planner_task_outcomes.user_id', $studentIds)
                ->where('planner_task_outcomes.plan_date', '>=', now()->subDays(7))
                ->get()
                ->groupBy('user_id');

            // 3. Enhance students with analytics
            $enhancedStudents = $students->map(function ($student) use ($allOutcomes) {
                $outcomes = $allOutcomes->get($student->id, collect());
                return $this->analyticsService->enhanceStudentMetrics($student, $outcomes);
            });

            // 4. Calculate summary stats
            $summary = $this->analyticsService->getSummaryAnalytics($enhancedStudents);

            return response()->json([
                'success' => true,
                'summary' => $summary,
                'students' => $enhancedStudents
            ]);

        } catch (\Exception $e) {
            \Log::error('Mentorship data endpoint error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading mentorship data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
