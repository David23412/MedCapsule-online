<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Question;
use App\Models\Course;
use App\Models\User;
use App\Models\UserTopicProgress;
use App\Models\QuizAttempt;
use App\Models\SourceProgress;
use App\Models\QuestionAttempt;

class Topic extends Model
{
    use HasFactory;

    // Fields that are mass assignable
    protected $fillable = [
        'name',
        'course_id',
        'user_id',
        'description',
        'display_order',
        'percentage_grade',
        'last_attempt_date',
        'case_type',
        'cache_version'
    ];

    // Append dynamic properties to the model
    protected $appends = [
        'completion_percentage',
        'last_attempt_date',
        'questions_count',
        'total_duration',
        'formatted_duration',
        'all_sources_mastered'
    ];

    // Relationships
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('id'); // Ensure questions are ordered
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userProgress()
    {
        return $this->hasMany(UserTopicProgress::class);
    }

    public function getCurrentUserProgressAttribute()
    {
        if (!auth()->check()) return null;
        return $this->userProgress()->where('user_id', auth()->id())->first();
    }

    public function getPercentageGradeAttribute()
    {
        if (!auth()->check()) return null;
        $progress = $this->current_user_progress;
        return $progress ? $progress->percentage_grade : null;
    }

    public function getLastAttemptDateAttribute()
    {
        if (!auth()->check()) return null;
        $progress = $this->current_user_progress;
        return $progress ? $progress->last_attempt_date : null;
    }

    // Dynamic Attributes for the model

    /**
     * Completion percentage of the topic based on the user's progress.
     *
     * @return float
     */
    public function getCompletionPercentageAttribute(): float
    {
        if (!auth()->check()) return 0;

        $cacheKey = $this->getCacheKey(auth()->id(), 'completion');

        return Cache::remember($cacheKey, 3600, function () {
            // Get total questions in topic
            $totalQuestions = $this->questions()->count();
            if ($totalQuestions === 0) return 0;

            // Get the latest quiz attempt for this topic
            $latestAttempt = QuizAttempt::where([
                'user_id' => auth()->id(),
                'topic_id' => $this->id,
            ])
            ->orderBy('created_at', 'desc')
            ->first();

            if (!$latestAttempt) return 0;

            // Calculate percentage based on the latest attempt
            return min(100, round(($latestAttempt->score / $totalQuestions) * 100, 2));
        });
    }

    /**
     * Get the count of questions associated with the topic.
     *
     * @return int
     */
    public function getQuestionsCountAttribute(): int
    {
        return Cache::remember("topic:{$this->id}:questions_count", 3600, function () {
            return $this->questions()->count();
        });
    }

    /**
     * Get the mastery level based on percentage grade (for analytics only)
     */
    public function getMasteryLevelForAnalytics(): string
    {
        if (!$this->percentage_grade) return 'Not Attempted';
        if ($this->percentage_grade >= 100) return 'Mastered';
        if ($this->percentage_grade >= 80) return 'Great';
        if ($this->percentage_grade >= 60) return 'Good';
        return 'Weak';
    }

    /**
     * Get the color for analytics display
     */
    public function getMasteryColorForAnalytics(): string
    {
        if (!$this->percentage_grade) return '#6B7280'; // gray-500
        if ($this->percentage_grade >= 100) return '#FFD700'; // gold
        if ($this->percentage_grade >= 80) return '#047857'; // dark green
        if ($this->percentage_grade >= 60) return '#34D399'; // light green
        return '#EF4444'; // red
    }

    // Query Scopes for better query optimization
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('id');
    }

    /**
     * Scope for weak topics (< 60%)
     */
    public function scopeWeak($query)
    {
        return $query->where('percentage_grade', '<', 60);
    }

    /**
     * Scope for good topics (60-79%)
     */
    public function scopeGood($query)
    {
        return $query->whereBetween('percentage_grade', [60, 79]);
    }

    /**
     * Scope for great topics (80-99%)
     */
    public function scopeGreat($query)
    {
        return $query->whereBetween('percentage_grade', [80, 99]);
    }

    /**
     * Scope for mastered topics (100%)
     */
    public function scopeMastered($query)
    {
        return $query->where('percentage_grade', '>=', 100);
    }

    /**
     * Boot method for handling events such as saving, updating, and deleting topics.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically set display_order if not provided when saving
        static::saving(function ($topic) {
            if (!$topic->display_order) {
                $topic->display_order = static::where('course_id', $topic->course_id)
                    ->max('display_order') + 1;
            }
        });

        // Clear the cache whenever a topic is updated or deleted
        static::updated(function ($topic) {
            if (auth()->check()) {
                Cache::forget("topic:{$topic->id}:user:" . auth()->id() . ":completion");
                Cache::forget("topic:{$topic->id}:sources");
                Cache::forget("topic:{$topic->id}:v{$topic->cache_version}");
            }
        });

        static::deleted(function ($topic) {
            if (auth()->check()) {
                Cache::forget("topic:{$topic->id}:user:" . auth()->id() . ":completion");
                Cache::forget("topic:{$topic->id}:sources");
                Cache::forget("topic:{$topic->id}:v{$topic->cache_version}");
            }
        });

        // Increment cache version on any update
        static::saved(function ($topic) {
            $topic->increment('cache_version');
        });
    }

    public function getCacheKey($userId, $suffix = '')
    {
        return "topic:{$this->id}:user:{$userId}:{$suffix}:v{$this->cache_version}";
    }

    /**
     * Get total duration spent on this topic by the current user
     */
    public function getTotalDurationAttribute(): int
    {
        if (!auth()->check()) return 0;
        
        return QuizAttempt::where('user_id', auth()->id())
            ->where('topic_id', $this->id)
            ->sum('duration_seconds');
    }

    /**
     * Get formatted duration (in minutes)
     */
    public function getFormattedDurationAttribute(): string
    {
        $duration = $this->total_duration;
        $minutes = floor($duration / 60);
        return $minutes . ' min';
    }

    /**
     * Check if all sources in this topic are mastered
     */
    public function getAllSourcesMasteredAttribute(): bool
    {
        if (!auth()->check()) return false;
        
        $progress = QuizAttempt::getTopicProgress(auth()->id(), $this->id);
        if (empty($progress['source_progress'])) return false;

        foreach ($progress['source_progress'] as $sourceProgress) {
            if ($sourceProgress['percentage'] < 100) {
                return false;
            }
        }
        
        return true;
    }
}