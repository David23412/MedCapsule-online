<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    // ✅ SECURITY FIX: Reduced CSRF exclusions to only essential API endpoints
    protected $except = [
        // Only exclude external API webhooks that can't provide CSRF tokens
        'webhook/*',
        'api/external/*',
    ];

    // ✅ SECURITY FIX: Removed weak CSRF override - using Laravel's secure default implementation

    /**
     * Determine if the session and input CSRF tokens match.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function runningUnitTests()
    {
        return app()->runningInConsole() && app()->runningUnitTests();
    }
} 