<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\EnvironmentService;
use Illuminate\Http\Request;

class EnvironmentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the EnvironmentService as a singleton
        $this->app->singleton(EnvironmentService::class, function ($app) {
            return new EnvironmentService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configure environment on every request
        $this->app->resolving('request', function (Request $request) {
            EnvironmentService::configureEnvironment($request);
        });
        
        // Make environment config available to views
        view()->composer('*', function ($view) {
            $request = request();
            $environmentConfig = EnvironmentService::getEnvironmentConfig($request);
            
            $view->with('environmentConfig', $environmentConfig);
        });
        
        // Add environment detection to the IoC container
        $this->app->bind('environment.config', function () {
            return EnvironmentService::getEnvironmentConfig();
        });
    }
}