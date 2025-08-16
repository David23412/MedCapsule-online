<?php

namespace App\Http\Controllers\Topic\Locking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Topic;

/**
 * 🚀 PERFORMANCE BEAST: Topic Locking Controller
 * Handles: Individual topic locking/unlocking
 * Lines: ~80 (well under 200 limit)
 */
class TopicLockingController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Lock a topic
     */
    public function lock($topicId)
    {
        try {
            $topic = Topic::findOrFail($topicId);
            $topic->update(['is_locked' => true]);
            
            return response()->json([
                'success' => true,
                'message' => "Topic '{$topic->name}' has been locked",
                'topic_id' => $topicId,
                'is_locked' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to lock topic: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unlock a topic
     */
    public function unlock($topicId)
    {
        try {
            $topic = Topic::findOrFail($topicId);
            $topic->update(['is_locked' => false]);
            
            return response()->json([
                'success' => true,
                'message' => "Topic '{$topic->name}' has been unlocked",
                'topic_id' => $topicId,
                'is_locked' => false
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unlock topic: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle topic lock status
     */
    public function toggle($topicId)
    {
        try {
            $topic = Topic::findOrFail($topicId);
            $newStatus = !$topic->is_locked;
            $topic->update(['is_locked' => $newStatus]);
            
            $action = $newStatus ? 'locked' : 'unlocked';
            
            return response()->json([
                'success' => true,
                'message' => "Topic '{$topic->name}' has been {$action}",
                'topic_id' => $topicId,
                'is_locked' => $newStatus
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle topic: ' . $e->getMessage()
            ], 500);
        }
    }
}