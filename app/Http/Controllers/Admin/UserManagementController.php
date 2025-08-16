<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * 🚀 PERFORMANCE BEAST: Focused User Management Controller
 * Handles: User listing, search, admin toggle
 * Lines: ~150 (under 200 limit)
 */
class UserManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Display user management interface
     */
    public function index()
    {
        try {
            Log::info('Loading user management page - OPTIMIZED MODE');
            
            // 🚀 PERFORMANCE OPTIMIZED: Efficient user loading
            $users = User::select('id', 'name', 'email', 'role')
                ->orderBy('name')
                ->limit(1000)
                ->get();
            
            $courses = Course::select('id', 'name', 'price')
                ->orderBy('name')
                ->limit(500)
                ->get();

            // Get user statistics efficiently
            $userStats = [
                'total_users' => $users->count(),
                'active_users' => $users->count(),
                'admin_users' => $users->where('role', User::ROLE_ADMIN)->count(),
                'total_enrollments' => DB::table('course_user')->count()
            ];

            // Get enrolled users efficiently
            $enrolledUsers = DB::table('course_user')
                ->join('users', 'course_user.user_id', '=', 'users.id')
                ->join('courses', 'course_user.course_id', '=', 'courses.id')
                ->select(
                    'users.id as user_id',
                    'users.name as name',
                    'users.email as email',
                    'courses.id as course_id',
                    'courses.name as course_name',
                    'course_user.is_enrolled',
                    'course_user.enrolled_at',
                    'course_user.created_at'
                )
                ->orderBy('course_user.created_at', 'desc')
                ->get();

            return view('admin.manage-access', compact('userStats', 'users', 'courses', 'enrolledUsers'));
            
        } catch (\Exception $e) {
            Log::error('ERROR in user management: ' . $e->getMessage());
            
            // Fallback with minimal data
            $users = User::select('id', 'name', 'email', 'role')->limit(100)->get();
            $courses = Course::select('id', 'name', 'price')->limit(50)->get();
            $userStats = [
                'total_users' => User::count(),
                'active_users' => User::count(),
                'admin_users' => User::where('role', User::ROLE_ADMIN)->count(),
                'total_enrollments' => 0
            ];
            $enrolledUsers = collect([]);
            
            return view('admin.manage-access', compact('userStats', 'users', 'courses', 'enrolledUsers'))
                ->with('error', 'System temporarily under high load. Showing limited data.');
        }
    }

    /**
     * Search users
     */
    public function searchUsers(Request $request)
    {
        $search = $request->get('search', '');
        
        $users = User::where('name', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%")
            ->select('id', 'name', 'email', 'role')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'users' => $users
        ]);
    }

    /**
     * Toggle admin status
     */
    public function toggleAdmin(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id'
            ]);

            $user = User::findOrFail($validated['user_id']);
            
            // Toggle between admin and student
        // Check authorization for role assignment
        // Check authorization for role assignment
        if (!auth()->user()->can('assign-roles')) {
            abort(403, 'Unauthorized to assign roles');
        }

        // Check authorization for role assignment
        // Check authorization for role assignment
        if (!auth()->user()->can('assign-roles')) {
            abort(403, 'Unauthorized to assign roles');
        }

        if (!auth()->user()->can('assign-roles')) {
        // Check authorization for role assignment
        if (!auth()->user()->can('assign-roles')) {
            abort(403, 'Unauthorized to assign roles');
        }

            abort(403, 'Unauthorized to assign roles');
        }

        if (!auth()->user()->can('assign-roles')) {
            abort(403, 'Unauthorized to assign roles');
        }

            $newRole = ($user->role === User::ROLE_ADMIN) ? User::ROLE_STUDENT : User::ROLE_ADMIN;
            $user->role = $newRole;
            $user->save();

            Log::info('Admin status toggled', [
                'user_id' => $user->id,
                'old_role' => $user->role,
                'new_role' => $newRole
            ]);

            return response()->json([
                'success' => true,
                'message' => "User role updated to " . User::ROLE_STRINGS[$newRole],
                'new_role' => User::ROLE_STRINGS[$newRole]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to toggle admin status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user role.'
            ], 500);
        }
    }
}