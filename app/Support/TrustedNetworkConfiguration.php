<?php

namespace App\Support;

use Illuminate\Http\Request;

final class TrustedNetworkConfiguration
{
    /**
     * Normalize configured proxies into the shape expected by Laravel.
     *
     * Wildcard values are collapsed to trusting only the immediate peer,
     * which avoids the more ambiguous "trust everything" configuration.
     *
     * @return array<int, string>|string|null
     */
    public static function trustedProxies(mixed $configuredProxies): array|string|null
    {
        $value = is_string($configuredProxies) ? trim($configuredProxies) : $configuredProxies;

        if ($value === null || $value === '') {
            return null;
        }

        if ($value === '*' || $value === '**') {
            return 'REMOTE_ADDR';
        }

        if (is_array($value)) {
            $proxies = array_values(array_filter(array_map(
                static fn (mixed $proxy): string => trim((string) $proxy),
                $value
            )));

            return $proxies === [] ? null : $proxies;
        }

        $proxies = array_values(array_filter(array_map(
            static fn (string $proxy): string => trim($proxy),
            explode(',', (string) $value)
        )));

        return $proxies === [] ? null : $proxies;
    }

    public static function trustedProxyHeaders(): int
    {
        // Do not trust forwarded host/prefix headers unless explicitly needed.
        return Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO |
            Request::HEADER_X_FORWARDED_AWS_ELB;
    }

    /**
     * @return array<int, string>
     */
    public static function trustedHosts(
        mixed $configuredHosts,
        ?string $appUrl,
        bool $enableNgrokCompatibility = false
    ): array {
        $hosts = array_values(array_filter(array_map(
            static fn (string $host): string => trim($host),
            explode(',', (string) ($configuredHosts ?? ''))
        )));

        $appHost = parse_url((string) $appUrl, PHP_URL_HOST);
        if (is_string($appHost) && $appHost !== '') {
            $hosts[] = $appHost;
        }

        if ($enableNgrokCompatibility) {
            $hosts = array_merge($hosts, [
                '*.ngrok-free.dev',
                '*.ngrok-free.app',
                '*.ngrok.io',
            ]);
        }

        return array_values(array_unique(array_filter($hosts)));
    }

    /**
     * @param array<int, string> $hosts
     * @return array<int, string>
     */
    public static function hostPatterns(array $hosts): array
    {
        return array_values(array_filter(array_map(
            static function (string $host): string {
                $host = trim($host);

                if ($host === '') {
                    return '';
                }

                if (str_starts_with($host, '^')) {
                    return $host;
                }

                return '^'.str_replace('\*', '.*', preg_quote($host, '/')).'$';
            },
            $hosts
        )));
    }
}
