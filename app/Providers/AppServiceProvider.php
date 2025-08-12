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

        // Register TextProcessingService as singleton
        $this->app->singleton(\App\Services\TextProcessingService::class, function ($app) {
            return new \App\Services\TextProcessingService();
        });

        // Register PatternMatcherService as singleton
        $this->app->singleton(\App\Services\PatternMatcherService::class, function ($app) {
            return new \App\Services\PatternMatcherService();
        });

        // Register MedicalKnowledgeService as singleton
        $this->app->singleton(\App\Services\MedicalKnowledgeService::class, function ($app) {
            return new \App\Services\MedicalKnowledgeService();
        });

        // Register WrittenAnswerEvaluationService as singleton
        $this->app->singleton(WrittenAnswerEvaluationService::class, function ($app) {
            return new WrittenAnswerEvaluationService(
                $app->make(\App\Services\TextProcessingService::class),
                $app->make(\App\Services\PatternMatcherService::class),
                $app->make(\App\Services\MedicalKnowledgeService::class)
            );
        });

        // Register PerformanceOptimizationService as singleton (updated to use consolidated cache)
        $this->app->singleton(\App\Services\PerformanceOptimizationService::class, function ($app) {
            return new \App\Services\PerformanceOptimizationService(
                $app->make(\App\Services\Core\UltraConsolidatedCacheService::class)
            );
        });

        // Register PaymentVerificationService as singleton
        $this->app->singleton(\App\Services\PaymentVerificationService::class, function ($app) {
            return new \App\Services\PaymentVerificationService();
        });

        // Register FraudDetectionService as singleton
        $this->app->singleton(\App\Services\FraudDetectionService::class, function ($app) {
            return new \App\Services\FraudDetectionService();
        });

        // Register PaymentLogService as singleton
        $this->app->singleton(\App\Services\PaymentLogService::class, function ($app) {
            return new \App\Services\PaymentLogService();
        });

        // Register InputValidationService as singleton
        $this->app->singleton(\App\Services\InputValidationService::class, function ($app) {
            return new \App\Services\InputValidationService();
        });

        // Register DatabaseService as singleton
        $this->app->singleton(\App\Services\DatabaseService::class, function ($app) {
            return new \App\Services\DatabaseService();
        });

        // Register ResilientCacheService as singleton
        $this->app->singleton(\App\Services\ResilientUltraConsolidatedCacheService::class, function ($app) {
            return new \App\Services\ResilientCacheService();
        });
        
        // Register CourseTypeService as singleton
        $this->app->singleton(\App\Services\CourseTypeService::class, function ($app) {
            return new \App\Services\CourseTypeService();
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
        // Register the Payment model observer
        Payment::observe(PaymentObserver::class);
        
        // Register the Question model observer with dependency injection
        \App\Models\Question::observe(app()->make(\App\Observers\QuestionObserver::class));
        
        // Register the Course model observer for INSTANT cache clearing
        \App\Models\Course::observe(\App\Observers\CourseObserver::class);
    }
}
