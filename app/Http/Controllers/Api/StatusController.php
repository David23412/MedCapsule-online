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

        $completedCourses = Payment::where('user_id', $userId)
            ->whereIn('course_id', $courseIds)
            ->where('status', 'completed')
            ->pluck('course_id')
            ->map(static fn ($courseId) => (int) $courseId)
            ->all();

        return response()->json([
            'success' => true,
            'completed_courses' => $completedCourses,
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
