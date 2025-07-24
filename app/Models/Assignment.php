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
        'student_id',
        'topic_id',
        'course_id',
        'title',
        'description',
        'deadline',
        'duration_minutes',
        'assignment_mode',
        'assigned_source',
        'status',
        'completed_at',
        'expired_at',
        'started_at',
        'score',
        'score_percentage'
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'completed_at' => 'datetime',
        'expired_at' => 'datetime',
        'started_at' => 'datetime',
        'duration_minutes' => 'decimal:2',
        'score' => 'integer',
        'score_percentage' => 'decimal:2'
    ];

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

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
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
        return $this->hasMany(AssignmentAttempt::class);
    }

    public function completedAttempts(): HasMany
    {
        return $this->hasMany(AssignmentAttempt::class)->whereNotNull('completed_at');
    }

    /**
     * Query Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE)
                    ->orWhere(function($q) {
                        $q->where('status', self::STATUS_ACTIVE)
                          ->where('deadline', '<', now());
                    });
    }

    public function scopeForTutor($query, $tutorId)
    {
        return $query->where('tutor_id', $tutorId);
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
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
               $this->deadline > now() && 
               !$this->completed_at;
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
        return $this->status === self::STATUS_LOCKED;
    }

    public function markAsCompleted(int $score = null): bool
    {
        if (!$this->canBeCompleted()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'score' => $score
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
        if ($this->status === self::STATUS_COMPLETED) {
            return false;
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

        // If no start time recorded yet, return full time limit
        if (!$this->started_at) {
            return (float)$this->duration_minutes * 60; // Convert minutes to seconds
        }

        $startTime = Carbon::parse($this->started_at);
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
            'status' => self::STATUS_EXPIRED,
            'expired_at' => now()
        ]);
        
        return true;
    }

    /**
     * Auto-update overdue assignments
     */
    public static function updateOverdueAssignments(): int
    {
        return self::where('status', self::STATUS_ACTIVE)
                  ->where('deadline', '<', now())
                  ->update(['status' => self::STATUS_OVERDUE]);
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
                          ->whereNotNull('started_at')
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
        if ($this->status !== self::STATUS_ACTIVE || $this->started_at !== null) {
            return false;
        }
        
        $this->update(['started_at' => now()]);
        return true;
    }

    /**
     * Get assignment statistics
     */
    public static function getStatistics(int $tutorId = null): array
    {
        $baseQuery = self::query();
        
        if ($tutorId) {
            $baseQuery->where('tutor_id', $tutorId);
        }

        $total = $baseQuery->count();
        $active = (clone $baseQuery)->where('status', self::STATUS_ACTIVE)->count();
        $completed = (clone $baseQuery)->where('status', self::STATUS_COMPLETED)->count();
        $overdue = (clone $baseQuery)->where('status', self::STATUS_OVERDUE)->count();
        $locked = (clone $baseQuery)->where('status', self::STATUS_LOCKED)->count();
        $averageScore = (clone $baseQuery)->whereNotNull('score')->avg('score') ?? 0;

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