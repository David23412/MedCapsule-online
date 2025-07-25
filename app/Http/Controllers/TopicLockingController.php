<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\User;
use App\Services\TopicLockingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TopicLockingController extends Controller
{
    protected $lockingService;
    
    public function __construct(TopicLockingService $lockingService)
    {
        $this->lockingService = $lockingService;
        $this->middleware('auth');
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
     * Display the topic management index page
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = $this->checkPermission();
        
        // Get all courses the user has access to
        if ($user->isAdmin()) {
            $courses = \App\Models\Course::with(['topics' => function($query) {
                $query->orderBy('name');
            }])->orderBy('name')->get();
        } else {
            // For tutors, get courses they're assigned to
            $courses = $user->courses()->with(['topics' => function($query) {
                $query->orderBy('name');
            }])->orderBy('name')->get();
        }
        
        return view('topic-locking.index', compact('courses'));
    }
    
    /**
     * Display topics for a specific course
     *
     * @param \App\Models\Course $course
     * @return \Illuminate\View\View
     */
    public function courseTopics(\App\Models\Course $course)
    {
        $user = $this->checkPermission();
        
        // Check if user has access to this course
        if (!$user->isAdmin() && !$user->courses()->where('course_id', $course->id)->exists()) {
            abort(403, 'You do not have access to this course.');
        }
        
        $topics = $course->topics()->orderBy('name')->get();
        
        return view('topic-locking.course', compact('course', 'topics'));
    }
    
    /**
     * Lock a topic
     *
     * @param Request $request
     * @param int $topicId
     * @return \Illuminate\Http\JsonResponse
     */
    public function lock(Request $request, int $topicId)
    {
        $user = $this->checkPermission();
        
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }
        
        try {
            $topic = Topic::findOrFail($topicId);
            $reason = $request->input('reason');
            
            $result = $this->lockingService->lock($topic, $user, $reason);
            
            return response()->json([
                'success' => $result,
                'message' => $result ? 'Topic locked successfully' : 'Topic was already locked',
                'lock_info' => $this->lockingService->getLockInfo($topic->fresh())
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Topic not found'
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to lock topic: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Unlock a topic
     *
     * @param Request $request
     * @param int $topicId
     * @return \Illuminate\Http\JsonResponse
     */
    public function unlock(Request $request, int $topicId)
    {
        $user = $this->checkPermission();
        
        try {
            $topic = Topic::findOrFail($topicId);
            
            $result = $this->lockingService->unlock($topic, $user);
            
            return response()->json([
                'success' => $result,
                'message' => $result ? 'Topic unlocked successfully' : 'Topic was already unlocked'
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Topic not found'
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unlock topic: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Bulk lock topics
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkLock(Request $request)
    {
        $user = $this->checkPermission();
        
        $validator = Validator::make($request->all(), [
            'topic_ids' => 'required|array',
            'topic_ids.*' => 'required|integer|exists:topics,id',
            'reason' => 'nullable|string|max:500'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }
        
        try {
            $topicIds = $request->input('topic_ids');
            $reason = $request->input('reason');
            
            $results = $this->lockingService->bulkLock($topicIds, $user, $reason);
            
            // Check if all operations were successful
            $allSuccessful = true;
            foreach ($results as $result) {
                if (!$result['success']) {
                    $allSuccessful = false;
                    break;
                }
            }
            
            return response()->json([
                'success' => $allSuccessful,
                'message' => $allSuccessful ? 'All topics locked successfully' : 'Some topics could not be locked',
                'results' => $results
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to lock topics: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Bulk unlock topics
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUnlock(Request $request)
    {
        $user = $this->checkPermission();
        
        $validator = Validator::make($request->all(), [
            'topic_ids' => 'required|array',
            'topic_ids.*' => 'required|integer|exists:topics,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }
        
        try {
            $topicIds = $request->input('topic_ids');
            
            $results = $this->lockingService->bulkUnlock($topicIds, $user);
            
            // Check if all operations were successful
            $allSuccessful = true;
            foreach ($results as $result) {
                if (!$result['success']) {
                    $allSuccessful = false;
                    break;
                }
            }
            
            return response()->json([
                'success' => $allSuccessful,
                'message' => $allSuccessful ? 'All topics unlocked successfully' : 'Some topics could not be unlocked',
                'results' => $results
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unlock topics: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Get locked topics for a course
     *
     * @param Request $request
     * @param int $courseId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLockedTopics(Request $request, int $courseId)
    {
        try {
            $course = \App\Models\Course::findOrFail($courseId);
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
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMyLockedTopics(Request $request)
    {
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
     *
     * @param Request $request
     * @param int $topicId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLockInfo(Request $request, int $topicId)
    {
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