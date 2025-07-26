<?php

namespace App\Http\Controllers\Assignment;

use App\Http\Controllers\Controller;
use App\Services\AssignmentService;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 💀 NUCLEAR OPTIMIZED ASSIGNMENT ANALYTICS CONTROLLER 💀
 * Handles analytics with F-16 RAPTOR SPEED
 */
class AssignmentAnalyticsController extends Controller
{
    private AssignmentService $assignmentService;

    public function __construct(AssignmentService $assignmentService)
    {
        $this->middleware(['auth']);
        $this->assignmentService = $assignmentService;
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (in_array($user->role, ['admin', 'tutor'])) {
                return $next($request);
            }
            abort(403, 'Unauthorized access.');
        });
    }

    /**
     * Show assignment analytics dashboard
     */
    public function analytics(): View
    {
        try {
            $userId = auth()->id();
            
            $analytics = Cache::remember("assignment_analytics_{$userId}", 600, function() use ($userId) {
                return $this->assignmentService->getAnalytics($userId);
            });
            
            return view('assignments.analytics', compact('analytics'));
        } catch (\Exception $e) {
            Log::error('Analytics loading failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return view('assignments.analytics', [
                'analytics' => [],
                'error' => 'Failed to load analytics data'
            ]);
        }
    }
}