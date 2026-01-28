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
     * FIXED: Auto-enroll ALL users in FREE courses, admins get ALL courses
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
            // FIXED: Admins get ALL courses, regular users get FREE courses only
            if ($user->isAdmin()) {
                $this->ensureAdminEnrolledInAllCourses($user, $courseIds);
            } else {
                $this->ensureUserEnrolledInFreeCourses($user, $courseIds);
            }
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
     * Ensure user is enrolled in FREE courses they're trying to access
     * FIXED: Only enroll in courses with price = 0 or NULL
     */
    private function ensureUserEnrolledInFreeCourses($user, array $courseIds): void
    {
        try {
            // Get ONLY FREE courses (price = 0 or NULL)
            $freeCourses = \App\Models\Course::whereIn('id', $courseIds)
                ->where(function($query) {
                    $query->where('price', 0)
                          ->orWhereNull('price');
                })
                ->pluck('id')
                ->toArray();

            if (empty($freeCourses)) {
                return; // No free courses to enroll
            }

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
                }

                Log::info('Auto-enrolled user in FREE courses', [
                    'user_id' => $user->id,
                    'user_role' => $user->role,
                    'courses_enrolled' => $missingCourses,
                    'count' => count($missingCourses)
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to auto-enroll user in free courses', [
                'user_id' => $user->id,
                'course_ids' => $courseIds,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Ensure ADMIN is enrolled in ALL courses (free and paid)
     * FIXED: Admins bypass payment for all courses
     */
    private function ensureAdminEnrolledInAllCourses($user, array $courseIds): void
    {
        try {
            // Admins get access to ALL courses
            $allCourses = \App\Models\Course::whereIn('id', $courseIds)
                ->pluck('id')
                ->toArray();

            if (empty($allCourses)) {
                return;
            }

            // Check which courses admin is not enrolled in
            $enrolledCourses = \Illuminate\Support\Facades\DB::table('course_user')
                ->where('user_id', $user->id)
                ->whereIn('course_id', $allCourses)
                ->where('is_enrolled', true)
                ->pluck('course_id')
                ->toArray();

            $missingCourses = array_diff($allCourses, $enrolledCourses);

            if (!empty($missingCourses)) {
                // Auto-enroll admin in ALL courses
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
                }

                Log::info('Auto-enrolled ADMIN in ALL courses', [
                    'user_id' => $user->id,
                    'courses_enrolled' => $missingCourses,
                    'count' => count($missingCourses)
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to auto-enroll admin in courses', [
                'user_id' => $user->id,
                'course_ids' => $courseIds,
                'error' => $e->getMessage()
            ]);
        }
    }
}
