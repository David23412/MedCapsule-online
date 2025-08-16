<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

/**
 * 🚀 PERFORMANCE BEAST: Focused Payment Management Controller
 * Handles: Payment listing, settings update
 * Lines: ~100 (well under 200 limit)
 */
class PaymentManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Get payments for admin
     */
    public function payments(Request $request)
    {
        try {
            $query = Payment::with(['user:id,name,email', 'course:id,name']);

            // Apply filters
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->whereHas('user', function($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('course', function($courseQuery) use ($search) {
                        $courseQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhere('reference_number', 'like', "%{$search}%");
                });
            }

            $payments = $query->orderBy('created_at', 'desc')
                             ->paginate(50);

            return response()->json([
                'success' => true,
                'payments' => $payments
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load payments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load payments.'
            ], 500);
        }
    }

    /**
     * Update payment settings
     */
    public function updatePaymentSettings(Request $request)
    {
        try {
            $validated = $request->validate([
                'payment_id' => 'required|integer|exists:payments,id',
                'status' => 'required|string|in:pending,completed,failed,expired'
            ]);

            $payment = Payment::findOrFail($validated['payment_id']);
            
            // Update payment status based on the new status
            switch ($validated['status']) {
                case 'completed':
                    $payment->markAsPaid();
                    break;
                case 'failed':
                    $payment->markAsFailed('Admin marked as failed');
                    break;
                case 'expired':
                    $payment->failed_at = now();
                    $payment->save();
                    break;
                default:
                    // For pending, just update the status
                    $payment->failed_at = null;
                    $payment->paid_at = null;
                    $payment->save();
            }

            Log::info('Payment status updated', [
                'payment_id' => $payment->id,
                'new_status' => $validated['status'],
                'admin_user' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment status updated successfully.',
                'payment' => [
                    'id' => $payment->id,
                    'status' => $payment->getStatusStringAttribute(),
                    'updated_at' => $payment->updated_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update payment settings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment settings.'
            ], 500);
        }
    }
}