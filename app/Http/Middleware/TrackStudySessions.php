<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\StudySession;
use Illuminate\Support\Facades\Auth;

class TrackStudySessions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Check if user is authenticated and has an active study session
        if (Auth::check() && session()->has('active_study_session_id')) {
            $sessionId = session('active_study_session_id');
            $session = StudySession::find($sessionId);
            
            // If session exists and has been active for more than 2 hours, end it
            if ($session && $session->started_at && !$session->ended_at) {
                $hoursSinceStart = $session->started_at->diffInHours(now());
                
                if ($hoursSinceStart >= 2) {
                    // Calculate duration in minutes (cap at 120 minutes)
                    $durationInMinutes = min(120, ceil($session->started_at->diffInSeconds(now()) / 60));
                    
                    // Update session
                    $session->update([
                        'ended_at' => now(),
                        'duration' => $durationInMinutes
                    ]);
                    
                    // Update user's total study minutes
                    Auth::user()->increment('total_study_minutes', $durationInMinutes);
                    
                    // Clear the session variable
                    session()->forget('active_study_session_id');
                }
            }
        }
        
        return $response;
    }
} 