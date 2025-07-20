<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'status',
        'completed_at',
        'score'
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'completed_at' => 'datetime',
        'duration_minutes' => 'integer',
        'score' => 'integer'
    ];

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_LOCKED = 'locked';

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
     * Auto-update overdue assignments
     */
    public static function updateOverdueAssignments(): int
    {
        return self::where('status', self::STATUS_ACTIVE)
                  ->where('deadline', '<', now())
                  ->update(['status' => self::STATUS_OVERDUE]);
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