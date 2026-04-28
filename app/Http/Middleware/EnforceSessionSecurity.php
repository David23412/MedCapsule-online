<?php

namespace App\Http\Middleware;

use App\Services\SessionSecurityService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceSessionSecurity
{
    public function __construct(
        private SessionSecurityService $sessionSecurityService
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $validation = $this->sessionSecurityService->validateSessionSecurity($user, $request);

        if (!($validation['valid'] ?? false)) {
            $this->sessionSecurityService->logSecurityEvent($user, 'session_blocked', $request, [
                'risk_level' => 'high',
                'action_taken' => true,
                'reason' => $validation['reason'] ?? 'Session blocked',
                'action' => $validation['action'] ?? 'logout',
            ]);

            if (($validation['action'] ?? null) === 'temporary_block') {
                if ($request->expectsJson()) {
                    return new JsonResponse([
                        'message' => $validation['reason'] ?? 'Too much activity detected. Please slow down and try again shortly.',
                    ], 429);
                }

                /** @var RedirectResponse $response */
                $response = redirect()->back()->withErrors([
                    'email' => $validation['reason'] ?? 'Too much activity detected. Please try again shortly.',
                ]);

                return $response;
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return new JsonResponse([
                    'message' => $validation['reason'] ?? 'Session blocked for security reasons.',
                ], 423);
            }

            /** @var RedirectResponse $response */
            $response = redirect()->route('login')->withErrors([
                'email' => $validation['reason'] ?? 'Your session was ended for security reasons.',
            ]);

            return $response;
        }

        if ($this->sessionSecurityService->shouldTrackActivity($request)) {
            $this->sessionSecurityService->trackActivity($user->id, (string) $request->ip());
        }

        return $next($request);
    }
}
