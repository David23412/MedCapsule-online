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
     * Process manual payment for both single courses and bundles
     */
    public function processManualPayment(Request $request)
    {
        try {
            // Validate common fields
            $validated = $request->validate([
                'courses' => 'required|array|min:1',
                'courses.*' => 'required|integer|exists:courses,id',
                'is_bundle' => 'required|boolean'
            ]);

            // Additional validation for bundles
            if ($validated['is_bundle']) {
                $request->validate([
                    'original_price' => 'required|numeric|min:0',
                    'discount_amount' => 'required|numeric|min:0',
                    'total_price' => 'required|numeric|min:0'
                ]);
            }
            
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
                    'message' => 'You already have access to: ' . implode(', ', $accessibleCourses) . '. Please remove these courses.'
                ]);
            }
            
            // Invalidate any previous pending payments for these courses
            Payment::where('user_id', Auth::id())
                ->whereIn('course_id', $validated['courses'])
                ->where('status', 'pending')
                ->update(['status' => 'expired']);
            
            // Create payment record
            $payment = new Payment();
            $payment->user_id = Auth::id();
            $payment->course_id = $validated['courses'][0]; // Primary course
            $payment->status = 'pending';
            $payment->payment_method = 'manual_payment';
            $payment->reference_number = 'REF-' . strtoupper(Str::random(10));
            
            if ($validated['is_bundle']) {
                $payment->is_bundle = true;
                $payment->bundle_courses = $validated['courses'];
                $payment->original_price = $request->input('original_price');
                $payment->discount_amount = $request->input('discount_amount');
                $payment->amount = $request->input('total_price');
            } else {
                $payment->is_bundle = false;
                $payment->amount = $courses[0]->price ?? 200;
            }
            
            $payment->currency = config('payment.currency', 'EGP');
            $payment->payment_data = [
                'created_at' => now()->toDateTimeString(),
                'expires_at' => now()->addHours(config('payment.reference_expiry_hours', 24))->toDateTimeString(),
            ];
            $payment->save();
            
            // Generate payment instructions
            $instructions = $this->generatePaymentInstructions($payment);
            
            // Generate HTML for payment modal
            $html = view('payments.payment_methods', [
                'payment' => $payment,
                'instructions' => $instructions,
                'courses' => $courses,
                'is_bundle' => $validated['is_bundle']
            ])->render();
            
            // Log payment creation
            \Log::info('Manual payment initiated', [
                'payment_id' => $payment->id,
                'user_id' => Auth::id(),
                'is_bundle' => $validated['is_bundle'],
                'course_ids' => $validated['courses'],
                'amount' => $payment->amount
            ]);
            
            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Exception $e) {
            // Log the error
            \Log::error('Payment processing error: ' . $e->getMessage(), [
                'course_ids' => $request->input('courses', []),
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
            
            // Check if user is already enrolled
            if ($course->isUserEnrolled($user->id)) {
                return redirect()->back()->with('error', 'You are already enrolled in this course.');
            }
            
            // Check if user already has a pending payment for this course
            if ($course->hasUserPendingPayment($user->id)) {
                return redirect()->back()->with('error', 'You already have a pending payment for this course.');
            }
            
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
            $payment->status = 'pending_verification';
            
            \Log::error('About to save payment:', ['payment' => $payment->toArray()]);
            $payment->save();
            \Log::error('Payment saved successfully');

            // Create access request
            $accessRequest = new \App\Models\CourseAccessRequest();
            $accessRequest->user_id = $user->id;
            $accessRequest->course_id = $course->id;
            $accessRequest->status = 'pending';
            $accessRequest->requested_at = now();
            $accessRequest->admin_notes = "Payment submitted with transaction code: {$request->transaction_id}";
            $accessRequest->save();
            \Log::error('Access request saved successfully');

            // Send notification to admin
            app(\App\Services\NotificationService::class)->createCourseAccessRequestNotification(
                $user,
                $course
            );

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
     * Admin payment history with optimized query
     * OPTIMIZED: Reduced from N+1 queries to single optimized query
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
            // OPTIMIZED: Single query with selective column fetching and proper eager loading
            $query = Payment::select([
                'payments.id',
                'payments.user_id',
                'payments.course_id',
                'payments.amount',
                'payments.payment_method',
                'payments.status',
                'payments.reference_number',
                'payments.payment_data',
                'payments.created_at'
            ])
            ->with([
                'user:id,name,email',
                'course:id,name'
            ])
            ->orderBy('payments.created_at', 'desc');
            
            // Filter by status if provided
            if ($request->has('status') && $request->status) {
                $query->where('payments.status', $request->status);
            }
            
            // Filter by reference number if provided
            if ($request->has('reference') && !empty($request->reference)) {
                $query->where('payments.reference_number', 'like', '%' . $request->reference . '%');
            }
            
            // Filter by user if provided
            if ($request->has('user_id') && $request->user_id) {
                $query->where('payments.user_id', $request->user_id);
            }
            
            $payments = $query->paginate(20);
            
            // OPTIMIZED: Transform payment data efficiently without additional queries
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
    public function adminVerifyPayment(Request $request, Payment $payment)
    {
        if (!auth()->user()->is_admin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            // Check if there's an active payment for this user and course
            $activePayment = Payment::where('user_id', $payment->user_id)
                ->where('course_id', $payment->course_id)
                ->where('status', 'completed')
                ->where('id', '!=', $payment->id)
                ->first();

            if ($activePayment) {
                // If there's an active payment, mark it as expired in payment_data
                $activePayment->payment_data = array_merge($activePayment->payment_data ?? [], [
                    'expired_at' => now()->toDateTimeString(),
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
            
            // Invalidate any previous pending payments for this user and these courses
            Payment::where('user_id', Auth::id())
                ->whereIn('course_id', $validated['courses'])
                ->where('status', 'pending')
                ->update(['status' => 'expired']);
            
            // Create a single payment record for the bundle
            $payment = new Payment();
            $payment->user_id = Auth::id();
            $payment->course_id = $validated['courses'][0]; // Primary course
            $payment->amount = $validated['total_price'];
            $payment->currency = config('payment.currency', 'EGP');
            $payment->status = 'pending';
            $payment->payment_method = 'manual_payment';
            $payment->reference_number = 'REF-' . strtoupper(Str::random(10));
            $payment->is_bundle = true;
            $payment->bundle_courses = $validated['courses'];
            $payment->original_price = $validated['original_price'];
            $payment->discount_amount = $validated['discount_amount'];
            $payment->payment_data = [
                'created_at' => now()->toDateTimeString(),
                'expires_at' => now()->addHours(config('payment.reference_expiry_hours', 24))->toDateTimeString(),
            ];
            $payment->save();
            
            // Generate payment instructions
            $instructions = $this->generatePaymentInstructions($payment);
            
            // Generate HTML for payment modal
            $html = view('payments.payment_methods', [
                'payment' => $payment,
                'instructions' => $instructions,
                'courses' => $courses,
                'is_bundle' => true
            ])->render();
            
            // Log bundle payment creation
            \Log::info('Bundle manual payment initiated', [
                'user_id' => Auth::id(),
                'payment_id' => $payment->id,
                'course_ids' => $validated['courses'],
                'total_amount' => $validated['total_price'],
                'discount_amount' => $validated['discount_amount']
            ]);
            
            return response()->json([
                'success' => true,
                'html' => $html
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
     * Submit payment proof for both single and bundle payments
     */
    public function submitPaymentProof(Request $request, Payment $payment)
    {
        // Validate the request
        $validated = $request->validate([
            'transaction_id' => 'required|string|min:3|max:50',
            'payment_method' => 'required|string',
            'reference_number' => 'required|string',
        ]);
        
        try {
            // Check if payment belongs to user
            if ($payment->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }
            
            // Check if payment is still pending
            if ($payment->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'This payment cannot be updated.'
                ], 400);
            }
            
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
            \Log::info('Payment proof submitted', [
                'payment_id' => $payment->id,
                'user_id' => Auth::id(),
                'is_bundle' => $payment->is_bundle,
                'course_ids' => $payment->is_bundle ? $payment->bundle_courses : [$payment->course_id],
                'transaction_id' => $validated['transaction_id']
            ]);
            
            // Try auto-verification
            $autoVerified = false;
            
            if (config('payment.enable_auto_verification', true)) {
                $autoVerified = $this->attemptAutoVerification($payment, $validated['transaction_id']);
                
                if ($autoVerified) {
                    // Mark payment as verified and complete
                    $payment->payment_data = array_merge($payment->payment_data ?? [], [
                        'verified_by' => 'system',
                        'verified_at' => now()->toDateTimeString(),
                        'verification_method' => 'auto',
                        'admin_notes' => 'Automatically verified by system'
                    ]);
                    
                    // Mark as paid and handle enrollment
                    $payment->markAsPaid();
                    
                    // Log the auto-verification
                    \Log::info('Payment auto-verified', [
                        'payment_id' => $payment->id,
                        'user_id' => $payment->user_id,
                        'is_bundle' => $payment->is_bundle,
                        'course_ids' => $payment->is_bundle ? $payment->bundle_courses : [$payment->course_id],
                        'transaction_id' => $validated['transaction_id']
                    ]);
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
            \Log::error('Payment proof submission error: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your payment. Please try again or contact support.',
            ], 500);
        }
    }
} 