<?php

namespace App\Providers;

use App\Models\Payment;
use App\Observers\PaymentObserver;
use App\Services\GradingService;
use App\Services\QuizService;
use App\Services\NotificationService;
use App\Services\UserStatsService;
use App\Services\CacheService;
use App\Services\PaymentVerificationService;
use App\Services\FraudDetectionService;
use App\Services\PaymentLogService;
use App\Services\WrittenAnswerEvaluationService;
use App\Services\MedicalKnowledgeService;
use App\Services\PatternMatcherService;
use App\Services\TextProcessingService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Core application services
        $this->app->singleton(GradingService::class);
        $this->app->singleton(QuizService::class);
        $this->app->singleton(NotificationService::class);
        $this->app->singleton(UserStatsService::class);
        $this->app->singleton(CacheService::class);
        
        // Payment and verification services
        $this->app->singleton(PaymentVerificationService::class);
        $this->app->singleton(FraudDetectionService::class);
        $this->app->singleton(PaymentLogService::class);
        
        // Knowledge and processing services
        $this->app->singleton(WrittenAnswerEvaluationService::class);
        $this->app->singleton(MedicalKnowledgeService::class);
        $this->app->singleton(PatternMatcherService::class);
        $this->app->singleton(TextProcessingService::class);
        
        // Register service aliases for backward compatibility
        $this->app->alias(GradingService::class, 'grading.service');
        $this->app->alias(QuizService::class, 'quiz.service');
        $this->app->alias(NotificationService::class, 'notification.service');
        $this->app->alias(UserStatsService::class, 'user.stats.service');
        $this->app->alias(CacheService::class, 'cache.service');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register the Payment model observer
        Payment::observe(PaymentObserver::class);
        
        // Configure service bindings that depend on other services
        $this->configureServiceDependencies();
    }
    
    /**
     * Configure service dependencies and complex bindings
     */
    protected function configureServiceDependencies(): void
    {
        // Configure GradingService with its dependencies
        $this->app->when(GradingService::class)
            ->needs(NotificationService::class)
            ->give(function ($app) {
                return $app->make(NotificationService::class);
            });
            
        $this->app->when(GradingService::class)
            ->needs(UserStatsService::class)
            ->give(function ($app) {
                return $app->make(UserStatsService::class);
            });
            
        // Configure QuizService with CacheService dependency
        $this->app->when(QuizService::class)
            ->needs(CacheService::class)
            ->give(function ($app) {
                return $app->make(CacheService::class);
            });
            
        // Configure PaymentVerificationService with optional dependencies
        $this->app->when(PaymentVerificationService::class)
            ->needs('PaymentReceiptValidator')
            ->give(function ($app) {
                // Return null if the service doesn't exist (graceful fallback)
                return $app->bound('PaymentReceiptValidator') 
                    ? $app->make('PaymentReceiptValidator') 
                    : null;
            });
    }
} 