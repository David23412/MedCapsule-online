<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Core\EventDrivenGradingService;
use App\Services\Core\SmartCacheManager;
use App\Services\Core\MemoryEfficientQuizService;
use App\Events\QuizCompleted;
use App\Events\QuizStarted;
use App\Events\QuizGraded;
use App\Listeners\HandleQuizCompletion;

/**
 * Architectural Refactor Service Provider
 * 
 * Registers the new architectural components that replace
 * the tightly-coupled, memory-intensive services.
 */
class ArchitecturalRefactorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register Event-Driven Grading Service
        $this->app->singleton(EventDrivenGradingService::class, function ($app) {
            return new EventDrivenGradingService();
        });
        
        // Register Smart Cache Manager
        $this->app->singleton(SmartCacheManager::class, function ($app) {
            return new SmartCacheManager();
        });
        
        // Register Memory-Efficient Quiz Service
        $this->app->singleton(MemoryEfficientQuizService::class, function ($app) {
            return new MemoryEfficientQuizService();
        });
        
        // Alias for backward compatibility
        $this->app->alias(EventDrivenGradingService::class, 'grading.service.v2');
        $this->app->alias(SmartCacheManager::class, 'cache.manager.smart');
        $this->app->alias(MemoryEfficientQuizService::class, 'quiz.service.efficient');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register event listeners
        $this->registerEventListeners();
        
        // Register console commands if running in console
        if ($this->app->runningInConsole()) {
            $this->registerConsoleCommands();
        }
        
        // Register middleware
        $this->registerMiddleware();
    }
    
    /**
     * Register event listeners for the new architecture
     */
    private function registerEventListeners(): void
    {
        // Quiz completion handling
        $this->app['events']->listen(
            QuizCompleted::class,
            \App\Listeners\SyncPlannerAfterQuiz::class
        );
        $this->app['events']->listen(
            QuizCompleted::class,
            HandleQuizCompletion::class
        );
        
        // Quiz started logging
        $this->app['events']->listen(
            QuizStarted::class,
            function (QuizStarted $event) {
                \Log::info('Quiz started', [
                    'user_id' => $event->user->id,
                    'topic_id' => $event->topic->id,
                    'mode' => $event->mode,
                    'question_count' => $event->questionCount
                ]);
            }
        );
    }
    
    /**
     * Register console commands for the new architecture
     */
    private function registerConsoleCommands(): void
    {
        $this->commands([
            \App\Console\Commands\ArchitecturalHealthCheck::class,
            \App\Console\Commands\CacheOptimization::class,
            \App\Console\Commands\MemoryProfiler::class,
        ]);
    }
    
    /**
     * Register middleware for the new architecture
     */
    private function registerMiddleware(): void
    {
        // Memory monitoring middleware
        $this->app['router']->aliasMiddleware('memory.monitor', \App\Http\Middleware\MemoryMonitor::class);
        
        // Smart cache middleware
        $this->app['router']->aliasMiddleware('smart.cache', \App\Http\Middleware\SmartCacheMiddleware::class);
    }
    
    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            EventDrivenGradingService::class,
            SmartCacheManager::class,
            MemoryEfficientQuizService::class,
            'grading.service.v2',
            'cache.manager.smart',
            'quiz.service.efficient'
        ];
    }
}