<?php

namespace App\Http\Controllers\TopicLocking;

use App\Models\Topic;
use App\Models\User;
use App\Services\TopicLockingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * 🚀 CRITICAL REFACTOR: Topic Lock Action Service
 * 
 * Extracted business logic from fat TopicLockingActionController
 * Handles topic locking operations with clean separation of concerns
 */
class TopicLockActionService
{
    private TopicLockingService $lockingService;

    public function __construct(TopicLockingService $lockingService)
    {
        $this->lockingService = $lockingService;
    }

    /**
     * Lock a topic
     */
    public function lockTopic(int $topicId, User $user, array $data): array
    {
        $validator = Validator::make($data, [
            'reason' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ];
        }

        $topic = Topic::find($topicId);
        if (!$topic) {
            return [
                'success' => false,
                'message' => 'Topic not found'
            ];
        }

        $result = $this->lockingService->lockTopic($topic, $user, $data['reason'] ?? null);

        Log::info('Topic locked', [
            'topic_id' => $topicId,
            'user_id' => $user->id,
            'reason' => $data['reason'] ?? null
        ]);

        return [
            'success' => true,
            'message' => 'Topic locked successfully',
            'data' => $result
        ];
    }

    /**
     * Unlock a topic
     */
    public function unlockTopic(int $topicId, User $user, array $data): array
    {
        $validator = Validator::make($data, [
            'reason' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ];
        }

        $topic = Topic::find($topicId);
        if (!$topic) {
            return [
                'success' => false,
                'message' => 'Topic not found'
            ];
        }

        $result = $this->lockingService->unlockTopic($topic, $user, $data['reason'] ?? null);

        Log::info('Topic unlocked', [
            'topic_id' => $topicId,
            'user_id' => $user->id,
            'reason' => $data['reason'] ?? null
        ]);

        return [
            'success' => true,
            'message' => 'Topic unlocked successfully',
            'data' => $result
        ];
    }

    /**
     * Bulk lock topics
     */
    public function bulkLockTopics(array $topicIds, User $user, array $data): array
    {
        $validator = Validator::make($data, [
            'reason' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ];
        }

        $results = $this->lockingService->bulkLockTopics($topicIds, $user, $data['reason'] ?? null);

        Log::info('Bulk topic lock', [
            'topic_ids' => $topicIds,
            'user_id' => $user->id,
            'reason' => $data['reason'] ?? null
        ]);

        return [
            'success' => true,
            'message' => 'Topics processed successfully',
            'data' => $results
        ];
    }

    /**
     * Check user permissions
     */
    public function checkPermissions(User $user): bool
    {
        return $user->isAdmin() || $user->isTutor();
    }
}