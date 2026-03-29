<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * 👑 ADMIN ONLY ACCESS - Only administrators can pass through
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

        if (!$user->isAdmin()) {
            \Log::warning('👑 Admin-only access denied', [
                'user_id' => $user->id,
                'route' => $request->route()->getName(),
                'ip' => $request->ip()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Access denied. Administrator privileges required.',
                ], 403);
            }

            abort(403, 'Access denied. Administrator privileges required.');
        }

        return $next($request);
    }
}
