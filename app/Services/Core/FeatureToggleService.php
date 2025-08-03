<?php

namespace App\Services\Core;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * Feature Toggle Service
 * 
 * Manages safe rollout of new architectural components with fallback mechanisms
 */
class FeatureToggleService
{
    /**
     * Check if event-driven grading should be used for current user
     */
    public static function shouldUseEventDrivenGrading(?int $userId = null): bool
    {
        if (!config('features.event_driven_grading', false)) {
            return false;
        }

        $userId = $userId ?? Auth::id();
        if (!$userId) {
            return false;
        }

        return self::shouldUseFeatureForUser('event_driven_grading', $userId);
    }

    /**
     * Check if smart cache manager should be used
     */
    public static function shouldUseSmartCacheManager(?int $userId = null): bool
    {
        if (!config('features.smart_cache_manager', false)) {
            return false;
        }

        $userId = $userId ?? Auth::id();
        if (!$userId) {
            return false;
        }

        return self::shouldUseFeatureForUser('smart_cache_manager', $userId);
    }

    /**
     * Check if memory efficient quiz service should be used
     */
    public static function shouldUseMemoryEfficientQuiz(int $questionCount = 0): bool
    {
        if (!config('features.memory_efficient_quiz', false)) {
            return false;
        }

        $threshold = config('features.memory_efficient_quiz_threshold', 1000);
        return $questionCount >= $threshold;
    }

    /**
     * Check if memory monitor should be applied to route
     */
    public static function shouldUseMemoryMonitor(string $routeName): bool
    {
        if (!config('features.memory_monitor', true)) {
            return false;
        }

        $monitoredRoutes = config('features.memory_monitor_routes', []);
        return in_array($routeName, $monitoredRoutes);
    }

    /**
     * Generic feature toggle logic based on rollout strategy
     */
    private static function shouldUseFeatureForUser(string $feature, int $userId): bool
    {
        $strategy = config('features.rollout_strategy', 'user_id');
        
        switch ($strategy) {
            case 'whitelist':
                $whitelist = config('features.rollout_whitelist_users', []);
                return in_array($userId, $whitelist);
                
            case 'percentage':
                $percentage = config("features.{$feature}_percentage", 10);
                return ($userId % 100) < $percentage;
                
            case 'user_id':
            default:
                $percentage = config("features.{$feature}_percentage", 10);
                return ($userId % 100) < $percentage;
        }
    }

    /**
     * Log feature usage for monitoring
     */
    public static function logFeatureUsage(string $feature, int $userId, array $context = []): void
    {
        if (!config('features.log_feature_usage', true)) {
            return;
        }

        Log::info("Feature usage: {$feature}", array_merge([
            'user_id' => $userId,
            'feature' => $feature,
            'timestamp' => now()->toISOString()
        ], $context));
    }

    /**
     * Execute with fallback mechanism
     */
    public static function executeWithFallback(
        callable $newImplementation,
        callable $fallbackImplementation,
        string $feature,
        ?int $userId = null
    ) {
        $userId = $userId ?? Auth::id();
        
        try {
            $result = $newImplementation();
            
            self::logFeatureUsage($feature, $userId, [
                'status' => 'success',
                'implementation' => 'new'
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error("New {$feature} implementation failed, using fallback", [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if (config('features.enable_fallback', true)) {
                $result = $fallbackImplementation();
                
                self::logFeatureUsage($feature, $userId, [
                    'status' => 'fallback_used',
                    'implementation' => 'old',
                    'error' => $e->getMessage()
                ]);
                
                return $result;
            }
            
            throw $e;
        }
    }

    /**
     * Get feature status for debugging
     */
    public static function getFeatureStatus(?int $userId = null): array
    {
        $userId = $userId ?? Auth::id();
        
        return [
            'user_id' => $userId,
            'event_driven_grading' => [
                'enabled' => config('features.event_driven_grading', false),
                'should_use' => self::shouldUseEventDrivenGrading($userId),
                'percentage' => config('features.event_driven_grading_percentage', 10)
            ],
            'smart_cache_manager' => [
                'enabled' => config('features.smart_cache_manager', false),
                'should_use' => self::shouldUseSmartCacheManager($userId),
                'percentage' => config('features.smart_cache_manager_percentage', 25)
            ],
            'memory_efficient_quiz' => [
                'enabled' => config('features.memory_efficient_quiz', false),
                'threshold' => config('features.memory_efficient_quiz_threshold', 1000)
            ],
            'memory_monitor' => [
                'enabled' => config('features.memory_monitor', true),
                'routes' => config('features.memory_monitor_routes', [])
            ],
            'rollout_strategy' => config('features.rollout_strategy', 'user_id'),
            'fallback_enabled' => config('features.enable_fallback', true)
        ];
    }
}