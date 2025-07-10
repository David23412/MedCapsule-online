<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Events\CourseUpdated;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image',
        'price',
        'currency',
        'cache_version'
    ];

    protected $appends = [
        'enrolled_students_count',
        'completion_percentage',
        'mastered_topics_count',
        'formatted_price',
        'image_url'
    ];

    const CACHE_TTL = 3600; // 1 hour

    /** -------------------
     * RELATIONSHIPS
     * ------------------- */

    public function topics()
    {
        return $this->hasMany(Topic::class);
    }

    public function questions()
    {
        return $this->hasManyThrough(Question::class, Topic::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'course_user')
                    ->withPivot('enrollment_status', 'enrolled_at')
                    ->withTimestamps();
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function accessRequests()
    {
        return $this->hasMany(CourseAccessRequest::class);
    }

    /** -------------------
     * ENROLLED STUDENTS AND COUNT
     * ------------------- */

    // Get enrolled students with optional limit
    public function getEnrolledStudents($limit = null)
    {
        return Cache::remember("course_{$this->id}_enrolled_students" . ($limit ? "_limit_{$limit}" : ""), self::CACHE_TTL, function () use ($limit) {
            $query = $this->users()
                         ->where('enrollment_status', 'active')
                         ->whereNotNull('email');
            
            if ($limit) {
                $query->limit($limit);
            }

            return $query->get();
        });
    }

    // Get enrolled students count
    public function getEnrolledStudentsCountAttribute()
    {
        return Cache::remember("course_{$this->id}_enrolled_count", self::CACHE_TTL, function () {
            return $this->users()
                       ->where('enrollment_status', 'active')
                       ->whereNotNull('email')
                       ->count();
        });
    }

    /** -------------------
     * COMPLETION AND MASTERED TOPICS
     * ------------------- */

    // Get completion percentage for the current user
    public function getCompletionPercentageAttribute()
    {
        if (!auth()->check()) {
            return 0;
        }

        $userId = auth()->id();
        // OPTIMIZED: Use cache versioning for better invalidation
        $cacheVersion = $this->cache_version ?? 1;
        $cacheKey = "course_{$this->id}_user_{$userId}_completion_v{$cacheVersion}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            return $this->calculateCompletionPercentage($userId);
        });
    }

    /**
     * OPTIMIZED: Calculate completion percentage with single query
     */
    private function calculateCompletionPercentage($userId)
    {
        // OPTIMIZED: Single query instead of multiple queries
        $masteredTopicsCount = DB::table('user_topic_progress')
            ->join('topics', 'user_topic_progress.topic_id', '=', 'topics.id')
            ->where('user_topic_progress.user_id', $userId)
            ->where('topics.course_id', $this->id)
            ->where('user_topic_progress.percentage_grade', '>=', 80)
            ->count();

        $totalTopicsCount = $this->topics()->count();
        
        if ($totalTopicsCount === 0) {
            return 0;
        }

        return round(($masteredTopicsCount / $totalTopicsCount) * 100, 0);
    }

    // Check if a user is enrolled in this course
    public function isUserEnrolled($userId = null)
    {
        $userId = $userId ?? auth()->id();
        if (!$userId) return false;

        return Cache::remember("course_{$this->id}_user_{$userId}_enrolled", self::CACHE_TTL, function () use ($userId) {
            return $this->users()
                       ->where('user_id', $userId)
                       ->where('enrollment_status', 'active')
                       ->exists();
        });
    }

    // Get mastered topics count for the current user
    public function getMasteredTopicsCountAttribute()
    {
        if (!auth()->check()) {
            return 0;
        }

        $userId = auth()->id();
        return Cache::remember("course_{$this->id}_user_{$userId}_mastered", self::CACHE_TTL, function () {
            return $this->topics()
                       ->where('mastered', true)
                       ->count();
        });
    }

    /** -------------------
     * PAYMENT METHODS
     * ------------------- */
    
    // Check if a user has already paid for this course
    public function hasUserPaid($userId = null)
    {
        $userId = $userId ?? auth()->id();
        if (!$userId) return false;
        
        return $this->payments()
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->exists();
    }

    // Check if a user has a pending payment for this course
    public function hasUserPendingPayment($userId = null)
    {
        $userId = $userId ?? auth()->id();
        if (!$userId) return false;
        
        return $this->payments()
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'pending_verification'])
            ->exists();
    }

    // Check if a user has a pending access request for this course
    public function hasUserPendingAccessRequest($userId = null)
    {
        $userId = $userId ?? auth()->id();
        if (!$userId) return false;
        
        return $this->accessRequests()
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->exists();
    }

    // Check if user has pending payment OR access request (for UI display)
    public function hasUserPendingRequest($userId = null)
    {
        $userId = $userId ?? auth()->id();
        if (!$userId) return false;
        
        return $this->hasUserPendingPayment($userId) || $this->hasUserPendingAccessRequest($userId);
    }
    
    // Format price with currency
    public function getFormattedPriceAttribute()
    {
        if (!$this->price || $this->price <= 0) {
            return 'Free';
        }
        
        return number_format($this->price, 2) . ' ' . $this->currency;
    }

    /** -------------------
     * CACHE MANAGEMENT
     * ------------------- */

    // Method to clear all course-related caches
    public function clearCaches()
    {
        $cacheKeys = [
            "course_{$this->id}_enrolled_count",
            "course_{$this->id}_enrolled_students",
            "course_{$this->id}_enrolled_students_limit_3", // For the common case of showing 3 students
        ];

        // Clear user-specific caches if user is logged in
        if (auth()->check()) {
            $userId = auth()->id();
            $cacheKeys[] = "course_{$this->id}_user_{$userId}_completion";
            $cacheKeys[] = "course_{$this->id}_user_{$userId}_enrolled";
            $cacheKeys[] = "course_{$this->id}_user_{$userId}_mastered";
        }

        // Forget cache keys
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    // Boot method to handle cache clearing on model events
    protected static function boot()
    {
        parent::boot();

        static::updated(function ($course) {
            $course->clearCaches();
            
            // PHASE 2: Dispatch CourseUpdated event for cache invalidation
            event(new \App\Events\CourseUpdated(
                $course,
                auth()->id(),
                $course->getChanges()
            ));
        });

        static::deleted(function ($course) {
            $course->clearCaches();
            
            // PHASE 2: Dispatch CourseUpdated event for cache invalidation
            event(new \App\Events\CourseUpdated(
                $course,
                auth()->id(),
                ['deleted' => true]
            ));
        });

        // Clear course cache when any of its topics are updated
        Topic::updated(function ($topic) {
            if ($course = $topic->course) {
                $course->clearCaches();
                
                // PHASE 2: Dispatch CourseUpdated event for cache invalidation
                event(new \App\Events\CourseUpdated(
                    $course,
                    auth()->id(),
                    ['topic_updated' => $topic->id]
                ));
            }
        });
    }

    /** -------------------
     * IMAGE URL ATTRIBUTE
     * ------------------- */

    public function getImageUrlAttribute()
    {
        return $this->image;
    }
}