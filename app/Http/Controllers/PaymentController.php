<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentVerificationService;
use App\Services\FraudDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->middleware('auth');
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
            return response()->json([
                'success' => false,
                'message' => 'You already have access to this course.',
                'redirect' => route('topics.forCourse', $course->id)
            ]);
        }
        
        // Generate HTML for payment options modal
        $html = view('payments.single_course_payment_methods', [
            'course' => $course
        ])->render();
        
        return response()->json([
            'success' => true,
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
            
            // Simple check: Does user have active enrollment in this course?
            $hasAccess = $user->courses()
                ->where('course_id', $course->id)
                ->where('enrollment_status', 'active')
                ->exists();

            if ($hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have access to this course.',
                    'redirect' => route('topics.forCourse', $course->id)
                ]);
            }
            
            // Check if course has a valid price
            if ($course->is_paid && (!isset($course->price) || $course->price <= 0)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This course has an invalid price configuration. Please contact support.',
                ]);
            }
            
            // Generate HTML for payment methods modal
            $html = view('payments.single_course_payment_methods', [
                'course' => $course
            ])->render();
            
            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Exception $e) {
            \Log::error('Payment processing error: ' . $e->getMessage(), [
                'course_id' => $course->id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your payment. Please try again or contact support.',
            ], 500);
        }
    }

    /**
     * Submit payment proof for manual payment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $courseId
     * @return \Illuminate\Http\Response
     */
    public function submitProof(Request $request, $courseId)
    {
        \Log::error('=============== PAYMENT SUBMISSION START ===============');
        \Log::error('Request details:', [
            'method' => $request->method(),
            'url' => $request->url(),
            'all_input' => $request->all(),
            'headers' => $request->headers->all(),
            'ajax' => $request->ajax(),
            'wantsJson' => $request->wantsJson(),
            'xhr' => $request->header('X-Requested-With')
        ]);

        try {
            $course = Course::findOrFail($courseId);
            \Log::error('Course found:', ['course' => $course->toArray()]);
            
            $user = auth()->user();
            \Log::error('User found:', ['user' => $user->toArray()]);
            
            // Validate the transaction ID
            $validator = validator($request->all(), [
                'transaction_id' => 'required|string|min:10|unique:payments,reference_number'
            ], [
                'transaction_id.required' => 'Please provide a transaction code.',
                'transaction_id.min' => 'Transaction code must be at least 10 characters long.',
                'transaction_id.unique' => 'This transaction code has already been used.'
            ]);

            if ($validator->fails()) {
                \Log::error('Validation failed:', ['errors' => $validator->errors()->toArray()]);
                throw new ValidationException($validator);
            }

            \Log::error('Validation passed');

            // Create the payment record
            $payment = new Payment();
            $payment->user_id = $user->id;
            $payment->course_id = $course->id;
            $payment->reference_number = $request->transaction_id;
            $payment->amount = $course->price;
            $payment->payment_method = 'manual';
            $payment->status = 'pending';
            
            \Log::error('About to save payment:', ['payment' => $payment->toArray()]);
            $payment->save();
            \Log::error('Payment saved successfully');

            $isAjax = $request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest';
            \Log::error('Request type:', ['is_ajax' => $isAjax]);

            if ($isAjax) {
                \Log::error('Sending JSON response');
                return response()->json([
                    'success' => true,
                    'message' => 'Your payment has been submitted successfully! Our team will verify it shortly.'
                ]);
            }

            \Log::error('Sending redirect response');
            return redirect()->route('courses.index')
                ->with('success', 'Your payment has been submitted successfully! Our team will verify it shortly.');

        } catch (ValidationException $e) {
            \Log::error('Validation exception:', [
                'errors' => $e->validator->errors()->toArray(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => $e->validator->errors()->first()
                ], 422);
            }
            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            \Log::error('General exception:', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not process payment. Please try again.'
                ], 500);
            }
            return redirect()->back()
                ->with('error', 'Could not process payment. Please try again.')
                ->withInput();
        } finally {
            \Log::error('=============== PAYMENT SUBMISSION END ===============');
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
            \Log::error('Error fetching payment history: ' . $e->getMessage(), [
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
                \Log::info("Admin viewed payment and marked {$notificationsRead} notifications as read", [
                    'admin_id' => Auth::id(),
                    'payment_id' => $payment->id
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to mark notifications as read', [
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
    public function adminVerifyPayment(Request $request, \App\Models\Payment $payment)
    {
        if (!auth()->user()->is_admin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            // Check if there's an active payment for this user and course
            $activePayment = Payment::where('user_id', $payment->user_id)
                ->where('course_id', $payment->course_id)
                ->where('status', 'completed')
                ->whereNull('expired_at')
                ->where('id', '!=', $payment->id)
                ->first();

            if ($activePayment) {
                // If there's an active payment, expire it before activating the new one
                $activePayment->expired_at = now();
                $activePayment->payment_data = array_merge($activePayment->payment_data ?? [], [
                    'expired_by' => auth()->id(),
                    'expiry_reason' => 'New payment verification'
                ]);
                $activePayment->save();
            }

            // Update payment status
            $payment->status = 'completed';
            $payment->paid_at = now();
            $payment->payment_data = array_merge($payment->payment_data ?? [], [
                'verified_by' => auth()->id(),
                'verified_at' => now()->toDateTimeString(),
                'admin_notes' => $request->input('admin_notes'),
                'verification_method' => 'admin_manual',
                'is_renewal' => $activePayment ? true : false
            ]);
            $payment->save();

            // Grant course access to user
            $user = \App\Models\User::find($payment->user_id);
            $course = \App\Models\Course::find($payment->course_id);
            
            if ($user && $course) {
                // Attach the course with active status and enrollment date
                $user->courses()->syncWithoutDetaching([
                    $course->id => [
                        'enrollment_status' => 'active',
                        'enrolled_at' => now()
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment verified successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Payment verification error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Could not verify payment. Please try again.'
            ], 500);
        }
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
                \Log::error('Error in bulk verification: ' . $e->getMessage(), [
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
            \Log::warning('Unauthorized payment rejection attempt', [
                'payment_id' => $payment->id,
                'attempted_by' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }
        
        // Check if payment is in a rejectable state
        if ($payment->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot reject a completed payment.'
            ]);
        }
        
        if ($payment->status === 'expired') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot reject an expired payment.'
            ]);
        }
        
        try {
            $reason = $request->input('reason', 'Payment verification failed');
            
            // Update payment status
            $payment->status = 'rejected';
            $payment->payment_data = array_merge($payment->payment_data ?? [], [
                'rejected_by' => Auth::user()->id,
                'rejected_at' => now()->toDateTimeString(),
                'rejection_reason' => $reason,
                'admin_notes' => $request->input('admin_notes', ''),
                'rejection_method' => 'admin'
            ]);
            $payment->save();
            
            // Log the rejection
            \Log::info('Payment rejected by admin', [
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
                'course_id' => $payment->course_id,
                'admin_id' => Auth::id(),
                'reason' => $reason
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Payment rejected'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error rejecting payment: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while rejecting the payment.'
            ], 500);
        }
    }

    /**
     * Create a new payment
     */
    private function createPayment(User $user, Course $course, string $paymentMethod)
    {
        // Ensure course price is valid
        $price = $course->is_paid ? ($course->price ?? 0) : 0;
        
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
                \Log::info('Payment auto-verified', [
                    'payment_id' => $payment->id,
                    'user_id' => $payment->user_id,
                    'course_id' => $payment->course_id,
                    'transaction_id' => $transactionId
                ]);
                
                // Notify admin if configured
                if (config('payment.notify_admin_on_new_payment', true)) {
                    // This would be implemented with a notification or email class
                    // For now we'll just log it
                    \Log::info('Admin notification for auto-verified payment', [
                        'payment_id' => $payment->id,
                        'admin_email' => config('payment.admin_email'),
                    ]);
                }
                
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            \Log::error('Auto-verification error: ' . $e->getMessage(), [
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
            \Log::info('Bundle manual payment initiated', [
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
            \Log::error('Bundle payment processing error: ' . $e->getMessage(), [
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
                \Log::info('Bundle payment proof submitted', [
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
                        \Log::info('Bundle payment auto-verified', [
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
            \Log::error('Bundle payment proof submission error: ' . $e->getMessage(), [
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