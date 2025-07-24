<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentAttempt extends Model
{
    use HasFactory;

    // Status constants
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ABANDONED = 'abandoned';

    protected $fillable = [
        'assignment_id',
        'user_id',
        'student_id',
        'topic_id',
        'course_id',
        'source',
        'questions_answered',
        'total_questions',
        'correct_answers',
        'incorrect_answers',
        'score_percentage',
        'time_spent_seconds',
        'time_taken_seconds',
        'started_at',
        'completed_at',
        'auto_submitted',
        'answers',
        'question_responses',
        'status',
        'assignment_mode',
        'assigned_source',
        'is_late_submission',
        'notes'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'questions_answered' => 'integer',
        'total_questions' => 'integer',
        'correct_answers' => 'integer',
        'incorrect_answers' => 'integer',
        'score_percentage' => 'decimal:2',
        'time_spent_seconds' => 'integer',
        'time_taken_seconds' => 'integer',
        'answers' => 'array',
        'question_responses' => 'array',
        'auto_submitted' => 'boolean',
        'is_late_submission' => 'boolean'
    ];

    /**
     * Relationships
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }





    /**
     * Get time taken in human readable format
     */
    public function getTimeTakenHuman(): string
    {
        if (!$this->time_spent_seconds) {
            return '0:00';
        }
        
        $minutes = floor($this->time_spent_seconds / 60);
        $seconds = $this->time_spent_seconds % 60;
        
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Get completion percentage
     */
    public function getCompletionPercentage(): float
    {
        if ($this->questions_answered === 0) {
            return 0;
        }
        
        return ($this->correct_answers / $this->questions_answered) * 100;
    }

    /**
     * Check if attempt is completed
     */
    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Check if attempt is in progress
     */
    public function isInProgress(): bool
    {
        return $this->completed_at === null;
    }

    /**
     * Check if attempt has expired
     */
    public function hasExpired(): bool
    {
        return $this->auto_submitted === true;
    }

    /**
     * Check if attempt was successful (>= 70% correct)
     */
    public function isSuccessful(): bool
    {
        return $this->score_percentage >= 70;
    }

    /**
     * Get performance level
     */
    public function getPerformanceLevel(): string
    {
        if ($this->score_percentage >= 90) {
            return 'excellent';
        } elseif ($this->score_percentage >= 80) {
            return 'good';
        } elseif ($this->score_percentage >= 70) {
            return 'satisfactory';
        } elseif ($this->score_percentage >= 60) {
            return 'needs_improvement';
        } else {
            return 'poor';
        }
    }
}