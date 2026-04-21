<?php

namespace App\Http\Controllers\TopicLocking;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Services\TopicLockingService;
use Illuminate\Http\{Request, JsonResponse, Response};
use Illuminate\Support\Facades\{Log, Auth};

class TopicLockingApiController extends Controller
{
    protected $lockingService;
    
    public function __construct(TopicLockingService $lockingService)
    {
        $this->middleware(['auth']);
        $this->lockingService = $lockingService;
    }
    
    /**
     * Check if user has permission for topic management
     */
    private function checkPermission()
    {
        $user = Auth::user();
        if (!$user || (!$user->isAdmin() && !$user->isTutor())) {
            abort(403, 'You do not have permission to manage topics.');
        }
        return $user;
    }
    
    /**
     * Get locked topics for a course
     */
    public function getLockedTopics(Request $request, int $courseId): JsonResponse
    {
        if (app()->environment('local', 'testing')) {
        }
        try {
            $course = \App\Models\Course::query()
                ->select('courses.id')
                ->findOrFail($courseId);
                
            $lockedTopics = $this->lockingService->getLockedTopics($course);
            
            return response()->json([
                'success' => true,
                'locked_topics' => $lockedTopics->map(function ($topic) {
                    return [
                        'id' => $topic->id,
                        'name' => $topic->name,
                        'lock_info' => $this->lockingService->getLockInfo($topic)
                    ];
                })
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found'
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get locked topics: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Get topics locked by the current tutor
     */
    public function getMyLockedTopics(Request $request): JsonResponse
    {
        if (app()->environment('local', 'testing')) {
        }
        $user = $this->checkPermission();
        
        try {
            $lockedTopics = $this->lockingService->getTopicsLockedByTutor($user);
            
            return response()->json([
                'success' => true,
                'locked_topics' => $lockedTopics->map(function ($topic) {
                    return [
                        'id' => $topic->id,
                        'name' => $topic->name,
                        'course_id' => $topic->course_id,
                        'course_name' => $topic->course->name,
                        'lock_info' => $this->lockingService->getLockInfo($topic)
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get locked topics: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Get lock information for a topic
     */
    public function getLockInfo(Request $request, int $topicId): JsonResponse
    {
        if (app()->environment('local', 'testing')) {
        }
        try {
            $topic = Topic::findOrFail($topicId);
            $lockInfo = $this->lockingService->getLockInfo($topic);
            
            return response()->json([
                'success' => true,
                'is_locked' => (bool) $topic->is_locked,
                'lock_info' => $lockInfo
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Topic not found'
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get lock information: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
