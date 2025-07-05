<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image',
        'color',
        'is_paid',
        'price',
        'currency',
    ];

    protected $appends = ['image_url'];

    protected $casts = [
        'completion_percentage' => 'float',
        'is_paid' => 'boolean',
        'price' => 'decimal:2',
    ];

    // Cache TTL in seconds (1 hour)
    private const CACHE_TTL = 3600;

    /** -------------------
     * RELATIONSHIPS
     * ------------------- */
    // A course has many topics
    public function topics()
    {
        return $this->hasMany(Topic::class)->orderBy('display_order');
    }

    // Efficient relationship using hasManyThrough for questions
    public function questions()
    {
        return $this->hasManyThrough(Question::class, Topic::class);
    }

    // Define the many-to-many relationship with users
    public function users()
    {
        return $this->belongsToMany(User::class, 'course_user')
                    ->withPivot('enrollment_status')
                    ->withTimestamps();
    }

    // Define relationship with payments
    public function payments()
    {
        return $this->hasMany(Payment::class);
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
        return Cache::remember("course_{$this->id}_user_{$userId}_completion", self::CACHE_TTL, function () {
            $topics = $this->topics()->get();
            
            if ($topics->isEmpty()) {
                return 0;
            }

            // Count topics that have been mastered (>= 80% grade)
            $masteredTopics = $topics->filter(function ($topic) {
                $progress = $topic->userProgress()
                    ->where('user_id', auth()->id())
                    ->first();
                return $progress && $progress->percentage_grade >= 80;
            })->count();

            // Calculate percentage based on total topics
            return round(($masteredTopics / $topics->count()) * 100, 0);
        });
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
    
    // Format price with currency
    public function getFormattedPriceAttribute()
    {
        if (!$this->is_paid || $this->price <= 0) {
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
        });

        static::deleted(function ($course) {
            $course->clearCaches();
        });

        // Clear course cache when any of its topics are updated
        Topic::updated(function ($topic) {
            if ($course = $topic->course) {
                $course->clearCaches();
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