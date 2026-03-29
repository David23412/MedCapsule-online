<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use App\Services\PaymentService;
use App\Services\PaymentSecurityService;
use App\Services\AdvancedCacheService;
use App\Services\NavigationViewDataService;
use App\Services\PerformanceMonitorService;
use App\Models\Question;
use App\Observers\QuestionObserver;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // FIXED: Register all military-grade services
        $this->app->singleton(PaymentService::class, function ($app) {
            return new PaymentService(
                $app->make(\App\Services\SecurePaymentService::class),
                $app->make(\App\Services\PerformanceMonitorService::class),
                $app->make(PaymentSecurityService::class)
            );
        });

        $this->app->singleton(PaymentSecurityService::class, function ($app) {
            return new PaymentSecurityService();
        });

        $this->app->singleton(AdvancedCacheService::class, function ($app) {
            return new AdvancedCacheService();
        });

        $this->app->singleton(PerformanceMonitorService::class, function ($app) {
            return new PerformanceMonitorService();
        });

        // FIXED: Register existing services for compatibility
        $this->app->singleton(\App\Services\SecurePaymentService::class, function ($app) {
            return new \App\Services\SecurePaymentService();
        });


    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        $this->configureRateLimiting();

        // Register model observers (cache invalidation, etc.)
        Question::observe(QuestionObserver::class);

        // FIXED: Warm critical caches on application start
        $this->warmCriticalCaches();

        // FIXED: Set up global performance monitoring
        $this->setupGlobalPerformanceMonitoring();

        View::composer([
            'partials.navigation',
            'components.notification-bell',
            'partials.profile-picture-upload',
            'components.responsive-navigation',
        ], function ($view): void {
            // Use request attributes as a true per-request store (safe for both FPM and Octane).
            $req = request();
            if (!$req->attributes->has('_nav_shared_data')) {
                $req->attributes->set('_nav_shared_data', app(NavigationViewDataService::class)->getSharedData(auth()->user()));
            }
            $view->with($req->attributes->get('_nav_shared_data'));
        });
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            $email = Str::lower((string) $request->input('email'));

            return [
                Limit::perMinute(5)->by($email.'|'.$request->ip()),
                Limit::perHour(25)->by($request->ip()),
            ];
        });

        RateLimiter::for('register', function (Request $request) {
            return [
                Limit::perMinute(3)->by($request->ip()),
                Limit::perHour(10)->by($request->ip()),
            ];
        });

        RateLimiter::for('public-status', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('video-tokens', function (Request $request) {
            return Limit::perMinute(20)->by((string) ($request->user()?->id ?: $request->ip()));
        });

        RateLimiter::for('random-quiz', function (Request $request) {
            return Limit::perMinute(10)->by((string) ($request->user()?->id ?: $request->ip()));
        });

        RateLimiter::for('random-quiz-grading', function (Request $request) {
            return Limit::perMinute(30)->by((string) ($request->user()?->id ?: $request->ip()));
        });
    }

    /**
     * FIXED: Initialize performance monitoring system
     */
    private function initializePerformanceMonitoring(): void
    {
        try {
            $performanceService = app(PerformanceMonitorService::class);

            // Start system monitoring
            $operationId = $performanceService->startOperation('system_boot', [
                'timestamp' => now()->toISOString(),
                'environment' => app()->environment()
            ]);

            // Record system boot metrics
            $metrics = [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'database_connection' => config('database.default'),
                'cache_driver' => config('cache.default'),
                'session_driver' => config('session.driver')
            ];

            $performanceService->endOperation($operationId, $metrics);

        } catch (\Exception $e) {
            \Log::error('Failed to initialize performance monitoring', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * FIXED: Warm critical caches on application start
     */
    private function warmCriticalCaches(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        try {
            $cacheService = app(AdvancedCacheService::class);

            try {
                if (!Cache::add('critical_caches:warming', true, now()->addMinutes(10))) {
                    return;
                }
            } catch (\Throwable $e) {
                \Log::warning('Failed to acquire critical cache warming lock', [
                    'error' => $e->getMessage(),
                ]);

                return;
            }

            $this->app->terminating(function () use ($cacheService) {
                try {
                    $cacheService->warmCriticalCaches();
                } catch (\Throwable $e) {
                    \Log::warning('Deferred critical cache warming failed', [
                        'error' => $e->getMessage(),
                    ]);
                } finally {
                    try {
                        Cache::forget('critical_caches:warming');
                    } catch (\Throwable $e) {
                        \Log::warning('Failed to release critical cache warming lock', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        } catch (\Exception $e) {
            \Log::error('Failed to initialize cache warming', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * FIXED: Set up global performance monitoring hooks
     */
    private function setupGlobalPerformanceMonitoring(): void
    {
        // FIXED: Monitor database queries globally
        \DB::listen(function ($query) {
            if ($query->time <= 1000) { // Only process slow queries (>1 second)
                return;
            }

            $performanceService = app(PerformanceMonitorService::class);
            $operationId = $performanceService->startOperation('slow_query', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'connection' => $query->connectionName
            ]);

            $performanceService->endOperation($operationId, [
                'execution_time_ms' => $query->time,
                'query_type' => $this->getQueryType($query->sql)
            ]);
        });

        // FIXED: Monitor HTTP requests globally
        if (app()->runningInConsole() === false) {
            $this->app['events']->listen('kernel.handling', function () {
                $performanceService = app(PerformanceMonitorService::class);
                $request = request();

                $operationId = $performanceService->startOperation('http_request', [
                    'method' => $request->method(),
                    'url' => $request->url(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'route' => $request->route() ? $request->route()->getName() : 'unknown'
                ]);

                // Store operation ID in request for later retrieval
                $request->merge(['_performance_operation_id' => $operationId]);
            });

            $this->app['events']->listen('kernel.terminating', function () {
                try {
                    $performanceService = app(PerformanceMonitorService::class);
                    $request = request();

                    if ($request->has('_performance_operation_id')) {
                        $operationId = $request->get('_performance_operation_id');

                        $performanceService->endOperation($operationId, [
                            'response_code' => http_response_code(),
                            'response_size' => strlen(ob_get_contents() ?: ''),
                            'user_id' => auth()->id()
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to end HTTP request monitoring', [
                        'error' => $e->getMessage()
                    ]);
                }
            });
        }

        // FIXED: Monitor queue jobs
        $this->app['events']->listen('Illuminate\Queue\Events\JobProcessing', function ($event) {
            $performanceService = app(PerformanceMonitorService::class);

            $operationId = $performanceService->startOperation('queue_job', [
                'job' => $event->job->getName(),
                'queue' => $event->job->getQueue(),
                'connection' => $event->connectionName,
                'attempts' => $event->job->attempts()
            ]);

            // Store operation ID in job for completion tracking
            $event->job->_performance_operation_id = $operationId;
        });

        $this->app['events']->listen('Illuminate\Queue\Events\JobProcessed', function ($event) {
            try {
                $performanceService = app(PerformanceMonitorService::class);

                if (isset($event->job->_performance_operation_id)) {
                    $operationId = $event->job->_performance_operation_id;

                    $performanceService->endOperation($operationId, [
                        'status' => 'completed',
                        'execution_time' => method_exists($event->job, 'getExecutionTime')
                            ? $event->job->getExecutionTime()
                            : null,
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to end queue job monitoring', [
                    'error' => $e->getMessage()
                ]);
            }
        });
    }

    /**
     * FIXED: Get query type from SQL
     */
    private function getQueryType(string $sql): string
    {
        $sql = strtoupper(trim($sql));

        if (str_starts_with($sql, 'SELECT')) {
            return 'SELECT';
        } elseif (str_starts_with($sql, 'INSERT')) {
            return 'INSERT';
        } elseif (str_starts_with($sql, 'UPDATE')) {
            return 'UPDATE';
        } elseif (str_starts_with($sql, 'DELETE')) {
            return 'DELETE';
        } else {
            return 'OTHER';
        }
    }
}
