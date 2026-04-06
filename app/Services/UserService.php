<?php

namespace App\Services;

use App\Models\User;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserService
{
    /**
     * Toggle the admin role for a user.
     *
     * @param User $user
     * @param User $admin
     * @return array
     */
    public function toggleAdminRole(User $user, User $admin): array
    {
        if ($user->id === $admin->id) {
            return ['success' => false, 'message' => 'Admins cannot change their own role.'];
        }

        try {
            $newRole = $user->isAdmin() ? User::ROLE_STUDENT : User::ROLE_ADMIN;
            $user->role = $newRole;
            $user->save();

            $roleName = $user->isAdmin() ? 'Admin' : 'Student';


            return [
                'success' => true,
                'message' => "{$user->name}'s role has been updated to {$roleName}.",
                'new_role' => $newRole,
                'role_name' => $roleName
            ];
        } catch (\Exception $e) {
            Log::error('Admin toggle failed in service: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update admin status.'];
        }
    }

    /**
     * Get data for the manage access page - PERFORMANCE MONSTER VERSION.
     * Optimized to eliminate N+1 queries and use efficient database operations.
     *
     * @param int $perPage
     * @return array
     */
    public function getManageAccessPageData(int $perPage = 15): array
    {
        try {
            // PERFORMANCE OPTIMIZATION: Single query to get all user stats
            $userStatsQuery = DB::table('users')
                ->selectRaw('
                    COUNT(*) as total_users,
                    COUNT(CASE WHEN role = ? THEN 1 END) as student_users,
                    COUNT(CASE WHEN role = ? THEN 1 END) as admin_users,
                    COUNT(CASE WHEN role = ? THEN 1 END) as tutor_users
                ', [User::ROLE_STUDENT, User::ROLE_ADMIN, User::ROLE_TUTOR])
                ->first();

            // PERFORMANCE OPTIMIZATION: Get active users count efficiently
            $fiveMinutesAgo = now()->subMinutes(5)->timestamp;
            $activeUsersCount = DB::table('sessions')
                ->where('last_activity', '>=', $fiveMinutesAgo)
                ->whereNotNull('user_id')
                ->distinct('user_id')
                ->count('user_id'); // More efficient than count()

            // PERFORMANCE OPTIMIZATION: Get total enrollments count
            $totalEnrollments = DB::table('course_user')
                ->where('is_enrolled', true)
                ->count();

            // New users registered today
            $newUsersToday = DB::table('users')
                ->whereDate('created_at', today())
                ->count();

            // PERFORMANCE OPTIMIZATION: Get users with pagination
            $users = User::select('id', 'name', 'email', 'number', 'role')
                ->orderBy('name', 'asc')
                ->paginate($perPage);

            // PERFORMANCE OPTIMIZATION: Get all courses efficiently
            $courses = Course::select('id', 'name')
                ->orderBy('name', 'asc')
                ->get();

            // PERFORMANCE OPTIMIZATION: Get ALL enrollments (both active and revoked) for recent activity
            $enrolledUsersData = DB::table('course_user')
                ->join('users', 'course_user.user_id', '=', 'users.id')
                ->join('courses', 'course_user.course_id', '=', 'courses.id')
                ->select(
                    'users.id as user_id',
                    'users.name',
                    'users.email',
                    'courses.name as course_name',
                    'course_user.enrolled_at as created_at',
                    'course_user.course_id',
                    'course_user.is_enrolled'
                )
                ->orderBy('course_user.updated_at', 'desc')
                ->limit(50) // Show last 50 enrollment activities
                ->get();

            // Transform to the exact structure expected by the blade template
            $enrolledUsers = $enrolledUsersData->map(function ($enrollment) {
                return (object) [
                    'user_id' => $enrollment->user_id,
                    'name' => $enrollment->name,
                    'email' => $enrollment->email,
                    'course_name' => $enrollment->course_name,
                    'course_id' => $enrollment->course_id,
                    'created_at' => $enrollment->created_at,
                    'is_enrolled' => (bool) $enrollment->is_enrolled
                ];
            });

            // PERFORMANCE OPTIMIZATION: Build user stats array from single query result
            $userStats = [
                'total_users' => (int) $userStatsQuery->total_users,
                'student_users' => (int) $userStatsQuery->student_users,
                'admin_users' => (int) $userStatsQuery->admin_users,
                'tutor_users' => (int) $userStatsQuery->tutor_users,
                'active_users' => $activeUsersCount,
                'total_enrollments' => $totalEnrollments,
                'new_users_today' => $newUsersToday,
            ];

            return [
                'success' => true,
                'users' => $users,
                'courses' => $courses,
                'enrolledUsers' => $enrolledUsers,
                'userStats' => $userStats,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to load manage access page data: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'message' => 'Failed to load page data.'];
        }
    }

    /**
     * Get courses for a specific user - ULTRA-OPTIMIZED
     * Eliminates N+1 queries with eager loading and efficient data retrieval
     *
     * @param int $userId
     * @return array
     */
    public function getUserCourses(int $userId): array
    {
        try {
            // 🔥 SINGLE QUERY WITH ALL RELATIONSHIPS EAGER LOADED 🔥
            $user = User::with([
                'courses' => function ($query) {
                    $query->select('courses.id', 'courses.name', 'courses.price', 'courses.created_at')
                          ->withPivot('is_enrolled')
                          ->orderBy('courses.name');
                }
            ])
            ->select('id', 'name')
            ->findOrFail($userId);

            // Format courses data with enrollment status
            $courses = $user->courses->map(function ($course) {
                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'price' => $course->price,
                    'created_at' => $course->created_at,
                    'enrolled_at' => $course->pivot->enrolled_at,
                    'is_enrolled' => $course->pivot->is_enrolled,
                    'status' => $this->getEnrollmentStatus($course->pivot)
                ];
            });

            return ['success' => true, 'courses' => $courses];
        } catch (\Exception $e) {
            Log::error('User courses fetch failed in service: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to fetch user courses.'];
        }
    }

    /**
     * Get enrollment status text from pivot data
     */
    private function getEnrollmentStatus($pivot): string
    {
        if (!$pivot->is_enrolled) {
            return 'Never Enrolled';
        }

        return 'Currently Enrolled';
    }

    /**
     * Search for users by a query string.
     * FIXED: Allow empty query to return all users
     *
     * @param string|null $query
     * @return array
     */
    public function searchUsers(?string $query = ''): array
    {
        try {
            // FIXED: Handle null or empty query
            $query = trim((string) ($query ?? ''));
            
            // If query is empty, return all users
            if ($query === '') {
                $users = User::select('id', 'name', 'email', 'number', 'role')
                    ->orderBy('name', 'asc')
                    ->limit(100) // Limit to prevent overwhelming the dropdown
                    ->get();
            } else {
                $limit = 50;
                $prefixSearch = $query . '%';
                $containsSearch = '%' . $query . '%';

                $users = User::select('id', 'name', 'email', 'number', 'role')
                    ->where(function ($builder) use ($prefixSearch) {
                        $builder->where('email', 'like', $prefixSearch)
                            ->orWhere('number', 'like', $prefixSearch)
                            ->orWhere('name', 'like', $prefixSearch);
                    })
                    ->orderByRaw(
                        "CASE
                            WHEN email = ? THEN 1
                            WHEN number = ? THEN 2
                            WHEN name = ? THEN 3
                            WHEN email LIKE ? THEN 4
                            WHEN number LIKE ? THEN 5
                            WHEN name LIKE ? THEN 6
                            ELSE 7
                        END",
                        [$query, $query, $query, $prefixSearch, $prefixSearch, $prefixSearch]
                    )
                    ->orderBy('name', 'asc')
                    ->limit($limit)
                    ->get();

                if ($users->count() < $limit) {
                    $fallbackUsers = User::select('id', 'name', 'email', 'number', 'role')
                        ->whereNotIn('id', $users->pluck('id'))
                        ->where(function ($builder) use ($containsSearch) {
                            $builder->where('name', 'like', $containsSearch)
                                ->orWhere('email', 'like', $containsSearch)
                                ->orWhere('number', 'like', $containsSearch);
                        })
                        ->orderBy('name', 'asc')
                        ->limit($limit - $users->count())
                        ->get();

                    $users = $users->concat($fallbackUsers)->values();
                }
            }

            return ['success' => true, 'users' => $users];
        } catch (\Exception $e) {
            Log::error('User search failed in service: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to search for users.'];
        }
    }

    /**
     * Get paginated list of users ordered by last activity for admin dashboard.
     *
     * @param int $page
     * @param int $perPage
     * @param string|null $search
     * @return array
     */
    public function getLastActiveUsers(int $page = 1, int $perPage = 20, ?string $search = null): array
    {
        try {
            $query = User::select('id', 'name', 'email', 'role', 'last_active_at', 'created_at', 'xp', 'level')
                ->orderByRaw('last_active_at IS NULL ASC')
                ->orderBy('last_active_at', 'desc');

            $search = trim((string) ($search ?? ''));
            if ($search !== '') {
                $like = '%' . $search . '%';
                $query->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                      ->orWhere('email', 'like', $like);
                });
            }

            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

            $users = $paginator->getCollection()->map(function (User $user) {
                $lastActive = $user->last_active_at;
                $now = now();

                if ($lastActive) {
                    $diffMinutes = $lastActive->diffInMinutes($now);
                    if ($diffMinutes < 5) {
                        $status = 'online';
                    } elseif ($diffMinutes < 60) {
                        $status = 'recent';
                    } elseif ($lastActive->isToday()) {
                        $status = 'today';
                    } elseif ($lastActive->gte($now->copy()->subDays(7))) {
                        $status = 'this_week';
                    } else {
                        $status = 'inactive';
                    }
                } else {
                    $status = 'never';
                }

                return [
                    'id'               => $user->id,
                    'name'             => $user->name,
                    'email'            => $user->email,
                    'role'             => $user->role,
                    'role_label'       => match ((int) $user->role) {
                        User::ROLE_ADMIN  => 'Admin',
                        User::ROLE_TUTOR  => 'Tutor',
                        default           => 'Student',
                    },
                    'xp'               => $user->xp ?? 0,
                    'level'            => $user->level ?? 1,
                    'last_active_at'   => $lastActive?->toIso8601String(),
                    'last_active_human'=> $lastActive ? $lastActive->diffForHumans() : 'Never',
                    'created_at'       => $user->created_at?->toIso8601String(),
                    'status'           => $status,
                ];
            });

            return [
                'success'      => true,
                'users'        => $users->values(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get last active users: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load user activity data.'];
        }
    }

    /**
     * Get course enrollment status for a specific user
     * FIXED: Removed duplicate select and fixed ambiguous column
     *
     * @param int $userId
     * @return array
     */
    public function getCourseEnrollmentStatusForUser(int $userId): array
    {
        try {
            // FIXED: Use selectRaw only, removed duplicate select()
            $courses = Course::leftJoin('course_user', function ($join) use ($userId) {
                    $join->on('courses.id', '=', 'course_user.course_id')
                         ->where('course_user.user_id', '=', $userId);
                })
                ->selectRaw('courses.id, courses.name, courses.price, COALESCE(course_user.is_enrolled, 0) as is_enrolled')
                ->orderBy('courses.name', 'asc')
                ->get()
                ->map(function ($course) {
                    return [
                        'id' => $course->id,
                        'name' => $course->name,
                        'price' => $course->price,
                        'is_enrolled' => (bool) $course->is_enrolled
                    ];
                });

            return ['success' => true, 'courses' => $courses];
        } catch (\Exception $e) {
            Log::error('Failed to get course enrollment status: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get course enrollment status.'];
        }
    }

    /**
     * Collect real-time system health metrics for the admin dashboard.
     * Every value comes from the actual server — no hardcoded/fake data.
     */
    public function getSystemStats(): array
    {
        try {
            $startTime = microtime(true);

            // Active sessions (users online right now — last 5 min)
            $fiveMinAgo = now()->subMinutes(5)->timestamp;
            $activeNow = DB::table('sessions')
                ->where('last_activity', '>=', $fiveMinAgo)
                ->whereNotNull('user_id')
                ->distinct('user_id')
                ->count('user_id');

            // Users registered today
            $newUsersToday = DB::table('users')
                ->whereDate('created_at', today())
                ->count();

            // Enrollments today
            $enrollmentsToday = DB::table('course_user')
                ->where('is_enrolled', true)
                ->whereDate('enrolled_at', today())
                ->count();

            // DB check
            $dbOnline = true;
            try { DB::connection()->getPdo(); } catch (\Throwable $e) { $dbOnline = false; }

            // Memory
            $memUsage   = memory_get_usage(true);
            $memPeak    = memory_get_peak_usage(true);

            // Server load (Linux / macOS)
            $load = function_exists('sys_getloadavg') ? sys_getloadavg() : null;

            // PHP & Laravel versions
            $phpVersion     = PHP_VERSION;
            $laravelVersion = app()->version();

            // Server uptime (Linux)
            $uptime = null;
            if (is_readable('/proc/uptime')) {
                $raw = trim((string) file_get_contents('/proc/uptime'));
                $secs = (int) explode(' ', $raw)[0];
                $days = intdiv($secs, 86400);
                $hours = intdiv($secs % 86400, 3600);
                $mins = intdiv($secs % 3600, 60);
                $uptime = ($days > 0 ? "{$days}d " : '') . "{$hours}h {$mins}m";
            }

            $responseMs = round((microtime(true) - $startTime) * 1000, 1);

            return [
                'success'           => true,
                'active_now'        => $activeNow,
                'new_users_today'   => $newUsersToday,
                'enrollments_today' => $enrollmentsToday,
                'db_online'         => $dbOnline,
                'memory_mb'         => round($memUsage / 1024 / 1024, 1),
                'memory_peak_mb'    => round($memPeak / 1024 / 1024, 1),
                'server_load'       => $load ? round($load[0], 2) : null,
                'php_version'       => $phpVersion,
                'laravel_version'   => $laravelVersion,
                'uptime'            => $uptime,
                'response_ms'       => $responseMs,
            ];
        } catch (\Exception $e) {
            Log::error('System stats failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load system stats.'];
        }
    }
}
