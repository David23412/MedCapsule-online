<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Payment;
use App\Services\PaymentProcessingService;
use App\Services\PaymentVerificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Payment\Traits\PaymentControllerTrait;

/**
 * 🚀 CRITICAL REFACTOR: Payment Action Controller
 * 
 * Handles payment processing actions with clean separation of concerns
 * Extracted from fat PaymentProcessingController
 */
class PaymentActionController extends Controller
{
    use PaymentControllerTrait;

    private PaymentProcessingService $paymentService;
    private PaymentVerificationService $verificationService;

    public function __construct(
        PaymentProcessingService $paymentService,
        PaymentVerificationService $verificationService
    ) {
        $this->middleware('auth');
        $this->paymentService = $paymentService;
        $this->verificationService = $verificationService;
    }

    /**
     * Process manual payment for a course
     */
    public function processManualPayment(Request $request, Course $course): JsonResponse
    {
        $validated = $this->validateManualPayment($request);

        return $this->executeInTransaction(function () use ($validated, $course) {
            $user = $this->requireAuthenticatedUser();
            
            $payment = $this->paymentService->processManualPayment($user, $course, $validated);
            
            $this->logPaymentOperation('manual_payment_processed', $payment->id, $user->id);
            
            return $this->successResponse(
                'Payment initiated successfully. Please submit proof of payment.',
                ['payment_id' => $payment->id]
            );
        });
    }

    /**
     * Submit payment proof for a course
     */
    public function submitProofForCourse(Request $request, $courseId): JsonResponse
    {
        $validated = $this->validateProofSubmission($request);

        return $this->executeInTransaction(function () use ($validated, $courseId) {
            $user = $this->requireAuthenticatedUser();
            
            $payment = $this->paymentService->submitProofForCourse($user, $courseId, $validated);
            
            $this->logPaymentOperation('proof_submitted_for_course', $payment->id, $user->id);
            
            return $this->successResponse(
                'Payment submitted successfully and is under review.',
                ['payment_id' => $payment->id]
            );
        });
    }

    /**
     * Submit payment proof with image
     */
    public function submitProof(Request $request, Payment $payment): JsonResponse
    {
        $validated = $this->validateProofImage($request);

        return $this->executeInTransaction(function () use ($validated, $payment, $request) {
            $user = $this->requireAuthenticatedUser();
            
            $this->requirePaymentAccess($payment, $user);
            
            $proofPath = $this->storeProofImage($request->file('proof_image'));
            
            $this->paymentService->submitProofWithImage(
                $payment,
                $proofPath,
                $validated['notes'] ?? null
            );
            
            $this->logPaymentOperation('proof_submitted', $payment->id, $user->id);
            
            return $this->successResponse('Payment proof submitted successfully');
        });
    }

    /**
     * Verify payment (admin only)
     */
    public function verifyPayment(Request $request, Payment $payment): JsonResponse
    {
        $validated = $this->validatePaymentVerification($request);

        return $this->executeInTransaction(function () use ($validated, $payment) {
            $admin = $this->requireAuthenticatedUser();
            $this->requireAdmin($admin);
            
            $this->verificationService->verifyPayment(
                $payment,
                $validated['status'],
                $admin->id,
                $validated['admin_notes'] ?? null
            );
            
            $this->logPaymentOperation('payment_verified', $payment->id, $admin->id, [
                'status' => $validated['status']
            ]);
            
            return $this->successResponse('Payment ' . $validated['status'] . ' successfully');
        });
    }
}