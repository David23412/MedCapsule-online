<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTopicSourceProgress extends Model
{
    use HasFactory;

    protected $table = 'user_topic_source_progress';

    protected $fillable = [
        'user_id',
        'topic_id',
        'source',
        'completed_question_ids',
        'correct_question_ids',
        'completed_count',
        'correct_count',
        'total_questions',
        'last_attempt_date',
        'last_activity_at',
    ];

    protected $casts = [
        'completed_question_ids' => 'array',
        'correct_question_ids' => 'array',
        'completed_count' => 'integer',
        'correct_count' => 'integer',
        'total_questions' => 'integer',
        'last_attempt_date' => 'datetime',
        'last_activity_at' => 'datetime',
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
