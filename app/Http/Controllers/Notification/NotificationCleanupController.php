<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Services\UniversalNotificationService;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Contracts\Cache\Repository as Cache;

class NotificationCleanupController extends Controller
{
    private UniversalNotificationService $notificationService;
    private LoggerInterface $logger;
    private ResponseFactory $responseFactory;
    private Auth $auth;
    private Cache $cache;

    public function __construct(
        UniversalNotificationService $notificationService,
        LoggerInterface $logger,
        ResponseFactory $responseFactory,
        Auth $auth,
        Cache $cache
    ) {
        $this->middleware(['auth']);
        $this->notificationService = $notificationService;
        $this->logger = $logger;
        $this->responseFactory = $responseFactory;
        $this->auth = $auth;
        $this->cache = $cache;
    }

    public function cleanup(): JsonResponse
    {
        return $this->handleCleanupOperation(function ($user) {
            $removedCount = $this->notificationService->cleanupExpiredNotifications($user);
            return ['removed_count' => $removedCount, 'message' => "Successfully cleaned up {$removedCount} expired notifications"];
        }, 'Notification cleanup');
    }

    public function aggressiveCleanup(): JsonResponse
    {
        return $this->handleCleanupOperation(function ($user) {
            $deletedCount = $this->notificationService->aggressiveDeleteViewed($user);
            return ['deleted_count' => $deletedCount, 'message' => 'All viewed notifications deleted for database optimization'];
        }, 'Aggressive notification cleanup');
    }

    public function ultraCleanup(): JsonResponse
    {
        return $this->handleCleanupOperation(function ($user) {
            $deletedCount = $this->notificationService->ultraAggressiveCleanup($user, 1);
            return ['deleted_count' => $deletedCount, 'message' => 'Ultra aggressive cleanup completed for maximum database optimization'];
        }, 'Ultra aggressive notification cleanup');
    }

    public function clearTestNotifications(): JsonResponse
    {
        return $this->handleCleanupOperation(function ($user) {
            $this->cache->forget("user_notifications_{$user->id}");
            $this->cache->put("user_unread_count_{$user->id}", 0, 86400);
            return ['message' => 'All test notifications cleared'];
        }, 'Clear test notifications');
    }

    private function handleCleanupOperation(callable $cleanupLogic, string $operationName): JsonResponse
    {
        try {
            $user = $this->auth->user();
            $result = $cleanupLogic($user);

            $this->logger->info("{$operationName} completed", array_merge(['user_id' => $user->id], $result));

            return $this->jsonResponse($result['message'], 200, true, $result);
        } catch (\Exception $e) {
            $this->logger->error("{$operationName} failed", [
                'error' => $e->getMessage(),
                'user_id' => $this->auth->id(),
            ]);
            return $this->jsonResponse("Failed to {$operationName}", 500, false);
        }
    }

    private function jsonResponse(string $message, int $status, bool $success, array $data = []): JsonResponse
    {
        $responseData = ['success' => $success, 'message' => $message];
        if (!empty($data)) {
            $responseData = array_merge($responseData, $data);
        }
        return $this->responseFactory->json($responseData, $status);
    }
}
