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
                'payment_method' => 'nullable|string|in:vodafone_cash,fawry,instapay',
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
            
            // Log the payment method detection for debugging
            $paymentMethod = $validated['payment_method'] ?? 'manual';
            Log::info('Single payment submission', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'payment_method' => $paymentMethod,
                'transaction_id' => $validated['transaction_id'],
                'has_payment_method_in_request' => isset($validated['payment_method']),
                'all_request_data' => $request->all()
            ]);

            $payment = Payment::create([
                'user_id' => $user->id,
                'course_id' => $courseId,
                'amount' => $course->price,
                'payment_method' => $paymentMethod,
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
     * 🔥 FIXED BUNDLE PAYMENT SUBMISSION WITH AGGRESSIVE DEBUGGING 🔥
     */
    public function submitBundlePaymentProof(Request $request)
    {
        // AGGRESSIVE LOGGING - LOG EVERYTHING
        Log::info('🔥 BUNDLE PAYMENT: Starting submission', [
            'user_id' => Auth::id(),
            'request_data' => $request->all(),
            'timestamp' => now()
        ]);

        try {
            $validated = $request->validate([
                'courses' => 'required|array|min:1',
                'courses.*' => 'integer|exists:courses,id',
                'payment_method' => 'required|string|in:vodafone_cash,fawry,instapay',
                'transaction_id' => 'required|string|max:100',
                'phone_number' => 'nullable|string|max:20',
                'total_amount' => 'required|numeric|min:0.01'
            ]);

            Log::info('🔥 BUNDLE PAYMENT: Validation passed', [
                'validated_data' => $validated
            ]);

            $user = Auth::user();
            $bundleId = 'bundle_' . uniqid() . '_' . time();
            $createdPayments = [];
            
            // Get courses and verify they exist
            $courses = Course::select('id', 'name', 'price')
                ->whereIn('id', $validated['courses'])
                ->where('price', '>', 0)
                ->get();

            Log::info('🔥 BUNDLE PAYMENT: Courses found', [
                'requested_courses' => $validated['courses'],
                'found_courses' => $courses->pluck('id')->toArray(),
                'course_count' => $courses->count()
            ]);

            if ($courses->isEmpty()) {
                Log::warning('🔥 BUNDLE PAYMENT: No valid courses found');
                return response()->json([
                    'success' => false,
                    'message' => 'No valid courses found for bundle payment.'
                ]);
            }

            // Check for existing access - BUT ALLOW PENDING PAYMENTS
            $enrolledCourseIds = $user->courses()
                ->whereIn('course_id', $validated['courses'])
                ->where('is_enrolled', true)
                ->pluck('course_id')
                ->toArray();

            Log::info('🔥 BUNDLE PAYMENT: Enrollment check', [
                'enrolled_courses' => $enrolledCourseIds,
                'user_id' => $user->id
            ]);

            $availableCourses = $courses->whereNotIn('id', $enrolledCourseIds);
            
            if ($availableCourses->isEmpty()) {
                Log::warning('🔥 BUNDLE PAYMENT: User already enrolled in all courses');
                return response()->json([
                    'success' => false,
                    'message' => 'You already have access to all selected courses.'
                ]);
            }

            // Calculate bundle pricing
            $courseCount = $availableCourses->count();
            $individualTotal = $availableCourses->sum(function($course) {
                return floor($course->price) + 0.99;
            });
            
            $totalOriginalPrice = floor($individualTotal) + 0.99;
            
            $totalDiscount = 0;
            if ($courseCount === 2) $totalDiscount = 50;
            elseif ($courseCount === 3) $totalDiscount = 75;
            elseif ($courseCount >= 4) $totalDiscount = 100;
            
            $finalBundleTotal = floor($totalOriginalPrice - $totalDiscount) + 0.99;

            Log::info('🔥 BUNDLE PAYMENT: Pricing calculated', [
                'course_count' => $courseCount,
                'individual_total' => $individualTotal,
                'total_discount' => $totalDiscount,
                'final_total' => $finalBundleTotal
            ]);

            // Create payment records for each course
            $totalDiscountedAmount = 0;
            $totalProportionalOriginal = 0;
            $courseIndex = 0;

            foreach ($availableCourses as $course) {
                $individualPrice = floor($course->price) + 0.99;
                
                // Calculate proportional pricing
                $proportion = $individualPrice / $individualTotal;
                $proportionalOriginalPrice = $proportion * $totalOriginalPrice;
                
                if ($courseIndex === $courseCount - 1) {
                    $proportionalOriginalPrice = $totalOriginalPrice - $totalProportionalOriginal;
                    $discountedAmount = $finalBundleTotal - $totalDiscountedAmount;
                } else {
                    $discountPerCourse = $totalDiscount / $courseCount;
                    $discountedAmount = floor($proportionalOriginalPrice - $discountPerCourse) + 0.99;
                    $totalDiscountedAmount += $discountedAmount;
                    $totalProportionalOriginal += $proportionalOriginalPrice;
                }
                
                $courseIndex++;
                $actualDiscount = $proportionalOriginalPrice - $discountedAmount;
                
                // CREATE THE FUCKING PAYMENT RECORD
                $payment = Payment::create([
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'amount' => $discountedAmount,
                    'payment_method' => $validated['payment_method'],
                    'status' => 'pending',
                    'is_bundle' => true,
                    'original_price' => $proportionalOriginalPrice,
                    'discount_amount' => $actualDiscount,
                    'payment_data' => [
                        'bundle_id' => $bundleId,
                        'phone_number' => $validated['phone_number'] ?? null,
                        'course_name' => $course->name,
                        'transaction_id' => $validated['transaction_id'],
                        'submitted_at' => now(),
                        'bundle_total_amount' => $finalBundleTotal,
                        'original_course_price' => $proportionalOriginalPrice,
                        'discount_per_course' => $actualDiscount
                    ]
                ]);
                
                $createdPayments[] = $payment;

                Log::info('🔥 BUNDLE PAYMENT: Payment record created', [
                    'payment_id' => $payment->id,
                    'course_id' => $course->id,
                    'amount' => $discountedAmount,
                    'bundle_id' => $bundleId
                ]);
            }

            Log::info('🔥 BUNDLE PAYMENT: All payments created successfully', [
                'bundle_id' => $bundleId,
                'user_id' => $user->id,
                'transaction_id' => $validated['transaction_id'],
                'courses' => $validated['courses'],
                'payments_created' => count($createdPayments),
                'total_amount' => $validated['total_amount']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bundle payment submitted successfully! We created ' . count($createdPayments) . ' payment records for verification.',
                'bundle_id' => $bundleId,
                'payments_created' => count($createdPayments)
            ]);

        } catch (\Exception $e) {
            Log::error('🔥 BUNDLE PAYMENT: Submission failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'courses' => $validated['courses'] ?? [],
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit bundle payment proof. Error: ' . $e->getMessage()
            ], 500);
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