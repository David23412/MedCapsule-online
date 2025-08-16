<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use App\Support\State\AssignmentStatusService;

class Assignment extends Model
{
    use HasFactory;

    // Status constants for computed status (Integer constants for performance)
    // SIMPLIFIED LOGIC: ACTIVE → OVERDUE → COMPLETED (locks when solved/overdue)
    const STATUS_ACTIVE = 1;      // During due time, not solved
    const STATUS_OVERDUE = 2;     // After due time, not solved  
    const STATUS_COMPLETED = 3;   // Solved (locks assignment)
    
    /**
     * Status string mappings for backward compatibility
     */
    const STATUS_STRINGS = [
        self::STATUS_ACTIVE => 'active',
        self::STATUS_OVERDUE => 'overdue',
        self::STATUS_COMPLETED => 'completed'
    ];

    protected $fillable = [
        'topic_id',
        'course_id',
        'title',
        'deadline',
        'duration_minutes',
        'assignment_mode',
        'assigned_source'
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'duration_minutes' => 'decimal:2'
        // 🔥 RUTHLESS REMOVAL: No more score_percentage cast!
    ];

    /**
     * 🚀 PERFORMANCE OPTIMIZED: Get computed status via SSOT
     * Since status column was removed, this computes status dynamically
     */
    public function getCurrentStatusAttribute(): string
    {
        $statusService = app(AssignmentStatusService::class);
        return $statusService->getStatus($this);
    }



    /**
     * 🚀 PERFORMANCE CRITICAL: Get real-time status for display (NO DATABASE WRITES)
    // 💀 N+1 WARNING: This accessor accesses 'getCurrentStatusAttribute' - consider eager loading
     */
    public function getDisplayStatusAttribute(): string
    {
        return $this->getCurrentStatusAttribute();
    }

    // Assignment mode constants
    const MODE_SOURCE_SPECIFIC = 'source_specific';
    const MODE_COMPLETE_TOPIC = 'complete_topic';

    /**
     * Relationships
     */

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * 🚀 GENIUS: Many-to-many relationship with users via pivot table
     */
    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'assignment_users', 'assignment_id', 'user_id')
                   ->withPivot('extended_deadline')
                   ->withTimestamps();
    }

    // Get users with their quiz attempt progress
    public function usersWithProgress()
    {
        return $this->assignedUsers()
                   ->with(['quizAttempts' => function($query) {
                       $query->where('assignment_id', $this->id)
                             ->orderBy('created_at', 'desc')
                             ->limit(1);
                   }]);
    }

    // Alias for better readability
    public function users()
    {
        return $this->assignedUsers();
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'assignment_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'assignment_id');
    }

    public function userProgress(): HasMany
    {
        return $this->hasMany(UserTopicProgress::class, 'assignment_id');
    }

    public function allAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'assignment_id');
    }

    public function attemptsForUser($userId): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'assignment_id')
                   ->where('user_id', $userId);
    }

    /**
     * Query Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('deadline', '>', now());
    }

    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', now());
    }

    // Removed scopeForTutor since tutor_id column was removed
    // Use assignment_users pivot table instead

    // 🔥 RUTHLESS REMOVAL: No more user-specific scopes - assignments are templates!

    public function scopeForCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeDueWithin($query, $hours = 24)
    {
        return $query->where('deadline', '<=', now()->addHours($hours))
                    ->where('deadline', '>=', now());
    }

    /**
     * Business Logic Methods - SSOT via AssignmentStatusService
     */
    public function isOverdue(): bool
    {
        $statusService = app(AssignmentStatusService::class);
        return $statusService->isOverdue($this);
    }

    public function isActive(): bool
    {
        $statusService = app(AssignmentStatusService::class);
        return $statusService->isActive($this);
    }

    public function isExpired(): bool
    {
        $statusService = app(AssignmentStatusService::class);
        return $statusService->isExpired($this);
    }

    public function hasTimeLimitExpired(): bool
    {
        if (!$this->duration_minutes || $this->duration_minutes == 0 || !$this->created_at) {
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
        // Since status column was removed, check if overdue directly
        $remaining = now()->diffInSeconds($this->deadline, false);
        
        if ($remaining <= 0) {
            return null; // Overdue
        }

        return $remaining;
    }

    public function getTimeRemainingHuman(): string
    {
        if ($this->isOverdue()) {
            return 'Overdue by ' . $this->deadline->diffForHumans(now(), true);
        }

        return 'Due ' . $this->deadline->diffForHumans();
    }

    public function canBeCompleted(): bool
    {
        // Since status column was removed, check if not expired and not past deadline
        return !$this->isExpired() && $this->deadline > now();
    }

    public function isLocked(): bool
    {
        // Since status column was removed, assignment is locked if expired or overdue
        return $this->isExpired() || $this->isOverdue();
    }

    public function markAsCompleted(?int $score = null): bool
    {
        if (!$this->canBeCompleted()) {
            return false;
        }

        // Since status column was removed, completion is tracked in quiz_attempts
        // This method is kept for compatibility but doesn't update status
        return true;
    }

    public function markAsOverdue(): bool
    {
        // Since status column was removed, overdue status is computed dynamically
        return $this->isOverdue();
    }

    public function lock(): bool
    {
        // Since status column was removed, locking is handled by deadline/time limits
        return true;
    }

    public function unlock(): bool
    {
        // Since status column was removed, unlocking is not needed
        return true;
    }

    /**
     * Utility Methods
     */
    public function getProgressPercentage(): float
    {
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
        if (!$this->duration_minutes || $this->duration_minutes == 0 || $this->isExpired()) {
            return null;
        }

        // Calculate time limit from creation time
        $startTime = $this->created_at;
        $endTime = $startTime->copy()->addMinutes((float)$this->duration_minutes);
        
        $remaining = now()->diffInSeconds($endTime, false);
        return $remaining > 0 ? $remaining : 0;
    }

    /**
     * Get elapsed time for assignments with no time limit (count up timer)
     */
    public function getElapsedTime(): ?int
    {
        if (!$this->duration_minutes || $this->duration_minutes != 0) {
            return null;
        }

        return now()->diffInSeconds($this->created_at);
    }

    /**
     * Check if timer should be shown
     */
    public function shouldShowTimer(): bool
    {
        return $this->isActive() && 
               $this->duration_minutes !== null;
    }

    /**
     * Check if assignment has a time limit (for countdown timer)
     */
    public function hasTimeLimit(): bool
    {
        return $this->duration_minutes > 0;
    }

    /**
     * Check if assignment has no time limit (for count up timer)
     */
    public function hasNoTimeLimit(): bool
    {
        return $this->duration_minutes == 0;
    }

    /**
     * Mark assignment as expired due to time limit
     */
    public function markAsExpired(): bool
    {
        // Since status column was removed, expiry is computed dynamically
        return $this->isExpired();
    }

    /**
     * 🚀 PERFORMANCE OPTIMIZED: Bulk update overdue assignments
     * Since status column was removed, this returns count of overdue assignments
     */
    public static function updateOverdueAssignments(): int
    {
        return self::where('deadline', '<', now())->count();
    }

    /**
     * 🚀 PERFORMANCE OPTIMIZED: Bulk update expired assignments
     * Since status column was removed, this returns count of expired assignments
     */
    public static function updateExpiredAssignments(): int
    {
        // Get assignments that should be expired due to time limits
        return self::whereNotNull('duration_minutes')
                  ->where('duration_minutes', '>', 0)
                  ->get()
                  ->filter(function ($assignment) {
                      return $assignment->hasTimeLimitExpired();
                  })
                  ->count();
    }

    // 🔥 RUTHLESS REMOVAL: No more auto-completion - assignments are templates!
    // Progress is tracked dynamically in quiz_attempts table
    
    /**
     * BULLETPROOF: Get real-time status regardless of database value
     * SIMPLIFIED: ACTIVE → OVERDUE → COMPLETED
     */
    public function getRealTimeStatus(): int
    {
        // Check if completed (solved) - this locks the assignment
        if ($this->isCompletedByUser(auth()->id())) {
            return self::STATUS_COMPLETED;
        }
        
        // Check if overdue (past deadline) - this also locks the assignment
        if ($this->deadline < now()) {
            return self::STATUS_OVERDUE;
        }
        
        // Default to active (during due time, not solved)
        return self::STATUS_ACTIVE;
    }
    
    /**
     * BULLETPROOF: Verify and correct status if needed
     * Since status column was removed, this just returns the computed status
     */
    public function verifyAndCorrectStatus(): bool
    {
        // Status is now computed dynamically, no correction needed
        return false;
    }

    /**
     * Lock assignments that are overdue beyond grace period
     * Since status column was removed, this returns count of assignments past grace period
     */
    public static function lockOverdueAssignments(int $graceHours = 24): int
    {
        return self::where('deadline', '<', now()->subHours($graceHours))->count();
    }

    /**
     * Expire time-limited assignments that have exceeded their duration
     * Since status column was removed, this returns count of expired assignments
     */
    public static function expireTimeLimitedAssignments(): int
    {
        return self::whereNotNull('duration_minutes')
                  ->get()
                  ->filter(function ($assignment) {
                      return $assignment->hasTimeLimitExpired();
                  })
                  ->count();
    }

    /**
     * Check if assignment should be automatically locked
     */
    public function shouldBeLocked(): bool
    {
        // Lock if overdue beyond grace period (24 hours)
        if ($this->isOverdue() && $this->deadline < now()->subHours(24)) {
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
        if ($this->isExpired()) {
            return 'Assignment time limit has expired';
        }
        
        if ($this->isOverdue()) {
            return 'Assignment is overdue and no longer accessible';
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
    // 💀 N+1 WARNING: This accessor accesses 'asDateTime' - consider eager loading
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
     * 🚀 GENIUS: Get user progress dynamically from quiz attempts
     */
    public function getUserProgress($userId): array
    {
        $attempts = $this->attemptsForUser($userId)->get();
        
        if ($attempts->isEmpty()) {
            return [
                'completed' => false,
                'score' => 0,
                'percentage' => 0,
                'attempts_count' => 0,
                'best_score' => 0,
                'latest_attempt' => null
            ];
        }

        $bestAttempt = $attempts->sortByDesc('percentage_grade')->first();
        $latestAttempt = $attempts->sortByDesc('created_at')->first();

        return [
            'completed' => $bestAttempt->percentage_grade >= 50,
            'score' => $bestAttempt->score,
            'percentage' => $bestAttempt->percentage_grade,
            'attempts_count' => $attempts->count(),
            'best_score' => $bestAttempt->percentage_grade,
            'latest_attempt' => $latestAttempt->created_at
        ];
    }

    /**
     * 🚀 GENIUS: Check if user has completed this assignment
     */
    public function isCompletedByUser($userId): bool
    {
        return $this->attemptsForUser($userId)
                   ->where('percentage_grade', '>=', 50)
                   ->exists();
    }

    /**
     * 🚀 GENIUS: Get user's best score for this assignment
     */
    public function getUserBestScore($userId): float
    {
        return $this->attemptsForUser($userId)
                   ->max('percentage_grade') ?? 0;
    }

    /**
     * INSTANT STATUS ENFORCER - Force immediate status check and update
     */
    public function enforceRealTimeStatus(): string
    {
        // Return computed status since status column was removed
        return $this->getCurrentStatusAttribute();
    }

    /**
     * BULK REAL-TIME ENFORCEMENT - Update all assignments instantly
     * Since status column was removed, this returns total assignment count
     */
    public static function enforceAllStatuses(): int
    {
        return self::count();
    }

    /**
     * Get assignment statistics with REAL-TIME status enforcement
     */
    public static function getStatistics(?int $tutorId = null): array
    {
        $baseQuery = self::query();
        
        if ($tutorId) {
            // Since tutor_id was removed, we can't filter by tutor
            // This would need to be handled differently with the new pivot table structure
        }

        $assignments = $baseQuery->get();
        
        $total = $assignments->count();
        $active = $assignments->filter(function($assignment) {
            return $assignment->getCurrentStatusAttribute() === self::STATUS_ACTIVE;
        })->count();
        
        $overdue = $assignments->filter(function($assignment) {
            return $assignment->getCurrentStatusAttribute() === self::STATUS_OVERDUE;
        })->count();
        
        $completed = $assignments->filter(function($assignment) {
            return $assignment->getCurrentStatusAttribute() === self::STATUS_COMPLETED;
        })->count();

        return [
            'total' => $total,
            'active' => $active,
            'overdue' => $overdue,
            'completed' => $completed,
            'completion_rate' => $total > 0 ? ($completed / $total) * 100 : 0
        ];
    }

    /**
     * 🔥 CRITICAL: Check if user has attempted this assignment (one shot rule)
     */
    public function hasUserAttempted($userId): bool
    {
        return $this->attemptsForUser($userId)->exists();
    }

    /**
     * 🔥 CRITICAL: Check if assignment is locked for user (completed or attempted)
     */
    public function isLockedForUser($userId): bool
    {
        // Assignment is locked if user has already attempted it (one shot rule)
        return $this->hasUserAttempted($userId);
    }

    /**
     * 🔥 CRITICAL: Get user-specific assignment status
     */
    public function getStatusForUser($userId): string
    {
        // Check if user has attempted
        if ($this->hasUserAttempted($userId)) {
            // Check if completed (score >= 50%)
            if ($this->isCompletedByUser($userId)) {
                return 'completed_locked';
            } else {
                return 'attempted_locked';
            }
        }
        
        // Check global assignment status
        $globalStatus = $this->getCurrentStatusAttribute();
        
        if ($globalStatus === self::STATUS_OVERDUE) {
            return 'overdue_locked';
        }
        
        if ($globalStatus === self::STATUS_COMPLETED) {
            return 'completed_locked';
        }
        
        return 'available';
    }

    /**
     * 🔥 CRITICAL: Check if user can start this assignment
     */
    public function canUserStart($userId): bool
    {
        $status = $this->getStatusForUser($userId);
        return $status === 'available';
    }

    /**
     * 🔥 CRITICAL: Get lock reason for user
     */
    public function getLockReasonForUser($userId): ?string
    {
        $status = $this->getStatusForUser($userId);
        
        switch ($status) {
            case 'completed_locked':
                return 'Assignment completed - No retakes allowed';
            case 'attempted_locked':
                return 'Assignment attempted - No retakes allowed';
            case 'overdue_locked':
                return 'Assignment is overdue - No longer accessible';
            case 'admin_locked':
                return 'Assignment locked by instructor';
            default:
                return null;
        }
    }

    /**
     * 🔥 CRITICAL: Get user progress with lock status
     */
    public function getUserProgressWithStatus($userId): array
    {
        $progress = $this->getUserProgress($userId);
        $status = $this->getStatusForUser($userId);
        
        return array_merge($progress, [
            'status' => $status,
            'is_locked' => $status !== 'available',
            'lock_reason' => $this->getLockReasonForUser($userId),
            'can_start' => $this->canUserStart($userId)
        ]);
    }

    /**
     * 🚀 GENIUS: Check if assignment is assigned to specific user
     */
    public function isAssignedToUser($userId): bool
    {
        return $this->assignedUsers()->where('user_id', $userId)->exists();
    }

    /**
     * 🚀 GENIUS: Get all users assigned to this assignment
     */
    public function getAssignedUserIds(): array
    {
        return $this->assignedUsers()->pluck('user_id')->toArray();
    }

    /**
     * 🚀 GENIUS: Get deadline for specific user (with extensions)
     */
    public function getDeadlineForUser($userId): Carbon
    {
        // Find the user in the assignedUsers relationship
        $user = $this->assignedUsers()->where('user_id', $userId)->first();
        
        // Check if user has an extension in pivot table
        if ($user && $user->pivot->extended_deadline) {
            return Carbon::parse($user->pivot->extended_deadline);
        }
        
        // Return default assignment deadline
        return $this->deadline;
    }

    /**
     * 🚀 GENIUS: Check if user has deadline extension
     */
    public function hasExtensionForUser($userId): bool
    {
        $user = $this->assignedUsers()->where('user_id', $userId)->first();
        return $user && $user->pivot->extended_deadline;
    }

    /**
     * 🚀 GENIUS: Get extension info for user
     */
    public function getExtensionInfoForUser($userId): ?array
    {
        if (!$this->hasExtensionForUser($userId)) {
            return null;
        }

        $userDeadline = $this->getDeadlineForUser($userId);
        $originalDeadline = $this->deadline;
        
        return [
            'extended_deadline' => $userDeadline,
            'original_deadline' => $originalDeadline,
            'extension_hours' => $originalDeadline->diffInHours($userDeadline),
            'extension_human' => $originalDeadline->diffForHumans($userDeadline, true)
        ];
    }
}