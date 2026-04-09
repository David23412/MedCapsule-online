<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnrollRequest;
use App\Http\Requests\RevokeRequest;
use App\Http\Requests\GetPaymentsRequest;
use App\Http\Requests\ToggleAdminRequest;
use App\Http\Requests\SearchUsersRequest;
use App\Models\User;
use App\Models\Course;
use App\Services\UserCourseService;
use App\Services\FreeCourseSurveyService;
use App\Services\PaymentService;
use App\Services\UserService;
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

    public function __construct(UserCourseService $userCourseService, PaymentService $paymentService, UserService $userService, FreeCourseSurveyService $freeCourseSurveyService)
    {
        $this->userCourseService = $userCourseService;
        $this->paymentService = $paymentService;
        $this->userService = $userService;
        $this->freeCourseSurveyService = $freeCourseSurveyService;
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
}
