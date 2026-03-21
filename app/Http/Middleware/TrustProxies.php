<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = '*';

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    /**
     * Prepare the middleware for incoming requests.
     */
    public function boot(): void
    {
        // Handle ngrok specific configurations
        if ($this->isNgrokRequest()) {
            $this->setTrustedProxyHeaderNames([
                'FORWARDED' => 'FORWARDED',
                'X_FORWARDED_FOR' => 'X_FORWARDED_FOR',
                'X_FORWARDED_HOST' => 'X_FORWARDED_HOST',
                'X_FORWARDED_PORT' => 'X_FORWARDED_PORT',
                'X_FORWARDED_PROTO' => 'X_FORWARDED_PROTO',
            ]);
        }
    }

    /**
     * Check if the current request is from ngrok
     */
    protected function isNgrokRequest(): bool
    {
        $host = request()->getHost();
        
        return str_contains($host, 'ngrok-free.dev') ||
               str_contains($host, 'ngrok-free.app') ||
               str_contains($host, 'ngrok.io') ||
               str_contains($host, 'ngrok.com');
    }

    /**
     * Get the trusted proxies.
     */
    public function proxies(): array|string|null
    {
        // For ngrok, trust all proxies
        if ($this->isNgrokRequest()) {
            return '*';
        }

        // Use environment configuration or trust all for development
        return env('TRUSTED_PROXIES', '*');
    }
}
