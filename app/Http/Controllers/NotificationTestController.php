<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Auth\Factory as Auth;

class NotificationTestController extends Controller
{
    private NotificationService $notificationService;
    private LoggerInterface $logger;
    private ResponseFactory $responseFactory;
    private Auth $auth;

    public function __construct(
        NotificationService $notificationService,
        LoggerInterface $logger,
        ResponseFactory $responseFactory,
        Auth $auth
    ) {
        $this->middleware('auth');
        $this->notificationService = $notificationService;
        $this->logger = $logger;
        $this->responseFactory = $responseFactory;
        $this->auth = $auth;
    }

    public function sendTestNotification(Request $request): JsonResponse
    {
        try {
            $user = $this->auth->user();
            $this->notificationService->sendTestNotification($user);

            $this->logger->info('Test notification sent successfully', ['user_id' => $user->id]);

            return $this->responseFactory->json(['success' => true, 'message' => 'Test notification sent successfully.']);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send test notification', [
                'user_id' => $this->auth->id(),
                'error' => $e->getMessage(),
            ]);

            return $this->responseFactory->json(['success' => false, 'message' => 'Failed to send test notification.'], 500);
        }
    }
}
