<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\LiveUserMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatusController extends Controller
{
    private LiveUserMetricsService $liveUserMetricsService;

    public function __construct(LiveUserMetricsService $liveUserMetricsService)
    {
        $this->liveUserMetricsService = $liveUserMetricsService;
    }

    public function checkEnrollmentStatus(Request $request): JsonResponse
    {
        $courseIds = array_filter((array) $request->input('course_ids', []));
        $userId = auth()->id();

        if (!$userId || empty($courseIds)) {
            return response()->json(['success' => false]);
        }

        $enrollments = DB::table('course_user')
            ->where('user_id', $userId)
            ->whereIn('course_id', $courseIds)
            ->whereNotNull('enrolled_at')
            ->pluck('enrolled_at', 'course_id')
            ->toArray();

        $changes = array_map(static function ($courseId) use ($enrollments): array {
            return [
                'course_id' => (int) $courseId,
                'is_enrolled' => isset($enrollments[$courseId]),
            ];
        }, $courseIds);

        return response()->json([
            'success' => true,
            'enrollment_changes' => array_values($changes),
        ]);
    }

    public function checkPaymentStatus(Request $request): JsonResponse
    {
        $courseIds = array_filter((array) $request->input('course_ids', []));
        $userId = auth()->id();

        if (!$userId || empty($courseIds)) {
            return response()->json(['success' => false]);
        }

        $latestStatuses = Payment::where('user_id', $userId)
            ->whereIn('course_id', $courseIds)
            ->orderByDesc('id')
            ->get(['course_id', 'status'])
            ->unique('course_id')
            ->values();

        $completedCourses = [];
        $pendingCourses = [];
        $rejectedCourses = [];

        foreach ($latestStatuses as $payment) {
            $courseId = (int) $payment->course_id;

            if ($payment->status === 'completed') {
                $completedCourses[] = $courseId;
                continue;
            }

            if (in_array($payment->status, ['pending', 'pending_verification'], true)) {
                $pendingCourses[] = $courseId;
                continue;
            }

            if (in_array($payment->status, ['rejected', 'failed', 'expired'], true)) {
                $rejectedCourses[] = $courseId;
            }
        }

        return response()->json([
            'success' => true,
            'completed_courses' => $completedCourses,
            'pending_courses' => $pendingCourses,
            'rejected_courses' => $rejectedCourses,
        ]);
    }

    public function activeUsers(): JsonResponse
    {
        try {
            return response()->json($this->liveUserMetricsService->getSnapshot());
        } catch (\Throwable) {
            return response()->json([
                'count' => 1,
                'visitors' => 0,
                'users' => 1,
                'fake_mode' => false,
            ]);
        }
    }
}
