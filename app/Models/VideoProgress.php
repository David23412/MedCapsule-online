<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoProgress extends Model
{
    use HasFactory;

    protected $table = 'video_user_progress';

    protected $fillable = [
        'user_id',
        'topic_id', 
        'video_id',
        'current_time',
        'duration',
        'percentage',
        'last_watched_at'
    ];

    protected $casts = [
        'current_time' => 'decimal:2',
        'duration' => 'decimal:2',
        'percentage' => 'decimal:2',
        'last_watched_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * Update video progress for a user
     */
    public static function updateProgress($userId, $topicId, $videoId, $currentTime, $duration)
    {
        $percentage = $duration > 0 ? min(100, ($currentTime / $duration) * 100) : 0;

        return self::updateOrCreate(
            [
                'user_id' => $userId,
                'video_id' => $videoId,
                'topic_id' => $topicId
            ],
            [
                'current_time' => $currentTime,
                'duration' => $duration,
                'percentage' => $percentage,
                'last_watched_at' => now()
            ]
        );
    }

    /**
     * Check if video is completed (100% watched)
     */
    public function isCompleted()
    {
        return $this->percentage >= 100;
    }

    /**
     * Get completion status for a user and topic
     */
    public static function isTopicVideoCompleted($userId, $topicId)
    {
        $progress = self::getTopicProgress($userId, $topicId);
        return $progress ? $progress->isCompleted() : false;
    }

    /**
     * Get progress for a specific user and topic
     */
    public static function getTopicProgress($userId, $topicId)
    {
        return self::where('user_id', $userId)
                  ->where('topic_id', $topicId)
                  ->first();
    }
}