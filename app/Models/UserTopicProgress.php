<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTopicProgress extends Model
{
    use HasFactory;

    protected $table = 'user_topic_progress';

    protected $fillable = [
        'user_id',
        'topic_id',
        'percentage_grade',
        'last_attempt_date',
        'planner_tutor_cleared_at',
        'planner_quiz_cleared_at',
        'planner_cleared_at',
    ];

    protected $casts = [
        'percentage_grade' => 'float',
        'last_attempt_date' => 'datetime',
        'planner_tutor_cleared_at' => 'datetime',
        'planner_quiz_cleared_at' => 'datetime',
        'planner_cleared_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }
} 
