<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $roles  Comma-separated list of allowed roles
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $user = auth()->user();

        // Admin has superior privileges - can access all routes
        if ($user->isAdmin()) {
            return $next($request);
        }

        // For non-admin users, check if their role is in the allowed roles list (numbers expected)
        $allowedRoles = array_map('intval', explode(',', $roles));
        if (!in_array((int)$user->role, $allowedRoles, true)) {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }

        return $next($request);
    }
}