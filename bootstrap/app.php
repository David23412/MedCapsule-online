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
        $readEnv = static function (string $key, mixed $default = null): mixed {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

            return $value === false ? $default : $value;
        };

        $readBool = static function (string $key, bool $default = false) use ($readEnv): bool {
            $value = $readEnv($key, $default);
            $normalized = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

            return $normalized ?? $default;
        };

        $trustedProxies = TrustedNetworkConfiguration::trustedProxies(
            $readEnv('TRUSTED_PROXIES')
        );

        $trustedHosts = TrustedNetworkConfiguration::trustedHosts(
            $readEnv('TRUSTED_HOSTS', ''),
            (string) $readEnv('APP_URL', ''),
            $readBool('ENABLE_NGROK_COMPAT', false)
        );

        $middleware->trustProxies(
            at: $trustedProxies,
            headers: TrustedNetworkConfiguration::trustedProxyHeaders()
        );

        $middleware->trustHosts(at: static function () use ($trustedHosts): array {
            return TrustedNetworkConfiguration::hostPatterns($trustedHosts);
        }, subdomains: false);

        $middleware->alias([
            'tutor' => \App\Http\Middleware\TutorMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'ngrok.csrf' => \App\Http\Middleware\NgrokCsrfFix::class,
            'free.course.survey' => \App\Http\Middleware\EnsureFreeCourseSurveyUnlocked::class,
        ]);
        
        // Keep environment-sensitive middleware centralized in the web stack.
        $middleware->web(append: [
            \App\Http\Middleware\HttpsRedirectMiddleware::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\NgrokCsrfFix::class,
            \App\Http\Middleware\InjectDesignProtection::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
