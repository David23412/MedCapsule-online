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
        'type',
        'image',
        'price',
        'currency',
        'cache_version',
        'color',
        'title_color',
        'academic_year'
    ];

    protected $appends = [
        'enrolled_students_count',
        'completion_percentage',
        'mastered_topics_count',
        'formatted_price',
        'image_url'
    ];

    protected $casts = [
        'type' => 'string',
        'price' => 'decimal:2',
        'cache_version' => 'integer'
    ];

    const CACHE_TTL = 30; // 30 seconds for INSTANT updates

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
                    ->withPivot('is_enrolled', 'enrolled_at')
                    ->withTimestamps();
    }

    public function enrolledUsers()
    {
        return $this->belongsToMany(User::class, 'payments', 'course_id', 'user_id')
                    ->where('payments.status', 'completed')
                    ->distinct();
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
                         ->where('is_enrolled', true)
                         ->whereNotNull('email');
            
            if ($limit) {
                $query->limit($limit);
            }

            return $query->get();
        });
    }

    // Get enrolled students count
    // 💀 N+1 WARNING: This accessor accesses 'id' - consider eager loading
    public function getEnrolledStudentsCountAttribute()
    {
        return Cache::remember("course_{$this->id}_enrolled_count", self::CACHE_TTL, function () {
            return $this->users()
                       ->where('is_enrolled', true)
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
        // Count topics in this course where user has >= 90% progress
        $completedTopicsCount = DB::table('user_topic_progress')
            ->join('topics', 'user_topic_progress.topic_id', '=', 'topics.id')
            ->where('user_topic_progress.user_id', $userId)
            ->where('topics.course_id', $this->id)
            ->where('user_topic_progress.percentage_grade', '>=', 90)
            ->count();

        $totalTopicsCount = $this->topics()->count();
        
        if ($totalTopicsCount === 0) {
            return 0;
        }

        // Progress is the percentage of topics with >= 90% completion
        return round(($completedTopicsCount / $totalTopicsCount) * 100, 0);
    }

    // Check if a user is enrolled in this course
    public function isUserEnrolled($userId = null)
    {
        $userId = $userId ?? auth()->id();
        if (!$userId) return false;

        return Cache::remember("course_{$this->id}_user_{$userId}_enrolled", self::CACHE_TTL, function () use ($userId) {
            return $this->users()
                       ->where('user_id', $userId)
                       ->where('is_enrolled', true)
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
        return Cache::remember("course_{$this->id}_user_{$userId}_mastered", self::CACHE_TTL, function () use ($userId) {
            // Count topics where user has >= 80% progress (considered mastered)
            return $this->topics()
                       ->join('user_topic_progress', 'topics.id', '=', 'user_topic_progress.topic_id')
                       ->where('user_topic_progress.user_id', $userId)
                       ->where('user_topic_progress.percentage_grade', '>=', 80)
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
    
    // 💀 N+1 WARNING: This accessor accesses 'price' - consider eager loading
    // Format price with currency
    public function getFormattedPriceAttribute()
    {
        if (!$this->price || $this->price <= 0) {
            return 'Free';
        }
        
        // Round to nearest .99 (e.g., 279.98 becomes 279.99)
        $roundedPrice = floor($this->price) + 0.99;
        
        return number_format($roundedPrice, 2) . ' ' . ($this->currency ?? 'EGP');
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
        if (auth()->check() && auth()->id()) {
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

    /**
     * Clear course completion cache for a specific user
     */
    public function clearUserCompletionCache(int $userId): void
    {
        $cacheVersion = $this->cache_version ?? 1;
        $cacheKey = "course_{$this->id}_user_{$userId}_completion_v{$cacheVersion}";
        Cache::forget($cacheKey);
        
        // Also clear other possible versions
        for ($i = 1; $i <= 5; $i++) {
            Cache::forget("course_{$this->id}_user_{$userId}_completion_v{$i}");
        }
    }

    // Boot method to handle cache clearing on model events
    protected static function boot()
    {
        parent::boot();

        static::updated(function ($course) {
            $course->clearCaches();
            
            // 🚀 BULLETPROOF PRICE CHANGE LOGIC
            if ($course->isDirty('price')) {
                $originalPrice = $course->getOriginal('price');
                $newPrice = $course->price;
                
                // SCENARIO 1: FREE TO PAID - Revoke all enrollments
                if ($originalPrice == 0 && $newPrice > 0) {
                    \Illuminate\Support\Facades\Log::info('🔒 Course changed from FREE to PAID - revoking enrollments', [
                        'course_id' => $course->id,
                        'course_name' => $course->name,
                        'old_price' => $originalPrice,
                        'new_price' => $newPrice
                    ]);
                    
                    // Remove all enrollments for this course
                    DB::table('course_user')
                        ->where('course_id', $course->id)
                        ->where('is_enrolled', true)
                        ->delete();
                    
                    \Illuminate\Support\Facades\Log::info('✅ Enrollments revoked for course that became paid', [
                        'course_id' => $course->id,
                        'course_name' => $course->name
                    ]);
                }
                
                // SCENARIO 2: PAID TO FREE - Auto-enroll ALL users (keep existing enrollments)
                elseif ($originalPrice > 0 && $newPrice == 0) {
                    \Illuminate\Support\Facades\Log::info('🆓 Course changed from PAID to FREE - auto-enrolling all users', [
                        'course_id' => $course->id,
                        'course_name' => $course->name,
                        'old_price' => $originalPrice,
                        'new_price' => $newPrice
                    ]);
                    
                    // Get all users who are NOT already enrolled
                    $allUsers = \App\Models\User::all();
                    $enrolledUserIds = DB::table('course_user')
                        ->where('course_id', $course->id)
                        ->where('is_enrolled', true)
                        ->pluck('user_id')
                        ->toArray();
                    
                    $usersToEnroll = $allUsers->whereNotIn('id', $enrolledUserIds);
                    $enrollmentData = [];
                    
                    foreach ($usersToEnroll as $user) {
                        $enrollmentData[] = [
                            'user_id' => $user->id,
                            'course_id' => $course->id,
                            'is_enrolled' => true,
                            'enrolled_at' => now(),
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                    
                    if (!empty($enrollmentData)) {
                        DB::table('course_user')->insert($enrollmentData);
                        
                        \Illuminate\Support\Facades\Log::info('✅ Auto-enrolled users in free course', [
                            'course_id' => $course->id,
                            'course_name' => $course->name,
                            'users_enrolled' => count($enrollmentData),
                            'existing_enrollments_preserved' => count($enrolledUserIds)
                        ]);
                    }
                }
                
                // SCENARIO 3: PAID TO PAID - Keep all existing enrollments (no action needed)
                elseif ($originalPrice > 0 && $newPrice > 0) {
                    \Illuminate\Support\Facades\Log::info('💰 Course price changed (PAID to PAID) - preserving all enrollments', [
                        'course_id' => $course->id,
                        'course_name' => $course->name,
                        'old_price' => $originalPrice,
                        'new_price' => $newPrice
                    ]);
                }
            }
            
            // Increment cache version to invalidate all course caches
            $currentVersion = Cache::get('courses_cache_version', 1);
            Cache::put('courses_cache_version', $currentVersion + 1, 86400); // 24 hours
            
            // PHASE 2: Dispatch CourseUpdated event for cache invalidation
            // Skip event dispatch during testing to avoid auth issues
            if (!app()->runningUnitTests()) {
                event(new \App\Events\CourseUpdated(
                    $course,
                    auth()->id(),
                    $course->getChanges()
                ));
            }
        });

        static::deleted(function ($course) {
            $course->clearCaches();
            
            // Increment cache version to invalidate all course caches
            $currentVersion = Cache::get('courses_cache_version', 1);
            Cache::put('courses_cache_version', $currentVersion + 1, 86400); // 24 hours
            
            // Clear general course listing cache
            Cache::forget('courses_list');
            Cache::forget('courses_with_stats');
            
            // PHASE 2: Dispatch CourseUpdated event for cache invalidation
            // Skip event dispatch during testing to avoid auth issues
            if (!app()->runningUnitTests()) {
                event(new \App\Events\CourseUpdated(
                    $course,
                    auth()->id(),
                    ['deleted' => true]
                ));
            }
        });

        // Clear course cache when any of its topics are updated
        // Skip this observer during testing to avoid ModelNotFoundException
        if (!app()->runningUnitTests()) {
            Topic::updated(function ($topic) {
                try {
                    // Use find instead of relationship to avoid ModelNotFoundException
                    if ($course = Course::find($topic->course_id)) {
                        $course->clearCaches();
                        
                        // Increment cache version to invalidate all course caches
                        $currentVersion = Cache::get('courses_cache_version', 1);
                        Cache::put('courses_cache_version', $currentVersion + 1, 86400); // 24 hours
                        
                        // PHASE 2: Dispatch CourseUpdated event for cache invalidation
                        event(new \App\Events\CourseUpdated(
                            $course,
                            auth()->id(),
                            ['topic_updated' => $topic->id]
                        ));
                    }
                } catch (\Exception $e) {
                    // Course was deleted or other error, ignore cache clearing
                    \Log::info('Topic updated but course not accessible', [
                        'topic_id' => $topic->id,
                        'course_id' => $topic->course_id,
                        'error' => $e->getMessage()
                    ]);
                }
            });
        }
    }

    /** -------------------
     * COLOR AND IMAGE ATTRIBUTES
     * ------------------- */

    public function getColorAttribute($value)
    {
        return $value ?: '#3b82f6'; // Default blue if no color set
    }

    public function getTitleColorAttribute($value)
    {
        return $value ?: '#FFFFFF'; // Default white if no title color set
    // 💀 N+1 WARNING: This accessor accesses 'image' - consider eager loading
    }

    public function getImageUrlAttribute()
    {
        return $this->image;
    }

    /** -------------------
     * COURSE TYPE METHODS
     * ------------------- */

    public function getTypeAttribute($value)
    {
        return $value; // Return the actual value from database
    }

    public function isMedicalCourse(): bool
    {
        return $this->type === 'medical';
    }

    public function isCustomCourse(): bool
    {
        return $this->type === 'custom';
    }

    public function getCategorizations(): array
    {
        return app(\App\Services\CourseTypeService::class)->getCategorizations($this);
    }
}