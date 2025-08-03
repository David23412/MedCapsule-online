<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentVerificationRequest;
use App\Models\Course;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 💀 NUCLEAR OPTIMIZED PAYMENT PROCESSING CONTROLLER 💀
 * Handles payment processing with F-16 RAPTOR SPEED
 */
class PaymentProcessingController extends Controller
{
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->middleware(['auth']);
        $this->paymentService = $paymentService;
    }

    /**
     * Process manual payment for a single course
     */
    public function processManualPayment(Request $request, Course $course)
    {
        try {
            $user = Auth::user();
            
            // Removed problematic eager loading - using lazy loading instead
            $course = $course->load([
                'topics:id,course_id,name',
                'questions:id,topic_id,question,difficulty',
                'users:id,name,email',
                'payments:id,course_id,user_id,status,amount,created_at'
            ]);
            
            $user = $user->load([
                'courses:id,name,price',
                'payments:id,user_id,course_id,status,amount',
                'quizAttempts:id,user_id,topic_id,score'
            ]);
            
            // Validate course access
            $validationResult = $this->paymentService->validateCourseAccess($user, $course);
            
            if (!$validationResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $validationResult['message'],
                    'redirect' => $validationResult['redirect'] ?? null
                ]);
            }

            $validated = $request->validate([
                'payment_method' => 'required|string|in:vodafone_cash,fawry,instapay',
                'phone_number' => 'required_if:payment_method,vodafone_cash|string|max:15',
                'reference_number' => 'required|string|max:50',
                'amount' => 'required|numeric|min:0.01'
            ]);

            // Create streamlined payment record
            $payment = Payment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'],
                'status' => 'pending',
                'payment_data' => json_encode([
                    'phone_number' => $validated['phone_number'] ?? null,
                    'course_name' => $course->name,
                    'method_display' => $this->getPaymentMethodDisplay($validated['payment_method'])
                ])
            ]);

            Log::info('Manual payment initiated', [
                'payment_id' => $payment->id,
                'user_id' => $user->id,
                'course_id' => $course->id,
                'amount' => $validated['amount']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment initiated successfully. Please submit proof of payment.',
                'payment_id' => $payment->id,
                'redirect' => route('payment.submit-proof', $course->id)
            ]);

        } catch (\Exception $e) {
            Log::error('Manual payment processing failed', [
                'error' => $e->getMessage(),
                'course_id' => $course->id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Process manual payment for bundle of courses - COMPLEXITY REDUCED
     */
    public function processBundleManualPayment(Request $request)
    {
        try {
            // COMPLEXITY REDUCTION: Extract validation
            $validated = $this->validateBundlePaymentRequest($request);
            
            $user = Auth::user();
            $bundleId = Str::uuid();

            // COMPLEXITY REDUCTION: Extract payment creation
            $payments = $this->createBundlePayments($validated, $user, $bundleId);

            // COMPLEXITY REDUCTION: Extract logging
            $this->logBundlePaymentInitiation($bundleId, $user, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Bundle payment initiated successfully.',
                'bundle_id' => $bundleId,
                'redirect' => route('payment.submit-bundle-proof')
            ]);

        } catch (\Exception $e) {
            Log::error('Bundle payment processing failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Bundle payment processing failed. Please try again.'
            ], 500);
        }
    }

    /**
     * COMPLEXITY REDUCTION: Validate bundle payment request
     */
    private function validateBundlePaymentRequest(Request $request): array
    {
        return $request->validate([
            'courses' => 'required|array|min:1',
            'courses.*' => 'integer|exists:courses,id',
            'payment_method' => 'required|string|in:bank_transfer,mobile_money',
            'phone_number' => 'required_if:payment_method,mobile_money|string|max:20',
            'total_amount' => 'required|numeric|min:0.01'
        ]);
    }

    /**
     * COMPLEXITY REDUCTION: Create payment records for bundle
     */
    private function createBundlePayments(array $validated, $user, string $bundleId): array
    {
        $payments = [];
        
        foreach ($validated['courses'] as $courseId) {
            $course = Course::select('id', 'name', 'price')->find($courseId);
            
            $payment = Payment::create([
                'user_id' => $user->id,
                'course_id' => $courseId,
                'amount' => $course->price,
                'payment_method' => $validated['payment_method'],
                'status' => 'pending',
                'payment_data' => json_encode([
                    'bundle_id' => $bundleId,
                    'phone_number' => $validated['phone_number'] ?? null,
                    'course_name' => $course->name,
                    'is_bundle' => true
                ])
            ]);
            
            $payments[] = $payment;
        }
        
        return $payments;
    }

    /**
     * COMPLEXITY REDUCTION: Log bundle payment initiation
     */
    private function logBundlePaymentInitiation(string $bundleId, $user, array $validated): void
    {
        Log::info('Bundle payment initiated', [
            'bundle_id' => $bundleId,
            'user_id' => $user->id,
            'courses' => $validated['courses'],
            'total_amount' => $validated['total_amount']
        ]);
    }

    /**
     * Submit payment proof - SIMPLIFIED
     */
    public function submitProof(Request $request, $courseId)
    {
        try {
            $validated = $request->validate([
                'transaction_id' => 'required|string|max:100',
                'payment_proof' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed: ' . implode(', ', $e->errors()),
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }

        try {
            $user = Auth::user();
            $course = Course::findOrFail($courseId);
            
            // Check if user already has pending payment for this course
            $existingPayment = Payment::where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->where('status', 'pending')
                ->first();
                
            if ($existingPayment) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You already have a pending payment for this course.'
                    ], 400);
                }
                return back()->with('error', 'You already have a pending payment for this course.');
            }
            
            // Create payment record
            $paymentData = [
                'transaction_id' => $validated['transaction_id'],
                'course_name' => $course->name
            ];
            
            // Handle file upload if provided
            if ($request->hasFile('payment_proof')) {
                $proofPath = $request->file('payment_proof')->store('payment_proofs', 'public');
                $paymentData['proof_path'] = $proofPath;
            }
            
            $payment = Payment::create([
                'user_id' => $user->id,
                'course_id' => $courseId,
                'amount' => $course->price,
                'payment_method' => 'manual',
                'status' => 'pending',
                'payment_data' => json_encode($paymentData)
            ]);

            // Check if this is an AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment submitted successfully! Transaction ID: ' . $validated['transaction_id'] . '. Admin will verify within 24 hours.',
                    'payment_id' => $payment->id
                ]);
            }

            return back()->with('success', 'Payment submitted! Transaction ID: ' . $validated['transaction_id'] . '. Admin will verify within 24 hours.');

        } catch (\Exception $e) {
            Log::error('Payment proof submission failed', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'user_id' => Auth::id(),
                'transaction_id' => $validated['transaction_id'] ?? 'not provided'
            ]);

            // Check if this is an AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to submit payment. Error: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to submit payment. Error: ' . $e->getMessage());
        }
    }

    /**
     * Submit bundle payment proof
     */
    public function submitBundlePaymentProof(Request $request)
    {
        $validated = $request->validate([
            'bundle_id' => 'required|string',
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'transaction_reference' => 'nullable|string|max:100'
        ]);

        try {
            $user = Auth::user();
            
            // Find bundle payments
            $payments = Payment::select('id', 'user_id', 'course_id', 'status', 'payment_data')
                ->where('user_id', $user->id)
                ->where('payment_data->bundle_id', $validated['bundle_id'])
                ->where('status', 'pending')
                ->get();

            if ($payments->isEmpty()) {
                return back()->with('error', 'No pending bundle payments found.');
            }

            // Store payment proof
            $proofPath = $request->file('payment_proof')->store('payment_proofs', 'public');
            
            // Update all bundle payments
            foreach ($payments as $payment) {
                $paymentData = is_array($payment->payment_data) ? $payment->payment_data : (json_decode($payment->payment_data, true) ?: []);
                $paymentData['proof_path'] = $proofPath;
                $paymentData['transaction_reference'] = $validated['transaction_reference'];
                $paymentData['proof_submitted_at'] = now();
                
                $payment->update([
                    'payment_data' => json_encode($paymentData),
                    'status' => 'pending'
                ]);
            }

            Log::info('Bundle payment proof submitted', [
                'bundle_id' => $validated['bundle_id'],
                'user_id' => $user->id,
                'payments_count' => $payments->count(),
                'proof_path' => $proofPath
            ]);

            return back()->with('success', 'Bundle payment proof submitted successfully. We will verify it within 24 hours.');

        } catch (\Exception $e) {
            Log::error('Bundle payment proof submission failed', [
                'error' => $e->getMessage(),
                'bundle_id' => $validated['bundle_id'],
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to submit bundle payment proof. Please try again.');
        }
    }

    /**
     * Get display name for payment method
     */
    private function getPaymentMethodDisplay(string $method): string
    {
        return match($method) {
            'vodafone_cash' => 'Vodafone Cash',
            'fawry' => 'Fawry',
            'instapay' => 'InstaPay',
            default => ucfirst(str_replace('_', ' ', $method))
        };
    }
}