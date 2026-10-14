<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use App\Support\TrustedNetworkConfiguration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            // Additional route configurations can go here if needed
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Resolve proxy/host settings from Laravel's loaded configuration so
        // production config caching does not discard values from the .env file.
        $middleware->trustProxies(headers: TrustedNetworkConfiguration::trustedProxyHeaders());
        $middleware->trustHosts(
            at: static fn (): array => config('trustedproxy.hosts', []),
            subdomains: false
        );

        // Run after TrustProxies and before routing, sessions, and CSRF checks.
        $middleware->append(\App\Http\Middleware\HttpsRedirectMiddleware::class);

        $middleware->alias([
            'tutor' => \App\Http\Middleware\TutorMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'ngrok.csrf' => \App\Http\Middleware\NgrokCsrfFix::class,
            'free.course.survey' => \App\Http\Middleware\EnsureFreeCourseSurveyUnlocked::class,
            'secure.study' => \App\Http\Middleware\PreventSensitivePageCaching::class,
        ]);
        
        // Keep environment-sensitive middleware centralized in the web stack.
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\NgrokCsrfFix::class,
            \App\Http\Middleware\EnforceSessionSecurity::class,
            \App\Http\Middleware\TrackLastActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Absolute safety net: ensure TokenMismatchException (419) NEVER renders an error page.
        $exceptions->renderable(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success'  => false,
                    'error'    => 'Session Expired',
                    'message'  => 'Your session has expired. Redirecting…',
                    'code'     => 'SESSION_EXPIRED',
                    'redirect' => '/',
                ], 419);
            }

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return redirect('/');
        });
    })->create();
