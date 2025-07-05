<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TrackUserActivity
{
    public function handle(Request $request, Closure $next)
    {
        $fingerprint = $this->generateFingerprint($request);
        
        if (Auth::check()) {
            // For logged-in users, update their session
            DB::table('sessions')
                ->where('id', session()->getId())
                ->update([
                    'user_id' => Auth::id(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'last_activity' => now()->timestamp,
                    'is_visitor' => false,
                    'fingerprint' => $fingerprint
                ]);
        } else {
            // For visitors, check if they already have a session with the same fingerprint
            $existingSession = DB::table('sessions')
                ->where('fingerprint', $fingerprint)
                ->where('last_activity', '>=', now()->subMinutes(5)->timestamp)
                ->first();

            if (!$existingSession) {
                // Only create a new visitor session if no recent session exists with the same fingerprint
                DB::table('sessions')
                    ->where('id', session()->getId())
                    ->update([
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'last_activity' => now()->timestamp,
                        'is_visitor' => true,
                        'fingerprint' => $fingerprint
                    ]);
            }
        }

        return $next($request);
    }

    private function generateFingerprint(Request $request)
    {
        // Create a unique fingerprint based on IP and user agent
        // This helps prevent duplicate counting from the same user on different tabs
        $data = [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ];
        
        return hash('sha256', json_encode($data));
    }
} 