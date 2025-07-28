<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

/**
 * REAL-TIME MODEL STRESS TEST MONITOR
 * 
 * Monitor model performance in real-time during stress testing
 */
class ModelStressMonitor extends Command
{
    protected $signature = 'stress:monitor 
                           {--refresh=1 : Refresh interval in seconds}
                           {--models=* : Specific models to monitor}';

    protected $description = '📊 Real-time monitoring of model stress testing';

    private array $performanceHistory = [];
    private int $refreshInterval;

    public function handle(): int
    {
        $this->refreshInterval = (int) $this->option('refresh');
        
        $this->displayHeader();
        
        while (true) {
            $this->clearScreen();
            $this->displayHeader();
            $this->displaySystemMetrics();
            $this->displayModelMetrics();
            $this->displayRecentLogs();
            
            sleep($this->refreshInterval);
        }
        
        return 0;
    }

    /**
     * Display monitor header
     */
    private function displayHeader(): void
    {
        $this->line('╔══════════════════════════════════════════════════════════════╗');
        $this->line('║                📊 MODEL STRESS TEST MONITOR 📊               ║');
        $this->line('║                    Real-time Performance Dashboard          ║');
        $this->line('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();
        $this->info('Last Updated: ' . now()->format('Y-m-d H:i:s'));
        $this->newLine();
    }

    /**
     * Display system metrics
     */
    private function displaySystemMetrics(): void
    {
        $this->line('🖥️  SYSTEM METRICS');
        $this->line('─────────────────');
        
        // Memory usage
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = ini_get('memory_limit');
        
        $this->line('Memory Usage: ' . $this->formatBytes($memoryUsage));
        $this->line('Memory Peak:  ' . $this->formatBytes($memoryPeak));
        $this->line('Memory Limit: ' . $memoryLimit);
        
        // CPU load (if available)
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $this->line('CPU Load: ' . number_format($load[0], 2) . ' (1min)');
        }
        
        // Database connections
        try {
            $connections = DB::select('SHOW STATUS LIKE "Threads_connected"');
            if (!empty($connections)) {
                $this->line('DB Connections: ' . $connections[0]->Value);
            }
        } catch (Exception $e) {
            // Ignore DB errors
        }
        
        $this->newLine();
    }

    /**
     * Display model performance metrics
     */
    private function displayModelMetrics(): void
    {
        $this->line('🎯 MODEL PERFORMANCE');
        $this->line('────────────────────');
        
        // Get latest performance data from logs
        $logFiles = File::glob(storage_path('logs/model_stress_test_*.md'));
        
        if (empty($logFiles)) {
            $this->warn('No stress test data available');
            return;
        }
        
        // Get the most recent log file
        $latestLog = collect($logFiles)->sortByDesc(function ($file) {
            return File::lastModified($file);
        })->first();
        
        if ($latestLog && File::exists($latestLog)) {
            $content = File::get($latestLog);
            $this->parseAndDisplayMetrics($content);
        }
        
        $this->newLine();
    }

    /**
     * Parse and display metrics from log content
     */
    private function parseAndDisplayMetrics(string $content): void
    {
        // Extract performance data using regex
        preg_match_all('/### 🎯 (\w+).*?Overall Score:\*\* (\d+)%/s', $content, $matches);
        
        if (!empty($matches[1])) {
            $this->line('| Model | Score | Status |');
            $this->line('|-------|-------|--------|');
            
            for ($i = 0; $i < count($matches[1]); $i++) {
                $model = $matches[1][$i];
                $score = $matches[2][$i];
                $status = $this->getStatusEmoji((int) $score);
                
                $this->line("| {$model} | {$score}% | {$status} |");
            }
        }
    }

    /**
     * Get status emoji based on score
     */
    private function getStatusEmoji(int $score): string
    {
        if ($score >= 90) return '🟢 EXCELLENT';
        if ($score >= 70) return '🟡 GOOD';
        if ($score >= 50) return '🟠 FAIR';
        return '🔴 CRITICAL';
    }

    /**
     * Display recent log entries
     */
    private function displayRecentLogs(): void
    {
        $this->line('📋 RECENT ACTIVITY');
        $this->line('──────────────────');
        
        $logFile = storage_path('logs/laravel.log');
        
        if (File::exists($logFile)) {
            $lines = File::lines($logFile)->reverse()->take(5);
            
            foreach ($lines as $line) {
                if (strpos($line, 'MODEL STRESS') !== false) {
                    $this->line($this->formatLogLine($line));
                }
            }
        }
        
        $this->newLine();
        $this->line('Press Ctrl+C to exit monitoring');
    }

    /**
     * Format log line for display
     */
    private function formatLogLine(string $line): string
    {
        // Extract timestamp and message
        if (preg_match('/\[(.*?)\].*?(MODEL STRESS.*?)$/', $line, $matches)) {
            return $matches[1] . ' | ' . $matches[2];
        }
        
        return substr($line, 0, 80) . '...';
    }

    /**
     * Clear screen (cross-platform)
     */
    private function clearScreen(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            system('cls');
        } else {
            system('clear');
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}