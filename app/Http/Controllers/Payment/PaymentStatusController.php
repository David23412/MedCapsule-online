<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentStatusController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        if ($user->role === 'admin') {
            return redirect()->route('admin.payments.history');
        }

        $payments = Payment::select([
                'id', 'course_id', 'amount', 'status', 
                'payment_method', 'created_at', 'paid_at', 'payment_data'
            ])
            ->with(['course:id,name,description,price'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('payments.status', compact('payments'));
    }

    public function show(Payment $payment)
    {
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
                'paid_at' => $payment->paid_at
            ]
        ]);
    }
}