<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Models\Payment;
use App\Models\Assignment;
use App\Models\QuizAttempt;
use App\Models\Mistake;
use App\Models\Topic;
use App\Observers\EnrollmentObserver;
use App\Observers\AssignmentStatusObserver;
use App\Observers\UserProgressObserver;
use App\Observers\MistakeMasteryObserver;
use App\Observers\VideoProgressObserver;
use App\Observers\TopicLockObserver;

/**
 * Service Provider for State Management Observers
 * Registers observers for dual-write state management and legacy column warnings
 */
class StateObserverServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register state management services
        $this->app->singleton(\App\Support\State\EnrollmentService::class);
        $this->app->singleton(\App\Support\State\AssignmentStatusService::class);
        $this->app->singleton(\App\Support\State\UserProgressService::class);
        $this->app->singleton(\App\Support\State\MistakeMasteryService::class);
        $this->app->singleton(\App\Support\State\VideoProgressService::class);
        $this->app->singleton(\App\Support\State\TopicLockService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register model observers for dual-write management
        User::observe(UserProgressObserver::class);
        Payment::observe(EnrollmentObserver::class);
        Assignment::observe(AssignmentStatusObserver::class);
        QuizAttempt::observe(UserProgressObserver::class);
        Mistake::observe(MistakeMasteryObserver::class);
        Topic::observe(TopicLockObserver::class);

        // Register database event listeners for direct table modifications
        $this->registerDatabaseEventListeners();
    }

    /**
     * Register database event listeners for direct table modifications
     */
    private function registerDatabaseEventListeners(): void
    {
        // Listen for direct course_user table modifications
        \DB::listen(function ($query) {
            if (str_contains($query->sql, 'course_user') && 
                (str_contains($query->sql, 'UPDATE') || str_contains($query->sql, 'INSERT'))) {
                
                logger()->warning('LEGACY WRITE DETECTED: Direct course_user table modification', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                    'timestamp' => now(),
                    'recommendation' => 'Use EnrollmentService instead'
                ]);
            }

            // Listen for direct video_progress table modifications
            if (str_contains($query->sql, 'video_progress') && 
                (str_contains($query->sql, 'UPDATE') || str_contains($query->sql, 'INSERT'))) {
                
                logger()->warning('LEGACY WRITE DETECTED: Direct video_progress table modification', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                    'timestamp' => now(),
                    'recommendation' => 'Use VideoProgressService instead'
                ]);
            }

            // Listen for direct user progress field updates
            if (str_contains($query->sql, 'users') && str_contains($query->sql, 'UPDATE') &&
                (str_contains($query->sql, 'correct_answers_count') || 
                 str_contains($query->sql, 'total_questions_attempted') ||
                 str_contains($query->sql, 'xp') || 
                 str_contains($query->sql, 'level') ||
                 str_contains($query->sql, 'study_streak_days'))) {
                
                logger()->warning('LEGACY WRITE DETECTED: Direct user progress field modification', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                    'timestamp' => now(),
                    'recommendation' => 'Use UserProgressService instead'
                ]);
            }
        });
    }
}