<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Exception;

class PaymentVerificationController extends Controller
{
    private ResponseFactory $responseFactory;
    private LoggerInterface $logger;
    private DatabaseManager $db;

    public function __construct(
        ResponseFactory $responseFactory, 
        LoggerInterface $logger,
        DatabaseManager $db
    ) {
        $this->responseFactory = $responseFactory;
        $this->logger = $logger;
        $this->db = $db;
    }

    /**
     * Test payment verification route
     * FIXED: Eliminated SQL injection vulnerabilities and added database transactions
     */
    public function verifyPayment(Request $request, $paymentId): JsonResponse
    {
        return $this->db->transaction(function () use ($paymentId) {
            try {
                // FIXED: Validate payment ID to prevent SQL injection
                $paymentId = $this->validatePaymentId($paymentId);
                
                // FIXED: Use safe Eloquent ORM instead of raw SQL
                $payment = Payment::findOrFail($paymentId);
                
                // FIXED: Update payment status safely
                $this->updatePaymentStatus($payment);
                
                // FIXED: Enroll user in course safely
                $this->enrollUserInCourse($payment);
                
                $this->logOperation('payment_verified', $paymentId, $payment->user_id);
                
                return $this->responseFactory->json([
                    'success' => true,
                    'message' => 'Payment verified successfully via API',
                    'payment_id' => $payment->id,
                    'status' => $payment->status
                ]);
                
            } catch (Exception $e) {
                $this->logError('Payment verification failed', $e, $paymentId ?? null);
                return $this->responseFactory->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 500);
            }
        });
    }

    /**
     * FIXED: Extract payment ID validation to prevent SQL injection
     */
    private function validatePaymentId($paymentId): int
    {
        if (!is_numeric($paymentId) || $paymentId <= 0) {
            throw new \InvalidArgumentException('Invalid payment ID');
        }
        return (int) $paymentId;
    }

    /**
     * FIXED: Extract payment status update to eliminate duplication
     */
    private function updatePaymentStatus(Payment $payment): void
    {
        $payment->update([
            'status' => 'completed',
            'paid_at' => now(),
            'payment_data' => array_merge($payment->payment_data ?? [], [
                'verified_by' => 'api_test',
                'verified_at' => now()->toDateTimeString(),
                'admin_notes' => 'API test verification'
            ])
        ]);
    }

    /**
     * FIXED: Extract user enrollment logic to eliminate duplication
     */
    private function enrollUserInCourse(Payment $payment): void
    {
        $user = User::find($payment->user_id);
        if ($user) {
            $user->courses()->syncWithoutDetaching([$payment->course_id]);
        }
    }

    /**
     * FIXED: Extract logging logic to eliminate duplication
     */
    private function logOperation(string $operation, int $paymentId, int $userId): void
    {
        $this->logger->info("Payment verification operation: {$operation}", [
            'payment_id' => $paymentId,
            'user_id' => $userId,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * FIXED: Extract error logging logic to eliminate duplication
     */
    private function logError(string $message, Exception $exception, ?int $paymentId = null): void
    {
        $this->logger->error($message, [
            'error' => $exception->getMessage(),
            'payment_id' => $paymentId,
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
