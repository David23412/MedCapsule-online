<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\HealthCheckService;
use App\Services\EmergencyFallbackService;
use App\Services\DatabaseService;
use App\Services\ResilientCacheService;
use Exception;

class SystemStatusController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Display comprehensive system status dashboard
     */
    public function index()
    {
        $startTime = microtime(true);
        $requestId = uniqid('status_');
        
        try {
            Log::info('System status dashboard accessed', [
                'request_id' => $requestId,
                'admin_id' => auth()->id(),
                'ip' => request()->ip()
            ]);

            // Gather all system information
            $data = [
                'health_check' => HealthCheckService::performHealthCheck(),
                'emergency_status' => EmergencyFallbackService::getSystemStatusMessage(),
                'emergency_stats' => EmergencyFallbackService::getEmergencyStatistics(),
                'system_metrics' => HealthCheckService::getSystemMetrics(),
                'readiness' => HealthCheckService::getReadinessStatus(),
                'database_stats' => $this->getDatabaseStatistics(),
                'cache_stats' => ResilientCacheService::getStatistics(),
                'error_statistics' => $this->getErrorStatistics(),
                'performance_metrics' => $this->getPerformanceMetrics(),
                'request_id' => $requestId,
                'timestamp' => now()
            ];

            $executionTime = (microtime(true) - $startTime) * 1000;
            
            Log::info('System status dashboard loaded', [
                'request_id' => $requestId,
                'execution_time_ms' => round($executionTime, 2),
                'overall_status' => $data['health_check']['overall_status']
            ]);

            return view('admin.system-status', $data);

        } catch (Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            Log::error('System status dashboard failed', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'execution_time_ms' => round($executionTime, 2)
            ]);

            // Emergency fallback for admin dashboard
            return view('admin.system-status-fallback', [
                'error' => 'System status temporarily unavailable',
                'timestamp' => now(),
                'request_id' => $requestId
            ]);
        }
    }

    /**
     * API endpoint for real-time status updates
     */
    public function status(Request $request)
    {
        try {
            $quick = $request->get('quick', false);
            
            if ($quick) {
                return response()->json([
                    'status' => 'healthy',
                    'quick_check' => HealthCheckService::quickHealthCheck(),
                    'emergency_active' => EmergencyFallbackService::isEmergencyModeActive(),
                    'timestamp' => now()
                ]);
            }

            return response()->json([
                'health' => HealthCheckService::performHealthCheck(),
                'emergency' => EmergencyFallbackService::getSystemStatusMessage(),
                'metrics' => HealthCheckService::getSystemMetrics(),
                'timestamp' => now()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'error' => 'Status check failed',
                'timestamp' => now()
            ], 500);
        }
    }

    /**
     * Force activate emergency mode
     */
    public function activateEmergency(Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:255'
        ]);

        $result = EmergencyFallbackService::activateEmergencyMode($request->reason);

        Log::warning('Emergency mode manually activated', [
            'admin_id' => auth()->id(),
            'reason' => $request->reason,
            'result' => $result
        ]);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Emergency mode activated' : 'Failed to activate emergency mode'
        ]);
    }

    /**
     * Deactivate emergency mode
     */
    public function deactivateEmergency()
    {
        $result = EmergencyFallbackService::deactivateEmergencyMode();

        Log::info('Emergency mode manually deactivated', [
            'admin_id' => auth()->id(),
            'result' => $result
        ]);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Emergency mode deactivated' : 'Failed to deactivate emergency mode'
        ]);
    }

    /**
     * Clear system caches
     */
    public function clearCaches()
    {
        try {
            $results = [
                'application_cache' => ResilientCacheService::safeFlush(),
                'config_cache' => $this->clearConfigCache(),
                'view_cache' => $this->clearViewCache(),
                'route_cache' => $this->clearRouteCache()
            ];

            Log::info('System caches cleared', [
                'admin_id' => auth()->id(),
                'results' => $results
            ]);

            return response()->json([
                'success' => true,
                'results' => $results,
                'message' => 'Caches cleared successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Cache clearing failed', [
                'admin_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to clear caches'
            ], 500);
        }
    }

    /**
     * Run full health check
     */
    public function healthCheck()
    {
        try {
            $health = HealthCheckService::performHealthCheck();
            
            return response()->json([
                'success' => true,
                'health' => $health,
                'timestamp' => now()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Health check failed',
                'timestamp' => now()
            ], 500);
        }
    }

    /**
     * Get database statistics
     */
    private function getDatabaseStatistics(): array
    {
        try {
            return [
                'connection_status' => DatabaseService::healthCheck(),
                'table_counts' => [
                    'users' => DatabaseService::safeCount('users', [], 0),
                    'courses' => DatabaseService::safeCount('courses', [], 0),
                    'topics' => DatabaseService::safeCount('topics', [], 0),
                    'questions' => DatabaseService::safeCount('questions', [], 0),
                    'quiz_attempts' => DatabaseService::safeCount('quiz_attempts', [], 0),
                    'payments' => DatabaseService::safeCount('payments', [], 0)
                ],
                'recent_activity' => [
                    'quiz_attempts_24h' => DatabaseService::safeCount('quiz_attempts', [
                        'created_at', '>=', now()->subDay()
                    ], 0),
                    'new_users_24h' => DatabaseService::safeCount('users', [
                        'created_at', '>=', now()->subDay()
                    ], 0),
                    'payments_24h' => DatabaseService::safeCount('payments', [
                        'created_at', '>=', now()->subDay()
                    ], 0)
                ]
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get error statistics from logs
     */
    private function getErrorStatistics(): array
    {
        try {
            $errorStats = [];
            $hourlyKeys = [];
            
            // Get error stats for last 24 hours
            for ($i = 23; $i >= 0; $i--) {
                $hour = now()->subHours($i)->format('Y-m-d-H');
                $key = "error_stats_{$hour}";
                $hourlyKeys[] = $hour;
                
                $stats = ResilientCacheService::safeGet($key, [
                    'total_errors' => 0,
                    'error_types' => [],
                    'routes' => []
                ]);
                
                $errorStats[$hour] = $stats;
            }
            
            return [
                'hourly_stats' => $errorStats,
                'total_24h' => array_sum(array_column($errorStats, 'total_errors')),
                'last_updated' => now()
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics(): array
    {
        try {
            $performanceKeys = [];
            $performanceStats = [];
            
            // Get performance data for last 6 hours
            for ($i = 5; $i >= 0; $i--) {
                $hour = now()->subHours($i)->format('Y-m-d-H');
                $key = "performance_stats_{$hour}";
                
                $stats = ResilientCacheService::safeGet($key, [
                    'average_response_time' => 0,
                    'slow_queries' => 0,
                    'memory_usage' => 0,
                    'request_count' => 0
                ]);
                
                $performanceStats[$hour] = $stats;
            }
            
            return [
                'hourly_performance' => $performanceStats,
                'current_memory' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'peak_memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'last_updated' => now()
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Clear configuration cache
     */
    private function clearConfigCache(): bool
    {
        try {
            \Artisan::call('config:clear');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Clear view cache
     */
    private function clearViewCache(): bool
    {
        try {
            \Artisan::call('view:clear');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Clear route cache
     */
    private function clearRouteCache(): bool
    {
        try {
            \Artisan::call('route:clear');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * System maintenance toggle
     */
    public function toggleMaintenance(Request $request)
    {
        try {
            $enable = $request->boolean('enable');
            $message = $request->input('message', 'System maintenance in progress');
            
            if ($enable) {
                \Artisan::call('down', [
                    '--message' => $message,
                    '--retry' => 60
                ]);
                $result = 'enabled';
            } else {
                \Artisan::call('up');
                $result = 'disabled';
            }

            Log::info("Maintenance mode {$result}", [
                'admin_id' => auth()->id(),
                'message' => $message
            ]);

            return response()->json([
                'success' => true,
                'status' => $result,
                'message' => "Maintenance mode {$result}"
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to toggle maintenance mode'
            ], 500);
        }
    }

    /**
     * Export system logs
     */
    public function exportLogs(Request $request)
    {
        try {
            $type = $request->input('type', 'all');
            $hours = $request->input('hours', 24);
            
            $logData = [
                'export_info' => [
                    'type' => $type,
                    'hours' => $hours,
                    'exported_at' => now(),
                    'exported_by' => auth()->user()->name
                ],
                'system_status' => HealthCheckService::performHealthCheck(),
                'emergency_history' => EmergencyFallbackService::getEmergencyStatistics(),
                'database_stats' => $this->getDatabaseStatistics(),
                'error_stats' => $this->getErrorStatistics(),
                'performance_stats' => $this->getPerformanceMetrics()
            ];

            $filename = "system_logs_{$type}_" . now()->format('Y-m-d_H-i-s') . '.json';
            
            Log::info('System logs exported', [
                'admin_id' => auth()->id(),
                'type' => $type,
                'hours' => $hours,
                'filename' => $filename
            ]);

            return response()->json($logData, 200, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => "attachment; filename=\"{$filename}\""
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to export logs'
            ], 500);
        }
    }
}