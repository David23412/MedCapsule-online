<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected array $profileImageUrlCache = [];

    protected static array $profileImageUrlForCache = [];

    protected static ?array $availableCourseUserPlannerPivotColumns = null;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'number',
        'university',
        'password',
        'role',
        'weekly_goal',
        'exam_date',
        'daily_study_minutes',
        'planner_focus',
        'repair_mode',
        'mistake_mastery_mode',
        'last_active_at',
        'profile_picture_url',
        'correct_answers_count',
        'total_questions_attempted',
        'xp',
        'study_streak_days',
        'study_time_seconds',
        'active_session_token',
        'session_created_at',
        'device_fingerprint',
        'last_login_ip',
        'last_user_agent',
        'last_security_check',
        'concurrent_login_attempts',
        'last_suspicious_activity',
        'free_courses_unlocked',
        'survey_completed_at',
        'free_courses_unlocked_at',
        'has_sr_access',
        'show_on_leaderboard',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'active_session_token',
        'device_fingerprint',
        'last_login_ip',
        'last_user_agent',
        'last_security_check',
        'concurrent_login_attempts',
        'last_suspicious_activity',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'number' => 'string',
        'exam_date' => 'date',
        'last_active_at' => 'datetime',
        'session_created_at' => 'datetime',
        'last_security_check' => 'datetime',
        'last_suspicious_activity' => 'datetime',
        'password' => 'hashed',
        'role' => 'integer',
        'weekly_goal' => 'integer',
        'daily_study_minutes' => 'integer',
        'profile_picture_url' => 'string',
        'repair_mode' => 'string',
        'mistake_mastery_mode' => 'string',
        'correct_answers_count' => 'integer',
        'total_questions_attempted' => 'integer',
        'concurrent_login_attempts' => 'integer',
        'survey_completed' => 'boolean',
        'free_courses_unlocked' => 'boolean',
        'planner_focus' => 'string',
        'survey_completed_at' => 'datetime',
        'free_courses_unlocked_at' => 'datetime',
        'has_sr_access' => 'boolean',
        'show_on_leaderboard' => 'boolean',
    ];

    /**
     * User roles
     */
    // Integer role enums (SSOT)
    const ROLE_ADMIN = 1;
    const ROLE_TUTOR = 2;
    const ROLE_STUDENT = 3;

    const COURSE_ACCESS_PREVIEW = 'preview';
    const COURSE_ACCESS_FULL = 'full';

    /**
     * Per-request memo cache for course access levels to avoid repeated DB queries.
     */
    protected array $courseAccessLevelCache = [];

    /**
     * Define the relationship with courses (many-to-many).
     * Only returns enrolled courses (enrolled_at is not null).
     */
    public function courses()
    {
        // Simple boolean check for enrollment
        return $this->belongsToMany(Course::class, 'course_user')
                    ->withPivot($this->courseUserPivotColumns(['is_enrolled', 'access_level']))
                    ->where('course_user.is_enrolled', true)
                    ->withTimestamps();
    }

    /**
     * Define the relationship with all course enrollments regardless of enrollment status.
     * Useful for admin interfaces.
     */
    public function allCourseEnrollments()
    {
        return $this->belongsToMany(Course::class, 'course_user')
                    ->withPivot($this->courseUserPivotColumns(['enrolled_at', 'access_level', 'is_enrolled']))
                    ->withTimestamps();
    }

    /**
     * Avoid selecting planner pivot columns on databases that have not run the
     * newer course_user schema yet.
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

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return (int)($this->role ?? 0) === self::ROLE_ADMIN;
    }

    /**
     * Handle role changes and automatic free course enrollment
     */
    protected static function booted()
    {
        static::creating(function ($user) {
            if (blank($user->number)) {
                $user->number = static::generateFallbackNumber();
            }
        });

        static::updating(function ($user) {
            // Check if role is being changed from admin to non-admin
            if ($user->isDirty('role') &&
                (int)$user->getOriginal('role') === self::ROLE_ADMIN &&
                (int)$user->role !== self::ROLE_ADMIN) {

                // Revoke all course enrollments for former admin
                $user->courses()->detach();

            }
        });

    }

    private static function generateFallbackNumber(): string
    {
        do {
            $number = 'U'.str_pad((string) random_int(1, 999999999999), 12, '0', STR_PAD_LEFT);
        } while (static::query()->where('number', $number)->exists());

        return $number;
    }

    /**
     * Get the user's payments.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the user's activity logs.
     */
    public function activityLogs()
    {
        return $this->hasMany(UserActivityLog::class);
    }

    /**
     * Get the user's quiz attempts.
     */
    public function quizAttempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * Get the user's most recent quiz attempt.
     */
    public function latestQuizAttempt()
    {
        return $this->hasOne(QuizAttempt::class)->latestOfMany();
    }

    /**
     * Get the user's mistakes.
     */
    public function mistakes()
    {
        return $this->hasMany(Mistake::class);
    }

    /**
     * Get the user's assignment progress through quiz attempts.
     */
    public function assignmentProgress()
    {
        return $this->quizAttempts()->whereNotNull('assignment_id');
    }

    /**
     * 🚀 GENIUS: Get assignments assigned to this user via pivot table
     */
    public function assignments()
    {
        return $this->belongsToMany(Assignment::class, 'assignment_user', 'user_id', 'assignment_id')
                   ->withTimestamps();
    }

    /**
     * Get the user's topic progress.
     */
    public function userTopicProgress()
    {
        return $this->hasMany(UserTopicProgress::class);
    }

    public function surveyResponses()
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function surveyDrafts()
    {
        return $this->hasMany(SurveyDraft::class);
    }

    /**
     * Get the user's unread notifications.
     */
    public function unreadNotifications()
    {
        return $this->notifications()->where('read', false);
    }



    /**
        // Check authorization for role assignment
        if (!auth()->user()->can('assign-roles')) {
            abort(403, 'Unauthorized to assign roles');
        }



    /**
     * Check if user has access to a single course.
     * Returns boolean indicating access status.
     * Uses simple boolean checks for clean, fast operations.
     *
     * @param int $courseId
     * @return bool
     */
    public function hasAccessToCourse($courseId): bool
    {
        // Admins always have access to all courses
        if ($this->isAdmin()) {
            return true;
        }

        // Get access status using the existing bulk method
        $accessMap = $this->hasAccessToCourses([$courseId]);
        return $accessMap[$courseId] ?? false;
    }

    /**
     * Bulk check if user has access to multiple courses.
     * Returns an array mapping course IDs to access status.
     * Uses simple boolean checks for clean, fast operations.
     *
     * @param array $courseIds
     * @return array
     */
    public function hasAccessToCourses(array $courseIds): array
    {
        // Admins always have access to all courses
        if ($this->isAdmin()) {
            return array_fill_keys($courseIds, true);
        }

        // Early return for empty array
        if (empty($courseIds)) {
            return [];
        }

        return collect($this->getCourseAccessLevels($courseIds))
            ->map(fn ($accessLevel) => $accessLevel !== null)
            ->all();
    }

    public function getCourseAccessLevel(Course|int $course): ?string
    {
        $courseId = $course instanceof Course ? $course->id : (int) $course;
        $accessMap = $this->getCourseAccessLevels([$courseId]);

        return $accessMap[$courseId] ?? null;
    }

    public function getCourseAccessLevels(array $courseIds): array
    {
        if (empty($courseIds)) {
            return [];
        }

        // 🔥 FIX: Admins and Tutors get FULL access to ALL courses (even without enrollment)
        if ($this->isAdmin() || $this->isTutor()) {
            return array_fill_keys($courseIds, self::COURSE_ACCESS_FULL);
        }

        $normalizedCourseIds = collect($courseIds)
            ->map(fn ($courseId) => (int) $courseId)
            ->unique()
            ->values()
            ->all();

        // Per-request memoization: only query IDs we haven't resolved yet
        $uncachedIds = array_diff($normalizedCourseIds, array_keys($this->courseAccessLevelCache));

        if (!empty($uncachedIds)) {
            $this->resolveAndCacheCourseAccess($uncachedIds);
        }

        // Build result from cache
        $accessMap = [];
        foreach ($normalizedCourseIds as $id) {
            $accessMap[$id] = $this->courseAccessLevelCache[$id] ?? null;
        }

        return $accessMap;
    }

    /**
     * Resolve course access for uncached IDs and store in the per-request memo cache.
     */
    private function resolveAndCacheCourseAccess(array $courseIds): void
    {
        $accessMap = array_fill_keys($courseIds, null);
        $hasFreeSurveyUnlock = $this->hasUnlockedFreeCourses();

        $rows = DB::table('courses')
            ->leftJoin('course_user', function ($join) {
                $join->on('courses.id', '=', 'course_user.course_id')
                    ->where('course_user.user_id', '=', $this->id);
            })
            ->whereIn('courses.id', $courseIds)
            ->select(
                'courses.id',
                'courses.access_type',
                'course_user.is_enrolled',
                'course_user.access_level'
            )
            ->get();

        $completedPaymentCourseIds = DB::table('payments')
            ->where('user_id', $this->id)
            ->whereIn('course_id', $courseIds)
            ->where('status', 'completed')
            ->pluck('course_id')
            ->map(static fn ($courseId) => (int) $courseId)
            ->flip()
            ->all();

        foreach ($rows as $row) {
            $courseId = (int) $row->id;
            $accessType = Course::normalizeAccessType($row->access_type ?? null);
            $isEnrolled = (bool) ($row->is_enrolled ?? false);
            $storedLevel = is_string($row->access_level) ? trim($row->access_level) : null;
            $hasCompletedPayment = isset($completedPaymentCourseIds[$courseId]);

            if (!$isEnrolled) {
                $accessMap[$courseId] = null;
                continue;
            }

            // Survey flags are authoritative for free courses and preview-only rows.
            if ($accessType === Course::ACCESS_TYPE_FREE && !$hasFreeSurveyUnlock) {
                $accessMap[$courseId] = null;
                continue;
            }

            if (
                $accessType === Course::ACCESS_TYPE_PREVIEW
                && !$hasFreeSurveyUnlock
                && $storedLevel !== self::COURSE_ACCESS_FULL
                && !$hasCompletedPayment
            ) {
                $accessMap[$courseId] = null;
                continue;
            }

            if (in_array($storedLevel, [self::COURSE_ACCESS_PREVIEW, self::COURSE_ACCESS_FULL], true)) {
                $accessMap[$courseId] = $storedLevel;
                continue;
            }

            switch ($accessType) {
                case Course::ACCESS_TYPE_FREE:
                    $accessMap[$courseId] = self::COURSE_ACCESS_FULL;
                    break;

                case Course::ACCESS_TYPE_PREVIEW:
                    $accessMap[$courseId] = $hasCompletedPayment
                        ? self::COURSE_ACCESS_FULL
                        : ($hasFreeSurveyUnlock ? self::COURSE_ACCESS_PREVIEW : null);
                    break;

                default:
                    $accessMap[$courseId] = $hasCompletedPayment
                        ? self::COURSE_ACCESS_FULL
                        : null;
                    break;
            }
        }

        // Store all resolved results in memo cache
        foreach ($accessMap as $id => $level) {
            $this->courseAccessLevelCache[$id] = $level;
        }
    }

    public function hasFullAccessToCourse(Course|int $course): bool
    {
        if ($this->isAdmin() || $this->isTutor()) {
            return true;
        }

        return $this->getCourseAccessLevel($course) === self::COURSE_ACCESS_FULL;
    }

    public function getPreviewTopicLimitForCourse(Course|int $course): ?int
    {
        return $this->getCourseAccessLevel($course) === self::COURSE_ACCESS_PREVIEW
            ? (int) config('free_course_survey.preview_topics_count', 5)
            : null;
    }

    public function isTopicPreviewLocked(Topic|int $topic): bool
    {
        $topicModel = $topic instanceof Topic
            ? $topic
            : Topic::query()->select('id', 'course_id', 'display_order')->find((int) $topic);

        if (!$topicModel) {
            return false;
        }

        $previewLimit = $this->getPreviewTopicLimitForCourse($topicModel->course_id);

        return $previewLimit !== null
            && (int) ($topicModel->display_order ?? 0) > $previewLimit;
    }

    /**
     * Bulk check if user has pending payments for multiple courses.
     * Returns an array mapping course IDs to pending payment status.
     * 
     * @param array $courseIds
     * @return array
     */
    public function hasPendingPaymentsForCourses(array $courseIds): array
    {
        // Early return for empty array
        if (empty($courseIds)) {
            return [];
        }
        
        // Get courses with pending payments in a single query
        $pendingPayments = DB::table('payments')
            ->where('user_id', $this->id)
            ->whereIn('course_id', $courseIds)
            ->where('status', 'pending')
            ->pluck('course_id')
            ->toArray();
        
        // Create result array with all courses set to false initially
        $result = array_fill_keys($courseIds, false);
        
        // Set courses with pending payments to true
        foreach ($pendingPayments as $courseId) {
            $result[$courseId] = true;
        }
        
        return $result;
    }

    /**
     * FIXED: Simple boolean check if user is enrolled in a course.
     * Uses the new is_enrolled boolean field for clean, fast lookups.
     * SQL injection vulnerability removed by using parameterized queries.
     */
    public function isEnrolledInCourse($courseId)
    {
        // Admins always have access
        if ($this->isAdmin()) {
            return true;
        }

        // FIXED: Use parameterized query to prevent SQL injection
        return DB::table('course_user')
            ->where('user_id', $this->id)
            ->where('course_id', (int) $courseId) // Cast to int for safety
            ->where('is_enrolled', true)
            ->exists();
    }

    public function hasCompletedFreeCourseSurvey(): bool
    {
        return $this->isAdmin() || (bool) $this->survey_completed;
    }

    public function hasUnlockedFreeCourses(): bool
    {
        return $this->isAdmin()
            || ((bool) $this->survey_completed && (bool) $this->free_courses_unlocked);
    }

    public function requiresSurveyUnlockForCourse(Course|int $course): bool
    {
        if ($this->isAdmin() || $this->hasUnlockedFreeCourses()) {
            return false;
        }

        $courseModel = $course instanceof Course
            ? $course
            : Course::query()->select('id', 'access_type')->find($course);

        if (!$courseModel) {
            return false;
        }

        return $courseModel->isSurveyUnlockable();
    }

    public function freeCourseSurveyRedirectFor(Course|int $course, ?string $fallback = null): string
    {
        $courseId = $course instanceof Course ? $course->id : (int) $course;
        $redirectTo = $fallback ?? route('topics.forCourse', $courseId, false);
        return route('courses.index', [
            'survey' => 1,
            'redirect_to' => $redirectTo,
        ]);
    }

    public function courseAccessStateSignature(): string
    {
        return implode(':', [
            'v2',
            (int) ($this->role ?? 0),
            (int) ((bool) $this->survey_completed),
            (int) ((bool) $this->free_courses_unlocked),
            $this->survey_completed_at?->getTimestamp() ?? 0,
            $this->free_courses_unlocked_at?->getTimestamp() ?? 0,
        ]);
    }

    /**
     * FIXED: Enroll a user in a course.
     * SQL injection vulnerability removed by using parameterized queries and type casting.
     */
    public function enrollInCourse($courseId, string $accessLevel = self::COURSE_ACCESS_FULL)
    {
        $courseId = (int) $courseId; // Cast to int for security
        $accessLevel = in_array($accessLevel, [self::COURSE_ACCESS_PREVIEW, self::COURSE_ACCESS_FULL], true)
            ? $accessLevel
            : self::COURSE_ACCESS_FULL;

        // First, check if the relationship exists
        $existing = DB::table('course_user')
            ->where('user_id', $this->id)
            ->where('course_id', $courseId)
            ->first();

        if ($existing) {
            // Update existing record
            DB::table('course_user')
                ->where('user_id', $this->id)
                ->where('course_id', $courseId)
                ->update([
                    'is_enrolled' => true,
                    'access_level' => $accessLevel,
                    'enrolled_at' => now(),
                    'updated_at' => now(),
                ]);
        } else {
            // Create new record
            DB::table('course_user')->insert([
                'user_id' => $this->id,
                'course_id' => $courseId,
                'is_enrolled' => true,
                'access_level' => $accessLevel,
                'enrolled_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    /**
     * Enroll the user in survey-unlockable courses using the current access policy.
     */
    public function enrollInAllFreeCourses()
    {
        try {
            $now = now();

            $freeCourses = \App\Models\Course::query()
                ->surveyUnlockable()
                ->get(['id', 'access_type']);

            $trulyFreeCourseIds = $freeCourses->pluck('id')->all();

            if (empty($trulyFreeCourseIds)) {
                return 0; // No truly free courses
            }

            $existingEnrollments = DB::table('course_user')
                ->where('user_id', $this->id)
                ->whereIn('course_id', $trulyFreeCourseIds)
                ->get(['course_id', 'is_enrolled'])
                ->keyBy('course_id');

            $missingCourseIds = array_values(array_diff($trulyFreeCourseIds, $existingEnrollments->keys()->all()));
            $reactivateCourseIds = $existingEnrollments
                ->filter(fn ($enrollment) => !(bool) $enrollment->is_enrolled)
                ->keys()
                ->all();

            $enrolledCount = 0;

            if (!empty($reactivateCourseIds)) {
                foreach ($reactivateCourseIds as $cid) {
                    $course = $freeCourses->firstWhere('id', $cid);
                    $level = $course && Course::normalizeAccessType($course->access_type) === Course::ACCESS_TYPE_PREVIEW
                        ? self::COURSE_ACCESS_PREVIEW
                        : self::COURSE_ACCESS_FULL;

                    DB::table('course_user')
                        ->where('user_id', $this->id)
                        ->where('course_id', $cid)
                        ->update([
                            'is_enrolled' => true,
                            'access_level' => $level,
                            'enrolled_at' => $now,
                            'updated_at' => $now,
                        ]);
                }
                $enrolledCount += count($reactivateCourseIds);
            }

            if (!empty($missingCourseIds)) {
                $inserts = collect($missingCourseIds)->map(function ($cid) use ($freeCourses, $now) {
                    $course = $freeCourses->firstWhere('id', $cid);
                    $level = $course && Course::normalizeAccessType($course->access_type) === Course::ACCESS_TYPE_PREVIEW
                        ? self::COURSE_ACCESS_PREVIEW
                        : self::COURSE_ACCESS_FULL;
                    return [
                        'user_id' => $this->id,
                        'course_id' => $cid,
                        'is_enrolled' => true,
                        'access_level' => $level,
                        'enrolled_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })->all();

                DB::table('course_user')->insert($inserts);
                $enrolledCount += count($missingCourseIds);
            }

            if ($enrolledCount > 0) {
            }

            return $enrolledCount;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('❌ CRITICAL SECURITY FAILURE: Auto-enrollment exception', [
                'user_id' => $this->id,
                'user_name' => $this->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 0; // Return 0 to indicate failure rather than throwing exception
        }
    }

    /**
     * Unenroll a user from a course.
     */
    public function unenrollFromCourse($courseId)
    {
        // Simple boolean update - much cleaner
        DB::table('course_user')
            ->where('user_id', $this->id)
            ->where('course_id', $courseId)
            ->update(['is_enrolled' => false, 'updated_at' => now()]);
    }

    /**
     * Get the enrollment status and completion progress for a given course.
     * Uses simple boolean check for clean, fast operations.
     */
    public function getCourseProgressAttribute()
    {
        // Simple boolean check - much cleaner and faster
        // Note: completion_percentage column doesn't exist yet, so returning empty array
        // This should be implemented when the completion tracking system is added
        $courseProgress = DB::table('course_user')
            ->where('user_id', $this->id)
            ->where('is_enrolled', true)
            ->pluck('course_id')  // Just get enrolled course IDs for now
            ->mapWithKeys(function ($courseId) {
                return [$courseId => 0];  // Default completion percentage of 0%
            })
            ->toArray();

        return $courseProgress;
    }

    public function setCourseProgressAttribute($value)
    {
        // FIXED: Removed authorization check from setter method
        // Authorization should be handled in controllers/services, not model setters
        // This eliminates the privilege escalation vulnerability

        // This is a virtual attribute, so we don't need to store it
        return;
    }



    /**
     * Check if user is a tutor
     *
     * @return bool
     */
    public function isTutor(): bool
    {
        return (int)($this->role ?? 0) === self::ROLE_TUTOR;
    }

    /**
     * Check if user is a student
     *
     * @return bool
     */
    public function isStudent(): bool
    {
        return (int)($this->role ?? 0) === self::ROLE_STUDENT;
    }

    /**
     * Get students assigned to this tutor
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function assignedStudents()
    {
        return $this->belongsToMany(User::class, 'tutor_student_relationships', 'tutor_id', 'user_id')
                    ->where('users.role', self::ROLE_STUDENT)
                    ->withPivot('assigned_at')
                    ->withTimestamps();
    }

    /**
     * Get tutors assigned to this student
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function assignedTutors()
    {
        return $this->belongsToMany(User::class, 'tutor_student_relationships', 'user_id', 'tutor_id')
                    ->where('users.role', self::ROLE_TUTOR)
                    ->withPivot('assigned_at')
                    ->withTimestamps();
    }

    /**
     * Get courses assigned to this tutor (for tutors only)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tutorCourses()
    {
        return $this->belongsToMany(Course::class, 'tutor_course_assignments', 'tutor_id', 'course_id')
                    ->withPivot('assigned_at')
                    ->withTimestamps();
    }

    /**
     * Check if user has access to Spaced Repetition features.
     * Derived from tier/subscription (mocked for now).
     */
    public function getHasSrAccessAttribute(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return (bool) ($this->attributes['has_sr_access'] ?? false);
    }
    // 💀 N+1 WARNING: This accessor accesses 'study_time_seconds' - consider eager loading

    public function getFormattedStudyTimeAttribute(): string
    {
        $totalMinutes = ceil($this->study_time_seconds / 60);
        return sprintf("%dm", $totalMinutes);
    }

    /**
     * Calculate the user's current XP based on activity
     * 
     * @return int
     */
    public function calculateXP(): int
    {
        // Formula: (correct_answers × 5) + (accuracy_bonus × 2) + (streak_bonus × 10)
        $correctAnswers = $this->correct_answers_count ?? 0;
        $totalQuestions = $this->total_questions_attempted ?? 0;
        
        // Calculate accuracy bonus (0-100% accuracy converted to 0-2 points)
        $accuracyBonus = ($totalQuestions > 0) 
            ? floor(($correctAnswers * 100.0 / $totalQuestions) * 0.02) 
            : 0;
            
        // Calculate streak bonus (each day is worth 10 points)
        $streakBonus = ($this->study_streak_days ?? 0) * 10;
        
        // Final XP calculation with reduced values
        return ($correctAnswers * 5) + 
               floor(($correctAnswers * 100.0 / ($totalQuestions ?: 1)) * 2) + 
               ($this->study_streak_days ?? 0) * 10;
    }

    /**
     * Calculate XP earned for a specific quiz
     * 
     * @param int $correctAnswers Number of correct answers in this quiz
     * @param int $totalQuestions Total questions in this quiz
     * @return int XP earned for this quiz
     */
    public function earnXP(int $correctAnswers, int $totalQuestions): int
    {
        // Base XP from correct answers (5 points per correct answer)
        $baseXP = $correctAnswers * 5;
        
        // Accuracy bonus for this quiz (0-100% accuracy converted to 0-2 points per question)
        $accuracyBonus = ($totalQuestions > 0) 
            ? floor(($correctAnswers * 100.0 / $totalQuestions) * 0.02 * $totalQuestions)
            : 0;
        
        // Return the XP earned just for this quiz
        return $baseXP + $accuracyBonus;
    }
    
    /**
     * Get the user's current level based on XP
     * 
     * @return int
     */
    public function getCurrentLevel(): int
    {
        $xp = $this->xp ?? 0;
        // Every 1000 XP is a new level, starting at level 1
        return floor($xp / 1000) + 1;
    }
    
    /**
     * Calculate the XP needed for the next level
     * 
     * @return int
     */
    public function getXPForNextLevel(): int
    {
        $currentXP = $this->xp ?? 0;
        $nextLevelTotalXP = ceil($currentXP / 1000) * 1000;
        return $nextLevelTotalXP - $currentXP;
    }
    
    /**
     * Update the user's study streak based on their quiz history
     * 
     * @param string|null $lastQuizDate Optional date of the user's last quiz before today
     * @return int Updated streak value
     */
    public function updateStudyStreak($lastQuizDate = null)
    {
        // Store original streak for tracking reset
        $originalStreak = $this->study_streak_days ?? 1;
        
        // Get the user's latest quiz attempt
        $lastAttempt = \App\Models\QuizAttempt::where('user_id', $this->id)
            ->orderBy('created_at', 'desc')
            ->first();
            
        // If they have no attempts, set streak to 1 (default for platform users)
        if (!$lastAttempt) {
            $this->study_streak_days = 1;
            $this->save();
            return 1;
        }
        
        $lastAttemptDate = $lastAttempt->created_at->startOfDay();
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();
        
        // Handle different cases
        if ($lastAttemptDate->equalTo($today)) {
            // They did a quiz today - check for previous days in sequence
            $streak = $this->calculateConsecutiveDays();
            $this->study_streak_days = $streak;
            $this->save();
            
            // Create streak notification for new streaks or milestones
            $this->createStreakNotificationIfNeeded($streak, $originalStreak);
            
            return $streak;
        } else if ($lastAttemptDate->equalTo($yesterday)) {
            // Quiz taken yesterday - check for previous days
            $streak = $this->calculateConsecutiveDays();
            $this->study_streak_days = $streak;
            $this->save();
            
            // Create streak notification for new streaks or milestones
            $this->createStreakNotificationIfNeeded($streak, $originalStreak);
            
            return $streak;
        } else {
            // Last quiz attempt was more than 1 day ago - streak resets to 1
            $wasReset = $originalStreak > 1;
            $this->study_streak_days = 1;
            $this->save();
            
            // Send streak reset notification if applicable
            if ($wasReset) {
                try {
                    \App\Services\NotificationTriggerService::onStreakReset($this);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to create streak reset notification', [
                        'error' => $e->getMessage(),
                        'user_id' => $this->id,
                        'previous_streak' => $originalStreak
                    ]);
                }
            }
            
            return 1;
        }
    }
    
    /**
     * Calculate how many consecutive days a user has been active
     */
    private function calculateConsecutiveDays()
    {
        // Start with today - go backwards day by day and see if the user was active
        $streak = 0;
        $currentDate = now()->startOfDay();
        
        while (true) {
            // Check if the user had quiz activity on this day
            $hadActivity = \App\Models\QuizAttempt::where('user_id', $this->id)
                ->whereDate('created_at', $currentDate)
                ->exists();
                
            if (!$hadActivity) {
                break; // Break the streak if no activity on this day
            }
            
            $streak++;
            $currentDate->subDay(); // Go back one day
        }
        
        return max(1, $streak); // Minimum streak is 1 if they've ever done a quiz
    }
    
    /**
     * Create streak notification if needed for milestones
     */
    private function createStreakNotificationIfNeeded($currentStreak, $previousStreak)
    {
        // Only create notifications for new streaks or significant milestones
        $milestones = [1, 2, 3, 7, 10, 14, 15, 21, 30, 60, 90, 120, 150, 180, 365];
        
        if ($currentStreak > $previousStreak && in_array($currentStreak, $milestones)) {
            try {
                \App\Services\NotificationTriggerService::onStudyStreak($this, $currentStreak);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to create streak notification', [
                    'error' => $e->getMessage(),
                    'user_id' => $this->id,
                    'streak' => $currentStreak
                ]);
            }
        }
        
        // SPECIAL CASE: First quiz ever (streak goes from 0 to 1)
        if ($previousStreak == 0 && $currentStreak == 1) {
            try {
                \App\Services\NotificationTriggerService::onStudyStreak($this, 1);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to create first quiz streak notification', [
                    'error' => $e->getMessage(),
                    'user_id' => $this->id
                ]);
            }
        }
    }

    /**
     * Get the user's profile image URL with consistent formatting
     * This ensures all views display the same image URL
     * 
     * @param bool $withCacheBust Whether to add cache busting parameter
     * @return string The profile image URL or fallback avatar
     */
    public function getProfileImageUrl($withCacheBust = true): string
    {
        $cacheKey = $withCacheBust ? 'with_bust' : 'without_bust';

        if (isset($this->profileImageUrlCache[$cacheKey])) {
            return $this->profileImageUrlCache[$cacheKey];
        }

        if (!$this->profile_picture_url) {
            return $this->profileImageUrlCache[$cacheKey] = 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&size=200&background=007bff&color=ffffff';
        }

        // Check if it's an external URL (starts with http/https)
        if (filter_var($this->profile_picture_url, FILTER_VALIDATE_URL)) {
            return $this->profileImageUrlCache[$cacheKey] = $this->profile_picture_url;
        }

        $cleanPath = self::normalizeProfileUploadPath($this->profile_picture_url);

        if (self::resolveProfileUploadPath($cleanPath) !== null) {
            return $this->profileImageUrlCache[$cacheKey] = self::buildProfileUploadUrl($cleanPath, $withCacheBust);
        }

        // Log missing file for debugging
        \Log::warning('Profile picture file not found', [
            'user_id' => $this->id,
            'stored_path' => $this->profile_picture_url,
            'expected_file_paths' => self::profileUploadCandidates($cleanPath),
            'clean_path' => $cleanPath
        ]);

        // Fallback to UI Avatars if file doesn't exist
        return $this->profileImageUrlCache[$cacheKey] = 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&size=200&background=007bff&color=ffffff';
    }

    /**
     * Get profile image URL for any user (static method for use in views)
     * 
     * @param User $user The user to get the profile image for
     * @param bool $withCacheBust Whether to add cache busting parameter
     * @return string The profile image URL or fallback avatar
     */
    public static function getProfileImageUrlFor($user, $withCacheBust = true): string
    {
        $userId = $user->id ?? spl_object_id((object) $user);
        $cacheKey = $userId . ':' . ($withCacheBust ? 'with_bust' : 'without_bust');

        if (isset(self::$profileImageUrlForCache[$cacheKey])) {
            return self::$profileImageUrlForCache[$cacheKey];
        }

        if (!$user || !$user->profile_picture_url) {
            return self::$profileImageUrlForCache[$cacheKey] = 'https://ui-avatars.com/api/?name=' . urlencode($user->name ?? 'User') . '&size=200&background=007bff&color=ffffff';
        }

        // Check if it's an external URL (starts with http/https)
        if (filter_var($user->profile_picture_url, FILTER_VALIDATE_URL)) {
            return self::$profileImageUrlForCache[$cacheKey] = $user->profile_picture_url;
        }

        $cleanPath = self::normalizeProfileUploadPath($user->profile_picture_url);

        if (self::resolveProfileUploadPath($cleanPath) !== null) {
            return self::$profileImageUrlForCache[$cacheKey] = self::buildProfileUploadUrl($cleanPath, $withCacheBust);
        }

        // Fallback to UI Avatars if file doesn't exist
        return self::$profileImageUrlForCache[$cacheKey] = 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&size=200&background=007bff&color=ffffff';
    }

    private static function normalizeProfileUploadPath(string $storedPath): string
    {
        return str_starts_with($storedPath, 'uploads/')
            ? substr($storedPath, strlen('uploads/'))
            : $storedPath;
    }

    /**
     * @return array<int, string>
     */
    private static function profileUploadCandidates(string $cleanPath): array
    {
        return [
            storage_path('app/private/uploads/' . $cleanPath),
            storage_path('app/uploads/' . $cleanPath),
        ];
    }

    private static function resolveProfileUploadPath(string $cleanPath): ?string
    {
        foreach (self::profileUploadCandidates($cleanPath) as $candidatePath) {
            if (file_exists($candidatePath)) {
                return $candidatePath;
            }
        }

        return null;
    }

    private static function buildProfileUploadUrl(string $cleanPath, bool $withCacheBust): string
    {
        $url = url('/uploads/' . $cleanPath);

        if ($withCacheBust) {
            $url .= '?v=' . time();
        }

        return $url;
    }

    /**
     * Calculate total correct answers from all quiz types (Khan Academy style)
     * This ensures the milestone bar counts ALL correct answers from:
     * - Regular quizzes
     * - Assignment quizzes  
     * - Tutor mode quizzes
     * - Random quizzes
     * 
     * @return int Total correct answers across all quiz types
     */
    public function getTotalCorrectAnswers(): int
    {
        // Use the cached correct_answers_count field which is updated by UserStatsService
        // This field is incremented for ALL quiz types in the GradingService
        return $this->correct_answers_count ?? 0;
    }

    /**
     * Get real-time correct answers count from database (for verification)
     * This method queries the quiz_attempts table directly to ensure accuracy
     * 
     * @return int Real-time correct answers count
     */
    public function getCorrectAnswersFromDatabase(): int
    {
        return $this->quizAttempts()
            ->selectRaw('SUM(correct_answers) as total_correct')
            ->value('total_correct') ?? 0;
    }

    /**
     * Verify and sync correct answers count if needed
     * This ensures the cached count matches the database reality
     *
     * @return bool True if sync was needed, false if already accurate
     */
    public function syncCorrectAnswersCount(): bool
    {
        $cachedCount = $this->correct_answers_count ?? 0;
        $actualCount = $this->getCorrectAnswersFromDatabase();

        if ($cachedCount !== $actualCount) {
            $this->update(['correct_answers_count' => $actualCount]);


            return true;
        }

        return false;
    }

    /**
     * Ensure the user only receives survey-based access for survey-unlockable courses.
     */
    public function ensureFreeCourseAccess(): int
    {
        try {
            $potentiallyFreeCourses = \App\Models\Course::query()
                ->surveyUnlockable()
                ->get(['id', 'access_type']);

            if ($potentiallyFreeCourses->isEmpty()) {
                return 0;
            }

            $trulyFreeCourseIds = [];
            foreach ($potentiallyFreeCourses as $course) {
                $currentAccessType = DB::table('courses')
                    ->where('id', $course->id)
                    ->value('access_type');

                if (in_array(Course::normalizeAccessType($currentAccessType), Course::SURVEY_UNLOCK_ACCESS_TYPES, true)) {
                    $trulyFreeCourseIds[] = $course->id;
                } else {
                    \Illuminate\Support\Facades\Log::warning('🚨 BLOCKED ACCESS: Course is no longer survey-unlockable', [
                        'course_id' => $course->id,
                        'current_access_type' => $currentAccessType,
                        'user_id' => $this->id,
                        'user_name' => $this->name
                    ]);
                }
            }

            if (empty($trulyFreeCourseIds)) {
                return 0;
            }

            $enrolledCourses = DB::table('course_user')
                ->where('user_id', $this->id)
                ->whereIn('course_id', $trulyFreeCourseIds)
                ->where('is_enrolled', true)
                ->pluck('course_id')
                ->toArray();

            $missingCourses = array_diff($trulyFreeCourseIds, $enrolledCourses);

            if (empty($missingCourses)) {
                return 0;
            }

            $enrolledCount = 0;
            foreach ($missingCourses as $courseId) {
                $course = $potentiallyFreeCourses->firstWhere('id', $courseId);
                DB::table('course_user')->updateOrInsert(
                    [
                        'user_id' => $this->id,
                        'course_id' => $courseId
                    ],
                    [
                        'is_enrolled' => true,
                        'access_level' => ($course && Course::normalizeAccessType($course->access_type) === Course::ACCESS_TYPE_PREVIEW)
                            ? self::COURSE_ACCESS_PREVIEW
                            : self::COURSE_ACCESS_FULL,
                        'enrolled_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
                $enrolledCount++;
            }

            if ($enrolledCount > 0) {
            }

            return $enrolledCount;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('❌ CRITICAL SECURITY FAILURE: Free course access check failed', [
                'user_id' => $this->id,
                'user_name' => $this->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 0; // Return 0 to indicate failure rather than throwing exception
        }
    }
}
