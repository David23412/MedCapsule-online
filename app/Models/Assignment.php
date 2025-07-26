<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tutor_id',
        'user_id',
        'topic_id',
        'course_id',
        'title',
        'deadline',
        'duration_minutes',
        'assignment_mode',
        'assigned_source',
        'status',
        'score_percentage'
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'duration_minutes' => 'decimal:2',
        'score_percentage' => 'decimal:2'
    ];

    /**
     * REAL-TIME INSTANT STATUS ENFORCER - Updates status IMMEDIATELY when accessed
     * NO DELAYS, NO SCHEDULING, INSTANT UPDATES!
     */
    public function getStatusAttribute($value)
    {
        // INSTANT ENFORCEMENT: Never allow active assignments past their deadline
        if ($value === self::STATUS_ACTIVE && $this->deadline < now()) {
            // INSTANT DATABASE UPDATE - NO DELAYS!
            $this->updateQuietly(['status' => self::STATUS_OVERDUE]);
            
            // Broadcast real-time update if needed
            $this->broadcastStatusChange(self::STATUS_ACTIVE, self::STATUS_OVERDUE);
            
            return self::STATUS_OVERDUE;
        }
        
        // INSTANT REACTIVATION: If deadline was extended and not completed/locked
        if ($value === self::STATUS_OVERDUE && 
            $this->deadline > now() && 
            !in_array($this->getOriginal('status'), [self::STATUS_COMPLETED, self::STATUS_LOCKED])) {
            
            // INSTANT DATABASE UPDATE - NO DELAYS!
            $this->updateQuietly(['status' => self::STATUS_ACTIVE]);
            
            // Broadcast real-time update if needed
            $this->broadcastStatusChange(self::STATUS_OVERDUE, self::STATUS_ACTIVE);
            
            return self::STATUS_ACTIVE;
        }
        
        // INSTANT COMPLETION PROTECTION: Never allow completed assignments to change
        if ($value === self::STATUS_COMPLETED) {
            return self::STATUS_COMPLETED;
        }
        
        // INSTANT TIME LIMIT EXPIRATION
        if ($value === self::STATUS_ACTIVE && $this->hasTimeLimitExpired()) {
            $this->updateQuietly(['status' => self::STATUS_EXPIRED]);
            $this->broadcastStatusChange(self::STATUS_ACTIVE, self::STATUS_EXPIRED);
            return self::STATUS_EXPIRED;
        }
        
        return $value;
    }

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_LOCKED = 'locked';
    const STATUS_EXPIRED = 'expired';
    
    // Assignment mode constants
    const MODE_SOURCE_SPECIFIC = 'source_specific';
    const MODE_COMPLETE_TOPIC = 'complete_topic';

    /**
     * Relationships
     */
    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'topic_id', 'topic_id')
            ->where('user_id', $this->user_id)
            ->where('quiz_type', 'assignment');
    }

    public function completedAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'topic_id', 'topic_id')
            ->where('user_id', $this->user_id)
            ->where('quiz_type', 'assignment')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Query Scopes
     */
    public function scopeActive($query)
    {
        // REAL-TIME: Status is updated instantly via accessor, no need for batch updates
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeOverdue($query)
    {
        // REAL-TIME: Status is updated instantly via accessor, no need for batch updates
        return $query->where('status', self::STATUS_OVERDUE);
    }

    public function scopeForTutor($query, $tutorId)
    {
        return $query->where('tutor_id', $tutorId);
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('user_id', $studentId);
    }

    public function scopeForCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeDueWithin($query, $hours = 24)
    {
        return $query->where('deadline', '<=', now()->addHours($hours))
                    ->where('deadline', '>=', now())
                    ->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Business Logic Methods
     */
    public function isOverdue(): bool
    {
        return $this->deadline < now() && $this->status !== self::STATUS_COMPLETED;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && 
               $this->deadline > now();
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED || 
               ($this->duration_minutes && $this->hasTimeLimitExpired());
    }

    public function hasTimeLimitExpired(): bool
    {
        if (!$this->duration_minutes || !$this->created_at) {
            return false;
        }
        
        $timeLimit = (float)$this->duration_minutes * 60; // Convert minutes to seconds
        return now()->diffInSeconds($this->created_at) > $timeLimit;
    }

    public function isSourceSpecific(): bool
    {
        return $this->assignment_mode === self::MODE_SOURCE_SPECIFIC;
    }

    public function isCompleteTopicMode(): bool
    {
        return $this->assignment_mode === self::MODE_COMPLETE_TOPIC;
    }

    public function getTimeRemaining(): ?int
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return null;
        }

        $remaining = now()->diffInSeconds($this->deadline, false);
        
        if ($remaining <= 0) {
            return null; // Overdue
        }

        return $remaining;
    }

    public function getTimeRemainingHuman(): string
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return 'Completed';
        }

        if ($this->isOverdue()) {
            return 'Overdue by ' . $this->deadline->diffForHumans(now(), true);
        }

        return 'Due ' . $this->deadline->diffForHumans();
    }

    public function canBeCompleted(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_OVERDUE]) 
               && !$this->isLocked();
    }

    public function isLocked(): bool
    {
        // FIXED: Both locked and completed assignments should be considered locked
        return in_array($this->status, [self::STATUS_LOCKED, self::STATUS_COMPLETED]);
    }

    public function markAsCompleted(?int $score = null): bool
    {
        if (!$this->canBeCompleted()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'score_percentage' => $score
        ]);

        return true;
    }

    public function markAsOverdue(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        $this->update(['status' => self::STATUS_OVERDUE]);
        return true;
    }

    public function lock(): bool
    {
        // FIXED: Completed assignments should remain completed, not be changed to locked
        // The isLocked() method should check for both LOCKED and COMPLETED status
        if ($this->status === self::STATUS_COMPLETED) {
            return true; // Already effectively locked (completed assignments can't be retaken)
        }

        $this->update(['status' => self::STATUS_LOCKED]);
        return true;
    }

    public function unlock(): bool
    {
        if ($this->status !== self::STATUS_LOCKED) {
            return false;
        }

        $status = $this->isOverdue() ? self::STATUS_OVERDUE : self::STATUS_ACTIVE;
        $this->update(['status' => $status]);
        return true;
    }

    /**
     * Utility Methods
     */
    public function getProgressPercentage(): float
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return 100.0;
        }

        if ($this->isOverdue()) {
            return 0.0;
        }

        $totalTime = $this->created_at->diffInSeconds($this->deadline);
        $elapsedTime = $this->created_at->diffInSeconds(now());

        if ($totalTime <= 0) {
            return 100.0;
        }

        return min(100.0, ($elapsedTime / $totalTime) * 100);
    }

    public function getDifficultyLevel(): string
    {
        $hoursRemaining = now()->diffInHours($this->deadline, false);
        
        if ($hoursRemaining < 0) {
            return 'overdue';
        } elseif ($hoursRemaining <= 2) {
            return 'urgent';
        } elseif ($hoursRemaining <= 24) {
            return 'high';
        } elseif ($hoursRemaining <= 72) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Check if assignment is complete-topic mode
     */
    public function isCompleteTopic(): bool
    {
        return $this->assignment_mode === self::MODE_COMPLETE_TOPIC;
    }

    /**
     * Get time limit remaining in seconds
     */
    public function getTimeLimitRemaining(): ?int
    {
        if (!$this->duration_minutes || $this->status === self::STATUS_COMPLETED || $this->isExpired()) {
            return null;
        }

        // Calculate time limit from creation time
        $startTime = $this->created_at;
        $endTime = $startTime->copy()->addMinutes((float)$this->duration_minutes);
        
        $remaining = now()->diffInSeconds($endTime, false);
        return $remaining > 0 ? $remaining : 0;
    }

    /**
     * Check if timer should be shown
     */
    public function shouldShowTimer(): bool
    {
        return $this->status === self::STATUS_ACTIVE && 
               $this->duration_minutes !== null && 
               $this->getTimeLimitRemaining() !== null;
    }

    /**
     * Mark assignment as expired due to time limit
     */
    public function markAsExpired(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_EXPIRED
        ]);
        
        return true;
    }

    /**
     * BULLETPROOF: Auto-update overdue assignments with comprehensive logging
     */
    public static function updateOverdueAssignments(): int
    {
        $assignments = self::where('status', self::STATUS_ACTIVE)
                          ->where('deadline', '<', now())
                          ->get();
        
        $updatedCount = 0;
        
        foreach ($assignments as $assignment) {
            $oldStatus = $assignment->status;
            $assignment->update(['status' => self::STATUS_OVERDUE]);
            
            // Log each correction for audit trail
            \Log::info("Assignment auto-updated to overdue", [
                'assignment_id' => $assignment->id,
                'title' => $assignment->title,
                'deadline' => $assignment->deadline,
                'old_status' => $oldStatus,
                'new_status' => self::STATUS_OVERDUE,
                'overdue_by_minutes' => now()->diffInMinutes($assignment->deadline),
                'updated_at' => now()
            ]);
            
            $updatedCount++;
        }
        
        return $updatedCount;
    }
    
    /**
     * BULLETPROOF: Get real-time status regardless of database value
     */
    public function getRealTimeStatus(): string
    {
        // Check if completed first
        if ($this->status === self::STATUS_COMPLETED) {
            return self::STATUS_COMPLETED;
        }
        
        // Check if expired due to time limit
        if ($this->hasTimeLimitExpired()) {
            return self::STATUS_EXPIRED;
        }
        
        // Check if overdue
        if ($this->deadline < now()) {
            return self::STATUS_OVERDUE;
        }
        
        // Check if locked
        if ($this->getOriginal('status') === self::STATUS_LOCKED) {
            return self::STATUS_LOCKED;
        }
        
        // Default to active if none of the above
        return self::STATUS_ACTIVE;
    }
    
    /**
     * BULLETPROOF: Verify and correct status if needed
     */
    public function verifyAndCorrectStatus(): bool
    {
        $currentStatus = $this->getOriginal('status');
        $correctStatus = $this->getRealTimeStatus();
        
        if ($currentStatus !== $correctStatus) {
            $this->update(['status' => $correctStatus]);
            
            \Log::warning("Assignment status corrected", [
                'assignment_id' => $this->id,
                'title' => $this->title,
                'deadline' => $this->deadline,
                'old_status' => $currentStatus,
                'correct_status' => $correctStatus,
                'corrected_at' => now()
            ]);
            
            return true;
        }
        
        return false;
    }

    /**
     * Lock assignments that are overdue beyond grace period
     */
    public static function lockOverdueAssignments(int $graceHours = 24): int
    {
        $assignments = self::where('status', self::STATUS_OVERDUE)
                          ->where('deadline', '<', now()->subHours($graceHours))
                          ->get();
        
        $lockedCount = 0;
        foreach ($assignments as $assignment) {
            if ($assignment->lock()) {
                $lockedCount++;
            }
        }
        
        return $lockedCount;
    }

    /**
     * Expire time-limited assignments that have exceeded their duration
     */
    public static function expireTimeLimitedAssignments(): int
    {
        $assignments = self::where('status', self::STATUS_ACTIVE)
                          ->whereNotNull('duration_minutes')
                          ->get()
                          ->filter(function ($assignment) {
                              return $assignment->hasTimeLimitExpired();
                          });
        
        $expiredCount = 0;
        foreach ($assignments as $assignment) {
            if ($assignment->markAsExpired()) {
                $expiredCount++;
            }
        }
        
        return $expiredCount;
    }

    /**
     * Check if assignment should be automatically locked
     */
    public function shouldBeLocked(): bool
    {
        // Lock if completed
        if ($this->status === self::STATUS_COMPLETED) {
            return true;
        }
        
        // Lock if overdue beyond grace period (24 hours)
        if ($this->status === self::STATUS_OVERDUE && 
            $this->deadline < now()->subHours(24)) {
            return true;
        }
        
        // Lock if time limit expired
        if ($this->hasTimeLimitExpired()) {
            return true;
        }
        
        return false;
    }

    /**
     * Get the reason why assignment is locked
     */
    public function getLockReason(): ?string
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return 'Assignment has been completed and is no longer accessible';
        }
        
        if ($this->status === self::STATUS_EXPIRED) {
            return 'Assignment time limit has expired';
        }
        
        if ($this->status === self::STATUS_OVERDUE) {
            return 'Assignment is overdue and no longer accessible';
        }
        
        if ($this->status === self::STATUS_LOCKED) {
            return 'Assignment has been locked by instructor';
        }
        
        return 'Assignment is not accessible';
    }


    /**
     * BULLETPROOF TIMEZONE PROTECTION
     * These methods ensure deadlines are NEVER timezone-converted incorrectly
     */
    
    /**
     * Prevent Laravel from auto-converting deadline timezone
     */
    public function setDeadlineAttribute($value)
    {
        // Store deadline exactly as provided - NO CONVERSION
        if ($value instanceof \Carbon\Carbon) {
            $this->attributes['deadline'] = $value->format('Y-m-d H:i:s');
        } else {
            $this->attributes['deadline'] = $value;
        }
    }
    
    /**
     * Ensure deadline is returned in consistent timezone
     */
    public function getDeadlineAttribute($value)
    {
        // Always return deadline in app timezone
        return $this->asDateTime($value)->setTimezone(config('app.timezone'));
    }
    
    /**
     * Create assignment with bulletproof deadline handling
     */
    public static function createWithSafeDeadline(array $attributes)
    {
        // If deadline is provided, ensure it's in the correct format
        if (isset($attributes['deadline'])) {
            $deadline = $attributes['deadline'];
            
            // Convert to Carbon in app timezone
            if (!($deadline instanceof \Carbon\Carbon)) {
                $deadline = \Carbon\Carbon::parse($deadline, config('app.timezone'));
            }
            
            // Store as string to prevent conversion
            $attributes['deadline'] = $deadline->format('Y-m-d H:i:s');
        }
        
        return self::create($attributes);
    }
    
    /**
     * Update deadline with bulletproof handling
     */
    public function updateDeadlineSafely($newDeadline)
    {
        // Convert to Carbon in app timezone
        if (!($newDeadline instanceof \Carbon\Carbon)) {
            $newDeadline = \Carbon\Carbon::parse($newDeadline, config('app.timezone'));
        }
        
        // Update directly in database to prevent conversion
        DB::table('assignments')
          ->where('id', $this->id)
          ->update(['deadline' => $newDeadline->format('Y-m-d H:i:s')]);
        
        // Refresh the model
        $this->refresh();
        
        return $this;
    }
    
    /**
     * Check if assignment is accessible for taking
     */
    public function isAccessible(): bool
    {
        return $this->status === self::STATUS_ACTIVE &&
               $this->deadline > now() &&
               !$this->hasTimeLimitExpired();
    }
    
    /**
     * Start the assignment timer
     */
    public function startTimer(): bool
    {
        // Timer starts from creation time, no need to track separately
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * REAL-TIME STATUS BROADCAST - Notify frontend instantly of status changes
     */
    protected function broadcastStatusChange(string $oldStatus, string $newStatus): void
    {
        // Only broadcast if we have listeners (avoid unnecessary processing)
        if (config('broadcasting.default') !== 'null') {
            try {
                broadcast(new \App\Events\AssignmentStatusChanged([
                    'assignment_id' => $this->id,
                    'user_id' => $this->user_id,
                    'tutor_id' => $this->tutor_id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'title' => $this->title,
                    'deadline' => $this->deadline,
                    'updated_at' => now()
                ]));
            } catch (\Exception $e) {
                // Don't let broadcasting failures break the status update
                \Log::warning('Assignment status broadcast failed', [
                    'assignment_id' => $this->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * INSTANT STATUS ENFORCER - Force immediate status check and update
     */
    public function enforceRealTimeStatus(): string
    {
        // Force accessor to run and update status if needed
        $currentStatus = $this->status;
        
        // Refresh from database to get the updated status
        $this->refresh();
        
        return $currentStatus;
    }

    /**
     * BULK REAL-TIME ENFORCEMENT - Update all assignments instantly
     */
    public static function enforceAllStatuses(): int
    {
        $updatedCount = 0;
        
        // Get all assignments that might need status updates
        $assignments = self::whereIn('status', [self::STATUS_ACTIVE, self::STATUS_OVERDUE])
                          ->get();
        
        foreach ($assignments as $assignment) {
            $oldStatus = $assignment->getOriginal('status');
            $newStatus = $assignment->enforceRealTimeStatus();
            
            if ($oldStatus !== $newStatus) {
                $updatedCount++;
            }
        }
        
        return $updatedCount;
    }

    /**
     * Get assignment statistics with REAL-TIME status enforcement
     */
    public static function getStatistics(?int $tutorId = null): array
    {
        $baseQuery = self::query();
        
        if ($tutorId) {
            $baseQuery->where('tutor_id', $tutorId);
        }

        // Force real-time status updates by accessing status attribute
        $assignments = $baseQuery->get();
        $assignments->each(function ($assignment) {
            $assignment->status; // This triggers the real-time status check
        });

        $total = $assignments->count();
        $active = $assignments->where('status', self::STATUS_ACTIVE)->count();
        $completed = $assignments->where('status', self::STATUS_COMPLETED)->count();
        $overdue = $assignments->where('status', self::STATUS_OVERDUE)->count();
        $locked = $assignments->where('status', self::STATUS_LOCKED)->count();
        $averageScore = $assignments->whereNotNull('score')->avg('score') ?? 0;

        return [
            'total' => $total,
            'active' => $active,
            'completed' => $completed,
            'overdue' => $overdue,
            'locked' => $locked,
            'average_score' => $averageScore,
            'completion_rate' => $total > 0 ? ($completed / $total) * 100 : 0
        ];
    }
}