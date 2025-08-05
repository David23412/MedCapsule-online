<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TutorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        if (!in_array($user->role, ['admin', 'tutor'])) {
            // Redirect non-admin/tutor users to homepage instead of showing 403
            return redirect()->route('home')->with('error', 'Access denied. Only administrators and tutors can access this area.');
        }

        return $next($request);
    }
} 