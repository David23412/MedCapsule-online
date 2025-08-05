<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use App\Services\SessionSecurityService;

class SingleSessionMiddleware
{
    protected $securityService;

    public function __construct(SessionSecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    /**
     * Handle an incoming request with advanced security checks.
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip for guests
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $currentSessionToken = Session::getId();

        // Track user activity for rate limiting
        $this->securityService->trackActivity($user->id, $request->ip());

        // If user doesn't have an active session token, set it
        if (!$user->active_session_token) {
            $user->update([
                'active_session_token' => $currentSessionToken,
                'session_created_at' => now(),
                'device_fingerprint' => $this->securityService->generateDeviceFingerprint($request),
                'last_login_ip' => $request->ip(),
                'last_user_agent' => $request->userAgent(),
                'last_security_check' => now()
            ]);
            return $next($request);
        }

        // Enhanced session validation with security checks
        $validation = $this->securityService->validateSessionSecurity($user, $request);

        if (!$validation['valid']) {
            // Log the security event
            $this->securityService->logSecurityEvent($user, 'security_violation', $request, [
                'risk_level' => 'high',
                'action_taken' => true,
                'reason' => $validation['reason']
            ]);

            // Handle different security actions
            switch ($validation['action']) {
                case 'logout':
                    return $this->handleLogout($request, $validation['reason']);
                
                case 'force_logout':
                    return $this->handleForceLogout($user, $request, $validation['reason']);
                
                case 'temporary_block':
                    return $this->handleTemporaryBlock($user, $request, $validation['reason']);
                
                default:
                    return $this->handleLogout($request, $validation['reason']);
            }
        }

        // Update security check timestamp periodically
        if ($user->last_security_check < now()->subMinutes(5)) {
            $user->update([
                'last_security_check' => now(),
                'last_login_ip' => $request->ip()
            ]);
        }

        return $next($request);
    }

    /**
     * Handle standard logout
     */
    private function handleLogout(Request $request, string $reason): \Illuminate\Http\RedirectResponse
    {
        Auth::logout();
        Session::invalidate();
        Session::regenerateToken();
        
        return redirect()->route('login')->with('error', $reason);
    }

    /**
     * Handle force logout with additional security measures
     */
    private function handleForceLogout($user, Request $request, string $reason): \Illuminate\Http\RedirectResponse
    {
        // Clear all session data
        $user->update([
            'active_session_token' => null,
            'session_created_at' => null,
            'last_suspicious_activity' => now()
        ]);

        // Increment suspicious activity counter
        $suspiciousKey = "suspicious_activity_{$user->id}";
        $count = Cache::get($suspiciousKey, 0);
        Cache::put($suspiciousKey, $count + 1, 3600); // 1 hour

        Auth::logout();
        Session::invalidate();
        Session::regenerateToken();
        
        return redirect()->route('login')->with('error', $reason . ' Please verify your identity.');
    }

    /**
     * Handle temporary block
     */
    private function handleTemporaryBlock($user, Request $request, string $reason): \Illuminate\Http\RedirectResponse
    {
        // Block user for 15 minutes
        $blockKey = "blocked_user_{$user->id}";
        Cache::put($blockKey, true, 900); // 15 minutes

        Auth::logout();
        Session::invalidate();
        Session::regenerateToken();
        
        return redirect()->route('login')->with('error', $reason . ' Please try again in 15 minutes.');
    }
}