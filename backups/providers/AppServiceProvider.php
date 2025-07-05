<?php

namespace App\Providers;

use App\Models\Payment;
use App\Observers\PaymentObserver;
use App\Services\TextProcessingService;
use App\Services\PatternMatcherService;
use App\Services\MedicalKnowledgeService;
use App\Services\WrittenAnswerEvaluationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the TextProcessingService as a singleton
        $this->app->singleton(TextProcessingService::class, function ($app) {
            return new TextProcessingService();
        });
        
        // Register the PatternMatcherService as a singleton
        $this->app->singleton(PatternMatcherService::class, function ($app) {
            return new PatternMatcherService();
        });
        
        // Register the MedicalKnowledgeService as a singleton
        $this->app->singleton(MedicalKnowledgeService::class, function ($app) {
            return new MedicalKnowledgeService();
        });
        
        // Register the WrittenAnswerEvaluationService
        $this->app->singleton(WrittenAnswerEvaluationService::class, function ($app) {
            return new WrittenAnswerEvaluationService(
                $app->make(TextProcessingService::class),
                $app->make(PatternMatcherService::class),
                $app->make(MedicalKnowledgeService::class)
            );
        });
        
        // Add a convenient binding for testing text similarity
        $this->app->bind('text.similarity', function ($app) {
            return $app->make(WrittenAnswerEvaluationService::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register the Payment model observer
        Payment::observe(PaymentObserver::class);
    }
}
