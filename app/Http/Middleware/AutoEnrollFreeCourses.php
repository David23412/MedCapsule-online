<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AutoEnrollFreeCourses
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Only process for authenticated users
        if (!$user) {
            return $next($request);
        }

        // Extract course/topic IDs from the request
        $courseIds = $this->extractCourseIdsFromRequest($request);

        if (!empty($courseIds)) {
            $this->ensureUserEnrolledInFreeCourses($user, $courseIds);
        }

        return $next($request);
    }

    /**
     * Extract course IDs from the request URL and parameters
     */
    private function extractCourseIdsFromRequest(Request $request): array
    {
        $courseIds = [];

        // Extract from route parameters
        if ($request->route()) {
            $courseId = $request->route('course');
            if ($courseId && is_numeric($courseId)) {
                $courseIds[] = (int) $courseId;
            }
        }

        // Extract from query parameters
        if ($request->has('course_ids')) {
            $queryCourseIds = $request->input('course_ids');
            if (is_array($queryCourseIds)) {
                $courseIds = array_merge($courseIds, array_map('intval', $queryCourseIds));
            } elseif (is_numeric($queryCourseIds)) {
                $courseIds[] = (int) $queryCourseIds;
            }
        }

        // Extract from request body (for API requests)
        if ($request->has('course_ids') && $request->isJson()) {
            $bodyCourseIds = $request->input('course_ids');
            if (is_array($bodyCourseIds)) {
                $courseIds = array_merge($courseIds, array_map('intval', $bodyCourseIds));
            }
        }

        return array_unique(array_filter($courseIds));
    }

    /**
     * Ensure user is enrolled in free courses they're trying to access
     */
    private function ensureUserEnrolledInFreeCourses($user, array $courseIds): void
    {
        try {
            // 🔥 CRITICAL SECURITY FIX: Get courses that are CURRENTLY free and paid courses are blocked
            $freeCourses = \App\Models\Course::whereIn('id', $courseIds)
                ->where(function($query) {
                    $query->where('price', 0)
                          ->orWhereNull('price');
                })
                ->pluck('id')
                ->toArray();

            if (empty($freeCourses)) {
                return; // No free courses to check
            }

            // 🔥 DOUBLE-CHECK PAYMENT REQUIRED: Block any auto-enrollment for courses that require payment
            $paidCourses = \App\Models\Course::whereIn('id', $freeCourses)
                ->where(function($query) {
                    $query->where('price', '>', 0);
                })
                ->pluck('id')
                ->toArray();

            // Remove paid courses from enrollment list - THEY REQUIRE PAYMENT!
            $freeCourses = array_diff($freeCourses, $paidCourses);

            // Check which free courses user is not enrolled in
            $enrolledCourses = \Illuminate\Support\Facades\DB::table('course_user')
                ->where('user_id', $user->id)
                ->whereIn('course_id', $freeCourses)
                ->where('is_enrolled', true)
                ->pluck('course_id')
                ->toArray();

            $missingCourses = array_diff($freeCourses, $enrolledCourses);

            if (!empty($missingCourses)) {
                // Auto-enroll in missing free courses
                $enrolledCount = 0;
                foreach ($missingCourses as $courseId) {
                    \Illuminate\Support\Facades\DB::table('course_user')->updateOrInsert(
                        [
                            'user_id' => $user->id,
                            'course_id' => $courseId
                        ],
                        [
                            'is_enrolled' => true,
                            'enrolled_at' => now(),
                            'updated_at' => now()
                        ]
                    );
                    $enrolledCount++;
                }

                if ($enrolledCount > 0) {
                    Log::info('Auto-enrolled user in free courses via middleware', [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'courses_auto_enrolled' => $missingCourses,
                        'enrollment_count' => $enrolledCount,
                        'request_url' => request()->fullUrl()
                    ]);
                }
            }

        } catch (\Exception $e) {
            // Log error but don't block the request
            Log::error('Failed to auto-enroll user in free courses via middleware', [
                'user_id' => $user->id,
                'course_ids' => $courseIds,
                'error' => $e->getMessage()
            ]);
        }
    }
}
