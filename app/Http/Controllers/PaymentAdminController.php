<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PaymentAdminService;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Routing\ResponseFactory;

class PaymentAdminController extends Controller
{
    private PaymentAdminService $paymentAdminService;
    private LoggerInterface $logger;
    private ResponseFactory $responseFactory;

    public function __construct(
        PaymentAdminService $paymentAdminService,
        LoggerInterface $logger,
        ResponseFactory $responseFactory
    ) {
        $this->middleware(['auth', 'admin']);
        $this->paymentAdminService = $paymentAdminService;
        $this->logger = $logger;
        $this->responseFactory = $responseFactory;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $payments = $this->paymentAdminService->getAllPayments($request);
            return $this->responseFactory->json(['success' => true, 'data' => $payments]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve payments', ['error' => $e->getMessage()]);
            return $this->responseFactory->json(['success' => false, 'message' => 'Failed to retrieve payments.'], 500);
        }
    }

    public function show(Request $request, $paymentId): JsonResponse
    {
        try {
            $payment = $this->paymentAdminService->getPaymentById($paymentId);
            return $this->responseFactory->json(['success' => true, 'data' => $payment]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve payment', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
            return $this->responseFactory->json(['success' => false, 'message' => 'Failed to retrieve payment.'], 500);
        }
    }
}
