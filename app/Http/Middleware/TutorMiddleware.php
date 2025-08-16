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
     * 👑 ADMIN SUPERIOR PRIVILEGES: Admins have unrestricted access
     * 🎓 TUTOR ACCESS: Tutors have standard access
     * 🚫 STUDENT RESTRICTION: Students are denied access
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
        
        // 👑 ADMIN SUPERIOR PRIVILEGES - Admins can access EVERYTHING
        if ($user->role === 'admin') {
            // Log admin access for security tracking
            \Log::info('👑 Admin superior access granted', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'route' => $request->route()->getName(),
                'method' => $request->method(),
                'ip' => $request->ip()
            ]);
            
            return $next($request);
        }
        
        // 🎓 TUTOR ACCESS - Standard tutor permissions
        if ($user->role === User::ROLE_TUTOR) {
            return $next($request);
        }
        
        // 🚫 DENY ACCESS - Students and other roles
        \Log::warning('🚫 Assignment access denied', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'route' => $request->route()->getName(),
            'ip' => $request->ip()
        ]);
        
        return redirect()->route('home')->with('error', 'Access denied. Only administrators and tutors can access this area.');
    }
}