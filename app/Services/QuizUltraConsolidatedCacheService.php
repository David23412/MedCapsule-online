<?php

namespace App\Services;

use App\Services\Core\UltraConsolidatedCacheService;
use Illuminate\Support\Facades\Cache;

class QuizUltraConsolidatedCacheService
{
    public function __construct(
        private UltraConsolidatedCacheService $cacheService
    ) {
    }

    public function __call(string $method, array $arguments): mixed
    {
        return $this->cacheService->{$method}(...$arguments);
    }

    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        return app(UltraConsolidatedCacheService::class)->remember($key, $callback, $ttl);
    }

    public static function generateCacheKey(string $prefix, array $data): string
    {
        return UltraConsolidatedCacheService::generateCacheKey($prefix, $data);
    }

    public static function storeQuizData(string $key, mixed $data, ?int $ttl = null): bool
    {
        if (is_resource($data)) {
            return false;
        }

        return UltraConsolidatedCacheService::safePut(
            $key,
            $data,
            $ttl ?? self::selectOptimalTTL($data)
        );
    }

    public static function getQuizData(string $key, ?callable $fallback = null, ?int $ttl = null): mixed
    {
        // Single cache read — eliminates the prior Cache::has() + safeGet() double-read.
        $cached = UltraConsolidatedCacheService::safeGet($key);

        if ($cached !== null) {
            return $cached;
        }

        if ($fallback === null) {
            return null;
        }

        $data = $fallback();
        self::storeQuizData($key, $data, $ttl);

        return $data;
    }

    public static function warmupCache(array $topicIds, int $userId): void
    {
        foreach ($topicIds as $topicId) {
            $warmingKey = self::generateCacheKey('quiz_options', [
                'topic_id' => $topicId,
                'user_id' => $userId,
            ]) . '_warming';

            Cache::put($warmingKey, true, 300);
        }
    }

    public static function clearTopicCache(int $topicId, ?int $userId = null): void
    {
        $cache = app(UltraConsolidatedCacheService::class);
        $cache->clearTopicCaches($topicId, $userId);
        $cache->clearQuizCaches($topicId, $userId);
    }

    public static function getCacheStats(): array
    {
        return app(UltraConsolidatedCacheService::class)->getCacheStats();
    }

    private static function selectOptimalTTL(mixed $data): int
    {
        $size = is_array($data) ? count($data, COUNT_RECURSIVE) : 1;

        // Small payloads get a shorter TTL to stay fresh; everything else uses 15 min.
        return $size <= 10 ? 300 : 900;
    }
}
