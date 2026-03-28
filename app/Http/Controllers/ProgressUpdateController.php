<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProgressUpdateController extends Controller
{
    /**
     * Store progress update for real-time synchronization - ASSIGNMENT FIXED
     */
    public function storeProgressUpdate(Request $request): JsonResponse
    {
        // Debug logging to see what data is being received
        Log::info('Progress update request received', [
            'request_data' => $request->all(),
            'content_type' => $request->header('Content-Type'),
            'user_id' => auth()->id()
        ]);

        try {
            $validated = $request->validate([
                'topicId' => 'required|integer',
                'assignmentId' => 'nullable|integer',
                'mode' => 'nullable|string|in:quiz,tutor',
                'progress' => 'nullable|array',
                'overall_topic_progress' => 'nullable|array',
                'availableSources' => 'nullable|array',
                'allSourcesMastered' => 'nullable|boolean',
                'userId' => 'required|integer'
            ]);

            // Provide defaults for optional fields
            $validated['mode'] = $validated['mode'] ?? 'quiz';
            $validated['progress'] = $validated['progress'] ?? [];
            $validated['overall_topic_progress'] = $validated['overall_topic_progress'] ?? [];
            $validated['availableSources'] = $validated['availableSources'] ?? [];
            $validated['allSourcesMastered'] = $validated['allSourcesMastered'] ?? false;

            // Store progress update with timestamp
            $progressData = array_merge($validated, [
                'timestamp' => now()->timestamp,
                'created_at' => now()->toISOString()
            ]);

            // CRITICAL FIX: Use assignment-specific cache key if assignment_id provided
            $cacheKey = $validated['assignmentId'] 
                ? "assignment_progress_{$validated['userId']}_{$validated['assignmentId']}"
                : "progress_update_{$validated['userId']}_{$validated['topicId']}";
                
            Cache::put($cacheKey, $progressData, 300); // 5 minutes

            // Also store globally for this user
            $globalKey = "user_progress_updates_{$validated['userId']}";
            $globalUpdates = Cache::get($globalKey, []);
            $globalUpdates[] = $progressData;
            
            // Keep only last 10 updates
            if (count($globalUpdates) > 10) {
                $globalUpdates = array_slice($globalUpdates, -10);
            }
            
            Cache::put($globalKey, $globalUpdates, 300);

            // 🔥 GENIUS: No sync needed - progress is calculated dynamically!

            Log::info('Progress update stored for real-time sync', [
                'user_id' => $validated['userId'],
                'topic_id' => $validated['topicId'],
                'assignment_id' => $validated['assignmentId'],
                'timestamp' => $progressData['timestamp']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Progress update stored successfully',
                'timestamp' => $progressData['timestamp']
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed in store progress update', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
                'request_headers' => $request->headers->all(),
                'content_type' => $request->header('Content-Type'),
                'raw_content' => $request->getContent()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'received_data' => $request->all() // Include received data for debugging
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to store progress update', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to store progress update: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent progress updates for a user
     */
    public function getRecentUpdates(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $topicId = $request->input('topicId');
            $since = $request->input('since', 0);

            $updates = [];

            if ($topicId) {
                // Get specific topic updates
                $cacheKey = "progress_update_{$userId}_{$topicId}";
                $update = Cache::get($cacheKey);
                if ($update && $update['timestamp'] > $since) {
                    $updates[] = $update;
                }
            } else {
                // Get all recent updates for user
                $globalKey = "user_progress_updates_{$userId}";
                $globalUpdates = Cache::get($globalKey, []);
                
                $updates = array_filter($globalUpdates, function($update) use ($since) {
                    return $update['timestamp'] > $since;
                });
            }

            return response()->json([
                'success' => true,
                'updates' => $updates,
                'timestamp' => now()->timestamp
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get recent updates', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get recent updates'
            ], 500);
        }
    }

    /**
     * Store assignment completion for real-time synchronization
     */
    public function storeAssignmentCompletion(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'assignmentId' => 'required|integer',
                'percentage' => 'required|numeric',
                'score' => 'required|integer',
                'total' => 'required|integer',
                'userId' => 'required|integer'
            ]);

            // Store assignment completion with timestamp
            $assignmentData = array_merge($validated, [
                'timestamp' => now()->timestamp,
                'created_at' => now()->toISOString()
            ]);

            // Store in cache for cross-tab communication
            $cacheKey = "assignment_completion_{$validated['userId']}_{$validated['assignmentId']}";
            Cache::put($cacheKey, $assignmentData, 300); // 5 minutes

            // Also store globally for this user
            $globalKey = "user_assignment_completions_{$validated['userId']}";
            $globalCompletions = Cache::get($globalKey, []);
            $globalCompletions[] = $assignmentData;
            
            // Keep only last 10 completions
            if (count($globalCompletions) > 10) {
                $globalCompletions = array_slice($globalCompletions, -10);
            }
            
            Cache::put($globalKey, $globalCompletions, 300);

            Log::info('Assignment completion stored for real-time sync', [
                'user_id' => $validated['userId'],
                'assignment_id' => $validated['assignmentId'],
                'percentage' => $validated['percentage'],
                'timestamp' => $assignmentData['timestamp']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Assignment completion stored successfully',
                'timestamp' => $assignmentData['timestamp']
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to store assignment completion', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to store assignment completion'
            ], 500);
        }
    }

    /**
     * 🚀 GENIUS: Get assignment progress dynamically
     */
    public function getAssignmentProgress(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'assignmentId' => 'required|integer',
                'userId' => 'required|integer'
            ]);

            $assignment = \App\Models\Assignment::find($validated['assignmentId']);
            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assignment not found'
                ], 404);
            }

            $progress = $assignment->getUserProgress($validated['userId']);

            return response()->json([
                'success' => true,
                'progress' => $progress,
                'assignment' => [
                    'id' => $assignment->id,
                    'title' => $assignment->title,
                    'percentage' => $progress['percentage']
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get assignment progress', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get assignment progress'
            ], 500);
        }
    }
} 
