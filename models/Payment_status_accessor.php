<?php

/**
 * SPLIT-BRAIN FIX: Payment model status accessor
 * Add this to app/Models/Payment.php to replace status string with computed accessor
 */

// Add this method to Payment model to replace status column
public function getStatusAttribute(): string
{
    // SSOT: Derive status from timestamps and business logic
    
    // If payment was completed (has paid_at timestamp)
    if ($this->paid_at) {
        return 'completed';
    }
    
    // If payment is older than 24 hours without completion
    if ($this->created_at->addHours(24) < now()) {
        return 'expired';
    }
    
    // If payment method requires verification (e.g., bank transfer)
    if (in_array($this->payment_method, ['bank_transfer', 'manual_verification'])) {
        return 'pending_verification';
    }
    
    // Default status for new payments
    return 'pending';
}

// Add method to check if payment is completed
public function isCompleted(): bool
{
    return !is_null($this->paid_at);
}

// Add method to check if payment is pending
public function isPending(): bool
{
    return is_null($this->paid_at) && !$this->isExpired();
}

// Add method to check if payment is expired
public function isExpired(): bool
{
    return is_null($this->paid_at) && $this->created_at->addHours(24) < now();
}

// Update markAsPaid method to use SSOT
public function markAsPaid()
{
    // Only update timestamp - status is computed
    $this->paid_at = now();
    $this->save();

    // Trigger enrollment and notifications
    $this->user->courses()->syncWithoutDetaching([$this->course_id => [
        'enrolled_at' => now() // Use enrollment SSOT pattern
    ]]);
    
    app(NotificationService::class)->createPaymentSuccessNotification(
        $this->user,
        $this->course->name,
        $this->amount
    );

    return $this;
}

// Add method to mark payment as failed
public function markAsFailed(string $reason = null)
{
    // Store failure reason in payment_data JSON
    $paymentData = $this->payment_data ?? [];
    $paymentData['failure_reason'] = $reason;
    $paymentData['failed_at'] = now()->toISOString();
    
    $this->payment_data = $paymentData;
    $this->save();
    
    return $this;
}

// Add scope for completed payments
public function scopeCompleted($query)
{
    return $query->whereNotNull('paid_at');
}

// Add scope for pending payments
public function scopePending($query)
{
    return $query->whereNull('paid_at')
                 ->where('created_at', '>', now()->subHours(24));
}

// Add scope for expired payments
public function scopeExpired($query)
{
    return $query->whereNull('paid_at')
                 ->where('created_at', '<=', now()->subHours(24));
}

// Update boot method to remove status column references
protected static function boot()
{
    parent::boot();

    // Generate reference number before creating
    static::creating(function ($payment) {
        if (empty($payment->reference_number)) {
            $payment->reference_number = 'MC' . strtoupper(substr(md5(uniqid()), 0, 4)) . time() . strtoupper(substr(md5(uniqid()), 0, 8));
        }
    });

    // Dispatch PaymentCompleted event when paid_at is set
    static::updated(function ($payment) {
        if ($payment->wasChanged('paid_at') && $payment->paid_at) {
            event(new \App\Events\PaymentCompleted(
                $payment,
                $payment->user_id,
                [$payment->course_id]
            ));
        }
    });
}