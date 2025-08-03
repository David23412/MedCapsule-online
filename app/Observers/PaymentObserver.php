<?php

namespace App\Observers;

use App\Models\Payment;

class PaymentObserver
{
    public function creating(Payment $payment)
    {
        // Set default status if not provided
        if (!$payment->status) {
            $payment->status = 'pending';
        }
    }
}