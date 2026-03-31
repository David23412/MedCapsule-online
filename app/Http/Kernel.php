<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        \App\Http\Middleware\TrustProxies::class,
        \App\Http\Middleware\HandleNgrokRequests::class,
        \App\Http\Middleware\HttpsRedirectMiddleware::class,
        \Fruitcake\Cors\HandleCors::class,
        \App\Http\Middleware\ErrorBoundary::class,
        //
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\PerformanceOptimization::class,
            \App\Http\Middleware\RequestTimeoutMiddleware::class,
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\SingleSessionMiddleware::class,
            \App\Http\Middleware\TrackUserActivity::class,
            \App\Http\Middleware\PerformanceMonitoring::class,
        ],

        'api' => [
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware aliases.
     *
     * Aliases may be used instead of class names to assign middleware to routes and groups.
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \App\Http\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'admin' => \App\Http\Middleware\AdminMiddleware::class,
        'check.course.access' => \App\Http\Middleware\CheckCourseAccess::class,
        'force.https' => \App\Http\Middleware\HttpsRedirectMiddleware::class,
        'error.boundary' => \App\Http\Middleware\ErrorBoundary::class,
        'performance.monitor' => \App\Http\Middleware\PerformanceMonitoring::class,
        'request.timeout' => \App\Http\Middleware\RequestTimeoutMiddleware::class,
        'sql.injection.protection' => \App\Http\Middleware\SqlInjectionProtectionMiddleware::class,
        'tutor' => \App\Http\Middleware\TutorMiddleware::class,
        'role' => \App\Http\Middleware\RoleMiddleware::class,
        'assignment.security' => \App\Http\Middleware\AssignmentSecurityMiddleware::class,
        'assignment.access' => \App\Http\Middleware\CheckAssignmentAccess::class,
        'course.cache.buster' => \App\Http\Middleware\CourseCacheBuster::class,
        'single.session' => \App\Http\Middleware\SingleSessionMiddleware::class,
        'update.session' => \App\Http\Middleware\UpdateActiveSession::class,
        'free.course.survey' => \App\Http\Middleware\EnsureFreeCourseSurveyUnlocked::class,
    ];
}
