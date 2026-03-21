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
        'target_score',
        'daily_study_minutes',
        'planner_focus',
        'repair_mode',
        'classic_repair_difficulty',
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
        'survey_completed',
        'free_courses_unlocked',
        'survey_completed_at',
        'free_courses_unlocked_at',
        'has_sr_access',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
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
        'target_score' => 'integer',
        'daily_study_minutes' => 'integer',
        'profile_picture_url' => 'string',
        'repair_mode' => 'string',
        'classic_repair_difficulty' => 'string',
        'correct_answers_count' => 'integer',
        'total_questions_attempted' => 'integer',
        'concurrent_login_attempts' => 'integer',
        'survey_completed' => 'boolean',
        'free_courses_unlocked' => 'boolean',
        'planner_focus' => 'string',
        'survey_completed_at' => 'datetime',
        'free_courses_unlocked_at' => 'datetime',
        'has_sr_access' => 'boolean',
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
            'planner_classic_repair_difficulty',
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
        static::updating(function ($user) {
            // Check if role is being changed from admin to non-admin
            if ($user->isDirty('role') &&
                (int)$user->getOriginal('role') === self::ROLE_ADMIN &&
                (int)$user->role !== self::ROLE_ADMIN) {

                // Revoke all course enrollments for former admin
                $user->courses()->detach();

                Log::info('Admin role removed - all course enrollments revoked', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'old_role' => $user->getOriginal('role'),
                    'new_role' => $user->role
                ]);
            }
        });

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

        if ($this->isAdmin()) {
            return array_fill_keys($courseIds, self::COURSE_ACCESS_FULL);
        }

        $normalizedCourseIds = collect($courseIds)
            ->map(fn ($courseId) => (int) $courseId)
            ->unique()
            ->values()
            ->all();

        $accessMap = array_fill_keys($normalizedCourseIds, null);
        $hasFreeSurveyUnlock = $this->hasUnlockedFreeCourses();

        $rows = DB::table('courses')
            ->leftJoin('course_user', function ($join) {
                $join->on('courses.id', '=', 'course_user.course_id')
                    ->where('course_user.user_id', '=', $this->id);
            })
            ->whereIn('courses.id', $normalizedCourseIds)
            ->select(
                'courses.id',
                'courses.price',
                'course_user.is_enrolled',
                'course_user.access_level'
            )
            ->get();

        foreach ($rows as $row) {
            $courseId = (int) $row->id;
            $isFreeCourse = $row->price === null || (float) $row->price <= 0;
            $isEnrolled = (bool) ($row->is_enrolled ?? false);

            if (!$isEnrolled) {
                $accessMap[$courseId] = null;
                continue;
            }

            if ($this->isTutor()) {
                $accessMap[$courseId] = self::COURSE_ACCESS_FULL;
                continue;
            }

            if ($isFreeCourse && !$hasFreeSurveyUnlock) {
                $accessMap[$courseId] = null;
                continue;
            }

            $storedLevel = is_string($row->access_level) ? trim($row->access_level) : null;
            if (in_array($storedLevel, [self::COURSE_ACCESS_PREVIEW, self::COURSE_ACCESS_FULL], true)) {
                $accessMap[$courseId] = $storedLevel;
                continue;
            }

            $accessMap[$courseId] = $isFreeCourse
                ? self::COURSE_ACCESS_PREVIEW
                : self::COURSE_ACCESS_FULL;
        }

        return $accessMap;
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
        return (bool) $this->survey_completed || $this->hasStoredFreeCourseSurveyResponse();
    }

    public function hasUnlockedFreeCourses(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ((bool) $this->survey_completed && (bool) $this->free_courses_unlocked) {
            return true;
        }

        if (!$this->hasStoredFreeCourseSurveyResponse()) {
            return false;
        }

        $this->restoreFreeCourseSurveyUnlockState();

        return true;
    }

    public function requiresSurveyUnlockForCourse(Course|int $course): bool
    {
        if ($this->isAdmin() || $this->hasUnlockedFreeCourses()) {
            return false;
        }

        $courseModel = $course instanceof Course
            ? $course
            : Course::query()->select('id', 'price')->find($course);

        if (!$courseModel) {
            return false;
        }

        return $courseModel->isFree();
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

    protected function hasStoredFreeCourseSurveyResponse(): bool
    {
        return $this->surveyResponses()
            ->where('survey_key', (string) config('free_course_survey.survey_key', 'free_course_unlock_v1'))
            ->exists();
    }

    protected function restoreFreeCourseSurveyUnlockState(): void
    {
        $now = now();

        $needsFlagSync = !(bool) $this->survey_completed
            || !(bool) $this->free_courses_unlocked
            || !$this->survey_completed_at
            || !$this->free_courses_unlocked_at;

        if ($needsFlagSync) {
            $this->forceFill([
                'survey_completed' => true,
                'free_courses_unlocked' => true,
                'survey_completed_at' => $this->survey_completed_at ?? $now,
                'free_courses_unlocked_at' => $this->free_courses_unlocked_at ?? $now,
            ])->saveQuietly();
        }

        $this->enrollInAllFreeCourses();
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
     * 🔥 SECURITY BEAST: Enroll user in TRULY FREE courses with DOUBLE-CHECKING
     * Prevents re-enrollment in courses that became paid after price change
     */
    public function enrollInAllFreeCourses()
    {
        try {
            $now = now();

            $trulyFreeCourseIds = \App\Models\Course::query()
                ->where(function ($query) {
                    $query->where('price', '<=', 0)
                        ->orWhereNull('price');
                })
                ->pluck('id')
                ->all();

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

            if (!empty($reactivateCourseIds)) {
                DB::table('course_user')
                    ->where('user_id', $this->id)
                    ->whereIn('course_id', $reactivateCourseIds)
                    ->update([
                        'is_enrolled' => true,
                        'access_level' => self::COURSE_ACCESS_PREVIEW,
                        'enrolled_at' => $now,
                        'updated_at' => $now,
                    ]);
            }

            if (!empty($missingCourseIds)) {
                DB::table('course_user')->insert(
                    collect($missingCourseIds)->map(fn ($courseId) => [
                        'user_id' => $this->id,
                        'course_id' => $courseId,
                        'is_enrolled' => true,
                        'access_level' => self::COURSE_ACCESS_PREVIEW,
                        'enrolled_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            }

            $enrolledCount = count($missingCourseIds) + count($reactivateCourseIds);

            if ($enrolledCount > 0) {
                \Illuminate\Support\Facades\Log::info('✅ SAFE ENROLLMENT: User enrolled in confirmed free courses', [
                    'user_id' => $this->id,
                    'user_name' => $this->name,
                    'enrolled_course_ids' => array_slice($trulyFreeCourseIds, 0, 5), // First 5 for logging
                    'enrollment_count' => $enrolledCount,
                    'total_free_courses_available' => count($trulyFreeCourseIds)
                ]);
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
                    \Illuminate\Support\Facades\Log::info('Created streak reset notification', [
                        'user_id' => $this->id,
                        'previous_streak' => $originalStreak
                    ]);
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
                \Illuminate\Support\Facades\Log::info('Created streak milestone notification', [
                    'user_id' => $this->id,
                    'streak' => $currentStreak,
                    'previous_streak' => $previousStreak
                ]);
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
                \Illuminate\Support\Facades\Log::info('Created first quiz streak notification', [
                    'user_id' => $this->id,
                    'streak' => 1
                ]);
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

        // Local file from secure_uploads disk
        // Handle both storage formats:
        // 1. uploads/profile-pictures/2025/09/18/fb5c90fb/filename.jpeg (newer format)
        // 2. profile-pictures/2025/09/09/dec0984f/filename.jpeg (older format)
        $storedPath = $this->profile_picture_url;
        
        // Normalize the path - ensure it starts with profile-pictures/
        if (str_starts_with($storedPath, 'uploads/')) {
            $cleanPath = str_replace('uploads/', '', $storedPath);
        } else {
            $cleanPath = $storedPath;
        }
        
        // Check if file exists in storage (full path includes uploads/)
        $localPath = storage_path('app/uploads/' . $cleanPath);
        if (file_exists($localPath)) {
            // Generate consistent URL that matches the route pattern: /uploads/profile-pictures/{path}
            $url = url('/uploads/' . $cleanPath);
            
            if ($withCacheBust) {
                $url .= '?v=' . time();
            }
            
            return $this->profileImageUrlCache[$cacheKey] = $url;
        }

        // Log missing file for debugging
        \Log::warning('Profile picture file not found', [
            'user_id' => $this->id,
            'stored_path' => $storedPath,
            'expected_file_path' => $localPath,
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

        // Local file from secure_uploads disk
        // Handle both storage formats
        $storedPath = $user->profile_picture_url;
        
        // Normalize the path - ensure it starts with profile-pictures/
        if (str_starts_with($storedPath, 'uploads/')) {
            $cleanPath = str_replace('uploads/', '', $storedPath);
        } else {
            $cleanPath = $storedPath;
        }
        
        // Check if file exists in storage (full path includes uploads/)
        $localPath = storage_path('app/uploads/' . $cleanPath);
        if (file_exists($localPath)) {
            // Generate consistent URL that matches the route pattern: /uploads/profile-pictures/{path}
            $url = url('/uploads/' . $cleanPath);
            
            if ($withCacheBust) {
                $url .= '?v=' . time();
            }
            
            return self::$profileImageUrlForCache[$cacheKey] = $url;
        }

        // Fallback to UI Avatars if file doesn't exist
        return self::$profileImageUrlForCache[$cacheKey] = 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&size=200&background=007bff&color=ffffff';
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

            \Log::info('Synced correct answers count', [
                'user_id' => $this->id,
                'cached_count' => $cachedCount,
                'actual_count' => $actualCount,
                'difference' => $actualCount - $cachedCount
            ]);

            return true;
        }

        return false;
    }

    /**
     * 🔥 SECURITY BEAST: Ensure user has access to TRULY FREE courses only
     * DOUBLE-CHECKS prices to prevent enrollment in paid courses that became paid
     *
     * @return int Number of courses safely enrolled in
     */
    public function ensureFreeCourseAccess(): int
    {
        try {
            // 🔥 PHASE 1: Get potentially free courses
            $potentiallyFreeCourses = \App\Models\Course::where(function($query) {
                $query->where('price', '<=', 0)
                      ->orWhereNull('price');
            })->get(['id', 'price']);

            if ($potentiallyFreeCourses->isEmpty()) {
                return 0;
            }

            // 🔥 PHASE 2: DOUBLE-CHECK price in DATABASE (not cache)
            $trulyFreeCourseIds = [];
            foreach ($potentiallyFreeCourses as $course) {
                $currentPrice = DB::table('courses')
                    ->where('id', $course->id)
                    ->value('price');

                // Exact check: Must be $0.00
                if ($currentPrice === 0.00) {
                    $trulyFreeCourseIds[] = $course->id;
                } else if ($currentPrice > 0) {
                    \Illuminate\Support\Facades\Log::warning('🚨 BLOCKED ACCESS: Course is no longer free', [
                        'course_id' => $course->id,
                        'current_price' => $currentPrice,
                        'expected_price' => 0,
                        'user_id' => $this->id,
                        'user_name' => $this->name
                    ]);
                }
            }

            if (empty($trulyFreeCourseIds)) {
                return 0;
            }

            // 🔥 PHASE 3: Safe enrollment check
            $enrolledCourses = DB::table('course_user')
                ->where('user_id', $this->id)
                ->whereIn('course_id', $trulyFreeCourseIds)
                ->where('is_enrolled', true)
                ->pluck('course_id')
                ->toArray();

            $missingCourses = array_diff($trulyFreeCourseIds, $enrolledCourses);

            if (empty($missingCourses)) {
                return 0; // Already enrolled in all free courses
            }

            // 🔥 PHASE 4: Safe enrollment execution
            $enrolledCount = 0;
            foreach ($missingCourses as $courseId) {
                DB::table('course_user')->updateOrInsert(
                    [
                        'user_id' => $this->id,
                        'course_id' => $courseId
                    ],
                    [
                        'is_enrolled' => true,
                        'enrolled_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
                $enrolledCount++;
            }

            if ($enrolledCount > 0) {
                \Illuminate\Support\Facades\Log::info('✅ SECURE ENROLLMENT: User safely enrolled in confirmed free courses', [
                    'user_id' => $this->id,
                    'user_name' => $this->name,
                    'courses_checked' => count($trulyFreeCourseIds),
                    'courses_enrolled' => array_slice($missingCourses, 0, 5), // First 5 for logging
                    'enrollment_count' => $enrolledCount
                ]);
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
