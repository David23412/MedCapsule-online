<?php

namespace App\Events;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Payment $payment;
    public User $user;
    public string $reason;
    public string $paymentMethod;
    public float $amount;

    /**
     * Create a new event instance.
     */
    public function __construct(Payment $payment, User $user, string $reason = 'Payment processing failed')
    {
        $this->payment = $payment;
        $this->user = $user;
        $this->reason = $reason;
        $this->paymentMethod = $payment->payment_method ?? 'manual';
        $this->amount = $payment->amount ?? 0;
    }
}