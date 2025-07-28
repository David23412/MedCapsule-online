<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Course;

class UserCourseController extends Controller
{
    public function index()
    {
        // Get user statistics
        $userStats = [
            'total_users' => User::count(),
            'active_users' => User::where('last_active_at', '>=', now()->subDays(7))->count(),
            'admin_users' => User::where('role', 'admin')->count(),
            'total_enrollments' => \DB::table('course_user')->count()
        ];

        // Get users and courses for the form
        $users = User::orderBy('name')->get();
        $courses = Course::orderBy('name')->get();

        return view('admin.manage-access', compact('userStats', 'users', 'courses'));
    }

    public function enroll(Request $request)
    {
        // Implementation for enrollment
        return response()->json(['success' => true]);
    }

    public function searchUsers(Request $request)
    {
        $users = User::where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->limit(10)
                    ->get();
        
        return response()->json($users);
    }

    public function toggleAdmin(Request $request)
    {
        $user = User::findOrFail($request->user_id);
        $user->role = $user->role === 'admin' ? 'student' : 'admin';
        $user->save();
        
        return response()->json(['success' => true]);
    }

    public function updatePaymentSettings(Request $request)
    {
        // Implementation for payment settings
        return response()->json(['success' => true]);
    }
}