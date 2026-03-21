<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlannerTaskOutcome extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'study_plan_id',
        'course_id',
        'topic_id',
        'plan_date',
        'task_id',
        'task_type',
        'title',
        'study_mode',
        'source',
        'reason_code',
        'planner_focus',
        'allocated_minutes',
        'total_daily_minutes',
        'question_target',
        'duration_minutes',
        'is_completed',
        'first_presented_at',
        'last_presented_at',
        'completed_at',
        'completion_latency_minutes',
        'task_payload',
    ];

    protected $casts = [
        'plan_date' => 'date',
        'is_completed' => 'boolean',
        'allocated_minutes' => 'integer',
        'total_daily_minutes' => 'integer',
        'question_target' => 'integer',
        'duration_minutes' => 'integer',
        'completion_latency_minutes' => 'integer',
        'first_presented_at' => 'datetime',
        'last_presented_at' => 'datetime',
        'completed_at' => 'datetime',
        'task_payload' => 'array',
    ];
}
