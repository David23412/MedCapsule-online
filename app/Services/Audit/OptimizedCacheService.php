<?php

namespace App\Services\Audit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class OptimizedCacheService
{
    public static function remember($key, $ttl, $callback)
    {
        return Cache::remember($key, $ttl, $callback);
    }
    
    public static function rememberForever($key, $callback)
    {
        return Cache::rememberForever($key, $callback);
    }
    
    public static function tags($tags)
    {
        return Cache::tags($tags);
    }
    
    public static function flush($tags = null)
    {
        if ($tags) {
            Cache::tags($tags)->flush();
        } else {
            Cache::flush();
        }
    }
    
    public static function optimizedGet($key, $default = null)
    {
        try {
            return Redis::get($key) ?? $default;
        } catch (\Exception $e) {
            return Cache::get($key, $default);
        }
    }
}