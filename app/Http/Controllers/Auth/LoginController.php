<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            // Update session to mark as not a visitor
            DB::table('sessions')
                ->where('id', $request->session()->getId())
                ->update(['is_visitor' => false]);

            // Auto-enroll in all free courses
            $user = Auth::user();
            try {
                $enrolledCount = $user->enrollInAllFreeCourses();
                if ($enrolledCount > 0) {
                    \Log::info('User auto-enrolled in free courses on login', [
                        'user_id' => $user->id,
                        'courses_enrolled' => $enrolledCount
                    ]);
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to auto-enroll user in free courses on login', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                // Continue with login even if auto-enrollment fails
            }

            return redirect()->intended('/courses');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}