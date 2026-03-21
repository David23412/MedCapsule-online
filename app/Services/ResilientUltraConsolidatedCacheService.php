<?php

namespace App\Services;

use App\Services\Core\UltraConsolidatedCacheService;
use Illuminate\Support\Facades\Cache;

class ResilientUltraConsolidatedCacheService
{
    public static function safeRemember(
        string $key,
        int $ttl,
        callable $callback,
        mixed $fallback = null,
        array $context = []
    ): mixed {
        return UltraConsolidatedCacheService::safeRemember($key, $ttl, $callback, $fallback, $context);
    }

    public static function safeGet(string $key, mixed $default = null): mixed
    {
        return UltraConsolidatedCacheService::safeGet($key, $default);
    }

    public static function safePut(string $key, mixed $value, int $ttl = 3600): bool
    {
        return UltraConsolidatedCacheService::safePut($key, $value, $ttl);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::safeGet($key, $default);
    }

    public static function healthCheck(): array
    {
        $startedAt = microtime(true);
        $probeKey = 'cache_health_' . md5((string) microtime(true));

        try {
            $writeOk = self::safePut($probeKey, 'ok', 60);
            $readOk = self::safeGet($probeKey) === 'ok';
            Cache::forget($probeKey);

            return [
                'status' => ($writeOk && $readOk) ? 'healthy' : 'degraded',
                'response_time_ms' => round((microtime(true) - $startedAt) * 1000, 2),
                'write' => $writeOk,
                'read' => $readOk,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'response_time_ms' => round((microtime(true) - $startedAt) * 1000, 2),
            ];
        }
    }

    public static function getStatistics(): array
    {
        return app(UltraConsolidatedCacheService::class)->getCacheStats();
    }
}
