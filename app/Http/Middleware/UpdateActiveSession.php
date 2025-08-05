<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class UpdateActiveSession
{
    /**
     * Handle an incoming request - updates session token on login.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only update if user just logged in
        if (Auth::check() && $request->route()->getName() === 'login') {
            $user = Auth::user();
            $currentSessionToken = Session::getId();
            
            // Update the active session token
            $user->update([
                'active_session_token' => $currentSessionToken,
                'session_created_at' => now()
            ]);
        }

        return $response;
    }
}