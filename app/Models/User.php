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
        'is_admin',
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
        'is_admin' => 'boolean',
        'profile_picture_url' => 'string',
        'correct_answers_count' => 'integer',
        'total_questions_attempted' => 'integer',
    ];

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
        return $this->is_admin || $this->courses()
            ->where('course_id', $courseId)
            ->where('enrollment_status', 'active')
            ->exists();
    }

    /**
     * Enroll a user in a course.
     */
    public function enrollInCourse($courseId)
    {
        if (!$this->courses()->where('course_id', $courseId)->exists()) {
            $this->courses()->attach($courseId, [
                'enrollment_status' => 'active',
                'enrolled_at' => now()
            ]);
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
     * Check if the user is an admin.
     */
    public function isAdmin()
    {
        return $this->is_admin;
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
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();
        
        // Store original streak for tracking reset
        $originalStreak = $this->study_streak_days ?? 0;
        
        // If no last quiz date is provided, find one from the database
        if (!$lastQuizDate) {
            $lastAttempt = \App\Models\QuizAttempt::where('user_id', $this->id)
                ->where('created_at', '<', now()->subMinutes(5)) // Exclude very recent attempts 
                ->orderBy('created_at', 'desc')
                ->first();
                
            $lastQuizDate = $lastAttempt ? $lastAttempt->created_at->startOfDay() : null;
        } else if (is_string($lastQuizDate)) {
            // Convert string date to Carbon instance if needed
            $lastQuizDate = \Carbon\Carbon::parse($lastQuizDate)->startOfDay();
        }
        
        // First ever quiz (no previous quiz attempts)
        if (!$lastQuizDate) {
            $this->study_streak_days = 1;
            $this->save();
            return 1;
        }
        
        // User already did a quiz today, streak stays the same
        if ($lastQuizDate->equalTo($today)) {
            // Initialize streak to 1 if it's not set
            if ($this->study_streak_days === null || $this->study_streak_days === 0) {
                $this->study_streak_days = 1;
                $this->save();
            }
            return $this->study_streak_days;
        }
        
        // User did a quiz yesterday, increment streak
        if ($lastQuizDate->equalTo($yesterday)) {
            if ($this->study_streak_days === null || $this->study_streak_days === 0) {
                $this->study_streak_days = 1;
            } else {
                $this->study_streak_days += 1;
            }
            $this->save();
            return $this->study_streak_days;
        }
        
        // User didn't do a quiz yesterday, reset streak to 1
        // Check if streak is being reset (was > 1 before)
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