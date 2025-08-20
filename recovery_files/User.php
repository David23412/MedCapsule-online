<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use App\Support\State\EnrollmentService;
use App\Support\State\UserProgressService;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'university',
        'password',
        'role',
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
    ];

    // REMOVED: protected $appends = ['course_progress']; 
    // Course progress is now an explicit method to prevent N+1 queries

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
        'last_active_at' => 'datetime',
        'session_created_at' => 'datetime',
        'last_security_check' => 'datetime',
        'last_suspicious_activity' => 'datetime',
        'password' => 'hashed',
        'role' => 'integer',
        'profile_picture_url' => 'string',
        'correct_answers_count' => 'integer',
        'total_questions_attempted' => 'integer',
        'concurrent_login_attempts' => 'integer',
    ];

    /**
     * User roles (Integer constants for performance)
     */
    const ROLE_ADMIN = 1;
    const ROLE_TUTOR = 2;
    const ROLE_STUDENT = 3;
    
    /**
     * Role string mappings for backward compatibility
     */
    const ROLE_STRINGS = [
        self::ROLE_ADMIN => 'admin',
        self::ROLE_TUTOR => 'tutor', 
        self::ROLE_STUDENT => 'student'
    ];

    /**
     * Define the relationship with courses (many-to-many).
     * SSOT: Uses enrolled_at timestamp as single source of truth
     */
    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_user')
                    ->withPivot('enrolled_at') // Only SSOT field needed
                    ->whereNotNull('course_user.enrolled_at') // SSOT: timestamp determines enrollment
                    ->withTimestamps();
    }

    /**
     * Define the relationship with all course enrollments regardless of enrollment status.
     * Useful for admin interfaces.
     */
    public function allCourseEnrollments()
    {
        return $this->belongsToMany(Course::class, 'course_user')
                    ->withPivot('enrolled_at') // Only SSOT field needed
                    ->withTimestamps();
    }

    /**
     * Get the user's notifications.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
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
        return $this->belongsToMany(Assignment::class, 'assignment_users', 'user_id', 'assignment_id')
                   ->withTimestamps();
    }

    /**
     * Get the user's topic progress.
     */
    public function userTopicProgress()
    {
        return $this->hasMany(UserTopicProgress::class);
    }

    /**
     * Get the user's unread notifications.
     */
    public function unreadNotifications()
    {
        return $this->notifications()->where('read', false);
    }

    /**
     * Get the user's security logs.
     */
    public function securityLogs()
    {
        return $this->hasMany(SessionSecurityLog::class);
    }

    /**
     * Check if user has access to a specific course.
     */
    public function hasAccessToCourse($courseId)
    {
        // Admins always have access
        if ($this->role === self::ROLE_ADMIN) {
            return true;
        }
        
        // SSOT: Use EnrollmentService instead of direct database query
        $enrollmentService = app(EnrollmentService::class);
        return $enrollmentService->isEnrolled($this->id, $courseId);
    }

    /**
     * Check if user is enrolled in a course using SSOT (enrolled_at timestamp).
     * SPLIT-BRAIN FIX: Only check enrolled_at timestamp, ignore is_enrolled boolean
     */
    public function isEnrolledInCourse($courseId)
    {
        // Admins are considered enrolled in all courses
        if ($this->role === self::ROLE_ADMIN) {
            return true;
        }
        
        // SSOT: Check enrolled_at timestamp only
        return DB::table('course_user')
            ->where('user_id', $this->id)
            ->where('course_id', $courseId)
            ->whereNotNull('enrolled_at') // SSOT: timestamp determines enrollment
            ->exists();
    }

    /**
     * Enroll a user in a course using SSOT pattern.
     * SSOT: Only set enrolled_at timestamp - clean and simple
     */
    public function enrollInCourse($courseId)
    {
        // SSOT: Only set enrolled_at timestamp
        $this->courses()->syncWithoutDetaching([$courseId => [
            'enrolled_at' => now() // SSOT: This is the authoritative field
        ]]);
    }

    /**
     * Enroll the user in all free courses (price 0 or NULL).
     * Ensures 'active' status and correct timestamp.
     * This method is idempotent - safe to run multiple times.
     */
    public function enrollInAllFreeCourses()
    {
        try {
            // Get all free course IDs (price = 0 OR price IS NULL)
            $freeCourseIds = \App\Models\Course::where(function($query) {
                $query->where('price', 0)
                      ->orWhereNull('price');
            })->pluck('id');

            if ($freeCourseIds->isEmpty()) {
                return 0; // No free courses to enroll in
            }

            // Use syncWithoutDetaching to ensure proper pivot data and avoid duplicates
            $this->courses()->syncWithoutDetaching(
                $freeCourseIds->mapWithKeys(function($courseId) {
                    return [$courseId => [
                        'is_enrolled' => true,
                        'enrolled_at' => now()
                    ]];
                })->toArray()
            );

            return $freeCourseIds->count();
        } catch (\Exception $e) {
            \Log::error('Failed to enroll user in free courses', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Unenroll a user from a course using SSOT pattern.
     * SSOT: Set enrolled_at to NULL instead of deleting record (preserves history)
     */
    public function unenrollFromCourse($courseId)
    {
        DB::table('course_user')
            ->where('user_id', $this->id)
            ->where('course_id', $courseId)
            ->update(['enrolled_at' => null, 'updated_at' => now()]);
    }

    /**
     * Get course progress for this user - OPTIMIZED: No longer an accessor
     * Call explicitly: $user->getCourseProgress()
     * 🚀 PERFORMANCE BEAST: Eliminated N+1 query with eager loading
     */
    public function getCourseProgress(): array
    {
        // 🚀 PERFORMANCE BEAST: Single query with eager loading - NO N+1 QUERIES
        return $this->courses()
            ->with([
                'topics', // 🚀 CRITICAL FIX: Eager load topics to prevent N+1
                'userTopicProgress' => function($query) {
                    $query->where('user_id', $this->id);
                }
            ])
            ->get()
            ->mapWithKeys(function ($course) {
                // 🚀 PERFORMANCE BEAST: Use eager-loaded topics count (no additional query)
                $topicCount = $course->topics->count();
                if ($topicCount === 0) {
                    return [$course->id => 0];
                }
                
                $completedTopics = $course->userTopicProgress
                    ->where('percentage_grade', '>=', 90)
                    ->count();
                    
                $percentage = round(($completedTopics / $topicCount) * 100, 0);
                return [$course->id => $percentage];
            })
            ->toArray();
    }

    /**
     * Get role as string for backward compatibility
     */
    public function getRoleStringAttribute(): string
    {
        return self::ROLE_STRINGS[$this->role] ?? 'student';
    }
    
    /**
     * Check if user is an admin
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Check if user is a tutor
     *
     * @return bool
     */
    public function isTutor(): bool
    {
        return $this->role === self::ROLE_TUTOR;
    }

    /**
     * Check if user is a student
     *
     * @return bool
     */
    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
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
     * 🚀 PERFORMANCE BEAST: Get formatted study time - NO N+1 QUERIES
     * Call explicitly: $user->getFormattedStudyTime()
     */
    public function getFormattedStudyTime(): string
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
        // SSOT: Use UserProgressService for XP calculation
        $progressService = app(UserProgressService::class);
        return $progressService->getUserXP($this->id);
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
        // SSOT: Use UserProgressService for level calculation
        $progressService = app(UserProgressService::class);
        return $progressService->getUserLevel($this->id);
    }
    
    /**
     * Calculate the XP needed for the next level
     * 
     * @return int
     */
    public function getXPForNextLevel(): int
    {
        // SSOT: Use UserProgressService for XP calculation
        $progressService = app(UserProgressService::class);
        return $progressService->getXPForNextLevel($this->id);
    }
    
    /**
     * Update the user's study streak based on their quiz history
     * 
     * @param string|null $lastQuizDate Optional date of the user's last quiz before today
     * @return int Updated streak value
     */
    public function updateStudyStreak($lastQuizDate = null)
    {
        // SSOT: Use UserProgressService for streak calculation
        $progressService = app(UserProgressService::class);
        $streakInfo = $progressService->calculateStudyStreak($this->id);
        
        // Update cached field
        $this->study_streak_days = $streakInfo['streak_days'];
        $this->save();
        
        return $streakInfo['streak_days'];
        
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
        // SSOT: Use UserProgressService for correct answers count
        $progressService = app(UserProgressService::class);
        return $progressService->getTotalCorrectAnswers($this->id);
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
        // SSOT: Use UserProgressService for both cached and computed values
        $progressService = app(UserProgressService::class);
        $cachedCount = $this->correct_answers_count ?? 0;
        $actualCount = $progressService->getTotalCorrectAnswers($this->id);
        
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
}