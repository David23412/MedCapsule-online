<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'payment_id',
        'user_id',
        'admin_id',
        'action',
        'status_before',
        'status_after',
        'data',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Get the payment that this log entry relates to.
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the user who performed the action.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who performed the action.
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Scope query to only include logs for a specific payment.
     */
    public function scopeForPayment($query, $paymentId)
    {
        return $query->where('payment_id', $paymentId);
    }

    /**
     * Scope query to only include logs for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope query to only include logs for a specific action.
     */
    public function scopeForAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Get a human-readable description of the action.
     */
    public function getActionDescriptionAttribute()
    {
        $descriptions = [
            'created' => 'Payment created',
            'submitted' => 'Proof submitted',
            'auto_verified' => 'Automatically verified',
            'admin_verified' => 'Manually verified by admin',
            'rejected' => 'Rejected',
            'expired' => 'Expired',
            'reactivated' => 'Reactivated',
            'retry_scheduled' => 'Scheduled for retry',
            'verification_attempted' => 'Verification attempted',
            'error' => 'Error occurred',
        ];

        return $descriptions[$this->action] ?? $this->action;
    }

    /**
     * Summarizes a collection of logs into a more compact form for old records.
     */
    public static function summarizeLogs($paymentId, $olderThan)
    {
        $logs = self::where('payment_id', $paymentId)
            ->where('created_at', '<', $olderThan)
            ->get();
        
        if ($logs->isEmpty()) {
            return null;
        }

        $summary = [
            'payment_id' => $paymentId,
            'action' => 'log_summary',
            'first_log_date' => $logs->first()->created_at->toDateTimeString(),
            'last_log_date' => $logs->last()->created_at->toDateTimeString(),
            'log_count' => $logs->count(),
            'actions' => $logs->pluck('action')->countBy()->toArray(),
            'final_status' => $logs->last()->status_after,
        ];

        // Create a summary log
        $summaryLog = self::create([
            'payment_id' => $paymentId,
            'action' => 'log_summary',
            'status_before' => $logs->first()->status_before,
            'status_after' => $logs->last()->status_after,
            'data' => $summary,
        ]);

        // Delete the original logs that were summarized
        self::where('payment_id', $paymentId)
            ->where('created_at', '<', $olderThan)
            ->where('action', '!=', 'log_summary')
            ->delete();

        return $summaryLog;
    }
} 