<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Assignment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;

class AssignmentSecurityMiddleware
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
        // Check if the request is for an assignment
        $assignmentId = $request->route('assignment') ?? $request->input('assignment_id');
        
        if (!$assignmentId) {
            return $next($request);
        }
        
        // Get the assignment
        $assignment = Assignment::find($assignmentId);
        
        if (!$assignment) {
            return response()->json([
                'error' => 'Assignment not found'
            ], Response::HTTP_NOT_FOUND);
        }
        
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'error' => 'Authentication required'
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        // Admin users can access any assignment
        if ($user->role === 'admin') {
            return $next($request);
        }
        
        // Check if user is the student assigned to this assignment
        if ($user->id === $assignment->user_id) {
            // Check if assignment is accessible (not locked)
            if ($assignment->isLocked()) {
                return response()->json([
                    'error' => 'This assignment is locked'
                ], Response::HTTP_FORBIDDEN);
            }
            
            // Check if the topic is locked
            if ($assignment->topic->is_locked) {
                return response()->json([
                    'error' => $assignment->topic->lock_reason ?? 'Topic is locked by tutor'
                ], Response::HTTP_FORBIDDEN);
            }
            
            // Student can access their own assignment
            return $next($request);
        }
        
        // Check if user is the tutor who created this assignment
        if ($user->id === $assignment->tutor_id) {
            // Tutor can access assignments they created
            return $next($request);
        }
        
        // Check if user is a tutor with a relationship to the student
        if ($user->role === 'tutor') {
            $hasRelationship = $this->verifyTutorStudentRelationship($user->id, $assignment->user_id);
            
            if ($hasRelationship) {
                return $next($request);
            }
        }
        
        // Access denied
        return response()->json([
            'error' => 'You do not have permission to access this assignment'
        ], Response::HTTP_FORBIDDEN);
    }
    
    /**
     * Verify tutor-student relationship
     *
     * @param int $tutorId
     * @param int $studentId
     * @return bool
     */
    private function verifyTutorStudentRelationship(int $tutorId, int $studentId): bool
    {
        return \DB::table('tutor_student_relationships')
            ->where('tutor_id', $tutorId)
            ->where('student_id', $studentId)
            ->exists();
    }
}