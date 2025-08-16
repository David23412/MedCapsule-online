<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\StudentProgressService;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Auth\Factory as Auth;

class StudentProgressController extends Controller
{
    private StudentProgressService $progressService;
    private LoggerInterface $logger;
    private ResponseFactory $responseFactory;
    private Auth $auth;

    public function __construct(
        StudentProgressService $progressService,
        LoggerInterface $logger,
        ResponseFactory $responseFactory,
        Auth $auth
    ) {
        $this->middleware('auth');
        $this->progressService = $progressService;
        $this->logger = $logger;
        $this->responseFactory = $responseFactory;
        $this->auth = $auth;
    }

    public function show(Request $request, $studentId): JsonResponse
    {
        try {
            $progressData = $this->progressService->getProgress($studentId);

            return $this->responseFactory->json(['success' => true, 'data' => $progressData]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve student progress', [
                'student_id' => $studentId,
                'error' => $e->getMessage(),
            ]);

            return $this->responseFactory->json(['success' => false, 'message' => 'Failed to retrieve student progress.'], 500);
        }
    }
}
