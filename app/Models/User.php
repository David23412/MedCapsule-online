<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
    ];

    protected $appends = ['course_progress'];

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
        'password' => 'hashed',
        'role' => 'string',
        'profile_picture_url' => 'string',
        'correct_answers_count' => 'integer',
        'total_questions_attempted' => 'integer',
    ];

    /**
     * User roles
     */
    const ROLE_ADMIN = 'admin';
    const ROLE_TUTOR = 'tutor';
    const ROLE_STUDENT = 'student';

    /**
     * Define the relationship with courses (many-to-many).
     */
    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_user')
                    ->withPivot('enrollment_status')
                    ->where('enrollment_status', 'active')
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
     * Check if user has access to a specific course.
     */
    public function hasAccessToCourse($courseId)
    {
        return $this->role === self::ROLE_ADMIN || $this->courses()
            ->where('course_id', $courseId)
            ->where('enrollment_status', 'active')
            ->exists();
    }

    /**
     * Enroll a user in a course.
     */
    public function enrollInCourse($courseId)
    {
        // Use syncWithoutDetaching to ensure proper pivot data and avoid duplicates
        $this->courses()->syncWithoutDetaching([$courseId => [
            'enrollment_status' => 'active',
            'enrolled_at' => now()
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
                        'enrollment_status' => 'active',
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
     * Unenroll a user from a course.
     */
    public function unenrollFromCourse($courseId)
    {
        $this->courses()->detach($courseId);
    }

    /**
     * Get the enrollment status and completion progress for a given course.
     */
    public function getCourseProgressAttribute()
    {
        return $this->courses()
            ->get()
            ->mapWithKeys(function ($course) {
                return [$course->id => $course->pivot->completion_percentage ?? 0];
            })
            ->toArray();
    }

    public function setCourseProgressAttribute($value)
    {
        // This is a virtual attribute, so we don't need to store it
        return;
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
        return $this->belongsToMany(User::class, 'tutor_student_relationships', 'tutor_id', 'student_id')
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
        return $this->belongsToMany(User::class, 'tutor_student_relationships', 'student_id', 'tutor_id')
                    ->where('users.role', self::ROLE_TUTOR)
                    ->withPivot('assigned_at')
                    ->withTimestamps();
    }

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
                    app(\App\Services\NotificationService::class)->createStreakResetNotification($this);
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
        $milestones = [1, 2, 3, 7, 14, 30, 60, 100];
        
        if ($currentStreak > $previousStreak && in_array($currentStreak, $milestones)) {
            try {
                app(\App\Services\NotificationService::class)->createStudyStreakNotification($this, $currentStreak);
                \Illuminate\Support\Facades\Log::info('Created streak milestone notification', [
                    'user_id' => $this->id,
                    'streak' => $currentStreak
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to create streak notification', [
                    'error' => $e->getMessage(),
                    'user_id' => $this->id,
                    'streak' => $currentStreak
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
}