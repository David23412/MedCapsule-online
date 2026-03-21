<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyDraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'survey_key',
        'answers',
        'current_step',
        'redirect_to',
        'last_saved_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'current_step' => 'integer',
        'last_saved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
