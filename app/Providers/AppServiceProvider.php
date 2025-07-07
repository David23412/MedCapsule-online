<?php

namespace App\Providers;

use App\Models\Payment;
use App\Observers\PaymentObserver;
use App\Services\GradingService;
use App\Services\QuizService;
use App\Services\NotificationService;
use App\Services\CacheService;
use App\Services\UserStatsService;
use App\Services\WrittenAnswerEvaluationService;
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
                $app->make(CacheService::class),
                $app->make(UserStatsService::class),
                $app->make(NotificationService::class),
                $app->make(WrittenAnswerEvaluationService::class)
            );
        });

        // Register QuizService with dependencies
        $this->app->singleton(QuizService::class, function ($app) {
            return new QuizService(
                $app->make(CacheService::class)
            );
        });

        // Register NotificationService as singleton
        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService();
        });

        // Register CacheService as singleton
        $this->app->singleton(CacheService::class, function ($app) {
            return new CacheService();
        });

        // Register UserStatsService as singleton
        $this->app->singleton(UserStatsService::class, function ($app) {
            return new UserStatsService();
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production') || 
            \Illuminate\Support\Str::contains(config('app.url'), 'ngrok')) {
            \URL::forceScheme('https');
        }
        // Register the Payment model observer
        Payment::observe(PaymentObserver::class);
    }
}
