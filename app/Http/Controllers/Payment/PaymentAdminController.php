<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentVerificationRequest;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Streamlined Payment Admin Controller
 */
class PaymentAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', function ($request, $next) {
            abort_unless(auth()->user()->isAdmin(), 403);
            return $next($request);
        }]);
    }

    /**
     * Lean payment history
     */
    public function adminPaymentHistory(Request $request)
    {
        $query = Payment::with(['user:id,name,email', 'course:id,name'])
            ->select('id', 'user_id', 'course_id', 'amount', 'status', 'payment_method', 'created_at');

        if ($status = $request->status) {
            $query->where('status', $status);
        }

        if ($search = $request->search) {
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%"));
        }

        $payments = $query->latest()->paginate(20);
        
        $stats = Cache::remember('payment_stats', 300, fn() => [
            'total' => Payment::count(),
            'pending' => Payment::where('status', 'pending')->count(),
            'completed' => Payment::where('status', 'completed')->count(),
            'rejected' => Payment::where('status', 'rejected')->count()
        ]);

        return view('admin.payments.history', compact('payments', 'stats'));
    }

    /**
     * Streamlined payment verification with auto-enrollment
     */
    public function adminVerifyPayment(Request $request, Payment $payment)
    {
        DB::transaction(function () use ($payment, $request) {
            // Ensure payment_data is an array
            $paymentData = is_array($payment->payment_data) ? $payment->payment_data : (json_decode($payment->payment_data, true) ?: []);
            
            // Update payment
            $payment->update([
                'status' => 'completed', 
                'paid_at' => now(),
                'payment_data' => array_merge($paymentData, [
                    'verified_by' => auth()->id(),
                    'verified_at' => now()->toDateTimeString(),
                    'admin_notes' => $request->admin_notes ?? 'Payment accepted'
                ])
            ]);
            
            // Auto-enroll user
            DB::table('course_user')->updateOrInsert(
                ['user_id' => $payment->user_id, 'course_id' => $payment->course_id],
                ['is_enrolled' => true, 'enrolled_at' => now(), 'updated_at' => now()]
            );
        });

        return response()->json(['success' => true, 'message' => 'Payment verified and user enrolled']);
    }

    /**
     * Reject payment
     */
    public function adminRejectPayment(Request $request, Payment $payment)
    {
        // Ensure payment_data is an array
        $paymentData = is_array($payment->payment_data) ? $payment->payment_data : (json_decode($payment->payment_data, true) ?: []);
        
        $payment->update([
            'status' => 'rejected',
            'payment_data' => array_merge($paymentData, [
                'rejection_reason' => $request->reason ?? 'Rejected by admin',
                'rejected_at' => now()->toDateTimeString(),
                'rejected_by' => auth()->id()
            ])
        ]);

        return response()->json(['success' => true, 'message' => 'Payment rejected']);
    }


}