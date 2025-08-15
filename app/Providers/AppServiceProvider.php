<?php

namespace App\Providers;

use App\Models\Payment;
use App\Observers\PaymentObserver;
use App\Services\GradingService;
use App\Services\QuizService;
use App\Services\NotificationService;
use App\Services\Core\UltraConsolidatedCacheService;
use App\Services\UserStatsService;
use App\Services\WrittenAnswerEvaluationService;
use App\Services\QueryMonitoringService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register GradingService with dependencies
        $this->app->singleton(GradingService::class, function ($app) {
            return new GradingService(
                $app->make(\App\Services\Core\UltraConsolidatedCacheService::class),
                $app->make(UserStatsService::class),
                $app->make(NotificationService::class),
                $app->make(WrittenAnswerEvaluationService::class)
            );
        });

        // Register QuizService with dependencies
        $this->app->singleton(QuizService::class, function ($app) {
            return new QuizService(
                $app->make(\App\Services\Core\UltraConsolidatedCacheService::class)
            );
        });

        // Register NotificationService as singleton
        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService();
        });

        // Register UltraConsolidatedCacheService as singleton (replaces CacheService)
        $this->app->singleton(\App\Services\Core\UltraConsolidatedCacheService::class, function ($app) {
            return new \App\Services\Core\UltraConsolidatedCacheService();
        });

        // Register UserStatsService as singleton
        $this->app->singleton(UserStatsService::class, function ($app) {
            return new UserStatsService();
        });

        // PHASE 3: Register QueryMonitoringService as singleton
        $this->app->singleton(QueryMonitoringService::class, function ($app) {
            return new QueryMonitoringService();
        });

        // Register new text processing services as singletons
        $this->app->singleton(\App\Services\TextNormalizationService::class, function ($app) {
            return new \App\Services\TextNormalizationService();
        });

        $this->app->singleton(\App\Services\TextSimilarityService::class, function ($app) {
            return new \App\Services\TextSimilarityService();
        });

        // Register TextProcessingService as singleton with dependencies
        $this->app->singleton(\App\Services\TextProcessingService::class, function ($app) {
            return new \App\Services\TextProcessingService(
                $app->make(\App\Services\TextNormalizationService::class),
                $app->make(\App\Services\TextSimilarityService::class),
                $app->make(\App\Services\MedicalDomainService::class)
            );
        });

        // Register PatternMatcherService as singleton
        $this->app->singleton(\App\Services\PatternMatcherService::class, function ($app) {
            return new \App\Services\PatternMatcherService();
        });

        // Register MedicalKnowledgeService as singleton
        $this->app->singleton(\App\Services\MedicalKnowledgeService::class, function ($app) {
            return new \App\Services\MedicalKnowledgeService();
        });

        // Register new focused services as singletons
        $this->app->singleton(\App\Services\MedicalDomainService::class, function ($app) {
            return new \App\Services\MedicalDomainService();
        });

        $this->app->singleton(\App\Services\AnswerSimilarityService::class, function ($app) {
            return new \App\Services\AnswerSimilarityService(
                $app->make(\App\Services\TextSimilarityService::class),
                $app->make(\App\Services\PatternMatcherService::class)
            );
        });

        $this->app->singleton(\App\Services\MedicalPatternService::class, function ($app) {
            return new \App\Services\MedicalPatternService();
        });

        $this->app->singleton(\App\Services\AnswerFeedbackService::class, function ($app) {
            return new \App\Services\AnswerFeedbackService(
                $app->make(\App\Services\MedicalDomainService::class),
                $app->make(\App\Services\MedicalPatternService::class)
            );
        });

        // Register WrittenAnswerEvaluationService as singleton with new dependencies
        $this->app->singleton(WrittenAnswerEvaluationService::class, function ($app) {
            return new WrittenAnswerEvaluationService(
                $app->make(\App\Services\TextProcessingService::class),
                $app->make(\App\Services\MedicalDomainService::class),
                $app->make(\App\Services\AnswerSimilarityService::class),
                $app->make(\App\Services\MedicalPatternService::class),
                $app->make(\App\Services\AnswerFeedbackService::class)
            );
        });

        // Register only EXISTING services that are actually used
        
        // Register PaymentLogService as singleton (EXISTS)
        $this->app->singleton(\App\Services\PaymentLogService::class, function ($app) {
            return new \App\Services\PaymentLogService();
        });

        // Register PaymentService as singleton (EXISTS)
        $this->app->singleton(\App\Services\PaymentService::class, function ($app) {
            return new \App\Services\PaymentService();
        });

        // Register CourseTypeService as singleton (EXISTS)
        $this->app->singleton(\App\Services\CourseTypeService::class, function ($app) {
            return new \App\Services\CourseTypeService();
        });

        // Register InputValidationService as singleton (USED by QuizValidationService)
        $this->app->singleton(\App\Services\InputValidationService::class, function ($app) {
            return new \App\Services\InputValidationService();
        });

        // Register infrastructure services that ARE being used in middleware
        $this->app->singleton(\App\Services\EmergencyFallbackService::class, function ($app) {
            return new \App\Services\EmergencyFallbackService();
        });

        $this->app->singleton(\App\Services\HealthCheckService::class, function ($app) {
            return new \App\Services\HealthCheckService();
        });

        $this->app->singleton(\App\Services\SessionSecurityService::class, function ($app) {
            return new \App\Services\SessionSecurityService();
        });

        $this->app->singleton(\App\Services\DatabaseService::class, function ($app) {
            return new \App\Services\DatabaseService();
        });

        // Register SSOT Services as singletons
        $this->app->singleton(\App\Support\State\EnrollmentService::class, function ($app) {
            return new \App\Support\State\EnrollmentService();
        });

        $this->app->singleton(\App\Support\State\AssignmentStatusService::class, function ($app) {
            return new \App\Support\State\AssignmentStatusService();
        });

        $this->app->singleton(\App\Support\State\UserProgressService::class, function ($app) {
            return new \App\Support\State\UserProgressService();
        });

        $this->app->singleton(\App\Support\State\MistakeMasteryService::class, function ($app) {
            return new \App\Support\State\MistakeMasteryService();
        });

        $this->app->singleton(\App\Support\State\VideoProgressService::class, function ($app) {
            return new \App\Support\State\VideoProgressService();
        });

        $this->app->singleton(\App\Support\State\TopicLockService::class, function ($app) {
            return new \App\Support\State\TopicLockService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Only force HTTPS in production, not for ngrok development
        if (app()->environment('production')) {
            \URL::forceScheme('https');
        }
        
        // Register existing observers
        Payment::observe(PaymentObserver::class);
        \App\Models\Question::observe(app()->make(\App\Observers\QuestionObserver::class));
        \App\Models\Course::observe(\App\Observers\CourseObserver::class);
        
        // Register SSOT Observers for dual-write synchronization
        if (config('ssot.sync_enabled')) {
            $this->registerSSOTObservers();
        }
    }

    /**
     * Register Single Source of Truth (SSOT) Observers
     */
    private function registerSSOTObservers(): void
    {
        // Enrollment Observer - handles course_user and payments
        if (config('ssot.observers.enrollment.enabled')) {
            // Note: We'll use custom observer registration since course_user is a pivot table
            \DB::listen(function ($query) {
                if (str_contains($query->sql, 'course_user') && in_array($query->type, ['update', 'insert'])) {
                    // Handle course_user table changes through custom logic
                    app(\App\Observers\EnrollmentObserver::class);
                }
            });
            
            \App\Models\Payment::observe(\App\Observers\EnrollmentObserver::class);
        }

        // Assignment Status Observer
        if (config('ssot.observers.assignment_status.enabled')) {
            \App\Models\Assignment::observe(\App\Observers\AssignmentStatusObserver::class);
        }

        // User Progress Observer
        if (config('ssot.observers.user_progress.enabled')) {
            \App\Models\User::observe(\App\Observers\UserProgressObserver::class);
            \App\Models\QuizAttempt::observe(\App\Observers\UserProgressObserver::class);
        }

        // Mistake Mastery Observer
        if (config('ssot.observers.mistake_mastery.enabled')) {
            \App\Models\Mistake::observe(\App\Observers\MistakeMasteryObserver::class);
        }

        // Video Progress Observer
        if (config('ssot.observers.video_progress.enabled')) {
            // Note: video_progress is handled via DB table, not Eloquent model
            \DB::listen(function ($query) {
                if (str_contains($query->sql, 'video_progress') && in_array($query->type, ['update', 'insert'])) {
                    // Handle video_progress table changes through custom logic
                    app(\App\Observers\VideoProgressObserver::class);
                }
            });
        }

        // Topic Lock Observer
        if (config('ssot.observers.topic_lock.enabled')) {
            \App\Models\Topic::observe(\App\Observers\TopicLockObserver::class);
        }
    }
}
