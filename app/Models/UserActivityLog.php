<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'page',
        'data',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'data' => 'array'
    ];

    /**
     * Get the user that owns the activity log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get recent activity for a user
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to get activity by action type
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to get page views
     */
    public function scopePageViews($query)
    {
        return $query->where('action', 'page_view');
    }

    /**
     * Scope to get quiz attempts
     */
    public function scopeQuizAttempts($query)
    {
        return $query->where('action', 'quiz_attempt');
    }

    /**
     * Scope to get course enrollments
     */
    public function scopeCourseEnrollments($query)
    {
        return $query->where('action', 'course_enroll');
    }
}
