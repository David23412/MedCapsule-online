<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentProcessingController extends Controller
{
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->middleware(['auth']);
        $this->paymentService = $paymentService;
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
                    'payment_data' => json_encode([
                        'bundle_id' => $bundleId,
                        'phone_number' => $validated['phone_number'] ?? null,
                        'course_name' => $course->name,
                        'transaction_id' => $validated['transaction_id'],
                        'submitted_at' => now(),
                        'bundle_total_amount' => $finalBundleTotal,
                        'original_course_price' => $proportionalOriginalPrice,
                        'discount_per_course' => $actualDiscount
                    ])
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
}