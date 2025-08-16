<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use App\Models\Notification;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Auth\Factory as Auth;

class NotificationCleanupController extends Controller
{
    private LoggerInterface $logger;
    private ResponseFactory $responseFactory;
    private Auth $auth;

    public function __construct(LoggerInterface $logger, ResponseFactory $responseFactory, Auth $auth)
    {
        $this->middleware('auth');
        $this->logger = $logger;
        $this->responseFactory = $responseFactory;
        $this->auth = $auth;
    }

    public function cleanup(Request $request): JsonResponse
    {
        try {
            $userId = $this->auth->id();
            $days = $request->input('days', 30);

            $deletedCount = Notification::where('user_id', $userId)
                ->where('created_at', '<', now()->subDays($days))
                ->delete();

            $this->logger->info("Notification cleanup completed for user {$userId}. Deleted {$deletedCount} notifications older than {$days} days.");

            return $this->responseFactory->json(['success' => true, 'message' => "Successfully deleted {$deletedCount} notifications."]);

        } catch (\Exception $e) {
            $this->logger->error("Notification cleanup failed for user {$this->auth->id()}: " . $e->getMessage());
            return $this->responseFactory->json(['success' => false, 'message' => 'Failed to cleanup notifications.'], 500);
        }
    }
}
