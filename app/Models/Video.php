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
        'duration'
    ];

    protected $casts = [
        // 'youtube_video_id' => 'encrypted' // Removed encryption for simplicity
    ];



    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function userProgress(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VideoProgress::class);
    }

    public function getUserProgress($userId)
    {
        return $this->userProgress()->where('user_id', $userId)->first();
    }
}
