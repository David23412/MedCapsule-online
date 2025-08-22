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

        // 🔍 DEBUG: Enhanced admin role checking with detailed logging
        $roleValue = $user->role;
        $roleType = gettype($roleValue);
        $isAdminCheck = $user->isAdmin();
        $expectedAdminRole = \App\Models\User::ROLE_ADMIN;

        \Log::info('🔍 Admin middleware debug', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_role_raw' => $roleValue,
            'user_role_type' => $roleType,
            'expected_admin_role' => $expectedAdminRole,
            'is_admin_check' => $isAdminCheck,
            'role_comparison' => ((int)$roleValue === $expectedAdminRole),
            'route' => $request->route()->getName(),
            'ip' => $request->ip()
        ]);

        // 👑 ADMIN ONLY - Superior privileges required
        if (!$isAdminCheck) {
            \Log::warning('👑 Admin-only access denied', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $roleValue,
                'user_role_type' => $roleType,
                'expected_role' => $expectedAdminRole,
                'is_admin_method_result' => $isAdminCheck,
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
