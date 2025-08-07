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
        // Wrap payment processing in database transaction
        DB::beginTransaction();
        try {
            public function __construct(Payment $payment, User $user, string $reason = 'Payment processing failed')
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    {
        $this->payment = $payment;
        $this->user = $user;
        $this->reason = $reason;
        $this->paymentMethod = $payment->payment_method ?? 'manual';
        $this->amount = $payment->amount ?? 0;
    }
}