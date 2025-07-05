<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('AdminMiddleware: Checking admin status', [
            'is_authenticated' => Auth::check(),
            'user' => Auth::user(),
            'is_admin' => Auth::check() ? Auth::user()->is_admin : null,
        ]);

        if (!Auth::check() || !Auth::user()->is_admin) {
            Log::warning('AdminMiddleware: Access denied', [
                'is_authenticated' => Auth::check(),
                'user' => Auth::check() ? Auth::user()->toArray() : null,
            ]);
            return redirect('/')->with('error', 'Access denied. Admin privileges required.');
        }

        Log::info('AdminMiddleware: Access granted');
        return $next($request);
    }
} 