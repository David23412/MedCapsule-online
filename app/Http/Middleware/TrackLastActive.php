<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackLastActive
{
    /**
     * Update the authenticated user's last_active_at timestamp.
     *
     * Throttled to once per minute per user to avoid excessive DB writes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (Auth::check()) {
            $user = Auth::user();
            $now  = now();

            // Only write if the last update was more than 1 minute ago (or never set)
            $lastActive = $user->last_active_at;   // Carbon|null (cast in User model)

            if (is_null($lastActive) || $lastActive->diffInSeconds($now) >= 60) {
                $user->newQuery()
                    ->where('id', $user->id)
                    ->update(['last_active_at' => $now]);
            }
        }

        return $response;
    }
}
