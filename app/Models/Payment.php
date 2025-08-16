<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;
    
    /**
     * Payment status constants (Integer constants for performance)
     */
    const STATUS_PENDING = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_FAILED = 3;
    const STATUS_EXPIRED = 4;
    
    /**
     * Status string mappings for backward compatibility
     */
    const STATUS_STRINGS = [
        self::STATUS_PENDING => 'pending',
        self::STATUS_COMPLETED => 'completed',
        self::STATUS_FAILED => 'failed',
        self::STATUS_EXPIRED => 'expired'
    ];

    protected $fillable = [
        'user_id',
        'course_id',
        'reference_number',
        'amount',
        'payment_method',
        'payment_data',
        'paid_at',
        'failed_at'
    ];

    protected $casts = [
        'payment_data' => 'json',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        // Generate reference number before creating
        static::creating(function ($payment) {
            if (empty($payment->reference_number)) {
                $payment->reference_number = 'MC' . strtoupper(substr(md5(uniqid()), 0, 4)) . time() . strtoupper(substr(md5(uniqid()), 0, 8));
            }
        });

        // SSOT: Dispatch PaymentCompleted event when paid_at is set
        static::updated(function ($payment) {
            // Use SSOT: paid_at timestamp determines completion
            if ($payment->wasChanged('paid_at') && $payment->paid_at) {
                event(new \App\Events\PaymentCompleted(
                    $payment,
                    $payment->user_id,
                    [$payment->course_id]
                ));
            }
        });
    }

    /**
     * Get the user that made the payment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course that was paid for
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }



    /**
     * SSOT: Status accessor based on timestamps and business logic
     * Returns integer status for performance
     */
    public function getStatusAttribute(): int
    {
        // SSOT: Derive status from timestamps and business logic
        
        // If payment failed
        if ($this->failed_at) {
            return self::STATUS_FAILED;
        }
        
        // If payment was completed (has paid_at timestamp)
        if ($this->paid_at) {
            return self::STATUS_COMPLETED;
        }
        
        // If payment is older than 24 hours without completion
        if ($this->created_at && $this->created_at->addHours(24) < now()) {
            return self::STATUS_EXPIRED;
        }
        
        // Default status for new payments
        return self::STATUS_PENDING;
    }
    
    /**
     * Get status as string for backward compatibility
     */
    public function getStatusStringAttribute(): string
    {
        return self::STATUS_STRINGS[$this->getStatusAttribute()] ?? 'pending';
    }

    /**
     * Check if payment is completed using SSOT
     */
    public function isCompleted(): bool
    {
        return !is_null($this->paid_at);
    }

    /**
     * Check if payment is pending using SSOT
     */
    public function isPending(): bool
    {
        return is_null($this->paid_at) && !$this->isExpired();
    }

    /**
     * Check if payment is expired using SSOT
     */
    public function isExpired(): bool
    {
        return is_null($this->paid_at) && is_null($this->failed_at) && $this->created_at && $this->created_at->addHours(24) < now();
    }

    /**
     * Check if payment failed using SSOT
     */
    public function isFailed(): bool
    {
        return !is_null($this->failed_at);
    }

    /**
     * Mark a payment as completed and enroll the user in the course
     * SSOT: Clean implementation using only timestamps
     */
    public function markAsPaid()
    {
        // SSOT: Set paid_at timestamp as the authoritative field
        $this->paid_at = now();
        $this->save();

        // Single course enrollment using SSOT pattern
        $this->user->courses()->syncWithoutDetaching([$this->course_id => [
            'enrolled_at' => now() // SSOT: This is the authoritative field
        ]]);
        
        // Create notification for single course (if service exists)
        try {
            if (class_exists('App\Services\NotificationService')) {
                app(\App\Services\NotificationService::class)->createPaymentSuccessNotification(
                    $this->user,
                    $this->course->name,
                    $this->amount
                );
            }
        } catch (\Exception $e) {
            // Log but don't fail on notification errors
            \Log::info('Payment notification skipped', ['error' => $e->getMessage()]);
        }

        return $this;
    }

    /**
     * Mark a payment as failed
     * SSOT: Use failed_at timestamp
     */
    public function markAsFailed(string $reason = null)
    {
        $this->failed_at = now();
        
        // Store failure reason in payment_data JSON
        $paymentData = $this->payment_data ?? [];
        $paymentData['failure_reason'] = $reason;
        $paymentData['failed_at'] = now()->toISOString();
        
        $this->payment_data = $paymentData;
        $this->save();
        
        return $this;
    }

    /**
     * Scope for completed payments using SSOT
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('paid_at');
    }

    /**
     * Scope for pending payments using SSOT
     */
    public function scopePending($query)
    {
        return $query->whereNull('paid_at')
                     ->where('created_at', '>', now()->subHours(24));
    }

    /**
     * Scope for expired payments using SSOT
     */
    public function scopeExpired($query)
    {
        return $query->whereNull('paid_at')
                     ->where('created_at', '<=', now()->subHours(24));
    }


}
