<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SessionSecurityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SecurityDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * Show security dashboard
     */
    public function index()
    {
        // Get security statistics
        $stats = [
            'total_security_events' => SessionSecurityLog::count(),
            'high_risk_events_today' => SessionSecurityLog::highRisk()->recent(24)->count(),
            'suspicious_logins_today' => SessionSecurityLog::where('event_type', 'login')
                ->whereIn('risk_level', ['high', 'critical'])
                ->recent(24)
                ->count(),
            'active_sessions' => User::whereNotNull('active_session_token')->count(),
            'blocked_users' => $this->getBlockedUsersCount(),
        ];

        // Get recent high-risk events
        $recentEvents = SessionSecurityLog::with('user')
            ->highRisk()
            ->recent(24)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Get users with multiple suspicious activities
        $suspiciousUsers = User::whereHas('securityLogs', function($query) {
            $query->highRisk()->recent(24);
        }, '>=', 3)
        ->withCount(['securityLogs as high_risk_count' => function($query) {
            $query->highRisk()->recent(24);
        }])
        ->orderBy('high_risk_count', 'desc')
        ->limit(10)
        ->get();

        // Get geographic distribution of logins
        $geoData = SessionSecurityLog::where('event_type', 'login')
            ->recent(24)
            ->select('ip_address', DB::raw('count(*) as count'))
            ->groupBy('ip_address')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return view('admin.security.dashboard', compact(
            'stats', 'recentEvents', 'suspiciousUsers', 'geoData'
        ));
    }

    /**
     * Show detailed security logs
     */
    public function logs(Request $request)
    {
        // CRITICAL SECURITY FIX: Validate all filter parameters
        $validated = $request->validate([
            'event_type' => 'nullable|string|max:50',
            'risk_level' => 'nullable|string|in:low,medium,high,critical',
            'user_id' => 'nullable|integer|exists:users,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from'
        ]);
        
        $query = SessionSecurityLog::with('user');

        // Apply filters with validated data
        if (!empty($validated['event_type'])) {
            $query->where('event_type', $validated['event_type']);
        }

        if (!empty($validated['risk_level'])) {
            $query->where('risk_level', $validated['risk_level']);
        }

        if (!empty($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        if (!empty($validated['date_from'])) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (!empty($validated['date_to'])) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('admin.security.logs', compact('logs'));
    }

    /**
     * Show user security details
     */
    public function userSecurity(User $user)
    {
        $user->load(['securityLogs' => function($query) {
            $query->orderBy('created_at', 'desc')->limit(50);
        }]);

        $securityStats = [
            'total_events' => $user->securityLogs()->count(),
            'high_risk_events' => $user->securityLogs()->highRisk()->count(),
            'last_login' => $user->securityLogs()->where('event_type', 'login')->latest()->first(),
            'suspicious_activities' => $user->securityLogs()->whereIn('risk_level', ['high', 'critical'])->count(),
        ];

        return view('admin.security.user-details', compact('user', 'securityStats'));
    }

    /**
     * Force logout a user
     */
    public function forceLogout(User $user)
    {
        $user->update([
            'active_session_token' => null,
            'session_created_at' => null,
            'device_fingerprint' => null,
            'last_security_check' => null
        ]);

        return back()->with('success', "User {$user->name} has been forcefully logged out.");
    }

    /**
     * Block a user temporarily
     */
    public function blockUser(User $user, Request $request)
    {
        $hours = $request->input('hours', 24);
        $blockKey = "blocked_user_{$user->id}";
        
        cache()->put($blockKey, true, now()->addHours($hours));

        // Force logout
        $user->update([
            'active_session_token' => null,
            'session_created_at' => null,
            'last_suspicious_activity' => now()
        ]);

        return back()->with('success', "User {$user->name} has been blocked for {$hours} hours.");
    }

    /**
     * Get count of currently blocked users
     */
    private function getBlockedUsersCount(): int
    {
        // This is a simplified count - in production you'd want to track this better
        return User::whereNotNull('last_suspicious_activity')
            ->where('last_suspicious_activity', '>=', now()->subDay())
            ->count();
    }
}