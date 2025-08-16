<?php

namespace App\Http\Controllers\Payment\Traits;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * 🚀 CRITICAL REFACTOR: Payment Controller Trait
 * 
 * Common functionality extracted from fat PaymentProcessingController
 * Eliminates code duplication across payment controllers
 */
trait PaymentControllerTrait
{
    /**
     * Execute operation in database transaction
     */
    protected function executeInTransaction(callable $callback): JsonResponse
    {
        return DB::transaction(function () use ($callback) {
            try {
                return $callback();
            } catch (Exception $e) {
                Log::error('Payment operation failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Get authenticated user or fail
     */
    protected function requireAuthenticatedUser(): User
    {
        $user = auth()->user();
        if (!$user) {
            abort(401, 'User not authenticated');
        }
        return $user;
    }

    /**
     * Require admin access
     */
    protected function requireAdmin(User $user): void
    {
        if (!$user->isAdmin()) {
            abort(403, 'Admin access required');
        }
    }

    /**
     * Require payment access
     */
    protected function requirePaymentAccess(Payment $payment, User $user): void
    {
        if ($payment->user_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'Unauthorized to access this payment');
        }
    }

    /**
     * Create success response
     */
    protected function successResponse(string $message, array $data = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    /**
     * Create error response
     */
    protected function errorResponse(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $status);
    }

    /**
     * Store proof image
     */
    protected function storeProofImage($file): string
    {
        return $file->store('payment_proofs', 'public');
    }

    /**
     * Log payment operation
     */
    protected function logPaymentOperation(string $action, int $paymentId, int $userId, array $data = []): void
    {
        Log::info("Payment operation: {$action}", [
            'payment_id' => $paymentId,
            'user_id' => $userId,
            'data' => $data
        ]);
    }

    /**
     * Validation rules for manual payment
     */
    protected function validateManualPayment(Request $request): array
    {
        return $request->validate([
            'payment_method' => 'required|string|in:bank_transfer,gcash,paymaya',
            'reference_number' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000'
        ]);
    }

    /**
     * Validation rules for proof submission
     */
    protected function validateProofSubmission(Request $request): array
    {
        return $request->validate([
            'payment_method' => 'required|string|in:bank_transfer,gcash,paymaya',
            'transaction_id' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'proof_image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'notes' => 'nullable|string|max:1000'
        ]);
    }

    /**
     * Validation rules for proof image
     */
    protected function validateProofImage(Request $request): array
    {
        return $request->validate([
            'proof_image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'notes' => 'nullable|string|max:1000'
        ]);
    }

    /**
     * Validation rules for payment verification
     */
    protected function validatePaymentVerification(Request $request): array
    {
        return $request->validate([
            'status' => 'required|string|in:verified,rejected',
            'admin_notes' => 'nullable|string|max:1000'
        ]);
    }
}