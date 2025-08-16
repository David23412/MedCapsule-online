<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Routing\ResponseFactory;
use App\Http\Requests\LoginRequest;

class LoginController extends Controller
{
    private AuthService $authService;
    private LoggerInterface $logger;
    private ResponseFactory $responseFactory;

    public function __construct(
        AuthService $authService,
        LoggerInterface $logger,
        ResponseFactory $responseFactory
    ) {
        $this->authService = $authService;
        $this->logger = $logger;
        $this->responseFactory = $responseFactory;
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $credentials = $request->only('email', 'password');
            $token = $this->authService->login($credentials);

            if (!$token) {
                return $this->responseFactory->json(['success' => false, 'message' => 'Invalid credentials.'], 401);
            }

            return $this->responseFactory->json(['success' => true, 'token' => $token]);
        } catch (\Exception $e) {
            $this->logger->error('Login failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->responseFactory->json(['success' => false, 'message' => 'Login failed.'], 500);
        }
    }
}
