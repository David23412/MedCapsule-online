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
        'paid_at',
        'is_bundle',
        'bundle_courses',
        'original_price',
        'discount_amount'
    ];

    protected $casts = [
        'payment_data' => 'json',
        'paid_at' => 'datetime',
        'is_bundle' => 'boolean',
        'bundle_courses' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();

        // PHASE 2: Dispatch PaymentCompleted event when payment is completed
        static::updated(function ($payment) {
            if ($payment->wasChanged('status') && $payment->status === 'completed') {
                $courseIds = $payment->is_bundle ? 
                    $payment->bundle_courses : 
                    [$payment->course_id];
                
                event(new \App\Events\PaymentCompleted(
                    $payment,
                    $payment->user_id,
                    $courseIds
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
     * Get the course that was paid for (primary course for bundles)
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get all courses included in this payment (for bundles)
     */
    public function courses()
    {
        if (!$this->is_bundle) {
            return collect([$this->course]);
        }
        return Course::whereIn('id', $this->bundle_courses ?? [])->get();
    }

    /**
     * Mark a payment as completed and enroll the user in the course(s)
     */
    public function markAsPaid()
    {
        $this->status = 'completed';
        $this->paid_at = now();
        $this->save();

        if ($this->is_bundle) {
            // Enroll in all bundle courses
            $this->user->courses()->syncWithoutDetaching($this->bundle_courses);
            
            // Create notifications for bundle
            app(NotificationService::class)->createBundlePaymentSuccessNotification(
                $this->user,
                $this->courses()->pluck('name')->toArray(),
                $this->amount,
                $this->discount_amount
            );
        } else {
            // Single course enrollment
            $this->user->courses()->syncWithoutDetaching([$this->course_id]);
            
            // Create notification for single course
            app(NotificationService::class)->createPaymentSuccessNotification(
                $this->user,
                $this->course->name,
                $this->amount
            );
        }

        return $this;
    }

    /**
     * Calculate total price for bundle
     */
    public function calculateTotalPrice(): float
    {
        if (!$this->is_bundle) {
            return $this->amount;
        }
        return $this->original_price - ($this->discount_amount ?? 0);
    }
}
