<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class AdminNotification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'message',
        'type',
        'priority',
        'related_type',
        'related_id',
        'data',
        'read_at',
        'handled_at',
        'read_by',
        'handled_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'handled_at' => 'datetime',
    ];

    /**
     * Get related model (polymorphic)
     */
    public function related()
    {
        return $this->morphTo();
    }

    /**
     * Get user who read the notification
     */
    public function reader()
    {
        return $this->belongsTo(User::class, 'read_by');
    }

    /**
     * Get user who handled the notification
     */
    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope for unhandled notifications
     */
    public function scopeUnhandled($query)
    {
        return $query->whereNull('handled_at');
    }

    /**
     * Scope for high priority notifications
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }

    /**
     * Scope for payment-related notifications
     */
    public function scopePaymentRelated($query)
    {
        return $query->where('related_type', 'payment');
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($userId = null)
    {
        $this->read_at = now();
        $this->read_by = $userId;
        $this->save();
        
        return $this;
    }

    /**
     * Mark notification as handled
     */
    public function markAsHandled($userId = null)
    {
        $this->handled_at = now();
        $this->handled_by = $userId;
        $this->save();
        
        return $this;
    }

    /**
     * Check if notification is read
     */
    public function isRead()
    {
        return $this->read_at !== null;
    }

    /**
     * Check if notification is handled
     */
    public function isHandled()
    {
        return $this->handled_at !== null;
    }

    /**
     * Get related URL
     */
    public function getUrl()
    {
        switch ($this->related_type) {
            case 'payment':
                return route('admin.payment-details', ['payment' => $this->related_id]);
            default:
                return route('admin.dashboard');
        }
    }
} 