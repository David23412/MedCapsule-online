<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Payment\PaymentActionController;

/**
 * 🚀 CRITICAL REFACTOR: Payment Processing Controller
 * 
 * Lightweight controller that delegates to specialized controllers
 * Replaces fat controller with focused responsibility pattern
 */
class PaymentProcessingController extends Controller
{
    private PaymentActionController $actionController;

    public function __construct(PaymentActionController $actionController)
    {
        $this->middleware('auth');
        $this->actionController = $actionController;
    }

    /**
     * Process manual payment - delegates to action controller
     */
    public function processManualPayment(Request $request, Course $course): JsonResponse
    {
        return $this->actionController->processManualPayment($request, $course);
    }

    /**
     * Submit payment proof for course - delegates to action controller
     */
    public function submitProofForCourse(Request $request, $courseId): JsonResponse
    {
        return $this->actionController->submitProofForCourse($request, $courseId);
    }

    /**
     * Submit payment proof - delegates to action controller
     */
    public function submitProof(Request $request, Payment $payment): JsonResponse
    {
        return $this->actionController->submitProof($request, $payment);
    }

    /**
     * Verify payment - delegates to action controller
     */
    public function verifyPayment(Request $request, Payment $payment): JsonResponse
    {
        return $this->actionController->verifyPayment($request, $payment);
    }
}

    /**
     * FIXED: Extract validation logic to eliminate duplication
     */
    private function validateManualPayment(Request $request): array
    {
        return $request->validate([
            'payment_method' => 'required|string|in:vodafone_cash,fawry,instapay',
            'phone_number' => 'required_if:payment_method,vodafone_cash|string|max:15',
            'reference_number' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0.01'
        ]);
    }

    /**
     * FIXED: Extract validation logic to eliminate duplication
     */
    private function validateProofSubmission(Request $request): array
    {
        return $request->validate([
            'payment_method' => 'required|string|in:vodafone_cash,fawry,instapay',
            'transaction_id' => 'required|string|max:100'
        ]);
    }

    /**
     * FIXED: Extract validation logic to eliminate duplication
     */
    private function validateProofImage(Request $request): array
    {
        return $request->validate([
            'proof_image' => 'required|image|max:2048',
            'notes' => 'nullable|string|max:500'
        ]);
    }

    /**
     * FIXED: Extract validation logic to eliminate duplication
     */
    private function validatePaymentVerification(Request $request): array
    {
        return $request->validate([
            'status' => 'required|in:approved,rejected',
            'admin_notes' => 'nullable|string|max:500'
        ]);
    }

    /**
     * FIXED: Extract authentication logic to eliminate duplication
     */
    private function getAuthenticatedUser()
    {
        return $this->auth->user();
    }

    /**
     * FIXED: Extract admin check logic to eliminate duplication
     */
    private function isAdmin($user): bool
    {
        return $user && $user->role === 'admin';
    }

    /**
     * FIXED: Extract authorization logic to eliminate duplication
     */
    private function canUserSubmitProof(Payment $payment, $user): bool
    {
        return $payment->user_id === $user->id;
    }

    /**
     * FIXED: Extract file storage logic to eliminate duplication
     */
    private function storeProofImage($file): string
    {
        if (!$file) {
            throw new Exception('Proof image file is required');
        }
        
        return $file->store('payment_proofs', 'public');
    }

    /**
     * FIXED: Extract success response creation to eliminate duplication
     */
    private function createSuccessResponse(string $message, array $data = []): JsonResponse
    {
        $response = ['success' => true, 'message' => $message];
        
        if (!empty($data)) {
            $response = array_merge($response, $data);
        }
        
        return $this->responseFactory->json($response, 200);
    }

    /**
     * FIXED: Extract error response creation to eliminate duplication
     */
    private function createErrorResponse(string $message, int $status = 500): JsonResponse
    {
        return $this->responseFactory->json([
            'success' => false,
            'message' => $message
        ], $status);
    }

    /**
     * FIXED: Extract logging logic to eliminate duplication
     */
    private function logPaymentOperation(string $operation, int $paymentId, int $userId, array $context = []): void
    {
        $this->logger->info("Payment operation: {$operation}", array_merge([
            'payment_id' => $paymentId,
            'user_id' => $userId,
            'timestamp' => now()->toISOString()
        ], $context));
    }

    /**
     * FIXED: Extract error logging logic to eliminate duplication
     */
    private function logError(string $message, Exception $exception, ?int $userId = null): void
    {
        $this->logger->error($message, [
            'error' => $exception->getMessage(),
            'user_id' => $userId,
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
