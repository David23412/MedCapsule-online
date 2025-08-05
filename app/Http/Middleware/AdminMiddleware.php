<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        if ($user->role !== 'admin') {
            // Redirect non-admin users to homepage instead of showing 403
            return redirect()->route('home')->with('error', 'Access denied. Only administrators can access this area.');
        }

        return $next($request);
    }
}