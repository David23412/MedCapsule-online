<?php

namespace App\Http\Controllers;

use App\Models\VideoProgress;
use App\Models\Topic;
use App\Models\Video;
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
        try {
            $request->validate([
                'topic_id' => 'required|exists:topics,id',
                'current_time' => 'required|numeric|min:0',
                'duration' => 'required|numeric|min:0'
            ]);

            $userId = Auth::id();
            $topicId = $request->topic_id;
            $currentTime = $request->current_time;
            $duration = $request->duration;

            // Get the video for this topic
            $topic = Topic::with('video')->find($topicId);
            if (!$topic || !$topic->video) {
                return response()->json(['error' => 'Video not found'], 404);
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
    public function getProgress($topicId)
    {
        try {
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