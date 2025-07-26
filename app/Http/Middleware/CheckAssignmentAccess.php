<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Assignment;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckAssignmentAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check for authenticated users
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();
        
        // Check if this is a quiz route with topic parameter
        $topicId = $request->route('topic')?->id ?? $request->input('topic_id');
        
        if (!$topicId) {
            return $next($request);
        }

        // Find any assignments for this user and topic
        $assignments = Assignment::where('user_id', $user->id)
                                ->where('topic_id', $topicId)
                                ->whereIn('status', [
                                    Assignment::STATUS_ACTIVE,
                                    Assignment::STATUS_OVERDUE,
                                    Assignment::STATUS_LOCKED,
                                    Assignment::STATUS_EXPIRED
                                ])
                                ->get();

        foreach ($assignments as $assignment) {
            // Auto-update assignment status if needed
            $this->updateAssignmentStatus($assignment);
            
            // Check if assignment should block access
            if ($this->shouldBlockAccess($assignment, $request)) {
                $reason = $assignment->getLockReason();
                
                Log::info('Assignment access blocked', [
                    'assignment_id' => $assignment->id,
                    'user_id' => $user->id,
                    'topic_id' => $topicId,
                    'status' => $assignment->status,
                    'reason' => $reason,
                    'route' => $request->route()->getName()
                ]);
                
                return redirect()->back()
                    ->with('error', $reason ?? 'This assignment is no longer accessible.');
            }
        }

        return $next($request);
    }

    /**
     * Update assignment status based on current conditions
     */
    private function updateAssignmentStatus(Assignment $assignment): void
    {
        try {
            // Check if time limit expired
            if ($assignment->hasTimeLimitExpired() && $assignment->status === Assignment::STATUS_ACTIVE) {
                $assignment->markAsExpired();
                Log::info('Assignment expired due to time limit', [
                    'assignment_id' => $assignment->id,
                    'duration_minutes' => $assignment->duration_minutes
                ]);
                return;
            }
            
            // Check if deadline passed
            if ($assignment->deadline < now() && $assignment->status === Assignment::STATUS_ACTIVE) {
                $assignment->markAsOverdue();
                Log::info('Assignment marked as overdue', [
                    'assignment_id' => $assignment->id,
                    'deadline' => $assignment->deadline
                ]);
                return;
            }
            
            // Check if should be locked due to grace period
            if ($assignment->shouldBeLocked() && !$assignment->isLocked()) {
                $assignment->lock();
                Log::info('Assignment auto-locked', [
                    'assignment_id' => $assignment->id,
                    'reason' => 'Grace period expired or other auto-lock condition met'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update assignment status in middleware', [
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Determine if assignment should block access to the quiz
     */
    private function shouldBlockAccess(Assignment $assignment, Request $request): bool
    {
        // Always block if locked
        if ($assignment->isLocked()) {
            return true;
        }
        
        // Block if expired
        if ($assignment->status === Assignment::STATUS_EXPIRED) {
            return true;
        }
        
        // Block if completed (unless it's a view-only request)
        if ($assignment->status === Assignment::STATUS_COMPLETED) {
            // Allow viewing results but not retaking
            $allowedRoutes = ['quiz.results', 'assignments.show'];
            if (!in_array($request->route()->getName(), $allowedRoutes)) {
                return true;
            }
        }
        
        // Block overdue assignments beyond grace period
        if ($assignment->status === Assignment::STATUS_OVERDUE && 
            $assignment->deadline < now()->subHours(24)) {
            return true;
        }
        
        return false;
    }
}