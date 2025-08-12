<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'reference_number',
        'amount',
        'payment_method',
        'status',
        'payment_data',
        'paid_at'
    ];

    protected $casts = [
        'payment_data' => 'json',
        'paid_at' => 'datetime'
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

        // PHASE 2: Dispatch PaymentCompleted event when payment is completed
        static::updated(function ($payment) {
            if ($payment->wasChanged('status') && $payment->status === 'completed') {
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
     * Mark a payment as completed and enroll the user in the course
     */
    public function markAsPaid()
    {
        $this->status = 'completed';
        $this->paid_at = now();
        $this->save();

        // Single course enrollment
        $this->user->courses()->syncWithoutDetaching([$this->course_id]);
        
        // Create notification for single course
        app(NotificationService::class)->createPaymentSuccessNotification(
            $this->user,
            $this->course->name,
            $this->amount
        );

        return $this;
    }


}
