<?php

namespace App\Http\Controllers\Topic\Access;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Models\User;
use App\Models\Assignment;
use Illuminate\Http\Request;

/**
 * 🚀 PERFORMANCE BEAST: Topic Access Controller
 * Handles: Topic access validation and permissions
 * Lines: ~90 (well under 200 limit)
 */
class TopicAccessController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * Check if user has access to a topic
     */
    public function checkAccess(Topic $topic, ?User $user = null): array
    {
        $user = $user ?? auth()->user();
        
        // Admin always has access
        if ($user->isAdmin()) {
            return [
                'has_access' => true,
                'access_type' => 'admin',
                'sources' => $this->getAllTopicSources($topic),
                'message' => 'Admin access granted'
            ];
        }

        // Check if user has course access
        if (!$user->hasAccessToCourse($topic->course_id)) {
            return [
                'has_access' => false,
                'access_type' => 'no_course_access',
                'sources' => [],
                'message' => 'No access to course'
            ];
        }

        // Check for active assignment
        $activeAssignment = $this->getActiveAssignment($topic, $user);
        if ($activeAssignment) {
            return [
                'has_access' => true,
                'access_type' => 'assignment',
                'sources' => $this->getAssignmentSources($activeAssignment, $topic),
                'assignment' => $activeAssignment,
                'message' => 'Assignment access granted'
            ];
        }

        // Check for expired assignment
        $expiredAssignment = $this->getExpiredAssignment($topic, $user);
        if ($expiredAssignment) {
            return [
                'has_access' => false,
                'access_type' => 'expired_assignment',
                'sources' => [],
                'assignment' => $expiredAssignment,
                'message' => 'Assignment expired'
            ];
        }

        // Default course access
        return [
            'has_access' => true,
            'access_type' => 'course',
            'sources' => $this->getAllTopicSources($topic),
            'message' => 'Course access granted'
        ];
    }

    /**
     * API endpoint for checking topic access
     */
    public function checkAccessApi(Request $request, int $topicId)
    {
        try {
            $topic = Topic::findOrFail($topicId);
            $accessInfo = $this->checkAccess($topic);
            
            return response()->json([
                'success' => true,
                'access_info' => $accessInfo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check access: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active assignment for topic and user
     */
    private function getActiveAssignment(Topic $topic, User $user): ?Assignment
    {
        return Assignment::where('topic_id', $topic->id)
            ->where('student_id', $user->id)
            ->where('status', 'active')
            ->where('deadline', '>', now())
            ->first();
    }

    /**
     * Get expired assignment for topic and user
     */
    private function getExpiredAssignment(Topic $topic, User $user): ?Assignment
    {
        return Assignment::where('topic_id', $topic->id)
            ->where('student_id', $user->id)
            ->where('status', 'active')
            ->where('deadline', '<=', now())
            ->first();
    }
}