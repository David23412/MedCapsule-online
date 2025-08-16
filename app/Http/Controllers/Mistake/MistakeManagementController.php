<?php

namespace App\Http\Controllers\Mistake;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mistake;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Http\JsonResponse;

class MistakeManagementController extends Controller
{
    private LoggerInterface $logger;
    private CacheRepository $cache;
    private ResponseFactory $responseFactory;
    private Auth $auth;

    public function __construct(LoggerInterface $logger, CacheRepository $cache, ResponseFactory $responseFactory, Auth $auth)
    {
        $this->middleware('auth');
        $this->logger = $logger;
        $this->cache = $cache;
        $this->responseFactory = $responseFactory;
        $this->auth = $auth;
    }

    public function destroy($questionId): JsonResponse
    {
        return $this->handleMistakeOperation($questionId, function ($mistake) use ($questionId) {
            $mistake->delete();
            $this->clearMistakeCaches($this->auth->id());
            $this->logMistakeOperation('Mistake removed', $questionId, $mistake->id);
            return ['message' => 'Mistake removed successfully'];
        });
    }

    public function skipMistake($questionId): JsonResponse
    {
        return $this->handleMistakeOperation($questionId, function ($mistake) use ($questionId) {
            $mistake->skipped = true;
            $mistake->save();
            $this->clearMistakeCaches($this->auth->id());
            $this->logMistakeOperation('Mistake skipped', $questionId, $mistake->id);
            return ['message' => 'Question skipped successfully'];
        });
    }

    public function rateMistake(Request $request, $questionId): JsonResponse
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5'
        ]);

        return $this->handleMistakeOperation($questionId, function ($mistake) use ($validated, $questionId) {
            $mistake->difficulty_rating = $validated['rating'];
            $mistake->save();
            $this->logMistakeRating($questionId, $validated['rating']);
            return [
                'message' => 'Rating saved successfully',
                'rating' => $validated['rating']
            ];
        });
    }

    /**
     * FIXED: Extract common mistake operation handling to eliminate duplication
     */
    private function handleMistakeOperation($questionId, callable $operation)
    {
        try {
            $mistake = $this->findMistake($questionId);

            if (!$mistake) {
                return $this->jsonResponse(false, 'Mistake not found', [], 404);
            }

            $data = $operation($mistake);

            return $this->jsonResponse(true, $data['message'] ?? 'Operation successful', $data);

        } catch (\Exception $e) {
            $this->logError('Failed to perform mistake operation: ' . $e->getMessage());
            return $this->jsonResponse(false, 'Failed to perform mistake operation', [], 500);
        }
    }

    /**
     * FIXED: Extract common logging logic to eliminate duplication
     */
    private function logMistakeOperation(string $action, int $questionId, int $mistakeId): void
    {
        $this->logger->info($action, [
            'user_id' => $this->auth->id(),
            'question_id' => $questionId,
            'mistake_id' => $mistakeId
        ]);
    }

    /**
     * FIXED: Extract rating-specific logging to eliminate duplication
     */
    private function logMistakeRating(int $questionId, int $rating): void
    {
        $this->logger->info('Mistake rated', [
            'user_id' => $this->auth->id(),
            'question_id' => $questionId,
            'rating' => $rating
        ]);
    }

    /**
     * FIXED: Extract error logging to eliminate duplication
     */
    private function logError(string $message): void
    {
        $this->logger->error($message);
    }

    /**
     * FIXED: Extract mistake finding logic to eliminate duplication
     */
    private function findMistake($questionId): ?Mistake
    {
        return Mistake::where('user_id', $this->auth->id())
            ->where('question_id', $questionId)
            ->first();
    }

    /**
     * FIXED: Extract cache clearing logic to eliminate duplication
     */
    private function clearMistakeCaches($userId): void
    {
        $cacheKeys = $this->getMistakeCacheKeys($userId);

        foreach ($cacheKeys as $key) {
            $this->cache->forget($key);
        }

        $this->cache->tags(["user_{$userId}", 'mistakes'])->flush();
    }

    /**
     * FIXED: Extract cache key generation to eliminate duplication
     */
    private function getMistakeCacheKeys($userId): array
    {
        return [
            "user_mistakes_{$userId}",
            "user_mistake_count_{$userId}",
            "user_progress_{$userId}",
            "user_stats_{$userId}"
        ];
    }

    /**
     * FIXED: Extract JSON response creation to eliminate duplication
     */
    protected function jsonResponse(bool $success, string $message, array $data = [], int $status = 200): JsonResponse
    {
        return $this->responseFactory->json([
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString()
        ], $status);
    }
}
