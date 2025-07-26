<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use App\Services\QuizCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Services\QuizPerformanceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

// TODO: Inject services in constructor
    // TODO: Inject services in constructor
    // TODO: Inject services in constructor
    class LoginController extends Controller
{
    public function __construct()
    {
        // DON'T call parent::__construct() to avoid auth middleware
        Log::info('Controller initialized', ['user_id' => auth()->id()]);
    }

    public function showLoginForm()
    {
        // Cache login form data for performance
        $cacheKey = QuizCacheService::generateCacheKey('login_form_data', ['timestamp' => now()->format('Y-m-d')]);
        $formData = Cache::remember($cacheKey, 86400, function () {
            return [
                'csrf_token' => csrf_token(),
                'app_name' => config('app.name'),
                'login_attempts_remaining' => 5
            ];
        });
        
        return view('auth.login', $formData);
    }

    public function login(\App\Http\Requests\LoginRequest $request)
    {
        $credentials = $request->validated();

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            // Update session to mark as not a visitor
            DB::table('sessions')
                ->where('id', $request->session()->getId())
                ->update(['is_visitor' => false]);

            // Auto-enroll in all free courses with eager loading
            $user = Auth::user()->load([
                'courses:id,name,price',
                'quizAttempts:id,user_id,score,created_at',
                'assignments:id,user_id,status,deadline'
            ]);
            
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
            'email' => 'The provided credentials do not match our records.'])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}