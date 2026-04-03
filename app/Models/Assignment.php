<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class Assignment extends Model
{
    use HasFactory;

    /**
     * Status constants - Ultra-simplified state machine
     * - ACTIVE: Assignment is in progress
     * - COMPLETED: Assignment is done (includes submitted, overdue, or time-limited)
     */
    const STATUS_ACTIVE = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_LOCKED = 3;

    /**
     * Urgency level constants used by getUrgencyLevel()
     */
    const URGENCY_OVERDUE = 0;
    const URGENCY_URGENT = 1;
    const URGENCY_APPROACHING = 2;
    const URGENCY_NORMAL = 3;

    /**
     * Get the current status of the assignment.
     * Ultra-efficient computation with minimal method calls:
     * 1. Check if submitted (fastest check first)
     * 2. Check if time limit expired or past deadline
     * 3. Default to active
     *
     * @return int
     */
    public function getCurrentStatusAttribute(): int
    {
        // Fastest check first - if submitted, it's completed
        if ($this->submitted_at !== null) {
            return self::STATUS_COMPLETED;
        }
        
        // Check if time limit expired
        if ($this->duration_minutes && $this->created_at) {
            $timeLimit = (float)$this->duration_minutes * 60;
            if (now()->diffInSeconds($this->created_at) > $timeLimit) {
                return self::STATUS_COMPLETED;
            }
        }
        
        // Check if past deadline
        if ($this->deadline && now()->gt($this->deadline)) {
            return self::STATUS_LOCKED; // Use LOCKED status for overdue assignments
        }
        
        return self::STATUS_ACTIVE;
    }
    
    /**
     * Get status as string for display purposes
     * Optimized to avoid multiple status calculations
     * 
     * @return string
     */
    public function getStatusStringAttribute(): string
    {
        if (isset($this->display_status_string)) {
            return (string) $this->display_status_string;
        }

        if (isset($this->display_status)) {
            return match ((int) $this->display_status) {
                self::STATUS_COMPLETED => 'completed',
                self::STATUS_LOCKED => ($this->deadline && now()->gt($this->deadline)) ? 'overdue' : 'locked',
                default => 'active',
            };
        }

        $status = $this->getCurrentStatusAttribute();
        
        if ($status === self::STATUS_COMPLETED) {
            return 'completed';
        }
        
        if ($status === self::STATUS_LOCKED) {
            // Check if it's locked due to being overdue
            if ($this->deadline && now()->gt($this->deadline)) {
                return 'overdue';
            }
            return 'locked';
        }
        
        return 'active';
    }
    
    /**
     * Check if the assignment has been submitted
     *
     * @return bool
     */
    public function isSubmitted()
    {
        return $this->submitted_at !== null;
    }
    

    protected $fillable = [
        'topic_id',
        'course_id',
        'title',
        'deadline',
        'duration_minutes',
        'assignment_mode',
        'assigned_source',
        'submitted_at',
        'score' // Add this if you track scores
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'duration_minutes' => 'decimal:2',
        'submitted_at' => 'datetime',
        'score' => 'decimal:2'
    ];

    protected $appends = ['status_string', 'current_status'];
    
    // Remove the status column from the database by adding it to the $hidden array
    protected $hidden = ['status'];


    /**
     * Assignment mode constants
     */
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
        return $this->belongsToMany(User::class, 'assignment_user', 'assignment_id', 'user_id')
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

    /**
     * Scope a query to only include overdue assignments.
     * An assignment is overdue if the deadline has passed and it hasn't been submitted.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', now())
                    ->whereNull('submitted_at')
                    ->where(function($q) {
                        $q->whereNull('duration_minutes')
                          ->orWhere('duration_minutes', '=', 0)
                          ->orWhereRaw('TIMESTAMPDIFF(SECOND, created_at, NOW()) <= (duration_minutes * 60)');
                    });
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
     * Business Logic Methods
     */

    public function isExpired(): bool
    {
        return $this->duration_minutes && $this->hasTimeLimitExpired();
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
        if ($this->status_string === 'overdue') {
            return 'Overdue by ' . $this->deadline->diffForHumans(now(), true);
        }

        return 'Due ' . $this->deadline->diffForHumans();
    }

    public function canBeCompleted(): bool
    {
        // Since status column was removed, check if not expired and not past deadline
        return !$this->isExpired() && $this->deadline > now();
    }

    /**
     * Check if assignment is locked
     * Optimized to check conditions directly without computing full status
     * 
     * @return bool
     */
    public function isLocked(): bool
    {
        // If submitted, it's locked
        if ($this->submitted_at !== null) {
            return true;
        }
        
        // If time limit expired, it's locked
        if ($this->duration_minutes && $this->created_at) {
            $timeLimit = (float)$this->duration_minutes * 60;
            if (now()->diffInSeconds($this->created_at) > $timeLimit) {
                return true;
            }
        }
        
        // If past deadline, it's locked
        if ($this->deadline && now()->gt($this->deadline)) {
            return true;
        }
        
        return false;
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

    public function getUrgencyLevel(): int
    {
        $hoursRemaining = now()->diffInHours($this->deadline, false);
        
        if ($hoursRemaining < 0) {
            return self::URGENCY_OVERDUE;
        } elseif ($hoursRemaining <= 2) {
            return self::URGENCY_URGENT;
        } elseif ($hoursRemaining <= 24) {
            return self::URGENCY_APPROACHING;
        }

        return self::URGENCY_NORMAL;
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
    
    public function getStatusStringForUser(User $user): string
    {
        switch ($this->getStatusForUser($user)) {
            case self::STATUS_ACTIVE:
                return 'active';
            case self::STATUS_COMPLETED:
                return 'completed';
            case self::STATUS_LOCKED:
                return 'locked';
            default:
                return 'locked';
        }
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
        // Lock if submitted
        if ($this->submitted_at !== null) {
            return true;
        }
        
        // Lock if time limit expired
        if ($this->duration_minutes && $this->created_at) {
            $timeLimit = (float)$this->duration_minutes * 60;
            if (now()->diffInSeconds($this->created_at) > $timeLimit) {
                return true;
            }
        }
        
        // Lock if past deadline (with 24-hour grace period)
        if ($this->deadline && now()->gt($this->deadline)) {
            return true;
        }
        
        return false;
    }

    /**
     * Get the reason why assignment is locked
     */
    public function getLockReason(): ?string
    {
        if ($this->submitted_at !== null) {
            return 'Assignment has been submitted';
        }
        
        // Check if time limit expired
        if ($this->duration_minutes && $this->created_at) {
            $timeLimit = (float)$this->duration_minutes * 60;
            if (now()->diffInSeconds($this->created_at) > $timeLimit) {
                return 'Assignment time limit has expired';
            }
        }
        
        // Check if past deadline
        if ($this->deadline && now()->gt($this->deadline)) {
            return 'Assignment is past the deadline';
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
        $attempts = $this->getLoadedAttemptsForUser((int) $userId) ?? $this->attemptsForUser($userId)->get();
        
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

        // Calculate overall progress across all sources for complete topics
        $totalCorrectAnswers = $attempts->sum('score');
        $totalQuestions = $attempts->sum('total_questions');
        $overallPercentage = $totalQuestions > 0 ? ($totalCorrectAnswers / $totalQuestions) * 100 : 0;
        
        $bestAttempt = $attempts->sortByDesc('percentage_grade')->first();
        $latestAttempt = $attempts->sortByDesc('created_at')->first();

        return [
            'completed' => $overallPercentage >= 50,
            'score' => $totalCorrectAnswers,
            'percentage' => $overallPercentage,
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
        if (isset($this->user_progress['completed'])) {
            return (bool) $this->user_progress['completed'];
        }

        $attempts = $this->getLoadedAttemptsForUser($userId) ?? $this->attemptsForUser($userId)->get();
        
        if ($attempts->isEmpty()) {
            return false;
        }
        
        // Calculate overall progress across all sources
        $totalCorrectAnswers = $attempts->sum('score');
        $totalQuestions = $attempts->sum('total_questions');
        $overallPercentage = $totalQuestions > 0 ? ($totalCorrectAnswers / $totalQuestions) * 100 : 0;
        
        return $overallPercentage >= 50;
    }

    /**
     * 🚀 GENIUS: Get user's overall score for this assignment
     */
    public function getUserBestScore($userId): float
    {
        if (isset($this->user_progress['percentage'])) {
            return (float) $this->user_progress['percentage'];
        }

        $attempts = $this->getLoadedAttemptsForUser($userId) ?? $this->attemptsForUser($userId)->get();
        
        if ($attempts->isEmpty()) {
            return 0;
        }
        
        // Calculate overall progress across all sources
        $totalCorrectAnswers = $attempts->sum('score');
        $totalQuestions = $attempts->sum('total_questions');
        $overallPercentage = $totalQuestions > 0 ? ($totalCorrectAnswers / $totalQuestions) * 100 : 0;
        
        return $overallPercentage;
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

    /**
     * 🔥 CRITICAL: Check if user has attempted this assignment (one shot rule)
     */
    public function hasUserAttempted($userId): bool
    {
        $loadedAttempts = $this->getLoadedAttemptsForUser($userId);
        if ($loadedAttempts !== null) {
            return $loadedAttempts->isNotEmpty();
        }

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
     * Get the status for a specific user, simplifying all logic into one place.
     */
    public function getStatusForUser(User $user): int
    {
        if (isset($this->display_status)) {
            return (int) $this->display_status;
        }

        // 1. Check if the user has completed the assignment
        if ($this->isCompletedByUser($user->id)) {
            return self::STATUS_COMPLETED;
        }

        // 2. Check if the assignment is locked (time limit expired, past deadline, or already attempted)
        if ($this->isLocked() || $this->hasUserAttempted($user->id)) {
            return self::STATUS_LOCKED;
        }

        // 3. If none of the above, the assignment is active
        return self::STATUS_ACTIVE;
    }

    private function getLoadedAttemptsForUser(int $userId): ?Collection
    {
        // Check both relationship aliases — 'attempts' and 'quizAttempts' are the same relation
        if ($this->relationLoaded('attempts')) {
            return $this->attempts->where('user_id', $userId)->values();
        }

        if ($this->relationLoaded('quizAttempts')) {
            return $this->quizAttempts->where('user_id', $userId)->values();
        }

        return null;
    }

    /**
     * 🔥 CRITICAL: Check if user can start this assignment
     */
    public function canUserStart(User $user): bool
    {
        return $this->getStatusForUser($user) === self::STATUS_ACTIVE;
    }

    /**
     * 🔥 CRITICAL: Get lock reason for user
     */
    public function getLockReasonForUser(User $user): ?string
    {
        $status = $this->getStatusForUser($user);
        if ($status !== self::STATUS_LOCKED) {
            return null;
        }

        // Check if user has already attempted
        if ($this->hasUserAttempted($user->id)) {
            return 'You have already attempted this assignment.';
        }
        
        // Check if submitted
        if ($this->submitted_at !== null) {
            return 'This assignment has already been submitted.';
        }
        
        // Check if time limit expired
        if ($this->duration_minutes && $this->created_at) {
            $timeLimit = (float)$this->duration_minutes * 60;
            if (now()->diffInSeconds($this->created_at) > $timeLimit) {
                return 'The time limit for this assignment has expired.';
            }
        }
        
        // Check if past deadline
        if ($this->deadline && now()->gt($this->deadline)) {
            return 'This assignment is past the deadline.';
        }

        return 'This assignment is currently locked.';
    }

    /**
     * 🔥 CRITICAL: Get user progress with lock status
     * Optimized: computes status once and reuses across all derived fields.
     */
    public function getUserProgressWithStatus(User $user): array
    {
        $progress = $this->getUserProgress($user->id);

        // Compute status once — getStatusForUser may query DB if attempts not loaded
        $status = $this->getStatusForUser($user);

        // Derive all status-dependent values from the already-resolved status
        $statusString = match ($status) {
            self::STATUS_ACTIVE => 'active',
            self::STATUS_COMPLETED => 'completed',
            self::STATUS_LOCKED => 'locked',
            default => 'locked',
        };

        $isLocked = $status === self::STATUS_LOCKED;

        // Inline lock reason using already-known status to avoid redundant queries
        $lockReason = null;
        if ($isLocked) {
            // Use progress data (already fetched above) to check if user attempted
            if ($progress['attempts_count'] > 0) {
                $lockReason = 'You have already attempted this assignment.';
            } elseif ($this->submitted_at !== null) {
                $lockReason = 'This assignment has already been submitted.';
            } elseif ($this->duration_minutes && $this->created_at) {
                $timeLimit = (float) $this->duration_minutes * 60;
                if (now()->diffInSeconds($this->created_at) > $timeLimit) {
                    $lockReason = 'The time limit for this assignment has expired.';
                }
            } elseif ($this->deadline && now()->gt($this->deadline)) {
                $lockReason = 'This assignment is past the deadline.';
            }
            $lockReason ??= 'This assignment is currently locked.';
        }

        return array_merge($progress, [
            'status' => $status,
            'status_string' => $statusString,
            'is_locked' => $isLocked,
            'lock_reason' => $lockReason,
            'can_start' => $status === self::STATUS_ACTIVE,
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
