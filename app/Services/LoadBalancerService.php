<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LoadBalancerService
{
    /**
     * Load balancer configuration
     */
    const LOAD_BALANCER_CONFIG = [
        'health_check_interval' => 30, // seconds
        'max_connections_per_server' => 1000,
        'connection_timeout' => 10, // seconds
        'retry_attempts' => 3,
        'circuit_breaker_threshold' => 5, // failures before circuit opens
        'circuit_breaker_timeout' => 60, // seconds
    ];

    /**
     * Server pool configuration
     */
    const SERVER_POOLS = [
        'web' => [
            'servers' => [
                ['host' => 'web1.local', 'port' => 80, 'weight' => 100],
                ['host' => 'web2.local', 'port' => 80, 'weight' => 100],
                ['host' => 'web3.local', 'port' => 80, 'weight' => 100],
            ],
            'algorithm' => 'round_robin'
        ],
        'api' => [
            'servers' => [
                ['host' => 'api1.local', 'port' => 80, 'weight' => 100],
                ['host' => 'api2.local', 'port' => 80, 'weight' => 100],
            ],
            'algorithm' => 'least_connections'
        ],
        'database' => [
            'servers' => [
                ['host' => 'db1.local', 'port' => 3306, 'weight' => 100],
                ['host' => 'db2.local', 'port' => 3306, 'weight' => 100],
                ['host' => 'db3.local', 'port' => 3306, 'weight' => 100],
            ],
            'algorithm' => 'round_robin'
        ]
    ];

    /**
     * Auto-scaling configuration
     */
    const AUTO_SCALING_CONFIG = [
        'cpu_threshold' => 70, // percentage
        'memory_threshold' => 80, // percentage
        'response_time_threshold' => 2000, // milliseconds
        'min_instances' => 2,
        'max_instances' => 10,
        'scale_up_cooldown' => 300, // seconds
        'scale_down_cooldown' => 600, // seconds
    ];
    /**
     * Get next available server using load balancing algorithm
     */
    public static function getNextServer(string $pool = 'web'): array
    {
        if (!isset(self::SERVER_POOLS[$pool])) {
            throw new \InvalidArgumentException("Server pool '{$pool}' not found");
        }

        $servers = self::SERVER_POOLS[$pool]['servers'];
        $algorithm = self::SERVER_POOLS[$pool]['algorithm'];

        // Filter healthy servers
        $healthyServers = self::getHealthyServers($servers);

        if (empty($healthyServers)) {
            throw new \RuntimeException("No healthy servers available in pool '{$pool}'");
        }

        return match ($algorithm) {
            'round_robin' => self::getRoundRobinServer($healthyServers, $pool),
            'least_connections' => self::getLeastConnectionsServer($healthyServers),
            default => self::getRoundRobinServer($healthyServers, $pool),
        };
    }

    /**
     * Get healthy servers from the pool
     */
    private static function getHealthyServers(array $servers): array
    {
        $healthyServers = [];

        foreach ($servers as $server) {
            if (self::isServerHealthy($server)) {
                $healthyServers[] = $server;
            }
        }

        return $healthyServers;
    }

    /**
     * Check if a server is healthy
     */
    private static function isServerHealthy(array $server): bool
    {
        $cacheKey = "server_health:{$server['host']}:{$server['port']}";

        return Cache::remember($cacheKey, self::LOAD_BALANCER_CONFIG['health_check_interval'], function () use ($server) {
            try {
                $response = Http::timeout(self::LOAD_BALANCER_CONFIG['connection_timeout'])
                    ->get("http://{$server['host']}:{$server['port']}/health");

                return $response->successful();
            } catch (\Exception $e) {
                Log::warning("Health check failed for server {$server['host']}:{$server['port']}", [
                    'error' => $e->getMessage()
                ]);
                return false;
            }
        });
    }

    /**
     * Get server using round-robin algorithm
     */
    private static function getRoundRobinServer(array $servers, string $pool): array
    {
        $cacheKey = "round_robin_index:{$pool}";
        $currentIndex = Cache::get($cacheKey, 0);

        $server = $servers[$currentIndex];
        $nextIndex = ($currentIndex + 1) % count($servers);

        Cache::put($cacheKey, $nextIndex, 3600); // Cache for 1 hour

        return $server;
    }

    /**
     * Get server with least connections
     */
    private static function getLeastConnectionsServer(array $servers): array
    {
        $serverConnections = [];

        foreach ($servers as $server) {
            $connections = self::getServerConnectionCount($server);
            $serverConnections[] = [
                'server' => $server,
                'connections' => $connections
            ];
        }

        // Sort by connection count ascending
        usort($serverConnections, function ($a, $b) {
            return $a['connections'] <=> $b['connections'];
        });

        return $serverConnections[0]['server'];
    }

    /**
     * Get connection count for a server
     */
    private static function getServerConnectionCount(array $server): int
    {
        $cacheKey = "server_connections:{$server['host']}:{$server['port']}";

        return Cache::remember($cacheKey, 30, function () use ($server) {
            // In a real implementation, this would query the load balancer
            // or use a monitoring system to get actual connection counts
            return rand(0, self::LOAD_BALANCER_CONFIG['max_connections_per_server']);
        });
    }

    /**
     * Auto-scale servers based on load
     */
    public static function autoScale(string $pool): array
    {
        $metrics = self::getPoolMetrics($pool);
        $currentInstances = count(self::SERVER_POOLS[$pool]['servers']);

        $scaleAction = [
            'action' => 'none',
            'reason' => 'Load within acceptable range',
            'target_instances' => $currentInstances
        ];

        // Scale up conditions
        if ($metrics['cpu_usage'] > self::AUTO_SCALING_CONFIG['cpu_threshold'] ||
            $metrics['memory_usage'] > self::AUTO_SCALING_CONFIG['memory_threshold'] ||
            $metrics['response_time'] > self::AUTO_SCALING_CONFIG['response_time_threshold']) {

            if ($currentInstances < self::AUTO_SCALING_CONFIG['max_instances']) {
                $scaleAction = [
                    'action' => 'scale_up',
                    'reason' => 'High resource usage detected',
                    'target_instances' => min($currentInstances + 1, self::AUTO_SCALING_CONFIG['max_instances'])
                ];
            }
        }

        // Scale down conditions
        elseif ($metrics['cpu_usage'] < self::AUTO_SCALING_CONFIG['cpu_threshold'] * 0.5 &&
                $metrics['memory_usage'] < self::AUTO_SCALING_CONFIG['memory_threshold'] * 0.5 &&
                $currentInstances > self::AUTO_SCALING_CONFIG['min_instances']) {

            $scaleAction = [
                'action' => 'scale_down',
                'reason' => 'Low resource usage detected',
                'target_instances' => max($currentInstances - 1, self::AUTO_SCALING_CONFIG['min_instances'])
            ];
        }

        // Log scaling decision
        Log::info("Auto-scaling decision for pool '{$pool}'", $scaleAction);

        return $scaleAction;
    }

    /**
     * Get pool metrics for auto-scaling
     */
    private static function getPoolMetrics(string $pool): array
    {
        $cacheKey = "pool_metrics:{$pool}";

        return Cache::remember($cacheKey, 60, function () use ($pool) {
            // In a real implementation, this would gather metrics from monitoring systems
            return [
                'cpu_usage' => rand(10, 90),
                'memory_usage' => rand(20, 95),
                'response_time' => rand(100, 3000),
                'active_connections' => rand(10, 1000)
            ];
        });
    }

    /**
     * Circuit breaker for server protection
     */
    public static function executeWithCircuitBreaker(callable $callback, string $serverKey): mixed
    {
        $failureKey = "circuit_breaker_failures:{$serverKey}";
        $stateKey = "circuit_breaker_state:{$serverKey}";

        $state = Cache::get($stateKey, 'closed');
        $failures = Cache::get($failureKey, 0);

        // If circuit is open, check if timeout has passed
        if ($state === 'open') {
            $lastFailure = Cache::get("circuit_breaker_last_failure:{$serverKey}");
            if ($lastFailure && (time() - $lastFailure) > self::LOAD_BALANCER_CONFIG['circuit_breaker_timeout']) {
                $state = 'half_open';
                Cache::put($stateKey, $state, 3600);
            } else {
                throw new \RuntimeException("Circuit breaker is open for server {$serverKey}");
            }
        }

        try {
            $result = $callback();

            // Success - reset failures if in half-open state
            if ($state === 'half_open') {
                Cache::put($failureKey, 0, 3600);
                Cache::put($stateKey, 'closed', 3600);
            }

            return $result;

        } catch (\Exception $e) {
            $failures++;

            if ($failures >= self::LOAD_BALANCER_CONFIG['circuit_breaker_threshold']) {
                Cache::put($stateKey, 'open', 3600);
                Cache::put("circuit_breaker_last_failure:{$serverKey}", time(), 3600);
            }

            Cache::put($failureKey, $failures, 3600);

            throw $e;
        }
    }

    /**
     * Get load balancer statistics
     */
    public static function getStatistics(string $pool = null): array
    {
        if ($pool) {
            return [
                'pool' => $pool,
                'total_servers' => count(self::SERVER_POOLS[$pool]['servers'] ?? []),
                'healthy_servers' => count(self::getHealthyServers(self::SERVER_POOLS[$pool]['servers'] ?? [])),
                'algorithm' => self::SERVER_POOLS[$pool]['algorithm'] ?? 'unknown',
                'metrics' => self::getPoolMetrics($pool)
            ];
        }

        $stats = [];
        foreach (self::SERVER_POOLS as $poolName => $poolConfig) {
            $stats[$poolName] = self::getStatistics($poolName);
        }

        return $stats;
    }
}