<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_id',
        'youtube_video_id',
        'title',
        'description',
        'duration',
        'is_active'
    ];

    protected $casts = [
        'youtube_video_id' => 'encrypted',
        'is_active' => 'boolean'
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }
}