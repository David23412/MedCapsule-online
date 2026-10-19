<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use App\Services\SessionSecurityService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class LoginController extends Controller
{
    protected $securityService;

    public function __construct(SessionSecurityService $securityService)
    {
        // DON'T call parent::__construct() to avoid auth middleware
        $this->securityService = $securityService;
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        $validated = $request->validated();
        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

        // Check if user is temporarily blocked
        $email = $credentials['email'];
        $user = \App\Models\User::where('email', $email)->first();
        
        if ($user) {
            if (!$user->isAdmin()) {
                $blockKey = "blocked_user_{$user->id}";
                if (Cache::has($blockKey)) {
                    return back()->withErrors([
                        'email' => 'Account temporarily blocked due to suspicious activity. Please try again later.'
                    ])->withInput($request->only('email', 'remember'));
                }

                // Check if user is permanently locked (Hard Lock escalation)
                if ($this->securityService->isPermanentlyLocked($user->id)) {
                    return back()->withErrors([
                        'email' => 'This account has been permanently locked due to multiple security violations. Please contact support.'
                    ])->withInput($request->only('email', 'remember'));
                }

            }
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // CRITICAL FIX: Regenerate session to prevent fixation attacks
            $request->session()->regenerate();
            
            // CRITICAL FIX: Ensure session is saved immediately
            $request->session()->save();
            
            // Update optional session analytics without risking the login itself.
            $this->markCurrentSessionAsAuthenticated($request);

            $user = Auth::user();
            
            // Analyze login attempt for security risks
            $securityAnalysis = $this->securityService->analyzeLoginAttempt($user, $request);
            
            // Log the login event
            $this->securityService->logSecurityEvent($user, 'login', $request, $securityAnalysis);
            $this->securityService->clearLoginAttemptState($user->id);
            
            // Handle high-risk logins
            if ($securityAnalysis['risk_level'] === 'critical') {
                // Force logout and require additional verification
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                return redirect()->route('login')->with('error', 
                    'Suspicious login detected. Please verify your identity and try again.');
            }
            
            $this->securityService->handleNewLogin($user, $request);
            
            // CRITICAL FIX: Clear all user-related caches to ensure fresh state
            \Illuminate\Support\Facades\Cache::forget("home_nuclear_v5_auth_{$user->id}_" . now()->format('Y-m-d-H'));
            \Illuminate\Support\Facades\Cache::forget("home_nuclear_v5_guest_guest_" . now()->format('Y-m-d-H'));
            try {
                \Illuminate\Support\Facades\Cache::tags(['user_' . $user->id])->flush();
            } catch (\BadMethodCallException $e) {
                // Cache store does not support tagging, ignore
            }
            
            // CRITICAL FIX: Store authentication flag in session
            $request->session()->put('auth.user_id', $user->id);
            $request->session()->put('auth.logged_in_at', now()->timestamp);
            $request->session()->save();

            // 🔔 TRIGGER MEDCAPSULE WELCOME NOTIFICATION ON LOGIN
            try {
                \App\Services\NotificationTriggerService::onUserLogin($user);
            } catch (\Exception $e) {
                \Log::warning('Failed to send welcome notification on login', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                // Continue with login even if notification fails
            }

            return redirect()->intended(route('courses.index'));
        }

        // Log failed login attempt
        if ($user) {
            $this->securityService->recordFailedLoginAttempt($user->id);
            $this->securityService->logSecurityEvent($user, 'failed_login', $request, [
                'risk_level' => 'medium',
                'reason' => 'Invalid credentials'
            ]);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.'])->withInput($request->only('email', 'remember'));
    }

    public function logout(Request $request)
    {
        // Log logout event and clear session data
        if (Auth::check()) {
            $user = Auth::user();
            
            // Log the logout event
            $this->securityService->logSecurityEvent($user, 'logout', $request, [
                'risk_level' => 'low',
                'voluntary' => true
            ]);
            
            // Clear all security-related session data
            $user->update([
                'active_session_token' => null,
                'session_created_at' => null,
                'device_fingerprint' => null,
                'last_security_check' => null
            ]);
        }
        
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    private function markCurrentSessionAsAuthenticated(Request $request): void
    {
        try {
            if (
                config('session.driver') !== 'database' ||
                ! Schema::hasTable('sessions') ||
                ! Schema::hasColumn('sessions', 'is_visitor')
            ) {
                return;
            }

            DB::table('sessions')
                ->where('id', $request->session()->getId())
                ->update(['is_visitor' => false]);
        } catch (\Throwable $e) {
            Log::warning('Login session analytics update skipped', [
                'error' => $e->getMessage(),
                'session_id_prefix' => substr((string) $request->session()->getId(), 0, 8),
            ]);
        }
    }
}
