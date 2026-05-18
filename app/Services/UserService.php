<?php

namespace App\Services;

use App\Models\User;
use App\Models\Course;
use App\Models\SessionSecurityLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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

            // Count of distinct users who have at least one active enrollment
            $enrolledUsersCount = DB::table('course_user')
                ->where('is_enrolled', true)
                ->distinct('user_id')
                ->count('user_id');

            // New users registered today
            $newUsersToday = DB::table('users')
                ->whereDate('created_at', today())
                ->count();

            // PERFORMANCE OPTIMIZATION: Get users with pagination
            $users = User::select('id', 'name', 'email', 'number', 'role', 'last_suspicious_activity')
                ->withExists('assignedTutors as is_mentored')
                ->orderBy('name', 'asc')
                ->paginate($perPage);
            $this->attachSecurityStatus($users->getCollection());

            // PERFORMANCE OPTIMIZATION: Get all courses efficiently
            $courses = Course::select('id', 'name')
                ->orderBy('name', 'asc')
                ->get();

            // PERFORMANCE OPTIMIZATION: Get ALL enrollments (both active and revoked) for recent activity
            $enrolledUsersData = DB::table('course_user')
                ->join('users', 'course_user.user_id', '=', 'users.id')
                ->join('courses', 'course_user.course_id', '=', 'courses.id')
                ->leftJoin('tutor_student_relationships', 'users.id', '=', 'tutor_student_relationships.user_id')
                ->select(
                    'users.id as user_id',
                    'users.name',
                    'users.email',
                    'courses.name as course_name',
                    'course_user.enrolled_at as created_at',
                    'course_user.course_id',
                    'course_user.is_enrolled',
                    'course_user.updated_at',
                    DB::raw('CASE WHEN tutor_student_relationships.id IS NOT NULL THEN 1 ELSE 0 END as is_mentored')
                )
                ->orderBy('course_user.updated_at', 'desc')
                ->limit(100) // Fetch more rows to group into ~50 unique users
                ->get();

            // Group enrollments by user: one row per user with all their courses
            $enrolledUsers = $enrolledUsersData->groupBy('user_id')->map(function ($userEnrollments) {
                $first = $userEnrollments->first();
                $courses = $userEnrollments->map(function ($e) {
                    return (object) [
                        'course_id' => $e->course_id,
                        'course_name' => $e->course_name,
                        'is_enrolled' => (bool) $e->is_enrolled,
                        'enrolled_at' => $e->created_at,
                    ];
                })->values()->all();

                $activeCount = collect($courses)->where('is_enrolled', true)->count();
                $revokedCount = collect($courses)->where('is_enrolled', false)->count();
                // Use most recent activity timestamp across all courses
                $lastActivity = $userEnrollments->max('updated_at');

                return (object) [
                    'user_id' => $first->user_id,
                    'name' => $first->name,
                    'email' => $first->email,
                    'courses' => $courses,
                    'active_count' => $activeCount,
                    'revoked_count' => $revokedCount,
                    'total_courses' => count($courses),
                    'last_activity' => $lastActivity,
                    'is_mentored' => (bool) $first->is_mentored,
                ];
            })->sortByDesc('last_activity')->values()->take(50);

            // ACCURATE per-course enrollment counts from the FULL database (not just recent 50)
            $courseStats = DB::table('course_user')
                ->select('course_id', DB::raw('SUM(CASE WHEN is_enrolled = 1 THEN 1 ELSE 0 END) as enrolled_count'))
                ->groupBy('course_id')
                ->pluck('enrolled_count', 'course_id')
                ->map(fn ($v) => (int) $v)
                ->all();

            // PERFORMANCE OPTIMIZATION: Build user stats array from single query result
            $userStats = [
                'total_users' => (int) $userStatsQuery->total_users,
                'student_users' => (int) $userStatsQuery->student_users,
                'admin_users' => (int) $userStatsQuery->admin_users,
                'tutor_users' => (int) $userStatsQuery->tutor_users,
                'active_users' => $activeUsersCount,
                'total_enrollments' => $totalEnrollments,
                'enrolled_users_count' => $enrolledUsersCount,
                'new_users_today' => $newUsersToday,
            ];

            return [
                'success' => true,
                'users' => $users,
                'courses' => $courses,
                'enrolledUsers' => $enrolledUsers,
                'courseStats' => $courseStats,
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
                $users = User::select('id', 'name', 'email', 'number', 'role', 'last_suspicious_activity')
                    ->withExists('assignedTutors as is_mentored')
                    ->orderBy('name', 'asc')
                    ->limit(100) // Limit to prevent overwhelming the dropdown
                    ->get();
            } else {
                $limit = 50;
                $prefixSearch = $query . '%';
                $containsSearch = '%' . $query . '%';

                $users = User::select('id', 'name', 'email', 'number', 'role', 'last_suspicious_activity')
                    ->withExists('assignedTutors as is_mentored')
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
                    $fallbackUsers = User::select('id', 'name', 'email', 'number', 'role', 'last_suspicious_activity')
                        ->withExists('assignedTutors as is_mentored')
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

            $this->attachSecurityStatus($users);

            return ['success' => true, 'users' => $users];
        } catch (\Exception $e) {
            Log::error('User search failed in service: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to search for users.'];
        }
    }

    public function getSecurityWatchlist(int $limit = 50): array
    {
        try {
            $latestLogIds = SessionSecurityLog::query()
                ->selectRaw('MAX(id) as id')
                ->whereIn('risk_level', ['high', 'critical'])
                ->where('created_at', '>=', now()->subDays(30))
                ->whereNotNull('user_id')
                ->groupBy('user_id')
                ->pluck('id');

            if ($latestLogIds->isEmpty()) {
                return ['success' => true, 'users' => []];
            }

            $logs = SessionSecurityLog::query()
                ->with('user:id,name,email,number,role,last_suspicious_activity')
                ->whereIn('id', $latestLogIds)
                ->latest()
                ->limit($limit)
                ->get();

            $counts = SessionSecurityLog::query()
                ->select('user_id', DB::raw('COUNT(*) as high_risk_count'))
                ->whereIn('risk_level', ['high', 'critical'])
                ->where('created_at', '>=', now()->subDays(30))
                ->whereIn('user_id', $logs->pluck('user_id')->filter()->all())
                ->groupBy('user_id')
                ->pluck('high_risk_count', 'user_id');

            $users = $logs->map(function (SessionSecurityLog $log) use ($counts) {
                $user = $log->user;
                if (!$user) {
                    return null;
                }

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'number' => $user->number,
                    'role' => $user->role,
                    'security_status' => $this->securityStatusForUser($user),
                    'last_event_type' => $log->event_type,
                    'last_risk_level' => $log->risk_level,
                    'last_event_at' => $log->created_at?->toIso8601String(),
                    'high_risk_count' => (int) ($counts[$user->id] ?? 0),
                ];
            })->filter()->values();

            return ['success' => true, 'users' => $users];
        } catch (\Throwable $e) {
            Log::error('Security watchlist fetch failed in service: ' . $e->getMessage());

            return ['success' => false, 'message' => 'Failed to load security watchlist.'];
        }
    }

    public function clearSecurityLock(User $user, User $admin): array
    {
        if ($user->id === $admin->id) {
            return ['success' => false, 'message' => 'You cannot clear your own security state from this panel.'];
        }

        foreach ([
            "blocked_user_{$user->id}",
            "account_locked_permanent_{$user->id}",
            "hard_lock_score_{$user->id}",
            "login_attempts_{$user->id}",
            "activity_count_{$user->id}",
            "recent_ips_{$user->id}",
        ] as $key) {
            Cache::forget($key);
        }

        $user->forceFill([
            'last_suspicious_activity' => null,
            'concurrent_login_attempts' => 0,
        ])->save();

        Log::info('Admin cleared user security lock state', [
            'admin_id' => $admin->id,
            'user_id' => $user->id,
        ]);

        return [
            'success' => true,
            'message' => "{$user->name}'s security lock state has been cleared.",
            'security_status' => $this->securityStatusForUser($user->refresh()),
        ];
    }

    private function attachSecurityStatus(Collection $users): void
    {
        if ($users->isEmpty()) {
            return;
        }

        $latestLogs = SessionSecurityLog::query()
            ->select('user_id', DB::raw('MAX(created_at) as last_high_risk_at'))
            ->whereIn('risk_level', ['high', 'critical'])
            ->where('created_at', '>=', now()->subDays(30))
            ->whereIn('user_id', $users->pluck('id')->all())
            ->groupBy('user_id')
            ->pluck('last_high_risk_at', 'user_id');

        $users->each(function (User $user) use ($latestLogs) {
            $user->setAttribute('security_status', $this->securityStatusForUser($user, $latestLogs[$user->id] ?? null));
        });
    }

    private function securityStatusForUser(User $user, ?string $lastHighRiskAt = null): array
    {
        $isTemporaryBlocked = Cache::has("blocked_user_{$user->id}");
        $isPermanentlyLocked = Cache::has("account_locked_permanent_{$user->id}");
        $hardLockScore = (int) Cache::get("hard_lock_score_{$user->id}", 0);
        $loginAttempts = (int) Cache::get("login_attempts_{$user->id}", 0);
        $lastSuspiciousAt = $lastHighRiskAt ?: $user->last_suspicious_activity?->toIso8601String();
        $isSuspicious = $isPermanentlyLocked || $isTemporaryBlocked || $hardLockScore > 0 || $lastSuspiciousAt !== null;

        return [
            'is_temporary_blocked' => $isTemporaryBlocked,
            'is_permanently_locked' => $isPermanentlyLocked,
            'is_blocked' => $isTemporaryBlocked || $isPermanentlyLocked,
            'is_suspicious' => $isSuspicious,
            'hard_lock_score' => $hardLockScore,
            'login_attempts' => $loginAttempts,
            'last_suspicious_at' => $lastSuspiciousAt,
            'label' => $isPermanentlyLocked
                ? 'Permanently locked'
                : ($isTemporaryBlocked ? 'Temporarily blocked' : ($isSuspicious ? 'Suspicious' : 'Clear')),
        ];
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
                ->with(['latestQuizAttempt' => function ($q) {
                    $q->select('quiz_attempts.id', 'quiz_attempts.user_id', 'quiz_attempts.topic_id', 'quiz_attempts.study_mode', 'quiz_attempts.score', 'quiz_attempts.total_questions', 'quiz_attempts.percentage_grade', 'quiz_attempts.created_at')
                      ->with('topic:id,name');
                }])
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

                // Build specific last-active label
                $lastActiveHuman = 'Never';
                if ($lastActive) {
                    $diffMinutes = $lastActive->diffInMinutes($now);
                    if ($diffMinutes < 1) {
                        $lastActiveHuman = 'Just now';
                    } elseif ($diffMinutes < 60) {
                        $lastActiveHuman = (int) $diffMinutes . 'm ago';
                    } elseif ($lastActive->isToday()) {
                        $lastActiveHuman = 'Today at ' . $lastActive->format('g:i A');
                    } elseif ($lastActive->isYesterday()) {
                        $lastActiveHuman = 'Yesterday at ' . $lastActive->format('g:i A');
                    } else {
                        $lastActiveHuman = $lastActive->format('M j, Y \a\t g:i A');
                    }
                }

                // Build last quiz summary (lightweight)
                $lastQuiz = null;
                $attempt = $user->latestQuizAttempt;
                if ($attempt) {
                    $lastQuiz = [
                        'topic'      => $attempt->topic->name ?? 'Unknown',
                        'mode'       => $attempt->study_mode ?? 'quiz',
                        'score'      => $attempt->score,
                        'total'      => $attempt->total_questions,
                        'pct'        => round($attempt->percentage_grade, 1),
                        'taken_at'   => $attempt->created_at?->toIso8601String(),
                    ];
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
                    'last_active_human'=> $lastActiveHuman,
                    'created_at'       => $user->created_at?->toIso8601String(),
                    'status'           => $status,
                    'last_quiz'        => $lastQuiz,
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
