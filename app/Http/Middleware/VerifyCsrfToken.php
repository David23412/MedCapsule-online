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
        'quiz/*',
        'payments/*/submit-proof',
        'payments/submit-proof-course/*',
        'topic-locking/*',
        'topic-locking/*/*',
        'topic-locking/*/*/*',
        'admin/payments/*/verify',
        'admin/payments/bulk-verify',
        'test/payment-verify/*',
        'test-payment-verify/*',
        'manage-access/*',
        'manage-access/enroll',
        'manage-access/revoke',
        'manage-access/toggle-admin',
        'manage-access/payment-settings',
        'quiz/*/grade',
        'quiz/grade'
    ];

    /**
     * Determine if the request has a valid CSRF token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    /**
     * Determine if the request has a valid CSRF token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function tokensMatch($request)
    {
        // Completely bypass CSRF for manage-access routes
        if (str_contains($request->path(), 'manage-access')) {
            return true;
        }
        
        // For ngrok development, be VERY lenient with CSRF (especially for Edge)
        if (str_contains($request->getHost(), 'ngrok') || str_contains($request->getHost(), 'ngrok-free.app')) {
            // Try multiple token sources
            $token = $request->input('_token') 
                ?: $request->header('X-CSRF-TOKEN')
                ?: $request->header('X-XSRF-TOKEN')
                ?: $request->cookie('XSRF-TOKEN');
            
            if (!$token) {
                // For ngrok, if no token found, just regenerate and accept
                $request->session()->regenerateToken();
                return true;
            }
            
            // More lenient token comparison for ngrok
            $sessionToken = $request->session()->token();
            if (!$sessionToken) {
                // If no session token, regenerate and accept
                $request->session()->regenerateToken();
                return true;
            }
            
            // For Edge compatibility, be more lenient
            if (str_contains($request->header('User-Agent', ''), 'Edge') || 
                str_contains($request->header('User-Agent', ''), 'Edg/')) {
                // Edge is being difficult, just accept if we have any token
                return !empty($token);
            }
            
            return is_string($token) && hash_equals($sessionToken, $token);
        }

        // Standard CSRF verification for non-ngrok requests
        $token = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');

        if (!$token && $request->header('X-Requested-With') == 'XMLHttpRequest') {
            $token = $request->header('X-XSRF-TOKEN');
        }

        if (!$token) {
            $token = $request->header('X-CSRF-TOKEN');
        }

        return is_string($token) && hash_equals($request->session()->token(), $token);
    }

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