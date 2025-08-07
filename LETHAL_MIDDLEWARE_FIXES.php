<?php

// LETHAL MIDDLEWARE FIXES

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * LETHAL FIX: Role-based access control
     */
    public function handle(Request $request, Closure $next, ...$roles): mixed
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $user = Auth::user();
        $userRole = $user->role ?? 'student';
        
        if (!in_array($userRole, $roles)) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }
        
        return $next($request);
    }
}

// Add to app/Http/Kernel.php in $routeMiddleware array:
// 'role' => \App\Http\Middleware\RoleMiddleware::class,
