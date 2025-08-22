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

            Log::info('Admin status toggled', [
                'user_id' => $user->id,
                'new_role' => $newRole,
                'role_name' => $roleName,
                'admin_id' => $admin->id
            ]);

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
                ->whereNotNull('enrolled_at')
                ->count();

            // PERFORMANCE OPTIMIZATION: Get users with pagination
            $users = User::select('id', 'name', 'email', 'role')
                ->orderBy('name', 'asc')
                ->paginate($perPage);

            // PERFORMANCE OPTIMIZATION: Get all courses efficiently
            $courses = Course::select('id', 'name')
                ->orderBy('name', 'asc')
                ->get();

            // PERFORMANCE OPTIMIZATION: Get enrolled users for current page only with single query
            $userIds = $users->pluck('id');
            if ($userIds->isNotEmpty()) {
                // Single optimized query to get all enrolled users for the current page
                $enrolledUsersData = DB::table('course_user')
                    ->join('users', 'course_user.user_id', '=', 'users.id')
                    ->join('courses', 'course_user.course_id', '=', 'courses.id')
                    ->whereIn('course_user.user_id', $userIds)
                    ->whereNotNull('course_user.enrolled_at') // Only active enrollments
                    ->select(
                        'users.id as user_id',
                        'users.name',
                        'users.email',
                        'courses.name as course_name',
                        'course_user.enrolled_at as created_at',
                        'course_user.course_id'
                    )
                    ->orderBy('course_user.enrolled_at', 'desc')
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
                        'is_enrolled' => true // If enrolled_at is not null, user is enrolled
                    ];
                });
            } else {
                $enrolledUsers = collect();
            }

            // PERFORMANCE OPTIMIZATION: Build user stats array from single query result
            $userStats = [
                'total_users' => (int) $userStatsQuery->total_users,
                'student_users' => (int) $userStatsQuery->student_users,
                'admin_users' => (int) $userStatsQuery->admin_users,
                'tutor_users' => (int) $userStatsQuery->tutor_users,
                'active_users' => $activeUsersCount,
                'total_enrollments' => $totalEnrollments,
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
                          ->withPivot('enrolled_at')
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
                    'is_enrolled' => !is_null($course->pivot->enrolled_at),
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
        if (is_null($pivot->enrolled_at)) {
            return 'Never Enrolled';
        }

        return 'Currently Enrolled';
    }

    /**
     * Search for users by a query string.
     *
     * @param string $query
     * @return array
     */
    public function searchUsers(string $query): array
    {
        if (strlen($query) < 2) {
            return ['success' => false, 'message' => 'Search query must be at least 2 characters.'];
        }

        try {
            $users = User::where('name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->select('id', 'name', 'email', 'role')
                ->limit(50)
                ->get();

            return ['success' => true, 'users' => $users];
        } catch (\Exception $e) {
            Log::error('User search failed in service: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to search for users.'];
        }
    }
}
