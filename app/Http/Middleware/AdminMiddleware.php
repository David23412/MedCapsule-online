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
        
        // 👑 ADMIN ONLY - Superior privileges required
        if ($user->role !== 'admin') {
            \Log::warning('👑 Admin-only access denied', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'route' => $request->route()->getName(),
                'ip' => $request->ip()
            ]);
            
            return redirect()->route('home')->with('error', 'Access denied. Administrator privileges required.');
        }
        
        // Log admin access
        \Log::info('👑 Admin-only access granted', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'route' => $request->route()->getName(),
            'method' => $request->method(),
            'ip' => $request->ip()
        ]);
        
        return $next($request);
    }
}