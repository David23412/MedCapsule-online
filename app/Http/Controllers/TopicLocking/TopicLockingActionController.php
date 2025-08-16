<?php

namespace App\Http\Controllers\TopicLocking;

use App\Http\Controllers\Controller;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\{Log, Auth};

/**
 * 🚀 CRITICAL REFACTOR: Topic Locking Action Controller
 * 
 * Lightweight controller that delegates to TopicLockActionService
 * Replaces fat controller with clean separation of concerns
 */
class TopicLockingActionController extends Controller
{
    private TopicLockActionService $actionService;
    
    public function __construct(TopicLockActionService $actionService)
    {
        $this->middleware(['auth']);
        $this->actionService = $actionService;
    }
    
    /**
     * Check permissions and get user
     */
    private function requirePermissions()
    {
        $user = Auth::user();
        if (!$user || !$this->actionService->checkPermissions($user)) {
            abort(403, 'You do not have permission to manage topics.');
        }
        return $user;
    }
    
    /**
     * Lock a topic
     */
    public function lock(Request $request, int $topicId): JsonResponse
    {
        try {
            Log::info('Topic lock requested', ['user_id' => auth()->id(), 'topic_id' => $topicId]);
            
            $user = $this->requirePermissions();
            $result = $this->actionService->lockTopic($topicId, $user, $request->all());
            
            return response()->json($result, $result['success'] ? 200 : 400);
                ], Response::HTTP_BAD_REQUEST);
            }
            
        } catch (\Exception $e) {
            Log::error('Topic lock failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'topic_id' => $topicId,
                'user_id' => auth()->id(),
                'ip' => request()->ip()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to lock topic'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
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
     */
    public function unlock(Request $request, int $topicId): JsonResponse
    {
        Log::info('Method unlock called', ['user_id' => auth()->id(), 'timestamp' => now()]);
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
     * Bulk lock topics - COMPLEXITY REDUCED
     */
    public function bulkLock(Request $request): JsonResponse
    {
        Log::info('Method bulkLock called', ['user_id' => auth()->id(), 'timestamp' => now()]);
        $user = $this->checkPermission();
        
        // COMPLEXITY REDUCTION: Extract validation
        $validationResponse = $this->validateBulkLockRequest($request);
        if ($validationResponse) {
            return $validationResponse;
        }
        
        // COMPLEXITY REDUCTION: Extract bulk processing
        return $this->processBulkLock($request, $user);
    }

    /**
     * COMPLEXITY REDUCTION: Validate bulk lock request
     */
    private function validateBulkLockRequest(Request $request): ?JsonResponse
    {
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
        
        return null;
    }

    /**
     * COMPLEXITY REDUCTION: Process bulk lock operation
     */
    private function processBulkLock(Request $request, $user): JsonResponse
    {
        try {
            $topicIds = $request->input('topic_ids');
            $reason = $request->input('reason');
            
            $results = $this->lockingService->bulkLock($topicIds, $user, $reason);
            $allSuccessful = $this->checkAllResultsSuccessful($results);
            
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
     * COMPLEXITY REDUCTION: Check if all results are successful
     */
    private function checkAllResultsSuccessful(array $results): bool
    {
        foreach ($results as $result) {
            if (!$result['success']) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Bulk unlock topics
     */
    public function bulkUnlock(Request $request): JsonResponse
    {
        Log::info('Method bulkUnlock called', ['user_id' => auth()->id(), 'timestamp' => now()]);
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
}