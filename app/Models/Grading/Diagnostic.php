<?php

namespace App\Models\Grading;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diagnostic extends Model
{
    use HasFactory;

    protected $table = 'grading_diagnostics';

    protected $fillable = [
        'grader_version',
        'question_id',
        'user_id',
        'submitted_answer_hash',
        'old_result',
        'new_result',
        'disagreement',
        'reason_code',
        'diagnostics',
    ];

    protected $casts = [
        'question_id' => 'integer',
        'user_id' => 'integer',
        'old_result' => 'boolean',
        'new_result' => 'boolean',
        'disagreement' => 'boolean',
        'diagnostics' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
