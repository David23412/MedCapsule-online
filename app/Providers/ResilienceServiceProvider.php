<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Resilience\ConnectionManager;
use App\Services\Resilience\ConfigurationGuard;
use App\Services\Resilience\QueueIntegrityManager;
use App\Services\Resilience\NetworkResilienceManager;
use App\Http\Middleware\PerformanceOptimizer;

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
        // Register Connection Manager as singleton
        $this->app->singleton(ConnectionManager::class, function ($app) {
            return new ConnectionManager();
        });
        
        // Register Configuration Guard as singleton
        $this->app->singleton(ConfigurationGuard::class, function ($app) {
            return new ConfigurationGuard();
        });
        
        // Register Queue Integrity Manager as singleton
        $this->app->singleton(QueueIntegrityManager::class, function ($app) {
            return new QueueIntegrityManager();
        });
        
        // Register Network Resilience Manager as singleton
        $this->app->singleton(NetworkResilienceManager::class, function ($app) {
            return new NetworkResilienceManager();
        });
    }
    
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register middleware
        $this->app['router']->aliasMiddleware('performance.optimizer', PerformanceOptimizer::class);
        
        // Apply performance optimizer to web routes
        $this->app['router']->pushMiddlewareToGroup('web', PerformanceOptimizer::class);
        
        // Set up database connection resilience
        $this->setupDatabaseResilience();
        
        // Set up configuration monitoring
        $this->setupConfigurationMonitoring();
        
        // Set up queue monitoring
        $this->setupQueueMonitoring();
    }
    
    /**
     * Set up database connection resilience
     */
    private function setupDatabaseResilience(): void
    {
        // Override default database manager with resilient version
        $this->app->extend('db', function ($db, $app) {
            $connectionManager = $app->make(ConnectionManager::class);
            
            // Add connection testing to database manager
            $db->listen(function ($query) use ($connectionManager) {
                // Test connection before each query if needed
                if (!$connectionManager->testDatabaseConnection()) {
                    throw new \Exception('Database connection failed resilience test');
                }
            });
            
            return $db;
        });
    }
    
    /**
     * Set up configuration monitoring
     */
    private function setupConfigurationMonitoring(): void
    {
        // Monitor configuration changes
        $this->app->booted(function () {
            $configGuard = $this->app->make(ConfigurationGuard::class);
            
            // Verify configuration integrity on boot
            $integrity = $configGuard->verifyConfigurationIntegrity();
            
            $invalidConfigs = array_filter($integrity, function($config) {
                return !$config['valid'] || !$config['has_value'];
            });
            
            if (!empty($invalidConfigs)) {
                \Log::warning('Configuration integrity issues detected', [
                    'invalid_configs' => array_keys($invalidConfigs)
                ]);
            }
        });
    }
    
    /**
     * Set up queue monitoring
     */
    private function setupQueueMonitoring(): void
    {
        \Queue::failing(function (\Illuminate\Queue\Events\JobFailed $event) {
            // Log job failure and attempt recovery
            \Log::error('Job failed', [
                'job' => $event->job->getName(),
                'queue' => $event->job->getQueue(),
                'exception' => $event->exception->getMessage()
            ]);
            
            // Trigger integrity check
            $queueManager = $this->app->make(QueueIntegrityManager::class);
            $queueManager->captureQueueState();
        });
    }
}