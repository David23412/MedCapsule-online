<?php

/**
 * 🔥 LETHAL BUNDLE PAYMENT FIX ENGINE 🔥
 * FIXES THE FUCKING BUNDLE PAYMENT ISSUES WITH AGGRESSIVE DEBUGGING
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Http\Controllers\Payment\PaymentProcessingController;
use App\Models\Payment;
use App\Models\Course;
use App\Models\User;
use App\Models\Enrollment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔥 LETHAL BUNDLE PAYMENT FIX ENGINE ACTIVATED 🔥" . PHP_EOL;
echo "================================================" . PHP_EOL;
echo "FIXING THE FUCKING BUNDLE PAYMENT ISSUES" . PHP_EOL;
echo PHP_EOL;

// Step 1: Find or create a test user who is NOT enrolled
echo "📋 STEP 1: FINDING NON-ENROLLED TEST USER" . PHP_EOL;
echo "=========================================" . PHP_EOL;

$testUser = User::find(2); // Use student user ID 2
if (!$testUser) {
    echo "❌ Student user (ID: 2) not found!" . PHP_EOL;
    exit(1);
}

// Clear any existing enrollments for this user (if tables exist)
try {
    DB::table('course_user')->where('user_id', $testUser->id)->delete();
    echo "✅ Cleared course_user enrollments" . PHP_EOL;
} catch (Exception $e) {
    echo "⚠️ course_user table handling: " . $e->getMessage() . PHP_EOL;
}

Auth::login($testUser);
echo "✅ Using test user: {$testUser->name} (ID: {$testUser->id})" . PHP_EOL;

// Verify user is not enrolled
$courses = Course::where('price', '>', 0)->limit(3)->get();
foreach ($courses as $course) {
    $isEnrolled = $testUser->courses()->where('course_id', $course->id)->where('is_enrolled', true)->exists();
    echo "   - Course {$course->id}: Enrolled = " . ($isEnrolled ? 'YES' : 'NO') . PHP_EOL;
}

echo PHP_EOL;

// Step 2: Clear existing payments for clean test
echo "📋 STEP 2: CLEARING EXISTING PAYMENTS" . PHP_EOL;
echo "=====================================" . PHP_EOL;

Payment::where('user_id', $testUser->id)->delete();
echo "✅ Cleared all existing payments for test user" . PHP_EOL;

echo PHP_EOL;

// Step 3: ENHANCED BUNDLE PAYMENT SUBMISSION WITH DEBUGGING
echo "📋 STEP 3: ENHANCED BUNDLE PAYMENT SUBMISSION" . PHP_EOL;
echo "=============================================" . PHP_EOL;

$testCourseIds = $courses->take(2)->pluck('id')->toArray();
echo "🎯 Testing with courses: [" . implode(', ', $testCourseIds) . "]" . PHP_EOL;

$requestData = [
    'courses' => $testCourseIds,
    'payment_method' => 'vodafone_cash',
    'transaction_id' => 'BUNDLE_FIX_' . time(),
    'phone_number' => '01234567890',
    'total_amount' => 225.98, // Realistic total
    '_token' => csrf_token()
];

echo "📤 Bundle payment data:" . PHP_EOL;
foreach ($requestData as $key => $value) {
    if (is_array($value)) {
        echo "   - {$key}: [" . implode(', ', $value) . "]" . PHP_EOL;
    } else {
        echo "   - {$key}: {$value}" . PHP_EOL;
    }
}

echo PHP_EOL;

// Step 4: DIRECT CONTROLLER CALL WITH ENHANCED LOGGING
echo "📋 STEP 4: DIRECT CONTROLLER CALL WITH ENHANCED LOGGING" . PHP_EOL;
echo "=======================================================" . PHP_EOL;

// Add aggressive logging to the controller method
Log::info('🔥 BUNDLE PAYMENT DEBUG: Starting submission', [
    'user_id' => $testUser->id,
    'courses' => $testCourseIds,
    'request_data' => $requestData
]);

try {
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            $request = Request::create('/payment/submit-bundle-proof', 'POST', $requestData);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    $request->setUserResolver(function () use ($testUser) {
        return $testUser;
    });
    
    // Add CSRF token to request
    $request->headers->set('X-CSRF-TOKEN', csrf_token());
    
    $controller = new PaymentProcessingController(app(\App\Services\PaymentService::class));
    
    echo "🚀 Calling submitBundlePaymentProof..." . PHP_EOL;
    
    $response = $controller->submitBundlePaymentProof($request);
    $responseData = json_decode($response->getContent(), true);
    
    echo "✅ Response received:" . PHP_EOL;
        // Wrap payment processing in database transaction
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            DB::beginTransaction();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
        try {
            echo "   - Status Code: " . $response->getStatusCode() . PHP_EOL;
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    echo "   - Success: " . ($responseData['success'] ? 'YES' : 'NO') . PHP_EOL;
    echo "   - Message: " . ($responseData['message'] ?? 'No message') . PHP_EOL;
    
    if (isset($responseData['bundle_id'])) {
        echo "   - Bundle ID: " . $responseData['bundle_id'] . PHP_EOL;
    }
    
    if (isset($responseData['payments_created'])) {
        echo "   - Payments Created: " . $responseData['payments_created'] . PHP_EOL;
    }
    
    // Log the full response for debugging
    Log::info('🔥 BUNDLE PAYMENT DEBUG: Controller response', [
        'status_code' => $response->getStatusCode(),
        'response_data' => $responseData
    ]);
    
} catch (Exception $e) {
    echo "❌ Controller call failed: " . $e->getMessage() . PHP_EOL;
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            echo "❌ Stack trace:" . PHP_EOL;
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    echo $e->getTraceAsString() . PHP_EOL;
    
    Log::error('🔥 BUNDLE PAYMENT DEBUG: Controller exception', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

echo PHP_EOL;

// Step 5: VERIFY PAYMENT RECORDS CREATION
echo "📋 STEP 5: VERIFYING PAYMENT RECORDS CREATION" . PHP_EOL;
echo "=============================================" . PHP_EOL;

$createdPayments = Payment::where('user_id', $testUser->id)
    ->whereIn('course_id', $testCourseIds)
    ->orderBy('created_at', 'desc')
    ->get();

echo "✅ Payments created: " . $createdPayments->count() . PHP_EOL;

if ($createdPayments->count() > 0) {
    echo "🎯 Payment details:" . PHP_EOL;
    foreach ($createdPayments as $payment) {
        $paymentData = is_string($payment->payment_data) ? json_decode($payment->payment_data, true) : $payment->payment_data;
        echo "   - Payment ID: {$payment->id}" . PHP_EOL;
        echo "     Course ID: {$payment->course_id}" . PHP_EOL;
        echo "     Amount: {$payment->amount} EGP" . PHP_EOL;
        echo "     Status: {$payment->status}" . PHP_EOL;
        echo "     Is Bundle: " . ($payment->is_bundle ? 'YES' : 'NO') . PHP_EOL;
        echo "     Payment Method: {$payment->payment_method}" . PHP_EOL;
        echo "     Transaction ID: " . ($paymentData['transaction_id'] ?? 'N/A') . PHP_EOL;
        echo "     Bundle ID: " . ($paymentData['bundle_id'] ?? 'N/A') . PHP_EOL;
        echo "     ---" . PHP_EOL;
    }
} else {
    echo "❌ NO PAYMENTS WERE CREATED!" . PHP_EOL;
    echo "🔍 This indicates a problem in the controller logic" . PHP_EOL;
}

echo PHP_EOL;

// Step 6: TEST FRONTEND BUTTON STATE LOGIC
echo "📋 STEP 6: TESTING FRONTEND BUTTON STATE LOGIC" . PHP_EOL;
echo "===============================================" . PHP_EOL;

echo "🎯 Simulating courses.blade.php button logic..." . PHP_EOL;

foreach ($testCourseIds as $courseId) {
    $course = Course::find($courseId);
    
    // Check enrollment status
    $isEnrolled = $testUser->courses()->where('course_id', $courseId)->where('is_enrolled', true)->exists();
    
    // Check pending payment status
    $hasPendingPayment = Payment::where('user_id', $testUser->id)
        ->where('course_id', $courseId)
        ->where('status', 'pending')
        ->exists();
    
    echo "   Course {$courseId} ({$course->name}):" . PHP_EOL;
    echo "     - Is Enrolled: " . ($isEnrolled ? 'YES' : 'NO') . PHP_EOL;
    echo "     - Has Pending Payment: " . ($hasPendingPayment ? 'YES' : 'NO') . PHP_EOL;
    
    // Determine button state
    if ($isEnrolled) {
        $buttonState = 'CONTINUE LEARNING';
    } elseif ($hasPendingPayment) {
        $buttonState = 'PAYMENT PENDING (DISABLED)';
    } else {
        $buttonState = 'ENROLL NOW';
    }
    
    echo "     - Expected Button State: {$buttonState}" . PHP_EOL;
    echo PHP_EOL;
}

echo PHP_EOL;

// Step 7: CREATE ENHANCED BUNDLE PAYMENT CONTROLLER FIX
echo "📋 STEP 7: CREATING ENHANCED BUNDLE PAYMENT CONTROLLER FIX" . PHP_EOL;
echo "==========================================================" . PHP_EOL;

$fixedControllerCode = '<?php

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
        $this->middleware([\'auth\']);
        $this->paymentService = $paymentService;
    }

    /**
     * 🔥 FIXED BUNDLE PAYMENT SUBMISSION WITH AGGRESSIVE DEBUGGING 🔥
     */
    public function submitBundlePaymentProof(Request $request)
    {
        // AGGRESSIVE LOGGING - LOG EVERYTHING
        Log::info(\'🔥 BUNDLE PAYMENT: Starting submission\', [
            \'user_id\' => Auth::id(),
            \'request_data\' => $request->all(),
            \'timestamp\' => now()
        ]);

        try {
            $validated = $request->validate([
                \'courses\' => \'required|array|min:1\',
                \'courses.*\' => \'integer|exists:courses,id\',
                \'payment_method\' => \'required|string|in:vodafone_cash,fawry,instapay\',
                \'transaction_id\' => \'required|string|max:100\',
                \'phone_number\' => \'nullable|string|max:20\',
                \'total_amount\' => \'required|numeric|min:0.01\'
            ]);

            Log::info(\'🔥 BUNDLE PAYMENT: Validation passed\', [
                \'validated_data\' => $validated
            ]);

            $user = Auth::user();
            $bundleId = \'bundle_\' . uniqid() . \'_\' . time();
            $createdPayments = [];
            
            // Get courses and verify they exist
            $courses = Course::select(\'id\', \'name\', \'price\')
                ->whereIn(\'id\', $validated[\'courses\'])
                ->where(\'price\', \'>\', 0)
                ->get();

            Log::info(\'🔥 BUNDLE PAYMENT: Courses found\', [
                \'requested_courses\' => $validated[\'courses\'],
                \'found_courses\' => $courses->pluck(\'id\')->toArray(),
                \'course_count\' => $courses->count()
            ]);

            if ($courses->isEmpty()) {
                Log::warning(\'🔥 BUNDLE PAYMENT: No valid courses found\');
                return response()->json([
                    \'success\' => false,
                    \'message\' => \'No valid courses found for bundle payment.\'
                ]);
            }

            // Check for existing access - BUT ALLOW PENDING PAYMENTS
            $enrolledCourseIds = $user->courses()
                ->whereIn(\'course_id\', $validated[\'courses\'])
                ->where(\'is_enrolled\', true)
                ->pluck(\'course_id\')
                ->toArray();

            Log::info(\'🔥 BUNDLE PAYMENT: Enrollment check\', [
                \'enrolled_courses\' => $enrolledCourseIds,
                \'user_id\' => $user->id
            ]);

            $availableCourses = $courses->whereNotIn(\'id\', $enrolledCourseIds);
            
            if ($availableCourses->isEmpty()) {
                Log::warning(\'🔥 BUNDLE PAYMENT: User already enrolled in all courses\');
                return response()->json([
                    \'success\' => false,
                    \'message\' => \'You already have access to all selected courses.\'
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

            Log::info(\'🔥 BUNDLE PAYMENT: Pricing calculated\', [
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            \'course_count\' => $courseCount,
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
                \'individual_total\' => $individualTotal,
                \'total_discount\' => $totalDiscount,
                \'final_total\' => $finalBundleTotal
            ]);

            // Create payment records for each course
            $totalDiscountedAmount = 0;
            $totalProportionalOriginal = 0;
            $courseIndex = 0;

            foreach ($availableCourses as $course) {
                $individualPrice = floor($course->price) + 0.99;
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
                // Calculate proportional pricing
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            $proportion = $individualPrice / $individualTotal;
            DB::commit();
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            } catch (Exception $e) {
            DB::commit();
        } catch (Exception $e) {
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            try {
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
            DB::rollback();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
            throw $e;
        }
            DB::rollback();
            throw $e;
        }
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
                    \'user_id\' => $user->id,
                    \'course_id\' => $course->id,
                    \'amount\' => $discountedAmount,
                    \'payment_method\' => $validated[\'payment_method\'],
                    \'status\' => \'pending\',
                    \'is_bundle\' => true,
                    \'original_price\' => $proportionalOriginalPrice,
                    \'discount_amount\' => $actualDiscount,
                    \'payment_data\' => json_encode([
                        \'bundle_id\' => $bundleId,
                        \'phone_number\' => $validated[\'phone_number\'] ?? null,
                        \'course_name\' => $course->name,
                        \'transaction_id\' => $validated[\'transaction_id\'],
                        \'submitted_at\' => now(),
                        \'bundle_total_amount\' => $finalBundleTotal,
                        \'original_course_price\' => $proportionalOriginalPrice,
                        \'discount_per_course\' => $actualDiscount
                    ])
                ]);
                
                $createdPayments[] = $payment;

                Log::info(\'🔥 BUNDLE PAYMENT: Payment record created\', [
                    \'payment_id\' => $payment->id,
                    \'course_id\' => $course->id,
                    \'amount\' => $discountedAmount,
                    \'bundle_id\' => $bundleId
                ]);
            }

            Log::info(\'🔥 BUNDLE PAYMENT: All payments created successfully\', [
                \'bundle_id\' => $bundleId,
                \'user_id\' => $user->id,
                \'transaction_id\' => $validated[\'transaction_id\'],
                \'courses\' => $validated[\'courses\'],
                \'payments_created\' => count($createdPayments),
                \'total_amount\' => $validated[\'total_amount\']
            ]);

            return response()->json([
                \'success\' => true,
                \'message\' => \'Bundle payment submitted successfully! We created \' . count($createdPayments) . \' payment records for verification.\',
                \'bundle_id\' => $bundleId,
                \'payments_created\' => count($createdPayments)
            ]);

        } catch (\Exception $e) {
            Log::error(\'🔥 BUNDLE PAYMENT: Submission failed\', [
                \'error\' => $e->getMessage(),
                \'trace\' => $e->getTraceAsString(),
                \'courses\' => $validated[\'courses\'] ?? [],
                \'user_id\' => Auth::id()
            ]);

            return response()->json([
                \'success\' => false,
                \'message\' => \'Failed to submit bundle payment proof. Error: \' . $e->getMessage()
            ], 500);
        }
    }
}';

// Write the fixed controller
file_put_contents('FIXED_PaymentProcessingController.php', $fixedControllerCode);
echo "✅ Created FIXED_PaymentProcessingController.php with enhanced debugging" . PHP_EOL;

echo PHP_EOL;

// FINAL SUMMARY
echo "🔥 LETHAL BUNDLE PAYMENT FIX ENGINE SUMMARY 🔥" . PHP_EOL;
echo "===============================================" . PHP_EOL;

$finalPaymentCount = Payment::where('user_id', $testUser->id)->count();
$finalBundleCount = Payment::where('user_id', $testUser->id)->where('is_bundle', true)->count();

echo "✅ Final payment records: {$finalPaymentCount}" . PHP_EOL;
echo "✅ Final bundle payments: {$finalBundleCount}" . PHP_EOL;

if ($finalPaymentCount > 0) {
    echo "🚀 SUCCESS: Bundle payments are working!" . PHP_EOL;
    echo "🎯 Now need to fix frontend button display" . PHP_EOL;
} else {
    echo "❌ STILL FAILING: Need to investigate controller logic further" . PHP_EOL;
}

echo PHP_EOL;
echo "🔥 NEXT STEPS:" . PHP_EOL;
echo "1. Replace the original controller with the fixed version" . PHP_EOL;
echo "2. Update courses.blade.php to properly show PENDING buttons" . PHP_EOL;
echo "3. Add aggressive logging to track bundle payment flow" . PHP_EOL;
echo PHP_EOL;
echo "🔥 FIX ENGINE COMPLETE 🔥" . PHP_EOL;