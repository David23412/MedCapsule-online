<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Core\UltraPerformanceOptimizer;
use App\Services\Core\AsyncOperationManager;
use App\Services\Core\UltraConsolidatedCacheService;
use App\Services\Core\FileIOOptimizer;
use App\Services\QuizUltraConsolidatedCacheService;
use App\Services\ResilientUltraConsolidatedCacheService;

class UltraPerformanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register the ONE AND ONLY cache service
        $this->app->singleton(UltraConsolidatedCacheService::class, function ($app) {
            return new UltraConsolidatedCacheService();
        });

        $this->app->singleton(QuizUltraConsolidatedCacheService::class, function ($app) {
            return new QuizUltraConsolidatedCacheService($app->make(UltraConsolidatedCacheService::class));
        });

        $this->app->singleton(ResilientUltraConsolidatedCacheService::class, function () {
            return new ResilientUltraConsolidatedCacheService();
        });

        $this->app->singleton(UltraPerformanceOptimizer::class, function ($app) {
            return new UltraPerformanceOptimizer();
        });

        $this->app->singleton(AsyncOperationManager::class, function ($app) {
            return new AsyncOperationManager();
        });

        $this->app->singleton(FileIOOptimizer::class, function ($app) {
            return new FileIOOptimizer();
        });

        // Bind to aliases for backward compatibility
        $this->app->alias(UltraConsolidatedCacheService::class, 'cache.service');
        $this->app->alias(UltraConsolidatedCacheService::class, 'cache.manager.smart');
        $this->app->alias(UltraConsolidatedCacheService::class, 'performance.cache');
        $this->app->alias(UltraConsolidatedCacheService::class, 'performance.redis');
        $this->app->alias(UltraConsolidatedCacheService::class, 'performance.querycache');
        $this->app->alias(UltraPerformanceOptimizer::class, 'performance.optimizer');
        $this->app->alias(AsyncOperationManager::class, 'performance.async');
        $this->app->alias(FileIOOptimizer::class, 'performance.fileio');
    }

    public function boot(): void
    {
        // Skip heavy boot logic during console operations that manipulate the database or run tests
        if ($this->shouldSkipBootForConsole()) {
            return;
        }

        // Initialize ultra-performance systems
        UltraPerformanceOptimizer::initialize();

        // Register performance events
        $this->registerPerformanceEvents();
    }

    private function registerPerformanceEvents(): void
    {
        // Listen for application events to optimize performance
        $this->app['events']->listen('Illuminate\Database\Events\QueryExecuted', function ($event) {
            // Monitor slow queries
            if ($event->time > 100) {
                \Log::warning('Slow query detected', [
                    'sql' => $event->sql,
                    'duration_ms' => $event->time,
                    'connection' => $event->connectionName
                ]);
            }
        });
    }

    private function shouldSkipBootForConsole(): bool
    {
        if (!$this->app->runningInConsole()) {
            return false;
        }

        $argv = $_SERVER['argv'] ?? [];
        $cmd = implode(' ', $argv);

        return str_contains($cmd, 'migrate')
            || str_contains($cmd, 'db:')
            || str_contains($cmd, 'seed')
            || str_contains($cmd, 'test');
    }

    public function provides(): array
    {
        return [
            'cache.service',
            'cache.manager.smart',
            'performance.cache',
            'performance.redis',
            'performance.querycache',
            'performance.optimizer',
            'performance.async',
            'performance.fileio',
            QuizUltraConsolidatedCacheService::class,
            ResilientUltraConsolidatedCacheService::class,
            UltraConsolidatedCacheService::class,
            UltraPerformanceOptimizer::class,
            AsyncOperationManager::class,
            FileIOOptimizer::class,
        ];
    }
}
