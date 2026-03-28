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
use App\Models\Video;

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
        'case_type',
        'cache_version',
        'is_locked',
        'is_exam',
        'locked_by_tutor_id',
        'lock_reason',
        'locked_at',
        'unlocked_at'
    ];

    protected $casts = [
        'is_locked' => 'boolean',
        'is_exam' => 'boolean',
        'locked_at' => 'datetime',
        'unlocked_at' => 'datetime'
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

    // DEPRECATED: Use allQuestions() or the extra_topic column
    public function linkedQuestions()
    {
        return collect();
    }

    // Get all questions for this topic (both direct and linked via extra_topic)
    public function allQuestions()
    {
        return Question::where(function($q) {
                $q->where('topic_id', $this->id)
                  ->orWhereRaw('FIND_IN_SET(?, REPLACE(extra_topic, " ", "")) > 0', [$this->id]);
            })
            ->with(['topic']) 
            ->orderBy('id')
            ->get();
    }

    public function isExamTopic(): bool
    {
        return (bool) $this->is_exam;
    }

    // Get questions for exam topics (only direct questions to maintain exam order)
    public function examQuestions()
    {
        return $this->allQuestions();
    }

    // Get lecture-only questions (exclude exam sources)
    public function lectureQuestions()
    {
        $all = $this->allQuestions();
        return $all->filter(fn($q) => !$q->isFromExam());
    }

    // Get past exam questions
    public function pastExamQuestions()
    {
        $all = $this->allQuestions();
        return $all->filter(fn($q) => $q->isFromExam());
    }

    // Get repeated questions (appeared in 2+ exams)
    public function repeatedQuestions()
    {
        $all = $this->allQuestions();
        return $all->filter(fn($q) => $q->exam_appearances_count >= 2);
    }

    // Get high-yield questions (appeared in 3+ exams)
    public function highYieldQuestions()
    {
        $all = $this->allQuestions();
        return $all->filter(fn($q) => $q->exam_appearances_count >= 3);
    }

    // Get questions with mistakes for current user
    public function mistakeQuestions()
    {
        if (!auth()->check()) return collect();
        
        $userMistakeIds = \App\Models\Mistake::where('user_id', auth()->id())
            ->pluck('question_id')
            ->toArray();
            
        return $this->allQuestions()->filter(function ($question) use ($userMistakeIds) {
            return in_array($question->id, $userMistakeIds);
        });
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userProgress()
    {
        return $this->hasMany(UserTopicProgress::class);
    }

    public function lockedByTutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by_tutor_id');
    }

    public function video()
    {
        return $this->hasOne(Video::class);
    }
    
    public function hasVideo()
    {
        return $this->video()->exists();
    }

    public function videoProgress()
    {
        return $this->hasMany(VideoProgress::class);
    }

    public function userVideoProgress()
    {
        return $this->hasOne(VideoProgress::class)->where('user_id', auth()->id());
    }

    // 💀 N+1 WARNING: This accessor accesses 'userProgress' - consider eager loading
    public function getCurrentUserProgressAttribute()
    {
        if (!auth()->check()) return null;

        if ($this->relationLoaded('userProgress')) {
            return $this->userProgress->first();
        }

        return $this->userProgress()->where('user_id', auth()->id())->first();
    }
    // 💀 N+1 WARNING: This accessor accesses 'current_user_progress' - consider eager loading

    public function getPercentageGradeAttribute()
    {
        if (!auth()->check()) return null;
        $progress = $this->current_user_progress;
        return $progress ? $progress->percentage_grade : null;
    // 💀 N+1 WARNING: This accessor accesses 'current_user_progress' - consider eager loading
    }

    public function getLastAttemptDateAttribute()
    {
        if (!auth()->check()) return null;

        if (array_key_exists('last_attempt_date', $this->attributes)) {
            return $this->attributes['last_attempt_date'];
        }

        if ($this->relationLoaded('userProgress')) {
            return $this->userProgress->first()?->last_attempt_date;
        }

        $progress = $this->current_user_progress;
        return $progress ? $progress->last_attempt_date : null;
    }

    // Dynamic Attributes for the model

    /**
     * Completion percentage of the topic based on the user's progress.
    // 💀 N+1 WARNING: This accessor accesses 'id' - consider eager loading
     *
     * @return float
     */
    public function getCompletionPercentageAttribute(): float
    {
        if (!auth()->check()) return 0;

        if (array_key_exists('completion_percentage', $this->attributes)) {
            return (float) $this->attributes['completion_percentage'];
        }

        if ($this->relationLoaded('userProgress')) {
            return (float) ($this->userProgress->first()?->percentage_grade ?? 0);
        }

        $progress = $this->getCurrentUserProgressAttribute();
        if ($progress?->percentage_grade !== null) {
            return (float) $progress->percentage_grade;
        }

        $cacheKey = $this->getCacheKey(auth()->id(), 'completion');

        return Cache::remember($cacheKey, 3600, function () {
            return (float) (
                UserTopicProgress::query()
                    ->where('user_id', auth()->id())
                    ->where('topic_id', $this->id)
                    ->value('percentage_grade') ?? 0
            );
        });
    }

    /**
    // 💀 N+1 WARNING: This accessor accesses 'id' - consider eager loading
     * Get the count of questions associated with the topic.
     * For exam topics: only direct questions
     * For lecture topics: direct + linked questions
     *
     * @return int
     */
    public function getQuestionsCountAttribute(): int
    {
        if (array_key_exists('questions_count', $this->attributes)) {
            return (int) $this->attributes['questions_count'];
        }

        return Cache::remember("topic:{$this->id}:questions_count", 3600, function () {
            return Question::where(function($q) {
                    $q->where('topic_id', $this->id)
                      ->orWhereRaw('FIND_IN_SET(?, REPLACE(extra_topic, " ", "")) > 0', [$this->id]);
                })->count();
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
            
            // Increment cache version on any save
            $topic->cache_version = (int) ($topic->cache_version ?? 0) + 1;
        });

        // Clear the cache whenever a topic is updated or deleted
        static::updated(function ($topic) {
            Cache::forget("topic:{$topic->id}:questions_count");
        });

        static::deleted(function ($topic) {
            Cache::forget("topic:{$topic->id}:questions_count");
        });
    }

    public function getCacheKey($userId, $suffix = '')
    {
        return "topic:{$this->id}:user:{$userId}:{$suffix}:v{$this->cache_version}";
    // 💀 N+1 WARNING: This accessor accesses 'id' - consider eager loading
    }

    /**
     * Get total duration spent on this topic by the current user
     */
    public function getTotalDurationAttribute(): int
    {
        if (!auth()->check()) return 0;

        if (array_key_exists('total_duration', $this->attributes)) {
            return (int) $this->attributes['total_duration'];
        }
        
        return QuizAttempt::where('user_id', auth()->id())
            ->where('topic_id', $this->id)
    // 💀 N+1 WARNING: This accessor accesses 'total_duration' - consider eager loading
            ->sum('duration_seconds');
    }

    /**
     * Get formatted duration (in minutes)
     */
    public function getFormattedDurationAttribute(): string
    {
        $duration = $this->total_duration;
    // 💀 N+1 WARNING: This accessor accesses 'id' - consider eager loading
        $minutes = floor($duration / 60);
        return $minutes . ' min';
    }

    /**
     * Check if all sources in this topic are mastered
     */
    public function getAllSourcesMasteredAttribute(): bool
    {
        if (!auth()->check()) return false;

        if (array_key_exists('all_sources_mastered', $this->attributes)) {
            return (bool) $this->attributes['all_sources_mastered'];
        }
        
        $progress = QuizAttempt::getTopicProgress(auth()->id(), $this->id);
        if (empty($progress['sources'])) return false;

        foreach ($progress['sources'] as $sourceProgress) {
            if ($sourceProgress['percentage'] < 100) {
                return false;
            }
        }
        
        return true;
    }
}
