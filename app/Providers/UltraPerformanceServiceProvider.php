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
        $this->app->alias(AsyncOperationManager::class, 'performance.async');
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
        // Slow query logging handled by PerformanceMonitoring middleware (opt-in via config)
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
            'performance.async',
            QuizUltraConsolidatedCacheService::class,
            ResilientUltraConsolidatedCacheService::class,
            UltraConsolidatedCacheService::class,
            UltraPerformanceOptimizer::class,
            AsyncOperationManager::class,
            FileIOOptimizer::class,
        ];
    }
}
