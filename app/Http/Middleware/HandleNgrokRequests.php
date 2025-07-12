<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HandleNgrokRequests
{
    public function handle(Request $request, Closure $next)
    {
        if (str_contains($request->getHost(), 'ngrok-free.app')) {
            // Configure session for ngrok
            config([
                'session.domain' => '',  // Empty domain for ngrok
                'session.secure' => false,  // Disable secure for ngrok
                'session.same_site' => 'none',  // Required for cross-site
                'session.http_only' => false,  // Allow JavaScript access for ngrok
                'app.url' => $request->getSchemeAndHttpHost(),
            ]);

            // Set SameSite=None for all cookies
            $response = $next($request);
            $cookies = $response->headers->getCookies();
            foreach ($cookies as $cookie) {
                $cookie->setSameSite('None');
                $cookie->setSecure(false);  // Disable secure for ngrok
            }
            return $response;
        }

        return $next($request);
    }
} 