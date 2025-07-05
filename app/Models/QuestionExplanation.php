<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionExplanation extends Model
{
    protected $fillable = [
        'question_id',
        'detailed_explanation',
        'key_points',
        'related_concepts',
        'answer_statistics',
        'references',
        'images',
    ];

    protected $casts = [
        'answer_statistics' => 'array',
        'images' => 'array',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
} 