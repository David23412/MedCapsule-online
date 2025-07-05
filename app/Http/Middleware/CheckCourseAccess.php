<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckCourseAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        $courseId = $request->route('course')->id ?? null;

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$courseId) {
            return $next($request);
        }

        if (!$user->is_admin && !$user->hasAccessToCourse($courseId)) {
            return redirect()->route('courses.index')
                ->with('error', 'Access denied. Please contact an administrator to request access to this course.');
        }

        return $next($request);
    }
} 