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

        // Find any assignments for this user and topic that are not just templates
        $assignments = Assignment::where('topic_id', $topicId)
                               ->whereHas('assignedUsers', function ($query) use ($user) {
                                   $query->where('user_id', $user->id);
                               })
                               ->get();

        foreach ($assignments as $assignment) {
            $status = $assignment->getStatusForUser($user);

            // Check if assignment should block access based on its dynamically computed status
            if ($this->shouldBlockAccess($status, $request)) {
                $reason = $assignment->getLockReasonForUser($user);
                
                Log::info('Assignment access blocked', [
                    'assignment_id' => $assignment->id,
                    'user_id' => $user->id,
                    'topic_id' => $topicId,
                    'computed_status' => $status,
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
     * Determine if assignment should block access to the quiz
     */
    private function shouldBlockAccess(int $status, Request $request): bool
    {
        // Block if the assignment is locked for any reason (overdue, expired, completed, etc.)
        if ($status === Assignment::STATUS_LOCKED) {
            return true;
        }

        // Block if completed, unless it's a route for viewing results.
        if ($status === Assignment::STATUS_COMPLETED) {
            $allowedRoutes = ['quiz.results', 'assignments.show'];
            if (!in_array($request->route()->getName(), $allowedRoutes)) {
                return true;
            }
        }

        return false;
    }
}