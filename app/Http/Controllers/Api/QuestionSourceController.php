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

class QuestionSourceController extends Controller
{
    private ResponseFactory $responseFactory;
    private LoggerInterface $logger;

    public function __construct(ResponseFactory $responseFactory, LoggerInterface $logger)
    {
        $this->responseFactory = $responseFactory;
        $this->logger = $logger;
    }

    /**
     * Get available sources for a topic
     * FIXED: Eliminated SQL injection vulnerabilities
     */
    public function getSources(Request $request, $topicId): JsonResponse
    {
        try {
            // FIXED: Validate topic ID to prevent SQL injection
            $topicId = $this->validateTopicId($topicId);
            
            // FIXED: Use safe Eloquent ORM instead of raw SQL
            $topic = Topic::findOrFail($topicId);
            
            $sources = $topic->questions()
                ->whereNotNull('source')
                ->where('source', '!=', '')
                ->distinct()
                ->pluck('source')
                ->toArray();
            
            $this->logOperation('sources_retrieved', $topicId, count($sources));
            
            return $this->responseFactory->json($sources);
            
        } catch (Exception $e) {
            $this->logError('Failed to get sources', $e, $topicId ?? null);
            return $this->responseFactory->json(['error' => 'Failed to get sources'], 500);
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
        $this->logger->info("Question source operation: {$operation}", [
            'topic_id' => $topicId,
            'source_count' => $count,
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
