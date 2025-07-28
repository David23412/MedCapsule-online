<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 💀 NUCLEAR OPTIMIZED PAYMENT STATUS CONTROLLER 💀
 * Handles payment status operations with F-16 RAPTOR SPEED
 */
class PaymentStatusController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * View user payment history
     */
    public function history()
    {
        $user = Auth::user();
        
        if ($user->role === 'admin') {
            return redirect()->route('admin.payments.history');
        }

        // Get user payments with ultra-aggressive eager loading
        $payments = Payment::select([
                'id', 'course_id', 'amount', 'status', 
                'payment_method', 'created_at', 'paid_at', 'payment_data'
            ])
            ->with([
                'course:id,name,description,price',
                'course.topics:id,course_id,name',
                'course.users:id,name,email'
            ])
            ->withCount(['course.topics', 'course.users'])
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->paginate(15);

        return view('payments.history', compact('payments'));
    }

    /**
     * Get payment status
     */
    public function status(Payment $payment)
    {
        // Verify payment belongs to user
        if ($payment->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to payment.');
        }

        return response()->json([
            'success' => true,
            'payment' => [
                'id' => $payment->id,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'created_at' => $payment->created_at,
                'paid_at' => $payment->paid_at,
                'rejection_reason' => $payment->rejection_reason
            ]
        ]);
    }

    /**
     * Refresh payment status
     */
    public function refreshStatus(Payment $payment)
    {
        // Verify payment belongs to user
        if ($payment->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to payment.');
        }

        try {
            // Auto-verify logic for certain payment methods
            if ($payment->status === 'proof_submitted' && $payment->payment_method === 'mobile_money') {
                $paymentData = json_decode($payment->payment_data, true);
                
                // Simple auto-verification for mobile money (can be enhanced)
                if (isset($paymentData['transaction_reference']) && 
                    strlen($paymentData['transaction_reference']) >= 8) {
                    
                    $payment->update([
                        'status' => 'completed',
                        'paid_at' => now()
                    ]);
                    
                    // Enroll user in course
                    $user = Auth::user();
                    $user->courses()->syncWithoutDetaching([$payment->course_id]);
                    
                    Log::info('Payment auto-verified', [
                        'payment_id' => $payment->id,
                        'user_id' => $user->id
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'payment' => [
                    'id' => $payment->id,
                    'status' => $payment->status,
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'created_at' => $payment->created_at,
                    'paid_at' => $payment->paid_at,
                    'rejection_reason' => $payment->rejection_reason
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Payment status refresh failed', [
                'payment_id' => $payment->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh payment status.'
            ], 500);
        }
    }

    /**
     * Retry failed payment
     */
    public function retry(Payment $payment)
    {
        // Verify payment belongs to user
        if ($payment->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to payment.');
        }

        // Only allow retry for rejected or failed payments
        if (!in_array($payment->status, ['rejected', 'failed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Payment cannot be retried in current status.'
            ]);
        }

        try {
            // Reset payment to pending status
            $payment->update([
                'status' => 'pending',
                'rejection_reason' => null,
                'paid_at' => null
            ]);

            Log::info('Payment retry initiated', [
                'payment_id' => $payment->id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment retry initiated. Please submit new proof.',
                'redirect' => route('payment.submit-proof', $payment->course_id)
            ]);

        } catch (\Exception $e) {
            Log::error('Payment retry failed', [
                'payment_id' => $payment->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment retry failed. Please try again.'
            ], 500);
        }
    }
}