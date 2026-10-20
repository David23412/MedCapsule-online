<?php

namespace App\Http\Controllers;

use App\Models\VideoProgress;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VideoProgressController extends Controller
{
    /**
     * Update video progress via AJAX
     */
    public function updateProgress(Request $request)
    {
        $validated = $request->validate([
            'topic_id' => 'required|integer|exists:topics,id',
            'current_time' => 'required|numeric|between:0,99999999.99',
            'duration' => 'required|numeric|between:0,99999999.99'
        ]);
        try {
            $userId = Auth::id();
            $topicId = $validated['topic_id'];
            $currentTime = $validated['current_time'];
            $duration = $validated['duration'];

            // Get the video for this topic
            $topic = Topic::with('video')->find($topicId);
            if (!$topic || !$topic->video) {
                return response()->json(['error' => 'Video not found'], 404);
            }

            $access = app(TopicAccessController::class)->checkAccess($topic);
            if (!$access['can_access']) {
                return response()->json(['success' => false, 'error' => $access['reason']], $access['status_code']);
            }

            // Update progress
            $progress = VideoProgress::updateProgress(
                $userId,
                $topicId,
                $topic->video->id,
                $currentTime,
                $duration
            );

            return response()->json([
                'success' => true,
                'progress' => [
                    'percentage' => $progress->percentage,
                    'current_time' => $progress->current_time,
                    'duration' => $progress->duration,
                    'completed' => $progress->percentage >= 100
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Video progress update failed:', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            'topic_id' => $request->topic_id ?? null
            ]);

            return response()->json(['error' => 'Failed to update progress'], 500);
        }
    }

    /**
     * Get video progress for a topic
     */
    public function getProgress(Topic $topic)
    {
        $topicId = $topic->id;
        try {
            $access = app(TopicAccessController::class)->checkAccess($topic);
            if (!$access['can_access']) {
                return response()->json(['success' => false, 'error' => $access['reason']], $access['status_code']);
            }
            $progress = VideoProgress::getTopicProgress(Auth::id(), $topicId);
            
            if (!$progress) {
                return response()->json([
                    'percentage' => 0,
                    'current_time' => 0,
                    'duration' => 0,
                    'completed' => false
                ]);
            }

            return response()->json([
                'percentage' => $progress->percentage,
                'current_time' => $progress->current_time,
                'duration' => $progress->duration,
                'completed' => $progress->percentage >= 100,
                'last_watched_at' => $progress->last_watched_at
            ]);

        } catch (\Exception $e) {
            Log::error('Get video progress failed:', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            'topic_id' => $topicId
            ]);

            return response()->json(['error' => 'Failed to get progress'], 500);
        }
    }
}