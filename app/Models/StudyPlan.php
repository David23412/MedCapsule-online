<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StudyPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_date',
        'daily_goal',
        'questions_completed',
        'notes',
        'topics',
        'tasks',
        'color',
        'exam_date',
        'is_exam_day',
        'is_committed',
        'is_completed'
    ];

    protected $casts = [
        'plan_date' => 'date',
        'exam_date' => 'date',
        'topics' => 'array',
        'tasks' => 'array',
        'is_exam_day' => 'boolean',
        'is_committed' => 'boolean',
        'is_completed' => 'boolean',
        'daily_goal' => 'integer',
        'questions_completed' => 'integer'
    ];

    /**
     * Get the user that owns the study plan
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Update progress for a specific date
     */
    public static function updateProgress($userId, $date, $questionsCompleted)
    {
        $plan = self::firstOrCreate(
            [
                'user_id' => $userId,
                'plan_date' => $date
            ],
            [
                'daily_goal' => 0,
                'questions_completed' => 0
            ]
        );

        $plan->questions_completed = $questionsCompleted;
        $plan->is_completed = $plan->daily_goal > 0 && $questionsCompleted >= $plan->daily_goal;
        $plan->save();

        return $plan;
    }

    /**
     * Get completion percentage
     */
    public function getCompletionPercentageAttribute()
    {
        if ($this->daily_goal <= 0) {
            return 0;
        }
        
        return min(100, round(($this->questions_completed / $this->daily_goal) * 100));
    }

    /**
     * Check if plan is overdue
     */
    public function getIsOverdueAttribute()
    {
        return $this->plan_date->isPast() && !$this->is_completed && $this->is_committed;
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute()
    {
        if ($this->is_completed) {
            return '#10b981'; // Green
        }
        
        if ($this->is_overdue) {
            return '#ef4444'; // Red
        }
        
        if ($this->is_committed) {
            return '#3b82f6'; // Blue
        }
        
        return '#94a3b8'; // Gray
    }
}
