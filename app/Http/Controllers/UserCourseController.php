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
use App\Services\PaymentService;
use App\Services\UserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\UpdatePaymentSettingsRequest;
use Illuminate\Http\Request;

class UserCourseController extends Controller
{
        protected $userCourseService;
    protected $paymentService;
    protected $userService;

    public function __construct(UserCourseService $userCourseService, PaymentService $paymentService, UserService $userService)
    {
        $this->userCourseService = $userCourseService;
        $this->paymentService = $paymentService;
        $this->userService = $userService;
    }

    public function index()
    {
        $data = $this->userService->getManageAccessPageData(15); // Default 15 users per page

        if (!$data['success']) {
            return view('admin.manage-access')->with('error', $data['message']);
        }

        return view('admin.manage-access', [
            'users' => $data['users'],
            'courses' => $data['courses'],
            'enrolledUsers' => $data['enrolledUsers'],
            'userStats' => $data['userStats'],
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

}
