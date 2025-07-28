<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentVerificationRequest;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 💀 NUCLEAR OPTIMIZED PAYMENT ADMIN CONTROLLER 💀
 * Handles admin payment operations with F-16 RAPTOR SPEED
 */
class PaymentAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
        $this->middleware(function ($request, $next) {
            if (auth()->user()->role !== 'admin') {
                abort(403, 'Unauthorized access.');
            }
            return $next($request);
        });
    }

    /**
     * Admin payment history with optimized queries
     */
    public function adminPaymentHistory(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $status = $request->input('status');
        $search = $request->input('search');

        // Build optimized query with ultra-aggressive eager loading
        $query = Payment::select([
                'payments.id',
                'payments.user_id', 
                'payments.course_id',
                'payments.amount',
                'payments.status',
                'payments.payment_method',
                'payments.created_at',
                'payments.payment_data'
            ])
            ->with([
                'user:id,name,email,university',
                'course:id,name,description,price',
                'course.topics:id,course_id,name',
                'user.quizAttempts:id,user_id,topic_id,score,created_at'
            ])
            ->withCount(['user.quizAttempts', 'course.topics'])
            ->with([
                'user:id,name,email,university',
                'course:id,name,price,description',
                'course.topics:id,course_id,name',
                'user.quizAttempts:id,user_id,score,created_at'
            ])
            ->with([
                'user:id,name,email',
                'course:id,name'
            ]);

        // Apply filters
        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $payments = $query->latest('created_at')
            ->paginate($perPage);

        // Cache statistics
        // LETHAL QUERY OPTIMIZATION: Single query for all stats
        $stats = Cache::remember('admin_payment_stats', 300, function() {
            return Payment::selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = "proof_submitted" THEN 1 ELSE 0 END) as proof_submitted,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected
            ')->first()->toArray();
        });

        return view('admin.payments.history', compact('payments', 'stats'));
    }

    /**
     * Admin payment details
     */
    public function adminPaymentDetails(Payment $payment)
    {
        $payment->load([
            'user:id,name,email,phone',
            'course:id,name,price'
        ]);

        return view('admin.payments.details', compact('payment'));
    }

    /**
     * Admin verify payment
     */
    public function adminVerifyPayment(Request $request, Payment $payment)
    {
        // Check if user is authenticated and is admin
        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $validated = $request->validate([
            'action' => 'nullable|string|in:verify,reject',
            'rejection_reason' => 'required_if:action,reject|string|max:500',
            'admin_notes' => 'nullable|string|max:1000'
        ]);

        try {
            // Default to verify if no action specified
            $action = $validated['action'] ?? 'verify';
            
            if ($action === 'verify') {
                $payment->update([
                    'status' => 'completed',
                    'paid_at' => now(),
                    'payment_data' => array_merge($payment->payment_data ?? [], [
                        'verified_by' => auth()->user()->name,
                        'verified_at' => now()->toDateTimeString(),
                        'admin_notes' => $validated['admin_notes'] ?? null
                    ])
                ]);

                // Enroll user in course
                $user = User::find($payment->user_id);
                if ($user) {
                    $user->courses()->syncWithoutDetaching([$payment->course_id]);
                }

                // 🔔 CRITICAL NOTIFICATION INTEGRATION - Payment Success
                \App\Services\NotificationTriggerService::onPaymentSuccess($user, [
                    'amount' => $payment->amount,
                    'course_name' => $payment->course->name ?? 'Course',
                    'payment_id' => $payment->id,
                    'payment_method' => $payment->payment_method ?? 'manual'
                ]);

                // 🔥 LETHAL FIX: Dispatch PaymentSuccessful event
                try {
                    // Create a PaymentSuccessful event (we'll need to create this)
                    event(new \App\Events\PaymentSuccessful($payment, $user));
                } catch (\Exception $e) {
                    Log::error('Failed to dispatch payment successful event', [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage()
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Payment verified successfully',
                    'payment_id' => $payment->id,
                    'status' => $payment->status
                ]);
            } else {
                $payment->update([
                    'status' => 'rejected',
                    'payment_data' => array_merge($payment->payment_data ?? [], [
                        'rejected_by' => auth()->user()->name,
                        'rejected_at' => now()->toDateTimeString(),
                        'rejection_reason' => $validated['rejection_reason'],
                        'admin_notes' => $validated['admin_notes'] ?? null
                    ])
                ]);

                // 🔔 CRITICAL NOTIFICATION INTEGRATION - Payment Failed
                $user = User::find($payment->user_id);
                if ($user) {
                    \App\Services\NotificationTriggerService::onPaymentFailed($user, [
                        'course_name' => $payment->course->name ?? 'Course',
                        'payment_id' => $payment->id,
                        'rejection_reason' => $validated['rejection_reason'],
                        'amount' => $payment->amount
                    ]);
                }

                // 🔥 LETHAL FIX: Dispatch PaymentFailed event
                try {
                    event(new \App\Events\PaymentFailed($payment, $user, $validated['rejection_reason']));
                } catch (\Exception $e) {
                    Log::error('Failed to dispatch payment failed event', [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage()
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Payment rejected successfully',
                    'payment_id' => $payment->id,
                    'status' => $payment->status
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk verify selected payments - COMPLEXITY REDUCED
     */
    public function adminBulkVerify(PaymentVerificationRequest $request)
    {
        $validated = $request->validated();
        
        try {
            // COMPLEXITY REDUCTION: Extract payment retrieval
            $payments = $this->getValidPaymentsForBulkOperation($validated['payment_ids']);
            
            if ($payments->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid payments found for bulk operation.'
                ]);
            }

            // COMPLEXITY REDUCTION: Extract bulk processing
            $processed = $this->processBulkPayments($payments, $validated);
            
            // Clear cache and log
            Cache::forget('admin_payment_stats');
            $this->logBulkOperation($validated['action'], $processed, count($validated['payment_ids']));

            return response()->json([
                'success' => true,
                'message' => "Successfully {$validated['action']}ed {$processed} payments."
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk payment operation failed', [
                'admin_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Bulk operation failed. Please try again.'
            ], 500);
        }
    }

    /**
     * COMPLEXITY REDUCTION: Get valid payments for bulk operation
     */
    private function getValidPaymentsForBulkOperation(array $paymentIds)
    {
        return Payment::select('id', 'user_id', 'course_id', 'status', 'amount', 'payment_method')
            ->with([
                'user:id,name,email',
                'course:id,name,price'
            ])
            ->whereIn('id', $paymentIds)
            ->where('status', 'proof_submitted')
            ->get();
    }

    /**
     * COMPLEXITY REDUCTION: Process bulk payments
     */
    private function processBulkPayments($payments, array $validated): int
    {
        $processed = 0;
        $action = $validated['action'];
        
        foreach ($payments as $payment) {
            try {
                if ($action === 'verify') {
                    $this->verifyPayment($payment);
                } else {
                    $this->rejectPayment($payment, $validated['rejection_reason']);
                }
                $processed++;
            } catch (\Exception $e) {
                Log::error('Error in bulk verification', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $processed;
    }

    /**
     * COMPLEXITY REDUCTION: Verify single payment
     */
    private function verifyPayment($payment): void
    {
        $payment->update([
            'status' => 'completed',
            'paid_at' => now()
        ]);
        
        // Enroll user in course
        $user = User::select('id', 'name', 'email')->find($payment->user_id);
        $user->courses()->syncWithoutDetaching([$payment->course_id]);
    }

    /**
     * COMPLEXITY REDUCTION: Reject single payment
     */
    private function rejectPayment($payment, string $reason): void
    {
        $payment->update([
            'status' => 'rejected',
            'rejection_reason' => $reason
        ]);
    }

    /**
     * COMPLEXITY REDUCTION: Log bulk operation
     */
    private function logBulkOperation(string $action, int $processed, int $totalRequested): void
    {
        Log::info('Bulk payment operation completed', [
            'admin_id' => Auth::id(),
            'action' => $action,
            'processed' => $processed,
            'total_requested' => $totalRequested
        ]);
    }

    /**
     * Admin reject payment
     */
    public function adminRejectPayment(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        try {
            $payment->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason']
            ]);

            Log::info('Payment rejected by admin', [
                'payment_id' => $payment->id,
                'admin_id' => Auth::id(),
                'reason' => $validated['rejection_reason']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment rejected successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Payment rejection failed', [
                'payment_id' => $payment->id,
                'admin_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment rejection failed. Please try again.'
            ], 500);
        }
    }
}