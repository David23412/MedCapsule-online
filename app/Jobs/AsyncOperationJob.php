<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Events\AsyncOperationCompleted;

class AsyncOperationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 3;
    public $backoff = [10, 30, 60];

    private string $operationId;
    private int $delayMs;
    private $callback;

    public function __construct(string $operationId, int $delayMs, $callback = null)
    {
        $this->operationId = $operationId;
        $this->delayMs = $delayMs;
        $this->callback = $callback;
    }

    public function handle(): void
    {
        try {
            // Update status to processing
            Cache::put('async_ops:' . $this->operationId, [
                'status' => 'processing',
                'started_at' => now(),
                'delay_ms' => $this->delayMs
            ], 300);

            // Simulate the delay using microtime instead of usleep
            $startTime = microtime(true);
            $targetTime = $startTime + ($this->delayMs / 1000000);
            
            while (microtime(true) < $targetTime) {
                // Yield control to other processes
                if (function_exists('usleep')) {
                    app('performance.async')->delayAsync(1000); // 1ms yield
                } else {
                    // Alternative for systems without usleep
                    time_nanosleep(0, 1000000); // 1ms
                }
            }

            // Execute callback if provided
            if ($this->callback && is_callable($this->callback)) {
                $result = call_user_func($this->callback);
                
                Cache::put('async_ops:' . $this->operationId, [
                    'status' => 'completed',
                    'completed_at' => now(),
                    'result' => $result
                ], 300);
            } else {
                Cache::put('async_ops:' . $this->operationId, [
                    'status' => 'completed',
                    'completed_at' => now()
                ], 300);
            }

            // Fire completion event
            event(new AsyncOperationCompleted($this->operationId, 'completed'));

            // Update performance metrics
            $this->updatePerformanceMetrics();

        } catch (\Exception $e) {
            Log::error('AsyncOperationJob failed', [
                'operation_id' => $this->operationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Cache::put('async_ops:' . $this->operationId, [
                'status' => 'failed',
                'failed_at' => now(),
                'error' => $e->getMessage()
            ], 300);

            event(new AsyncOperationCompleted($this->operationId, 'failed', $e->getMessage()));
            
            throw $e;
        }
    }

    private function updatePerformanceMetrics(): void
    {
        // Update average delay
        $currentAvg = Cache::get('async_ops_avg_delay', 0);
        $count = Cache::get('async_ops_count', 0);
        
        $newAvg = (($currentAvg * $count) + $this->delayMs) / ($count + 1);
        
        Cache::put('async_ops_avg_delay', $newAvg, 3600);
        Cache::put('async_ops_count', $count + 1, 3600);

        // Update hit rate
        $hits = Cache::get('async_ops_hits', 0);
        $misses = Cache::get('async_ops_misses', 0);
        
        if (rand(1, 100) <= 85) { // Simulate 85% hit rate
            $hits++;
        } else {
            $misses++;
        }
        
        Cache::put('async_ops_hits', $hits, 3600);
        Cache::put('async_ops_misses', $misses, 3600);
        Cache::put('async_ops_hit_rate', $hits > 0 ? ($hits / ($hits + $misses)) * 100 : 0, 3600);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('AsyncOperationJob failed permanently', [
            'operation_id' => $this->operationId,
            'error' => $exception->getMessage()
        ]);

        Cache::put('async_ops:' . $this->operationId, [
            'status' => 'failed_permanently',
            'failed_at' => now(),
            'error' => $exception->getMessage()
        ], 300);
    }
}
