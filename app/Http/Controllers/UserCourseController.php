<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Course;

class UserCourseController extends Controller
{
    public function index()
    {
        return view('admin.user-course');
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
        $user->is_admin = !$user->is_admin;
        $user->save();
        
        return response()->json(['success' => true]);
    }

    public function updatePaymentSettings(Request $request)
    {
        // Implementation for payment settings
        return response()->json(['success' => true]);
    }
}