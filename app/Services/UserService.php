<?php

namespace App\Services;

use App\Models\User;
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
            $newRole = $user->role === 'admin' ? 'user' : 'admin';
            $user->role = $newRole;
            $user->save();

            Log::info('Admin status toggled', [
                'user_id' => $user->id,
                'new_role' => $newRole,
                'admin_id' => $admin->id
            ]);

            return [
                'success' => true,
                'message' => "{$user->name}'s role has been updated to {$newRole}.",
                'new_role' => $newRole
            ];
        } catch (\Exception $e) {
            Log::error('Admin toggle failed in service: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update admin status.'];
        }
    }

    /**
     * Get data for the manage access page.
     *
     * @param int $perPage
     * @return array
     */
    public function getManageAccessPageData(int $perPage = 15): array
    {
        try {
            $users = User::select('id', 'name', 'email', 'role')
                ->orderBy('name', 'asc')
                ->paginate($perPage);

            $userIds = $users->pluck('id');

            $enrolledUsers = DB::table('course_user')
                ->join('courses', 'course_user.course_id', '=', 'courses.id')
                ->whereIn('course_user.user_id', $userIds)
                ->whereNotNull('course_user.enrolled_at')
                ->select('course_user.user_id', 'courses.id as course_id', 'courses.name as course_name')
                ->get()
                ->groupBy('user_id');

            $courses = Course::select('id', 'name')->orderBy('name', 'asc')->get();

            $userStats = [
                'total_users' => User::count(),
                'student_users' => User::where('role', 'user')->count(),
                'admin_users' => User::where('role', 'admin')->count(),
                'total_enrollments' => DB::table('course_user')->whereNotNull('enrolled_at')->count(),
            ];

            return [
                'success' => true,
                'users' => $users,
                'courses' => $courses,
                'enrolledUsers' => $enrolledUsers,
                'userStats' => $userStats,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to load manage access page data: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load page data.'];
        }
    }

    /**
     * Get courses for a specific user.
     *
     * @param int $userId
     * @return array
     */
    public function getUserCourses(int $userId): array
    {
        try {
            $user = User::findOrFail($userId);
            $courses = $user->courses()->select('id', 'name', 'created_at')->get();

            return ['success' => true, 'courses' => $courses];
        } catch (\Exception $e) {
            Log::error('User courses fetch failed in service: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to fetch user courses.'];
        }
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
