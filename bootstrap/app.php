<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

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
        $middleware->alias([
            'tutor' => \App\Http\Middleware\TutorMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'ngrok.csrf' => \App\Http\Middleware\NgrokCsrfFix::class,
            'free.course.survey' => \App\Http\Middleware\EnsureFreeCourseSurveyUnlocked::class,
        ]);
        
        // Add ngrok CSRF fix to web middleware group
        $middleware->web(append: [
            \App\Http\Middleware\NgrokCsrfFix::class,
            \App\Http\Middleware\InjectDesignProtection::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
