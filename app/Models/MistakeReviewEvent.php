<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MistakeReviewEvent extends Model
{
    use HasFactory;

    public const EVENT_TYPE_REVIEW = 'review';
    public const EVENT_TYPE_FAILURE_INIT = 'failure_init';
    public const EVENT_TYPE_SKIP = 'skip';

    protected $fillable = [
        'mistake_id',
        'user_id',
        'question_id',
        'review_mode',
        'event_type',
        'rating',
        'is_correct',
        'confidence_level',
        'confidence_weight',
        'response_seconds',
        'first_selected_answer',
        'final_selected_answer',
        'answer_change_count',
        'time_to_first_selection',
        'first_confidence_level',
        'confidence_change_count',
        'elapsed_days_since_last_review',
        'prior_difficulty',
        'prior_stability',
        'prior_retrievability',
        'prior_interval_days',
        'prior_next_review_at',
        'resulting_difficulty',
        'resulting_stability',
        'resulting_retrievability',
        'resulting_interval_days',
        'resulting_next_review_at',
        'target_retention',
        'decay_base',
        'calibration_snapshot',
        'reviewed_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_correct' => 'boolean',
        'confidence_weight' => 'float',
        'response_seconds' => 'integer',
        'answer_change_count' => 'integer',
        'time_to_first_selection' => 'integer',
        'confidence_change_count' => 'integer',
        'elapsed_days_since_last_review' => 'float',
        'prior_difficulty' => 'float',
        'prior_stability' => 'float',
        'prior_retrievability' => 'float',
        'prior_interval_days' => 'integer',
        'prior_next_review_at' => 'datetime',
        'resulting_difficulty' => 'float',
        'resulting_stability' => 'float',
        'resulting_retrievability' => 'float',
        'resulting_interval_days' => 'integer',
        'resulting_next_review_at' => 'datetime',
        'target_retention' => 'float',
        'decay_base' => 'float',
        'calibration_snapshot' => 'array',
        'reviewed_at' => 'datetime',
    ];
}
