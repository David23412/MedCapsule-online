<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TopicLockingService;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Auth\Factory as Auth;

class TopicLockingApiController extends Controller
{
    private TopicLockingService $topicLockingService;
    private LoggerInterface $logger;
    private ResponseFactory $responseFactory;
    private Auth $auth;

    public function __construct(
        TopicLockingService $topicLockingService,
        LoggerInterface $logger,
        ResponseFactory $responseFactory,
        Auth $auth
    ) {
        $this->middleware('auth');
        $this->topicLockingService = $topicLockingService;
        $this->logger = $logger;
        $this->responseFactory = $responseFactory;
        $this->auth = $auth;
    }

    public function lock(Request $request, $topicId): JsonResponse
    {
        try {
            $this->topicLockingService->lock($this->auth->id(), $topicId);
            return $this->responseFactory->json(['success' => true, 'message' => 'Topic locked successfully.']);
        } catch (\Exception $e) {
            $this->logger->error('Failed to lock topic', [
                'topic_id' => $topicId,
                'user_id' => $this->auth->id(),
                'error' => $e->getMessage(),
            ]);
            return $this->responseFactory->json(['success' => false, 'message' => 'Failed to lock topic.'], 500);
        }
    }

    public function unlock(Request $request, $topicId): JsonResponse
    {
        try {
            $this->topicLockingService->unlock($this->auth->id(), $topicId);
            return $this->responseFactory->json(['success' => true, 'message' => 'Topic unlocked successfully.']);
        } catch (\Exception $e) {
            $this->logger->error('Failed to unlock topic', [
                'topic_id' => $topicId,
                'user_id' => $this->auth->id(),
                'error' => $e->getMessage(),
            ]);
            return $this->responseFactory->json(['success' => false, 'message' => 'Failed to unlock topic.'], 500);
        }
    }
}
