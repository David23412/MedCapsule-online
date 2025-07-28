<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function adminPaymentHistory(Request $request)
    {
        $query = Payment::with('user', 'course');
        
        // Handle search
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
                ->orWhere('reference_number', 'like', "%{$search}%")
                ->orWhere('payment_data->transaction_id', 'like', "%{$search}%");
            });
        }
        
        $payments = $query->orderBy('created_at', 'desc')->paginate(20);
        
        // Transform data for frontend
        $transformedPayments = $payments->getCollection()->map(function($payment) {
            return [
                'id' => $payment->id,
                'user_name' => $payment->user->name ?? 'Unknown User',
                'user_email' => $payment->user->email ?? 'No Email',
                'course_name' => $payment->course->name ?? 'Unknown Course',
                'amount' => $payment->amount,
                'currency' => 'EGP',
                'payment_method' => $payment->payment_method,
                'status' => $payment->status,
                'reference_number' => $payment->reference_number,
                'created_at' => $payment->created_at->toISOString(),
                'paid_at' => $payment->paid_at ? $payment->paid_at->toISOString() : null,
                'payment_data' => $payment->payment_data,
                'expired_at' => null // Add if you have this field
            ];
        });
        
        return response()->json([
            'success' => true,
            'payments' => [
                'data' => $transformedPayments,
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total()
            ]
        ]);
    }

    public function adminPaymentDetails(Payment $payment)
    {
        $payment->load('user', 'course');
        
        return view('admin.payment-details', compact('payment'));
    }
}