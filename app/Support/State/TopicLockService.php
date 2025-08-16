<?php

namespace App\Support\State;

use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * 🚀 CRITICAL SERVICE: Topic Lock Service
 * 
 * Handles topic locking logic with batch processing to prevent N+1 queries
 * Created to support the TopicController N+1 fix
 */
class TopicLockService
{
    /**
     * Get lock status for a single topic
     */
    public function getTopicLockStatus(Topic $topic, ?int $userId = null): array
    {
        $userId = $userId ?? auth()->id();
        
        // Simple lock logic - can be expanded based on business rules
        $isLocked = !$topic->is_active || ($topic->requires_prerequisite && !$this->hasPrerequisites($topic, $userId));
        
        return [
            'is_locked' => $isLocked,
            'reason' => $isLocked ? $this->getLockReason($topic) : null,
            'can_unlock' => !$isLocked || $this->canUnlock($topic, $userId)
        ];
    }

    /**
     * 🚀 CRITICAL N+1 FIX: Batch get lock statuses for multiple topics
     */
    public function getBatchTopicLockStatuses(array $topicIds, int $userId): array
    {
        $cacheKey = "topic_locks_batch_{$userId}_" . md5(implode(',', $topicIds));
        
        return Cache::remember($cacheKey, 300, function () use ($topicIds, $userId) {
            $lockStatuses = [];
            
            // Get all topics in one query
            $topics = Topic::whereIn('id', $topicIds)
                ->select('id', 'is_active', 'requires_prerequisite')
                ->get()
                ->keyBy('id');
            
            // Process each topic
            foreach ($topicIds as $topicId) {
                $topic = $topics->get($topicId);
                
                if (!$topic) {
                    $lockStatuses[$topicId] = ['is_locked' => true, 'reason' => 'Topic not found'];
                    continue;
                }
                
                $isLocked = !$topic->is_active || 
                           ($topic->requires_prerequisite && !$this->hasPrerequisites($topic, $userId));
                
                $lockStatuses[$topicId] = [
                    'is_locked' => $isLocked,
                    'reason' => $isLocked ? $this->getLockReason($topic) : null,
                    'can_unlock' => !$isLocked || $this->canUnlock($topic, $userId)
                ];
            }
            
            return $lockStatuses;
        });
    }

    /**
     * Check if user has prerequisites for topic
     */
    private function hasPrerequisites(Topic $topic, int $userId): bool
    {
        // Implement prerequisite logic based on your business rules
        // This is a placeholder implementation
        return true;
    }

    /**
     * Get lock reason
     */
    private function getLockReason(Topic $topic): string
    {
        if (!$topic->is_active) {
            return 'Topic is not active';
        }
        
        if ($topic->requires_prerequisite) {
            return 'Prerequisites not met';
        }
        
        return 'Topic is locked';
    }

    /**
     * Check if topic can be unlocked
     */
    private function canUnlock(Topic $topic, int $userId): bool
    {
        // Implement unlock logic based on your business rules
        return false;
    }
}