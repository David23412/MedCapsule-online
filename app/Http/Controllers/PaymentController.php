<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;

class PaymentController extends Controller
{
    public function adminPaymentHistory()
    {
        $payments = Payment::with('user', 'course')
                          ->orderBy('created_at', 'desc')
                          ->paginate(20);
        
        return view('admin.payment-history', compact('payments'));
    }

    public function adminPaymentDetails(Payment $payment)
    {
        $payment->load('user', 'course');
        
        return view('admin.payment-details', compact('payment'));
    }
}