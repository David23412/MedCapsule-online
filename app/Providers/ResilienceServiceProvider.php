<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Resilience\ConnectionManager;
use App\Services\Resilience\ConfigurationGuard;
use App\Services\Resilience\QueueIntegrityManager;
use App\Services\Resilience\NetworkResilienceManager;

/**
 * Resilience Service Provider
 * Registers all system resilience services
 */
class ResilienceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ConnectionManager::class, fn($app) => new ConnectionManager());
        $this->app->singleton(ConfigurationGuard::class, fn($app) => new ConfigurationGuard());
        $this->app->singleton(QueueIntegrityManager::class, fn($app) => new QueueIntegrityManager());
        $this->app->singleton(NetworkResilienceManager::class, fn($app) => new NetworkResilienceManager());
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Log failed queue jobs for debugging
        \Queue::failing(function (\Illuminate\Queue\Events\JobFailed $event) {
            \Log::error('Job failed', [
                'job' => $event->job->getName(),
                'queue' => $event->job->getQueue(),
                'exception' => $event->exception->getMessage(),
            ]);
        });
    }
}