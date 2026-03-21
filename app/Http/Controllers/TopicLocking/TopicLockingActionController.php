<?php

namespace App\Http\Controllers\TopicLocking;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Services\TopicLockingService;
use Illuminate\Http\{Request, JsonResponse, Response};
use Illuminate\Support\Facades\{Log, Auth, Validator};

class TopicLockingActionController extends Controller
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
     * Lock a topic - LETHAL ERROR HANDLING
     */
    public function lock(Request $request, int $topicId): JsonResponse
    {
        try {
            if (app()->environment('local', 'testing')) {
                Log::info('Method lock called', ['user_id' => auth()->id(), 'timestamp' => now()]);
            }
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
        if (app()->environment('local', 'testing')) {
            Log::info('Method unlock called', ['user_id' => auth()->id(), 'timestamp' => now()]);
        }
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
        if (app()->environment('local', 'testing')) {
            Log::info('Method bulkLock called', ['user_id' => auth()->id(), 'timestamp' => now()]);
        }
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
        if (app()->environment('local', 'testing')) {
            Log::info('Method bulkUnlock called', ['user_id' => auth()->id(), 'timestamp' => now()]);
        }
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
