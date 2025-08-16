<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Routing\ResponseFactory;
use Psr\Log\LoggerInterface;
use Exception;

class QuestionCountController extends Controller
{
    private ResponseFactory $responseFactory;
    private LoggerInterface $logger;

    public function __construct(ResponseFactory $responseFactory, LoggerInterface $logger)
    {
        $this->responseFactory = $responseFactory;
        $this->logger = $logger;
    }

    /**
     * Get question counts by source for a topic
     * FIXED: Eliminated SQL injection vulnerabilities
     */
    public function getQuestionCounts(Request $request, $topicId): JsonResponse
    {
        try {
            // FIXED: Validate topic ID to prevent SQL injection
            $topicId = $this->validateTopicId($topicId);
            
            // FIXED: Use safe Eloquent ORM instead of raw SQL
            $topic = Topic::findOrFail($topicId);
            
            $questions = $topic->questions;
            
            // FIXED: Use safe collection methods instead of raw SQL
            $counts = [
                'total' => $questions->count(),
                'sources' => $questions->groupBy('source')
                    ->map(function($group) {
                        return $group->count();
                    })
            ];
            
            $this->logOperation('question_counts_retrieved', $topicId, count($questions));
            
            return $this->responseFactory->json($counts);
            
        } catch (Exception $e) {
            $this->logError('Failed to get question counts', $e, $topicId ?? null);
            return $this->responseFactory->json(['error' => 'Failed to get question counts'], 500);
        }
    }

    /**
     * FIXED: Extract topic ID validation to prevent SQL injection
     */
    private function validateTopicId($topicId): int
    {
        if (!is_numeric($topicId) || $topicId <= 0) {
            throw new \InvalidArgumentException('Invalid topic ID');
        }
        return (int) $topicId;
    }

    /**
     * FIXED: Extract logging logic to eliminate duplication
     */
    private function logOperation(string $operation, int $topicId, int $count): void
    {
        $this->logger->info("Question count operation: {$operation}", [
            'topic_id' => $topicId,
            'question_count' => $count,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * FIXED: Extract error logging logic to eliminate duplication
     */
    private function logError(string $message, Exception $exception, ?int $topicId = null): void
    {
        $this->logger->error($message, [
            'error' => $exception->getMessage(),
            'topic_id' => $topicId,
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
