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
    protected $except = [
        'quiz/*/*/grade',
        'quiz/grade-random',
        'payments/*/submit-proof',
        'login',
        'register',
    ];

    /**
     * Determine if the request has a valid CSRF token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function tokensMatch($request)
    {
        // Temporarily disable CSRF for testing
        return true;
        
        // Allow ngrok domains to bypass strict CSRF checks
        if ($request->getHost() && (
            str_contains($request->getHost(), 'ngrok-free.app') ||
            str_contains($request->getHost(), 'ngrok.io') ||
            $request->getHost() === 'localhost' ||
            $request->getHost() === '127.0.0.1'
        )) {
            return true;
        }

        $token = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');

        if (!$token && $request->header('X-Requested-With') == 'XMLHttpRequest') {
            $token = $request->header('X-CSRF-TOKEN');
        }

        return is_string($token) && hash_equals($request->session()->token(), $token);
    }
} 