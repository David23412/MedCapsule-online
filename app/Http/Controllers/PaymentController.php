<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentVerificationService;
use App\Services\FraudDetectionService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    private PaymentService $paymentService;

    /**
     * Constructor
     */
    public function __construct(PaymentService $paymentService)
    {
        $this->middleware('auth');
        $this->paymentService = $paymentService;
    }

    /**
     * Helper method to detect AJAX requests consistently
     */
    private function isAjaxRequest(Request $request): bool
    {
        return $request->ajax() || 
               $request->wantsJson() || 
               $request->header('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Helper method for consistent JSON responses
     */
    private function jsonResponse(bool $success, string $message, array $data = [], int $status = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json(array_merge([
            'success' => $success,
            'message' => $message
        ], $data), $status);
    }

    /**
     * Get payment options for a course
     */
    public function getPaymentOptions(Course $course)
    {
        $user = Auth::user();
        
        // Simple check: Does user have active enrollment in this course?
        $hasAccess = $user->courses()
            ->where('course_id', $course->id)
            ->where('enrollment_status', 'active')
            ->exists();

        if ($hasAccess) {
            return $this->jsonResponse(false, 'You already have access to this course.', [
                'redirect' => route('topics.forCourse', $course->id)
            ]);
        }
        
        // Generate HTML for payment options modal
        $html = view('payments.single_course_payment_methods', [
            'course' => $course
        ])->render();
        
        return $this->jsonResponse(true, 'Payment options loaded successfully.', [
            'html' => $html
        ]);
    }

    /**
     * Process manual payment for a course
     */
    public function processManualPayment(Request $request, Course $course)
    {
        try {
            $user = Auth::user();
            
            // Use payment service for validation
            $validationResult = $this->paymentService->validateCourseAccess($user, $course);
            
            if (!$validationResult['success']) {
                return $this->jsonResponse(false, $validationResult['message'], 
                    isset($validationResult['redirect']) ? ['redirect' => $validationResult['redirect']] : []
                );
            }
            
            // Generate HTML for payment methods modal
            $html = view('payments.single_course_payment_methods', [
                'course' => $course
            ])->render();
            
            return $this->jsonResponse(true, 'Payment methods loaded successfully.', [
                'html' => $html
            ]);
            
        } catch (\Exception $e) {
            Log::error('Payment processing error: ' . $e->getMessage(), [
                'course_id' => $course->id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->jsonResponse(false, 
                'An error occurred while processing your payment. Please try again or contact support.', 
                [], 500
            );
        }
    }

    /**
     * Submit payment proof for manual payment.
     */
    public function submitProof(Request $request, $courseId)
    {
        Log::info('Payment submission started', [
            'course_id' => $courseId,
            'user_id' => Auth::id(),
            'method' => $request->method(),
            'is_ajax' => $this->isAjaxRequest($request)
        ]);

        try {
            $course = Course::findOrFail($courseId);
            $user = auth()->user();
            
            // Validate the transaction ID
            $validationResult = $this->paymentService->validateTransactionId($request->transaction_id);
            
            if (!$validationResult['success']) {
                if ($this->isAjaxRequest($request)) {
                    return $this->jsonResponse(false, $validationResult['message'], [], 422);
                }
                return redirect()->back()->withErrors(['transaction_id' => $validationResult['message']])->withInput();
            }

            // Create the payment record
            $payment = $this->paymentService->createPaymentRecord($user, $course, $request->transaction_id);
            
            Log::info('Payment submitted successfully', [
                'payment_id' => $payment->id,
                'course_id' => $courseId,
                'user_id' => $user->id
            ]);

            $message = 'Your payment has been submitted successfully! Our team will verify it shortly.';
            
            if ($this->isAjaxRequest($request)) {
                return $this->jsonResponse(true, $message);
            }

            return redirect()->route('courses.index')->with('success', $message);

        } catch (ValidationException $e) {
            Log::warning('Payment validation failed', [
                'course_id' => $courseId,
                'user_id' => Auth::id(),
                'errors' => $e->validator->errors()->toArray()
            ]);
            
            if ($this->isAjaxRequest($request)) {
                return $this->jsonResponse(false, $e->validator->errors()->first(), [], 422);
            }
            return redirect()->back()->withErrors($e->validator)->withInput();
            
        } catch (\Exception $e) {
            Log::error('Payment submission failed', [
                'course_id' => $courseId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($this->isAjaxRequest($request)) {
                return $this->jsonResponse(false, 'Could not process payment. Please try again.', [], 500);
            }
            return redirect()->back()
                ->with('error', 'Could not process payment. Please try again.')
                ->withInput();
        }
    }

    /**
     * View payment history
     */
    public function history()
    {
        if (Auth::user()->is_admin) {
            return redirect()->route('admin.payment-history');
        }
        
        // Redirect regular users to their courses page since we don't have a dedicated payment history page
        return redirect()->route('courses.index');
    }

    /**
     * Admin payment history
     */
    public function adminPaymentHistory(Request $request)
    {
        // Ensure user is admin
        if (!Auth::user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }
        
        try {
            $query = Payment::query()
                ->with(['user', 'course'])
                ->orderBy('created_at', 'desc');
            
            // Filter by status if provided
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }
            
            // Filter by reference number if provided
            if ($request->has('reference') && !empty($request->reference)) {
                $query->where('reference_number', 'like', '%' . $request->reference . '%');
            }
            
            // Filter by user if provided
            if ($request->has('user_id') && $request->user_id) {
                $query->where('user_id', $request->user_id);
            }
            
            $payments = $query->paginate(20);
            
            // Transform payment data for the table
            $transformedPayments = $payments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'user_name' => $payment->user->name ?? 'Unknown',
                    'user_email' => $payment->user->email ?? 'Unknown',
                    'course_name' => $payment->course->name ?? 'Unknown',
                    'amount' => $payment->amount,
                    'currency' => 'EGP',
                    'payment_method' => $payment->payment_method,
                    'status' => $payment->status,
                    'reference_number' => $payment->reference_number,
                    'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                    'payment_data' => $payment->payment_data
                ];
            });
            
            return response()->json([
                'success' => true,
                'payments' => [
                    'current_page' => $payments->currentPage(),
                    'data' => $transformedPayments,
                    'first_page_url' => $payments->url(1),
                    'from' => $payments->firstItem(),
                    'last_page' => $payments->lastPage(),
                    'last_page_url' => $payments->url($payments->lastPage()),
                    'next_page_url' => $payments->nextPageUrl(),
                    'path' => $payments->path(),
                    'per_page' => $payments->perPage(),
                    'prev_page_url' => $payments->previousPageUrl(),
                    'to' => $payments->lastItem(),
                    'total' => $payments->total(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching payment history: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching payment history.'
            ], 500);
        }
    }

    /**
     * Admin payment details
     */
    public function adminPaymentDetails(Payment $payment)
    {
        // Ensure user is admin
        if (!Auth::user()->is_admin) {
            return redirect()->route('home')->with('error', 'Unauthorized access');
        }
        
        // Eager load relations to reduce queries
        $payment->load('user', 'course');
        
        // Check if the payment proof image exists
        $hasProofImage = false;
        $proofUrl = null;
        
        if (isset($payment->payment_data['proof_path'])) {
            $proofPath = $payment->payment_data['proof_path'];
            $hasProofImage = \Storage::disk('public')->exists($proofPath);
            $proofUrl = $hasProofImage ? asset('storage/' . $proofPath) : null;
        }
        
        // Mark payment notifications as read
        try {
            $notificationService = app(\App\Services\NotificationService::class);
            $notificationsRead = $notificationService->markPaymentNotificationsAsRead($payment, Auth::user());
            
            if ($notificationsRead > 0) {
                Log::info("Admin viewed payment and marked {$notificationsRead} notifications as read", [
                    'admin_id' => Auth::id(),
                    'payment_id' => $payment->id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to mark notifications as read', [
                'payment_id' => $payment->id,
                'admin_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
        }
        
        return view('admin.payments.details', compact('payment', 'hasProofImage', 'proofUrl'));
    }

    /**
     * Admin verify payment
     */
    public function adminVerifyPayment(Request $request, Payment $payment)
    {
        if (!auth()->user()->is_admin) {
            return $this->jsonResponse(false, 'Unauthorized', [], 403);
        }

        $result = $this->paymentService->verifyPayment(
            $payment, 
            auth()->user(), 
            $request->input('admin_notes')
        );

        return $this->jsonResponse(
            $result['success'], 
            $result['message'], 
            [], 
            $result['success'] ? 200 : 500
        );
    }
    
    /**
     * Bulk verify selected payments
     */
    public function adminBulkVerify(Request $request)
    {
        // Ensure user is admin
        if (!Auth::user()->is_admin) {
            return redirect()->route('home')->with('error', 'Unauthorized access');
        }
        
        $paymentIds = $request->input('payment_ids', []);
        if (empty($paymentIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No payments selected'
            ]);
        }
        
        $processed = 0;
        $errors = 0;
        
        foreach ($paymentIds as $paymentId) {
            try {
                $payment = Payment::findOrFail($paymentId);
                
                // Skip already completed payments
                if ($payment->status === 'completed') {
                    continue;
                }
                
                // Add admin verification info
                $payment->payment_data = array_merge($payment->payment_data ?? [], [
                    'verified_by' => Auth::user()->id,
                    'verified_at' => now()->toDateTimeString(),
                    'admin_notes' => 'Bulk verified',
                    'verification_method' => 'admin_bulk'
                ]);
                
                // Mark as paid
                $payment->status = 'completed';
                $payment->paid_at = now();
                $payment->save();
                
                // Enroll the user in the course
                $user = User::find($payment->user_id);
                $user->courses()->syncWithoutDetaching([$payment->course_id]);
                
                $processed++;
            } catch (\Exception $e) {
                Log::error('Error in bulk verification: ' . $e->getMessage(), [
                    'payment_id' => $paymentId,
                    'trace' => $e->getTraceAsString()
                ]);
                $errors++;
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => "Successfully processed $processed payments. Errors: $errors"
        ]);
    }

    /**
     * Admin reject payment
     */
    public function adminRejectPayment(Request $request, Payment $payment)
    {
        // Ensure user is admin
        if (!Auth::user()->is_admin) {
            Log::warning('Unauthorized payment rejection attempt', [
                'payment_id' => $payment->id,
                'attempted_by' => Auth::id()
            ]);
            
            return $this->jsonResponse(false, 'Unauthorized access', [], 403);
        }
        
        // Check if payment is in a rejectable state
        if ($payment->status === 'completed') {
            return $this->jsonResponse(false, 'Cannot reject a completed payment.');
        }
        
        if ($payment->status === 'expired') {
            return $this->jsonResponse(false, 'Cannot reject an expired payment.');
        }
        
        $result = $this->paymentService->rejectPayment(
            $payment, 
            auth()->user(), 
            $request->input('reason', 'Payment verification failed')
        );

        return $this->jsonResponse(
            $result['success'], 
            $result['message'], 
            [], 
            $result['success'] ? 200 : 500
        );
    }

    /**
     * Create a new payment
     */
    private function createPayment(User $user, Course $course, string $paymentMethod)
    {
        // Ensure course price is valid
        $price = $course->price ?? 0;
        
        // Generate a more secure reference number including user ID and course ID for better traceability
        $refNumber = 'MC' . 
                     strtoupper(substr(md5($user->id . $course->id), 0, 4)) . 
                     time() . 
                     strtoupper(Str::random(8));
        
        $payment = Payment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'reference_number' => $refNumber,
            'amount' => $price,
            'payment_method' => $paymentMethod,
            'status' => 'pending',
        ]);

        return $payment;
    }

    /**
     * Generate instructions for manual payment
     */
    private function generateManualPaymentInstructions(Payment $payment)
    {
        $paymentOptions = [
            'vodafone_cash' => config('payment.vodafone_cash.number', '01XXXXXXXXX'),
            'fawry' => config('payment.fawry.number', 'FAWRY-ACCOUNT-NUMBER'),
            'instapay' => config('payment.instapay.username', 'INSTAPAY-USERNAME')
        ];
        
        // Set expiration based on config
        $expiryHours = config('payment.reference_expiry_hours', 24);
        
        return [
            'reference_number' => $payment->reference_number,
            'amount' => $payment->amount,
            'course_name' => $payment->course->name,
            'payment_options' => $paymentOptions,
            'expires_at' => now()->addHours($expiryHours)->toDateTimeString()
        ];
    }
    
    /**
     * Attempt to automatically verify a payment
     * 
     * This allows for automating the verification process when possible
     */
    private function attemptAutoVerification(Payment $payment, string $transactionId)
    {
        try {
            // Check if auto-verification is enabled in config
            if (!config('payment.enable_auto_verification', true)) {
                return false;
            }
            
            // Get the payment method used
            $method = $payment->payment_data['payment_method_used'] ?? '';
            $isLikelyValid = false;
            
            // Get patterns from config
            $patterns = config('payment.transaction_patterns', []);
            
            // Check if the transaction ID matches the expected pattern for this payment method
            if (isset($patterns[$method]['pattern'])) {
                $pattern = '/' . $patterns[$method]['pattern'] . '/';
                if (preg_match($pattern, $transactionId)) {
                    $isLikelyValid = true;
                }
            }
            
            // For now, we'll assume the payment is valid if it matches our pattern
            // In a production environment, you would connect to the payment provider's API
            
            if ($isLikelyValid) {
                // Mark the payment as verified
                $payment->payment_data = array_merge($payment->payment_data ?? [], [
                    'verified_by' => 'system',
                    'verified_at' => now()->toDateTimeString(),
                    'verification_method' => 'auto',
                    'admin_notes' => 'Automatically verified by system'
                ]);
                
                // Mark as paid
                $payment->status = 'completed';
                $payment->paid_at = now();
                $payment->save();
                
                // Enroll the user in the course
                $user = User::find($payment->user_id);
                $user->courses()->syncWithoutDetaching([$payment->course_id]);
                
                // Log the auto-verification
                Log::info('Payment auto-verified', [
                    'payment_id' => $payment->id,
                    'user_id' => $payment->user_id,
                    'course_id' => $payment->course_id,
                    'transaction_id' => $transactionId
                ]);
                
                // Notify admin if configured
                if (config('payment.notify_admin_on_new_payment', true)) {
                    // This would be implemented with a notification or email class
                    // For now we'll just log it
                    Log::info('Admin notification for auto-verified payment', [
                        'payment_id' => $payment->id,
                        'admin_email' => config('payment.admin_email'),
                    ]);
                }
                
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('Auto-verification error: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Show the payment status page.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function status(Payment $payment)
    {
        // Check if the payment belongs to the logged-in user
        if (auth()->id() !== $payment->user_id && !auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }
        
        return view('payments.status', compact('payment'));
    }

    /**
     * Refresh payment status and redirect back to status page.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function refreshStatus(Payment $payment)
    {
        // Check if the payment belongs to the logged-in user
        if (auth()->id() !== $payment->user_id && !auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }
        
        // For payments in pending_verification, try to verify again
        if ($payment->status === 'pending_verification') {
            try {
                // Get transaction ID from payment data
                $transactionId = $payment->payment_data['transaction_id'] ?? null;
                $proofPath = $payment->payment_data['proof_path'] ?? null;
                
                if ($transactionId && $proofPath) {
                    // Attempt verification
                    $verificationService = app(PaymentVerificationService::class);
                    $verificationService->verifyPayment($payment, $transactionId, $proofPath);
                    
                    // Refresh the payment model to get updated status
                    $payment->refresh();
                }
            } catch (\Exception $e) {
                // Log error but continue - don't show error to user
                Log::error('Error refreshing payment status: ' . $e->getMessage(), [
                    'payment_id' => $payment->id,
                    'user_id' => auth()->id()
                ]);
            }
        }
        
        return redirect()->route('payments.status', $payment->id)
            ->with('message', 'Payment status refreshed.');
    }

    /**
     * Allow user to retry a rejected payment.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function retry(Payment $payment)
    {
        // Check if the payment belongs to the logged-in user
        if (auth()->id() !== $payment->user_id) {
            abort(403, 'Unauthorized action.');
        }
        
        // Only allow retry for rejected payments
        if ($payment->status !== 'rejected') {
            return redirect()->route('payments.status', $payment->id)
                ->with('error', 'Only rejected payments can be retried.');
        }
        
        // Create a new payment for the same course
        $newPayment = $this->createPayment(auth()->user(), $payment->course, $payment->payment_method);
        
        // Generate payment instructions
        $instructions = $this->generateManualPaymentInstructions($newPayment);
        
        return redirect()->route('payments.show', $newPayment->id)
            ->with('message', 'A new payment has been created. Please use the new reference number.');
    }

    /**
     * Get payment options for a bundle of courses
     */
    public function getBundlePaymentOptions(Request $request)
    {
        $validated = $request->validate([
            'courses' => 'required|array|min:2',
            'courses.*' => 'required|integer|exists:courses,id',
            'original_price' => 'required|numeric|min:0',
            'discount_amount' => 'required|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
        ]);
        
        // Check if the math adds up
        if ($validated['original_price'] - $validated['discount_amount'] != $validated['total_price']) {
            return response()->json([
                'success' => false,
                'message' => 'Price calculation mismatch. Please refresh and try again.'
            ]);
        }
        
        // Get course details
        $courses = Course::whereIn('id', $validated['courses'])->get();
        
        // Check if user already has access to any of these courses
        $accessibleCourses = [];
        foreach ($courses as $course) {
            if (Auth::user()->hasAccessToCourse($course->id)) {
                $accessibleCourses[] = $course->name;
            }
        }
        
        if (!empty($accessibleCourses)) {
            return response()->json([
                'success' => false,
                'message' => 'You already have access to: ' . implode(', ', $accessibleCourses) . '. Please remove these courses from your bundle.'
            ]);
        }
        
        // Create bundle info
        $bundle = [
            'courses' => $courses,
            'course_ids' => $validated['courses'],
            'original_price' => $validated['original_price'],
            'discount_amount' => $validated['discount_amount'],
            'total_price' => $validated['total_price'],
            'count' => count($validated['courses'])
        ];
        
        // Generate HTML for bundle payment options modal
        $html = view('payments.bundle_payment_methods', compact('bundle'))->render();
        
        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }
    
    /**
     * Process manual payment for a bundle of courses
     */
    public function processBundleManualPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'courses' => 'required|array|min:2',
                'courses.*' => 'required|integer|exists:courses,id',
                'original_price' => 'required|numeric|min:0',
                'discount_amount' => 'required|numeric|min:0',
                'total_price' => 'required|numeric|min:0',
            ]);
            
            // Check if user already has access to any of the courses
            $courses = Course::whereIn('id', $validated['courses'])->get();
            $accessibleCourses = [];
            
            foreach ($courses as $course) {
                if (Auth::user()->hasAccessToCourse($course->id)) {
                    $accessibleCourses[] = $course->name;
                }
            }
            
            if (!empty($accessibleCourses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have access to: ' . implode(', ', $accessibleCourses) . '. Please remove these courses from your bundle.'
                ]);
            }
            
            // First, invalidate any previous pending payments for this user and these courses
            Payment::where('user_id', Auth::id())
                ->whereIn('course_id', $validated['courses'])
                ->where('status', 'pending')
                ->update(['status' => 'expired']);
            
            // Generate a unique bundle ID
            $bundleId = 'BUNDLE-' . strtoupper(Str::random(8));
            
            // Create payment records for each course in the bundle
            $payments = [];
            foreach ($courses as $index => $course) {
                $payment = $this->createBundlePayment(
                    Auth::user(), 
                    $course, 
                    'manual_payment',
                    $bundleId,
                    $validated['total_price'],
                    $validated['original_price'],
                    $validated['discount_amount'],
                    count($validated['courses']),
                    $index + 1
                );
                $payments[] = $payment;
            }
            
            // Use the first payment as the reference payment
            $referencePayment = $payments[0];
            
            // Generate payment instructions
            $instructions = $this->generateBundlePaymentInstructions($referencePayment, $bundleId, $courses);
            
            // Generate HTML for bundle manual payment modal
            $html = view('payments.bundle_payment_course_selection', [
                'payment' => $referencePayment,
                'instructions' => $instructions,
                'bundle' => [
                    'id' => $bundleId,
                    'courses' => $courses,
                    'original_price' => $validated['original_price'],
                    'discount_amount' => $validated['discount_amount'],
                    'total_price' => $validated['total_price'],
                    'count' => count($validated['courses'])
                ]
            ])->render();
            
            // Log bundle payment creation
            Log::info('Bundle manual payment initiated', [
                'user_id' => Auth::id(),
                'bundle_id' => $bundleId,
                'course_ids' => $validated['courses'],
                'payment_ids' => collect($payments)->pluck('id')->toArray(),
                'total_amount' => $validated['total_price'],
                'discount_amount' => $validated['discount_amount']
            ]);
            
            return response()->json([
                'success' => true,
                'html' => $html,
                'bundle_id' => $bundleId
            ]);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Bundle payment processing error: ' . $e->getMessage(), [
                'course_ids' => $request->input('courses', []),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your bundle payment. Please try again or contact support.',
            ], 500);
        }
    }
    
    /**
     * Create a payment record for a course in a bundle
     */
    private function createBundlePayment(User $user, Course $course, string $paymentMethod, string $bundleId, float $totalPrice, float $originalPrice, float $discountAmount, int $courseCount, int $bundlePosition)
    {
        $payment = new Payment();
        $payment->user_id = $user->id;
        $payment->course_id = $course->id;
        $payment->amount = $course->price ?? 200;
        $payment->currency = config('payment.currency', 'EGP');
        $payment->status = 'pending';
        $payment->payment_method = $paymentMethod;
        $payment->reference_number = 'REF-' . strtoupper(Str::random(10));
        $payment->payment_data = [
            'bundle_id' => $bundleId,
            'bundle_position' => $bundlePosition,
            'bundle_total' => $courseCount,
            'bundle_price' => $totalPrice,
            'original_price' => $originalPrice,
            'discount_amount' => $discountAmount,
            'created_at' => now()->toDateTimeString(),
            'expires_at' => now()->addHours(config('payment.reference_expiry_hours', 24))->toDateTimeString(),
        ];
        $payment->save();
        
        return $payment;
    }
    
    /**
     * Generate payment instructions for a bundle
     */
    private function generateBundlePaymentInstructions(Payment $payment, string $bundleId, $courses)
    {
        $instructions = [
            'payment_methods' => config('payment.manual_payment_methods', []),
            'reference_number' => $payment->reference_number,
            'bundle_id' => $bundleId,
            'course_count' => count($courses),
            'course_names' => $courses->pluck('name')->toArray(),
            'expiry_hours' => config('payment.reference_expiry_hours', 24),
            'expires_at' => now()->addHours(config('payment.reference_expiry_hours', 24))->format('F j, Y, g:i a'),
            'notes' => config('payment.manual_payment_notes', ''),
            'contact_email' => config('payment.support_email', 'support@medcapsule.com'),
            'contact_phone' => config('payment.support_phone', ''),
        ];
        
        return $instructions;
    }
    
    /**
     * Submit bundle payment proof - simplified version
     */
    public function submitBundlePaymentProof(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'transaction_id' => 'required|string|min:3|max:50',
            'payment_method' => 'required|string',
            'bundle_id' => 'required|string',
            'reference_number' => 'required|string',
        ]);
        
        try {
            // Find all payments with this bundle ID for the current user
            $payments = Payment::where('user_id', Auth::id())
                                ->where('payment_data->bundle_id', $validated['bundle_id'])
                                ->where('status', 'pending')
                                ->get();
            
            if ($payments->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pending bundle payments found with this ID.'
                ], 404);
            }
            
            // Set all bundle payments to pending verification
            foreach ($payments as $payment) {
                // Update payment data with transaction info
                $payment->payment_data = array_merge($payment->payment_data ?? [], [
                    'transaction_id' => $validated['transaction_id'],
                    'payment_method_used' => $validated['payment_method'],
                    'submission_date' => now()->toDateTimeString(),
                    'submission_ip' => $request->ip(),
                    'submission_user_agent' => $request->userAgent(),
                ]);
                
                // Update payment status
                $payment->status = 'pending_verification';
                $payment->save();
                
                // Log the payment proof submission
                Log::info('Bundle payment proof submitted', [
                    'payment_id' => $payment->id,
                    'user_id' => Auth::id(),
                    'bundle_id' => $validated['bundle_id'],
                    'course_id' => $payment->course_id,
                    'transaction_id' => $validated['transaction_id']
                ]);
            }
            
            // Try auto-verification (simplified)
            // In a real implementation, you would call a verification service
            $autoVerified = false;
            
            if (config('payment.enable_auto_verification', true)) {
                // Simple auto-verification logic - for demonstration only
                // This would be replaced with actual verification logic in production
                $autoVerified = strlen($validated['transaction_id']) >= 6;
                
                if ($autoVerified) {
                    // Auto-verify all payments in this bundle
                    foreach ($payments as $payment) {
                        // Mark as verified
                        $payment->payment_data = array_merge($payment->payment_data ?? [], [
                            'verified_by' => 'system',
                            'verified_at' => now()->toDateTimeString(),
                            'verification_method' => 'auto',
                            'admin_notes' => 'Automatically verified by system'
                        ]);
                        
                        // Mark as paid
                        $payment->status = 'completed';
                        $payment->paid_at = now();
                        $payment->save();
                        
                        // Enroll the user in the course
                        $user = User::find($payment->user_id);
                        $user->courses()->syncWithoutDetaching([$payment->course_id]);
                        
                        // Log the auto-verification
                        Log::info('Bundle payment auto-verified', [
                            'payment_id' => $payment->id,
                            'user_id' => $payment->user_id,
                            'course_id' => $payment->course_id,
                            'bundle_id' => $validated['bundle_id']
                        ]);
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => $autoVerified 
                    ? 'Your payment has been automatically verified!'
                    : 'Your payment proof has been submitted and is pending verification.',
                'auto_verified' => $autoVerified
            ]);
            
        } catch (\Exception $e) {
            // Log the error
            Log::error('Bundle payment proof submission error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'bundle_id' => $request->input('bundle_id'),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your payment. Please try again or contact support.',
            ], 500);
        }
    }
} 