<?php

namespace App\Support\State;

use App\Models\Assignment;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Single Source of Truth for Assignment Status State
 * 
 * Replaces split brain logic between:
 * - Computed status (removed from database but still used in logic)
 * - deadline (datetime)
 * - duration_minutes (decimal)
 * - created_at (datetime for time limit calculations)
 */
class AssignmentStatusService
{
    const CACHE_TTL = 60; // 1 minute for real-time status

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_EXPIRED = 'expired';
    const STATUS_LOCKED = 'locked';

    /**
     * Get definitive assignment status
     * 
     * @param Assignment $assignment
     * @return array ['status' => string, 'reason' => string, 'computed_at' => Carbon]
     */
    public function getAssignmentStatus(Assignment $assignment): array
    {
        $cacheKey = "assignment_status_{$assignment->id}_v" . ($assignment->updated_at?->timestamp ?? 0);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($assignment) {
            $now = now();
            
            // Priority 1: Check time limit expiration
            if ($this->hasTimeLimitExpired($assignment)) {
                return [
                    'status' => self::STATUS_EXPIRED,
                    'reason' => 'Time limit exceeded',
                    'computed_at' => $now,
                    'expires_at' => $this->getTimeLimitExpiry($assignment),
                    'time_remaining' => 0
                ];
            }
            
            // Priority 2: Check deadline expiration
            if ($assignment->deadline < $now) {
                $overdueMinutes = $now->diffInMinutes($assignment->deadline);
                return [
                    'status' => self::STATUS_OVERDUE,
                    'reason' => "Overdue by {$overdueMinutes} minutes",
                    'computed_at' => $now,
                    'deadline' => $assignment->deadline,
                    'overdue_minutes' => $overdueMinutes
                ];
            }
            
            // Priority 3: Active assignment
            $timeRemaining = $assignment->deadline->diffInSeconds($now);
            return [
                'status' => self::STATUS_ACTIVE,
                'reason' => 'Assignment is active',
                'computed_at' => $now,
                'deadline' => $assignment->deadline,
                'time_remaining' => $timeRemaining
            ];
        });
    }

    /**
     * Get simple status string
     */
    public function getStatus(Assignment $assignment): string
    {
        return $this->getAssignmentStatus($assignment)['status'];
    }

    /**
     * Check if assignment is active
     */
    public function isActive(Assignment $assignment): bool
    {
        return $this->getStatus($assignment) === self::STATUS_ACTIVE;
    }

    /**
     * Check if assignment is overdue
     */
    public function isOverdue(Assignment $assignment): bool
    {
        return $this->getStatus($assignment) === self::STATUS_OVERDUE;
    }

    /**
     * Check if assignment is expired (time limit)
     */
    public function isExpired(Assignment $assignment): bool
    {
        return $this->getStatus($assignment) === self::STATUS_EXPIRED;
    }

    /**
     * Check if assignment can be accessed
     */
    public function canAccess(Assignment $assignment): bool
    {
        $status = $this->getStatus($assignment);
        return in_array($status, [self::STATUS_ACTIVE]);
    }

    /**
     * Get time remaining in seconds (null if expired/overdue)
     */
    public function getTimeRemaining(Assignment $assignment): ?int
    {
        $statusData = $this->getAssignmentStatus($assignment);
        
        if ($statusData['status'] !== self::STATUS_ACTIVE) {
            return null;
        }
        
        return max(0, $statusData['time_remaining'] ?? 0);
    }

    /**
     * Get human-readable time remaining
     */
    public function getTimeRemainingHuman(Assignment $assignment): string
    {
        $statusData = $this->getAssignmentStatus($assignment);
        
        switch ($statusData['status']) {
            case self::STATUS_OVERDUE:
                return "Overdue by " . $assignment->deadline->diffForHumans(now(), true);
            case self::STATUS_EXPIRED:
                return "Time limit expired";
            case self::STATUS_ACTIVE:
                return "Due " . $assignment->deadline->diffForHumans();
            default:
                return "Status unknown";
        }
    }

    /**
     * Check if assignment has time limit
     */
    public function hasTimeLimit(Assignment $assignment): bool
    {
        return $assignment->duration_minutes !== null && $assignment->duration_minutes > 0;
    }

    /**
     * Check if time limit has expired
     */
    public function hasTimeLimitExpired(Assignment $assignment): bool
    {
        if (!$this->hasTimeLimit($assignment)) {
            return false;
        }
        
        if (!$assignment->created_at) {
            return false;
        }
        
        $timeLimit = (float)$assignment->duration_minutes * 60; // Convert to seconds
        $elapsed = now()->diffInSeconds($assignment->created_at);
        
        return $elapsed > $timeLimit;
    }

    /**
     * Get time limit expiry timestamp
     */
    public function getTimeLimitExpiry(Assignment $assignment): ?Carbon
    {
        if (!$this->hasTimeLimit($assignment) || !$assignment->created_at) {
            return null;
        }
        
        return $assignment->created_at->copy()->addMinutes($assignment->duration_minutes);
    }

    /**
     * Get assignment progress percentage (time-based)
     */
    public function getProgressPercentage(Assignment $assignment): float
    {
        if ($this->isOverdue($assignment) || $this->isExpired($assignment)) {
            return 100.0;
        }

        if (!$assignment->created_at) {
            return 0.0;
        }

        $totalTime = $assignment->created_at->diffInSeconds($assignment->deadline);
        $elapsedTime = $assignment->created_at->diffInSeconds(now());

        if ($totalTime <= 0) {
            return 100.0;
        }

        return min(100.0, ($elapsedTime / $totalTime) * 100);
    }

    /**
     * Get user-specific assignment status (includes completion check)
     */
    public function getUserAssignmentStatus(Assignment $assignment, int $userId): array
    {
        $baseStatus = $this->getAssignmentStatus($assignment);
        
        // Check if user has attempted this assignment
        $hasAttempted = $assignment->quizAttempts()
            ->where('user_id', $userId)
            ->exists();
            
        if ($hasAttempted) {
            // Check if completed (score >= 50%)
            $bestAttempt = $assignment->quizAttempts()
                ->where('user_id', $userId)
                ->orderBy('percentage_grade', 'desc')
                ->first();
                
            $isCompleted = $bestAttempt && $bestAttempt->percentage_grade >= 50;
            
            return array_merge($baseStatus, [
                'user_status' => $isCompleted ? 'completed' : 'attempted',
                'can_access' => false, // One-shot rule
                'lock_reason' => $isCompleted ? 'Assignment completed' : 'Assignment attempted',
                'best_score' => $bestAttempt?->percentage_grade ?? 0
            ]);
        }
        
        // User hasn't attempted - check if can access
        $canAccess = $this->canAccess($assignment);
        
        return array_merge($baseStatus, [
            'user_status' => 'available',
            'can_access' => $canAccess,
            'lock_reason' => $canAccess ? null : $this->getLockReason($baseStatus['status'])
        ]);
    }

    /**
     * Get lock reason based on status
     */
    private function getLockReason(string $status): ?string
    {
        switch ($status) {
            case self::STATUS_OVERDUE:
                return 'Assignment is overdue';
            case self::STATUS_EXPIRED:
                return 'Assignment time limit expired';
            case self::STATUS_LOCKED:
                return 'Assignment is locked';
            default:
                return null;
        }
    }

    /**
     * Bulk status check for multiple assignments
     */
    public function getBulkAssignmentStatus(array $assignments): array
    {
        $results = [];
        foreach ($assignments as $assignment) {
            $results[$assignment->id] = $this->getAssignmentStatus($assignment);
        }
        return $results;
    }

    /**
     * Clear assignment status cache
     */
    public function clearStatusCache(Assignment $assignment): void
    {
        $pattern = "assignment_status_{$assignment->id}_v*";
        // Clear all versions of this assignment's cache
        for ($i = 0; $i < 10; $i++) {
            Cache::forget("assignment_status_{$assignment->id}_v{$i}");
        }
        Cache::forget("assignment_status_{$assignment->id}_v" . ($assignment->updated_at?->timestamp ?? 0));
    }

    /**
     * Get assignments by status
     */
    public function getAssignmentsByStatus(string $status, ?int $userId = null): array
    {
        $assignments = Assignment::all();
        $filtered = [];
        
        foreach ($assignments as $assignment) {
            $assignmentStatus = $userId 
                ? $this->getUserAssignmentStatus($assignment, $userId)
                : $this->getAssignmentStatus($assignment);
                
            if ($assignmentStatus['status'] === $status) {
                $filtered[] = $assignment;
            }
        }
        
        return $filtered;
    }
}