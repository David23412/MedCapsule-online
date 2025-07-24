<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\User;
use App\Models\Assignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TopicAccessController extends Controller
{
    /**
     * Check access permissions for a topic and user
     *
     * @param Topic $topic The topic to check access for
     * @param User|null $user The user to check access for (defaults to authenticated user)
     * @return array Access permissions array with details
     */
    public function checkAccess(Topic $topic, ?User $user = null): array
    {
        // Default to authenticated user if none provided
        $user = $user ?? Auth::user();
        
        if (!$user) {
            return [
                'can_access' => false,
                'reason' => 'Authentication required',
                'status_code' => Response::HTTP_UNAUTHORIZED
            ];
        }
        
        // Admin users always have access
        if ($user->role === 'admin') {
            return [
                'can_access' => true,
                'is_admin' => true,
                'sources' => $this->getAllTopicSources($topic),
                'status_code' => Response::HTTP_OK
            ];
        }
        
        // Check if topic is locked
        if ($topic->is_locked) {
            return [
                'can_access' => false,
                'reason' => $topic->lock_reason ?? 'Topic is locked by tutor',
                'locked_by' => $topic->lockedByTutor ? $topic->lockedByTutor->name : 'A tutor',
                'locked_at' => $topic->locked_at ? $topic->locked_at->format('Y-m-d H:i:s') : null,
                'status_code' => Response::HTTP_FORBIDDEN
            ];
        }
        
        // Check if user is enrolled in the course
        $isEnrolled = $topic->course->isUserEnrolled($user->id);
        if (!$isEnrolled) {
            return [
                'can_access' => false,
                'reason' => 'User is not enrolled in this course',
                'status_code' => Response::HTTP_FORBIDDEN
            ];
        }
        
        // Check for active assignments
        $activeAssignment = $this->getActiveAssignment($topic, $user);
        
        if ($activeAssignment) {
            // User has an active assignment for this topic
            return [
                'can_access' => true,
                'has_assignment' => true,
                'assignment_id' => $activeAssignment->id,
                'assignment_mode' => $activeAssignment->assignment_mode,
                'assignment_deadline' => $activeAssignment->deadline->format('Y-m-d H:i:s'),
                'time_limit' => $activeAssignment->time_limit,
                'sources' => $this->getAssignmentSources($activeAssignment, $topic),
                'status_code' => Response::HTTP_OK
            ];
        }
        
        // Check for expired assignments that still allow practice mode
        $expiredAssignment = $this->getExpiredAssignment($topic, $user);
        
        if ($expiredAssignment) {
            // User has an expired assignment but can still access in practice mode
            return [
                'can_access' => true,
                'has_expired_assignment' => true,
                'assignment_id' => $expiredAssignment->id,
                'practice_mode' => true,
                'sources' => $this->getAllTopicSources($topic),
                'status_code' => Response::HTTP_OK
            ];
        }
        
        // Regular access - no assignments
        return [
            'can_access' => true,
            'practice_mode' => true,
            'sources' => $this->getAllTopicSources($topic),
            'status_code' => Response::HTTP_OK
        ];
    }
    
    /**
     * Get topic sources based on user access mode
     *
     * @param Topic $topic The topic to get sources for
     * @param User|null $user The user to check access for (defaults to authenticated user)
     * @return array List of available sources
     */
    public function getTopicSources(Topic $topic, ?User $user = null): array
    {
        $user = $user ?? Auth::user();
        
        if (!$user) {
            return [];
        }
        
        $accessResult = $this->checkAccess($topic, $user);
        
        if (!$accessResult['can_access']) {
            return [];
        }
        
        return $accessResult['sources'] ?? $this->getAllTopicSources($topic);
    }
    
    /**
     * Get all sources available for a topic
     *
     * @param Topic $topic The topic to get sources for
     * @return array List of all sources
     */
    private function getAllTopicSources(Topic $topic): array
    {
        return DB::table('questions')
            ->where('topic_id', $topic->id)
            ->select('source')
            ->distinct()
            ->pluck('source')
            ->toArray();
    }
    
    /**
     * Get sources available for an assignment
     *
     * @param Assignment $assignment The assignment to get sources for
     * @param Topic $topic The topic associated with the assignment
     * @return array List of available sources
     */
    private function getAssignmentSources(Assignment $assignment, Topic $topic): array
    {
        if ($assignment->isSourceSpecific()) {
            // For source-specific assignments, only return the assigned source
            return [$assignment->assigned_source];
        } else {
            // For complete-topic assignments, return all sources
            return $this->getAllTopicSources($topic);
        }
    }
    
    /**
     * Get active assignment for a topic and user
     *
     * @param Topic $topic The topic to check
     * @param User $user The user to check
     * @return Assignment|null The active assignment or null
     */
    private function getActiveAssignment(Topic $topic, User $user): ?Assignment
    {
        return Assignment::where('topic_id', $topic->id)
            ->where('student_id', $user->id)
            ->where('status', Assignment::STATUS_ACTIVE)
            ->first();
    }
    
    /**
     * Get expired assignment for a topic and user
     *
     * @param Topic $topic The topic to check
     * @param User $user The user to check
     * @return Assignment|null The expired assignment or null
     */
    private function getExpiredAssignment(Topic $topic, User $user): ?Assignment
    {
        return Assignment::where('topic_id', $topic->id)
            ->where('student_id', $user->id)
            ->whereIn('status', [Assignment::STATUS_EXPIRED, Assignment::STATUS_COMPLETED, Assignment::STATUS_OVERDUE])
            ->first();
    }
    
    /**
     * API endpoint to check access for the current user
     *
     * @param Request $request
     * @param int $topicId
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAccessApi(Request $request, int $topicId)
    {
        $topic = Topic::findOrFail($topicId);
        $accessResult = $this->checkAccess($topic);
        
        return response()->json($accessResult, $accessResult['status_code']);
    }
    
    /**
     * API endpoint to get available sources for the current user
     *
     * @param Request $request
     * @param int $topicId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSourcesApi(Request $request, int $topicId)
    {
        $topic = Topic::findOrFail($topicId);
        $accessResult = $this->checkAccess($topic);
        
        if (!$accessResult['can_access']) {
            return response()->json([
                'error' => $accessResult['reason'],
                'sources' => []
            ], $accessResult['status_code']);
        }
        
        return response()->json([
            'sources' => $accessResult['sources'] ?? []
        ]);
    }
}