<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HandleNgrokRequests
{
    public function handle(Request $request, Closure $next)
    {
        if (
            app()->environment(['local', 'testing']) &&
            env('ENABLE_NGROK_COMPAT', false) &&
            (str_contains($request->getHost(), 'ngrok-free.app') || str_contains($request->getHost(), 'ngrok-free.dev'))
        ) {
            // Configure session for ngrok
            config([
                'session.domain' => '',  // Empty domain for ngrok
                'session.secure' => true,  // Enable secure for HTTPS ngrok
                'session.same_site' => 'none',  // Required for cross-site
                'app.url' => $request->getSchemeAndHttpHost(),
            ]);

            // Set SameSite=None for all cookies
            $response = $next($request);
            $cookies = $response->headers->getCookies();
            foreach ($cookies as $cookie) {
                $cookie->setSameSite('None');
                $cookie->setSecure(true);  // Enable secure for HTTPS ngrok
            }
            return $response;
        }

        return $next($request);
    }
} 
