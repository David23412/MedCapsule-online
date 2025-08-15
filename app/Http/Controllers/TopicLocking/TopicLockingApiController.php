<?php

namespace App\Http\Controllers\TopicLocking;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Services\TopicLockingService;
use App\Support\State\TopicLockService;
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
        Log::info('Method getLockedTopics called', ['user_id' => auth()->id(), 'timestamp' => now()]);
        try {
            // LETHAL EAGER LOADING: Load course with comprehensive topic data
            $course = \App\Models\Course::select('id', 'name', 'description')
                ->with([
                    'topics:id,course_id,name,is_locked,locked_by,locked_at,lock_reason',
                    'topics.lockedByTutor:id,name,email',
                    'topics.questions:id,topic_id,difficulty',
                    'topics.assignments:id,topic_id,status,created_at'
                ])
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
        Log::info('Method getMyLockedTopics called', ['user_id' => auth()->id(), 'timestamp' => now()]);
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
        Log::info('Method getLockInfo called', ['user_id' => auth()->id(), 'timestamp' => now()]);
        try {
            $topic = Topic::findOrFail($topicId);
            $lockInfo = $this->lockingService->getLockInfo($topic);
            
            $topicLockService = app(TopicLockService::class);
            $lockStatus = $topicLockService->getTopicLockStatus($topic);
            
            return response()->json([
                'success' => true,
                'is_locked' => $lockStatus['is_locked'],
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