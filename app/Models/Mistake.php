<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class Mistake extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'question_id',
        'submitted_answer',
        'question_text',
        'correct_answer',
        'image_path',
        'correct_streak',
        'last_attempt_date',
        'quick_correct_count',
        'quiz_correct_count',
        'mastered'
    ];

    protected $attributes = [
        'correct_streak' => 0,
        'submitted_answer' => '',  // Default empty string for submitted_answer
        'quick_correct_count' => 0,
        'quiz_correct_count' => 0,
        'mastered' => false
    ];

    protected $casts = [
        'last_attempt_date' => 'datetime',
        'correct_streak' => 'integer',
        'quick_correct_count' => 'integer',
        'quiz_correct_count' => 'integer',
        'mastered' => 'boolean'
    ];

    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($mistake) {
            // Ensure the question exists before creating a mistake entry
            if (!Question::find($mistake->question_id)) {
                Log::warning('⚠️ Attempting to create a mistake with an invalid question_id', [
                    'question_id' => $mistake->question_id,
                    'user_id' => $mistake->user_id
                ]);
                return false;
            }

            // Initialize tracking fields
            $mistake->correct_streak = 0;
            $mistake->last_attempt_date = now();
        });

        static::updating(function ($mistake) {
            if (!Question::find($mistake->question_id)) {
                Log::warning('⚠️ Attempting to update a mistake with an invalid question_id', [
                    'question_id' => $mistake->question_id,
                    'user_id' => $mistake->user_id
                ]);
                return false;
            }
        });

        static::deleted(function ($mistake) {
            $mistake->clearCaches();
        });
    }

    /**
     * Get the question associated with the mistake.
     */
    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Get the user who made the mistake.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the mistake is considered mastered based on mode
     */
    public function isMastered($mode = 'quiz'): bool
    {
        $requiredStreak = ($mode === 'quick') ? 2 : 4;
        return $this->correct_streak >= $requiredStreak;
    }

    /**
     * Update mistake tracking based on answer correctness and mode
     */
    public function updateTracking($isCorrect, $mode = 'quiz')
    {
        // Update last attempt date
        $this->last_attempt_date = now();

        if ($isCorrect) {
            $this->correct_streak++;
        } else {
            // Reset streak on incorrect answer
            $this->correct_streak = 0;
        }

        $this->save();
        
        Log::info('Updated mistake tracking', [
            'question_id' => $this->question_id,
            'user_id' => $this->user_id,
            'correct_streak' => $this->correct_streak,
            'is_mastered' => $this->isMastered($mode),
            'mode' => $mode
        ]);
    }

    /**
     * Scope a query to only include mistakes that aren't mastered yet.
     */
    public function scopeNotMastered($query, $mode = 'quiz')
    {
        $requiredStreak = ($mode === 'quick') ? 2 : 4;
        return $query->where('correct_streak', '<', $requiredStreak);
    }

    /**
     * Scope a query to only include mastered mistakes.
     */
    public function scopeMastered($query, $mode = 'quiz')
    {
        $requiredStreak = ($mode === 'quick') ? 2 : 4;
        return $query->where('correct_streak', '>=', $requiredStreak);
    }

    /**
     * Clear caches for progress tracking.
     */
    protected function clearCaches()
    {
        Cache::forget("mistake_{$this->user_id}_{$this->question_id}_progress");
    }
}
