<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'is_read',
        'read_at',
        'data'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'read_at' => 'datetime',
        'data' => 'json'
    ];

    /**
     * Get the user that owns the notification.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Human-readable time elapsed string.
     */
    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get the appropriate icon for the notification type.
     */
    public function getIconAttribute()
    {
        return match($this->type) {
            'achievement', 'correct_answers_milestone' => 'fas fa-award',
            'streak', 'study_streak' => 'fas fa-fire',
            'milestone' => 'fas fa-trophy',
            'topic_mastery' => 'fas fa-graduation-cap',
            'review_mistakes' => 'fas fa-redo',
            'weak_topics', 'weak_topic_added' => 'fas fa-exclamation-triangle',
            'leaderboard_rank' => 'fas fa-crown',
            'performance' => 'fas fa-chart-line',
            'quiz_completed' => 'fas fa-check-circle',
            'level_up' => 'fas fa-bolt',
            'course_welcome' => 'fas fa-book',
            'course_progress' => 'fas fa-running',
            'review_reminder' => 'fas fa-history',
            'info' => 'fas fa-info-circle',
            default => 'fas fa-bell',
        };
    }

    /**
     * Get the appropriate color for the notification type.
     */
    public function getColorAttribute()
    {
        return match($this->type) {
            'achievement', 'correct_answers_milestone', 'leaderboard_rank', 'level_up' => 'text-warning',
            'streak', 'study_streak', 'weak_topics', 'weak_topic_added' => 'text-danger',
            'milestone', 'topic_mastery', 'quiz_completed' => 'text-success',
            'performance', 'course_progress' => 'text-info',
            'review_mistakes', 'course_welcome' => 'text-primary',
            'review_reminder' => 'text-secondary',
            'info' => 'text-info',
            default => 'text-primary',
        };
    }

    /**
     * Scope a query to only include unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
} 