<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use App\Events\CourseUpdated;

class Course extends Model
{
    use HasFactory;

    protected static ?array $availableCourseUserPlannerPivotColumns = null;
    protected ?array $accessTransitionSnapshot = null;

    public const ACCESS_TYPE_FREE = 'free';
    public const ACCESS_TYPE_PREVIEW = 'preview';
    public const ACCESS_TYPE_PAID = 'paid';

    public const ACCESS_TYPES = [
        self::ACCESS_TYPE_FREE,
        self::ACCESS_TYPE_PREVIEW,
        self::ACCESS_TYPE_PAID,
    ];

    public const SURVEY_UNLOCK_ACCESS_TYPES = [
        self::ACCESS_TYPE_FREE,
        self::ACCESS_TYPE_PREVIEW,
    ];

    public const PURCHASABLE_ACCESS_TYPES = [
        self::ACCESS_TYPE_PREVIEW,
        self::ACCESS_TYPE_PAID,
    ];

    protected $fillable = [
        'name',
        'description',
        'type',
        'access_type',
        'image',
        'price',
        'currency',
        'color',
        'title_color',
        'academic_year'
    ];

    protected $appends = [
        'completion_percentage',
        'formatted_price',
        'image_url'
    ];

    protected $casts = [
        'type' => 'string',
        'access_type' => 'string',
        'price' => 'decimal:2'
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
                    ->withPivot($this->courseUserPivotColumns(['is_enrolled']))
                    ->withTimestamps();
    }

    /**
     * Relationship for users who are marked as enrolled in the pivot table.
     */
    public function enrolledUsers()
    {
        return $this->belongsToMany(User::class, 'course_user')
            ->withPivot($this->courseUserPivotColumns())
            ->wherePivot('is_enrolled', true);
    }

    /**
     * Avoid selecting planner pivot columns on installations where the
     * course_user table has not been migrated to include them.
     */
    protected function courseUserPivotColumns(array $baseColumns = []): array
    {
        return array_merge($baseColumns, self::availableCourseUserPlannerPivotColumns());
    }

    protected static function availableCourseUserPlannerPivotColumns(): array
    {
        if (self::$availableCourseUserPlannerPivotColumns !== null) {
            return self::$availableCourseUserPlannerPivotColumns;
        }

        $plannerColumns = [
            'planner_exam_date',
            'planner_target_score',
            'planner_daily_study_minutes',
            'planner_focus',
            'planner_preferred_source',
            'planner_repair_mode',
            'planner_mistake_mastery_mode',
        ];

        self::$availableCourseUserPlannerPivotColumns = array_values(array_filter(
            $plannerColumns,
            static fn (string $column): bool => Schema::hasColumn('course_user', $column)
        ));

        return self::$availableCourseUserPlannerPivotColumns;
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function accessRequests()
    {
        return $this->hasMany(CourseAccessRequest::class);
    }

    public function scopeFree($query)
    {
        return $query->where('access_type', self::ACCESS_TYPE_FREE);
    }

    public function scopeSurveyUnlockable($query)
    {
        return $query->whereIn('access_type', self::SURVEY_UNLOCK_ACCESS_TYPES);
    }

    public function scopePurchasable($query)
    {
        return $query->whereIn('access_type', self::PURCHASABLE_ACCESS_TYPES);
    }

    // New access type methods
    public function isFreeAccess(): bool
    {
        return $this->normalizedAccessType() === self::ACCESS_TYPE_FREE;
    }

    public function isPreviewAccess(): bool
    {
        return $this->normalizedAccessType() === self::ACCESS_TYPE_PREVIEW;
    }

    public function isPaidAccess(): bool
    {
        return $this->normalizedAccessType() === self::ACCESS_TYPE_PAID;
    }

    public function isSurveyUnlockable(): bool
    {
        return in_array($this->normalizedAccessType(), self::SURVEY_UNLOCK_ACCESS_TYPES, true);
    }

    public function defaultEnrollmentAccessLevel(): string
    {
        return $this->isPreviewAccess()
            ? User::COURSE_ACCESS_PREVIEW
            : User::COURSE_ACCESS_FULL;
    }

    public function hasPurchasePrice(): bool
    {
        return $this->price !== null && (float) $this->price > 0;
    }

    public function canBePurchased(): bool
    {
        return in_array($this->normalizedAccessType(), self::PURCHASABLE_ACCESS_TYPES, true)
            && $this->hasPurchasePrice();
    }

    public function normalizedAccessType(): string
    {
        return static::normalizeAccessType($this->access_type);
    }

    public static function normalizeAccessType(?string $accessType): string
    {
        return in_array($accessType, self::ACCESS_TYPES, true)
            ? $accessType
            : self::ACCESS_TYPE_PAID;
    }

    public static function normalizePriceForAccessType(?string $accessType, mixed $price): ?float
    {
        $normalizedAccessType = static::normalizeAccessType($accessType);

        if ($normalizedAccessType === self::ACCESS_TYPE_FREE) {
            return 0.0;
        }

        if ($price === '' || $price === null) {
            return $normalizedAccessType === self::ACCESS_TYPE_PAID ? null : 0.0;
        }

        return round((float) $price, 2);
    }

    public static function accessPolicyValidationMessage(?string $accessType, mixed $price): ?string
    {
        $normalizedAccessType = static::normalizeAccessType($accessType);
        $normalizedPrice = static::normalizePriceForAccessType($normalizedAccessType, $price);

        if ($normalizedAccessType === self::ACCESS_TYPE_PAID
            && ($normalizedPrice === null || $normalizedPrice <= 0)
        ) {
            return 'Paid courses must have a price greater than 0.';
        }

        return null;
    }

    public function canUserAccessTopic(User $user, Topic $topic): bool
    {
        // Use the standardized User model method for access checking
        $accessType = $user->getCourseAccessLevel($this);
        
        // Full access = all topics
        if ($accessType === User::COURSE_ACCESS_FULL) {
            return true;
        }
        
        // Preview access = limited topics
        if ($accessType === User::COURSE_ACCESS_PREVIEW) {
            return in_array((int) $topic->id, $user->getPreviewAccessibleTopicIdsForCourse($this), true);
        }
        
        // Locked = no access
        return false;
    }

    public function getPreviewTopicLimit(): int
    {
        if (Schema::hasColumn('topics', 'is_preview_enabled')) {
            $explicitPreviewCount = Topic::query()
                ->where('course_id', $this->id)
                ->where('is_preview_enabled', true)
                ->count();

            if ($explicitPreviewCount > 0) {
                return (int) $explicitPreviewCount;
            }
        }

        return (int) config('free_course_survey.preview_topics_count', 5);
    }

    /** -------------------
     * ENROLLED STUDENTS AND COUNT
     * ------------------- */

    // Get enrolled students with optional limit
    public function getEnrolledStudents($limit = null)
    {
        return Cache::remember("course_{$this->id}_enrolled_students" . ($limit ? "_limit_{$limit}" : ""), self::CACHE_TTL, function () use ($limit) {
            $query = $this->enrolledUsers();
            
            if ($limit) {
                $query->limit($limit);
            }

            return $query->get();
        });
    }

    // FIXED: Get enrolled students count
    // N+1 query vulnerability eliminated by using single optimized query
    public function getEnrolledStudentsCountAttribute()
    {
        return Cache::remember("course_{$this->id}_enrolled_count", self::CACHE_TTL, function () {
            // FIXED: Use direct DB query to avoid N+1 and improve performance
            return DB::table('course_user')
                ->where('course_id', $this->id)
                ->where('is_enrolled', true)
                ->count();
        });
    }

    /** -------------------
     * COMPLETION AND MASTERED TOPICS
     * ------------------- */

    // FIXED: Get completion percentage for the current user
    // N+1 query vulnerability removed by using eager loading and optimized queries
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
        $result = DB::table('topics')
            ->leftJoin('user_topic_progress', function ($join) use ($userId) {
                $join->on('topics.id', '=', 'user_topic_progress.topic_id')
                     ->where('user_topic_progress.user_id', $userId)
                     ->where('user_topic_progress.percentage_grade', '>=', 90);
            })
            ->where('topics.course_id', $this->id)
            ->selectRaw('COUNT(*) as total, COUNT(user_topic_progress.id) as completed')
            ->first();

        if (!$result || $result->total === 0) {
            return 0;
        }

        return round(($result->completed / $result->total) * 100, 0);
    }

    // FIXED: Check if a user is enrolled in this course
    // N+1 query vulnerability eliminated by using direct DB query
    public function isUserEnrolled($userId = null)
    {
        $userId = $userId ?? auth()->id();
        if (!$userId) return false;

        return Cache::remember("course_{$this->id}_user_{$userId}_enrolled", self::CACHE_TTL, function () use ($userId) {
            // FIXED: Use direct DB query to avoid N+1 and improve performance
            return DB::table('course_user')
                ->where('course_id', $this->id)
                ->where('user_id', $userId)
                ->where('is_enrolled', true)
                ->exists();
        });
    }

    // FIXED: Get mastered topics count for the current user
    // N+1 query vulnerability eliminated by using direct DB query
    public function getMasteredTopicsCountAttribute()
    {
        if (!auth()->check()) {
            return 0;
        }

        $userId = auth()->id();
        return Cache::remember("course_{$this->id}_user_{$userId}_mastered", self::CACHE_TTL, function () use ($userId) {
            // FIXED: Use direct DB query to avoid N+1 and improve performance
            return DB::table('user_topic_progress')
                ->join('topics', 'user_topic_progress.topic_id', '=', 'topics.id')
                ->where('user_topic_progress.user_id', $userId)
                ->where('topics.course_id', $this->id)
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
        if (!$this->canBePurchased()) {
            if ($this->isPreviewAccess()) {
                return 'Preview';
            }

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

        static::created(function ($course) {
            // BULLETPROOF: Invalidate home page cache when new courses are created
            try {
                $cacheService = app(\App\Services\CacheInvalidationService::class);
                $cacheService->invalidateHomePageCache();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to invalidate home page cache on course creation', [
                    'course_id' => $course->id,
                    'error' => $e->getMessage()
                ]);
            }
        });

        static::saving(function ($course) {
            $accessType = $course->access_type;

            if (!in_array($accessType, self::ACCESS_TYPES, true)) {
                $originalAccessType = $course->getOriginal('access_type');

                $accessType = in_array($originalAccessType, self::ACCESS_TYPES, true)
                    ? $originalAccessType
                    : (((float) ($course->price ?? $course->getOriginal('price') ?? 0)) > 0
                        ? self::ACCESS_TYPE_PAID
                        : self::ACCESS_TYPE_FREE);
            }

            $course->access_type = static::normalizeAccessType($accessType);
            $course->price = static::normalizePriceForAccessType($course->access_type, $course->price);

            $validationMessage = static::accessPolicyValidationMessage($course->access_type, $course->price);
            if ($validationMessage !== null) {
                throw ValidationException::withMessages([
                    'price' => $validationMessage,
                ]);
            }
        });

        static::updating(function ($course) {
            $course->accessTransitionSnapshot = [
                'price' => $course->getOriginal('price'),
                'access_type' => $course->getOriginal('access_type'),
            ];
        });

        static::updated(function ($course) {
            $transitionSnapshot = $course->accessTransitionSnapshot ?? [
                'price' => $course->getOriginal('price'),
                'access_type' => $course->getOriginal('access_type'),
            ];

            $priceChanged = $course->wasChanged('price');
            $accessTypeChanged = $course->wasChanged('access_type');

            $originalAccessType = static::normalizeAccessType($transitionSnapshot['access_type'] ?? null);
            $newAccessType = static::normalizeAccessType($course->access_type);

            if ($priceChanged || $accessTypeChanged) {
                $originalPrice = $transitionSnapshot['price'] ?? null;
                $newPrice = $course->price;


                // Access policy moved into paid mode: remove legacy survey-based student access.
                if ($accessTypeChanged && $originalAccessType !== self::ACCESS_TYPE_PAID && $newAccessType === self::ACCESS_TYPE_PAID) {
                    try {

                        // Use transaction for atomicity
                        DB::transaction(function () use ($course) {
                            // Get admin user IDs to preserve their enrollments
                            $adminUsers = \App\Models\User::where('role', \App\Models\User::ROLE_ADMIN)->pluck('id')->toArray();

                            // Get count before revocation for logging (excluding admins)
                            $enrollmentCount = DB::table('course_user')
                                ->where('course_id', $course->id)
                                ->where('is_enrolled', true)
                                ->whereNotIn('user_id', $adminUsers)
                                ->count();

                            // Remove all enrollments for this course EXCEPT for ADMINS
                            DB::table('course_user')
                                ->where('course_id', $course->id)
                                ->where('is_enrolled', true)
                                ->whereNotIn('user_id', $adminUsers) // 🔧 FIX: Preserve admin enrollments
                                ->update(['is_enrolled' => false, 'updated_at' => now()]);

                        });

                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('❌ Failed to revoke enrollments for paid-access course', [
                            'course_id' => $course->id,
                            'course_name' => $course->name,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        throw $e; // Re-throw to prevent the update if revocation fails
                    }
                }

                // Paid policy removed: existing explicit enrollments remain valid.
                elseif ($accessTypeChanged && $originalAccessType === self::ACCESS_TYPE_PAID && $newAccessType !== self::ACCESS_TYPE_PAID) {
                }

                elseif ($priceChanged && $newAccessType === self::ACCESS_TYPE_PAID) {
                }
            }

            $course->accessTransitionSnapshot = null;

            // Clear caches AFTER price change logic to prevent interference
            $course->clearCaches();

            // BULLETPROOF: Invalidate home page cache when courses change
            try {
                $cacheService = app(\App\Services\CacheInvalidationService::class);
                $cacheService->invalidateHomePageCache();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to invalidate home page cache on course update', [
                    'course_id' => $course->id,
                    'error' => $e->getMessage()
                ]);
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

            // BULLETPROOF: Invalidate home page cache when courses are deleted
            try {
                $cacheService = app(\App\Services\CacheInvalidationService::class);
                $cacheService->invalidateHomePageCache();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to invalidate home page cache on course deletion', [
                    'course_id' => $course->id,
                    'error' => $e->getMessage()
                ]);
            }

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

                        // BULLETPROOF: Invalidate home page cache when topics change
                        try {
                            $cacheService = app(\App\Services\CacheInvalidationService::class);
                            $cacheService->invalidateHomePageCache();
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Failed to invalidate home page cache on topic update', [
                                'topic_id' => $topic->id,
                                'course_id' => $topic->course_id,
                                'error' => $e->getMessage()
                            ]);
                        }

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
                }
            });

            // BULLETPROOF: Also handle topic creation and deletion
            Topic::created(function ($topic) {
                try {
                    if ($course = Course::find($topic->course_id)) {
                        $cacheService = app(\App\Services\CacheInvalidationService::class);
                        $cacheService->invalidateHomePageCache();
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to invalidate home page cache on topic creation', [
                        'topic_id' => $topic->id,
                        'error' => $e->getMessage()
                    ]);
                }
            });

            Topic::deleted(function ($topic) {
                try {
                    $cacheService = app(\App\Services\CacheInvalidationService::class);
                    $cacheService->invalidateHomePageCache();
                } catch (\Exception $e) {
                    \Log::error('Failed to invalidate home page cache on topic deletion', [
                        'topic_id' => $topic->id,
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
    }

    // 💀 N+1 WARNING: This accessor accesses 'image' - consider eager loading
    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }

        // If it's already a full URL, return as is
        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }

        // If it's a local file path, construct the route-backed URL.
        return url('/uploads/' . $this->image);
    }

    // Alias for backward compatibility - view expects image_url
    public function getImageUrl()
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
