<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\UniversalNotificationService;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Contracts\Cache\Repository as Cache;

class NotificationViewController extends Controller
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

    public function index(Request $request): JsonResponse
    {
        return $this->handleNotificationOperation(function ($user) use ($request) {
            $limit = $request->get('limit', 100);
            $cacheKey = "notifications_{$user->id}_{$limit}_" . now()->format('Y-m-d-H-i');
            $data = $this->cache->remember($cacheKey, 300, function () use ($user, $limit) {
                return [
                    'notifications' => $this->notificationService->getNotifications($user, $limit),
                    'unread_count' => $this->notificationService->getUnreadCount($user)
                ];
            });
            return ['notifications' => $data['notifications'], 'unread_count' => $data['unread_count']];
        }, 'get notifications');
    }

    public function stats(): JsonResponse
    {
        return $this->handleNotificationOperation(function ($user) {
            $cacheKey = "notification_stats_{$user->id}_" . now()->format('Y-m-d-H');
            $stats = $this->cache->remember($cacheKey, 1800, function () use ($user) {
                return $this->notificationService->getStats($user);
            });
            return ['stats' => $stats];
        }, 'get notification stats');
    }

    public function markAllAsRead(): JsonResponse
    {
        return $this->handleNotificationOperation(function ($user) {
            $success = $this->notificationService->markAllAsRead($user);
            return ['message' => $success ? 'All notifications marked as read' : 'Failed to mark notifications as read'];
        }, 'mark all notifications as read');
    }

    public function markAsRead(Request $request): JsonResponse
    {
        return $this->handleNotificationOperation(function ($user) use ($request) {
            $notificationId = $request->input('notification_id');
            if (!$notificationId) {
                return $this->jsonResponse(false, 'Notification ID is required', [], 400);
            }
            $success = $this->notificationService->markAsRead($user, $notificationId);
            return ['message' => $success ? 'Notification marked as read' : 'Failed to mark notification as read'];
        }, 'mark notification as read');
    }

    public function deleteNotification(Request $request): JsonResponse
    {
        return $this->handleNotificationOperation(function ($user) use ($request) {
            $notificationId = $request->input('notification_id');
            if (!$notificationId) {
                return $this->jsonResponse(false, 'Notification ID is required', [], 400);
            }
            $success = $this->notificationService->deleteNotification($user, $notificationId);
            return ['message' => $success ? 'Notification deleted' : 'Failed to delete notification'];
        }, 'delete notification');
    }

    public function cleanup(): JsonResponse
    {
        return $this->handleNotificationOperation(function ($user) {
            $deletedCount = $this->notificationService->cleanupExpiredNotifications($user);
            return ['deleted_count' => $deletedCount, 'message' => "Cleaned up {$deletedCount} expired notifications"];
        }, 'cleanup notifications');
    }

    private function handleNotificationOperation(callable $operation, string $operationName): JsonResponse
    {
        try {
            $user = $this->auth->user();
            $result = $operation($user);
            return $this->jsonResponse(true, $result['message'] ?? 'Operation successful', $result);
        } catch (\Exception $e) {
            $this->logger->error("{$operationName} failed", [
                'error' => $e->getMessage(),
                'user_id' => $this->auth->id(),
            ]);
            return $this->jsonResponse(false, "Failed to {$operationName}", [], 500);
        }
    }

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
