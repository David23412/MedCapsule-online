<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BackgroundFileProcessingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $backoff = [30, 60, 120];

    private string $filePath;
    private $content;
    private $processor;

    public function __construct(string $filePath, $content = null, $processor = null)
    {
        $this->filePath = $filePath;
        $this->content = $content;
        $this->processor = $processor;
    }

    public function handle(): void
    {
        $jobId = uniqid('file_job_', true);
        
        try {
            Cache::put("file_job_status:{$jobId}", [
                'status' => 'processing',
                'started_at' => now(),
                'file_path' => $this->filePath
            ], 600);

            if ($this->content !== null) {
                // Writing content to file
                $this->processFileWrite($jobId);
            } elseif ($this->processor !== null) {
                // Processing existing file
                $this->processFileRead($jobId);
            }

            Cache::put("file_job_status:{$jobId}", [
                'status' => 'completed',
                'completed_at' => now(),
                'file_path' => $this->filePath
            ], 600);

            // Update metrics
            $this->updateMetrics('completed');

        } catch (\Exception $e) {
            Log::error('BackgroundFileProcessingJob failed', [
                'job_id' => $jobId,
                'file_path' => $this->filePath,
                'error' => $e->getMessage()
            ]);

            Cache::put("file_job_status:{$jobId}", [
                'status' => 'failed',
                'failed_at' => now(),
                'error' => $e->getMessage(),
                'file_path' => $this->filePath
            ], 600);

            $this->updateMetrics('failed');
            throw $e;
        }
    }

    private function processFileWrite(string $jobId): void
    {
        $stream = Storage::writeStream($this->filePath, fopen('php://temp', 'r+'));
        
        if (is_string($this->content)) {
            fwrite($stream, $this->content);
        } elseif (is_resource($this->content)) {
            $chunkSize = 8192;
            while (!feof($this->content)) {
                $chunk = fread($this->content, $chunkSize);
                fwrite($stream, $chunk);
                
                // Update progress
                $this->updateProgress($jobId, 50); // Approximate progress
            }
        }

        fclose($stream);
        
        // Cache file metadata
        $this->cacheFileMetadata();
    }

    private function processFileRead(string $jobId): void
    {
        if (!Storage::exists($this->filePath)) {
            throw new \Exception("File not found: {$this->filePath}");
        }

        $stream = Storage::readStream($this->filePath);
        $result = null;
        $buffer = '';
        $chunkSize = 8192;
        $totalSize = Storage::size($this->filePath);
        $processed = 0;

        while (!feof($stream)) {
            $chunk = fread($stream, $chunkSize);
            $buffer .= $chunk;
            $processed += strlen($chunk);

            // Update progress
            $progress = ($processed / $totalSize) * 100;
            $this->updateProgress($jobId, $progress);

            if (is_callable($this->processor)) {
                $result = call_user_func($this->processor, $buffer, $result);
                $buffer = '';
            }
        }

        fclose($stream);
        
        // Store result if processor returned something
        if ($result !== null) {
            Cache::put("file_processing_result:{$this->filePath}", $result, 3600);
        }
    }

    private function updateProgress(string $jobId, float $progress): void
    {
        Cache::put("file_job_progress:{$jobId}", $progress, 300);
    }

    private function cacheFileMetadata(): void
    {
        $metadata = [
            'size' => Storage::size($this->filePath),
            'last_modified' => Storage::lastModified($this->filePath),
            'mime_type' => Storage::mimeType($this->filePath),
            'exists' => Storage::exists($this->filePath)
        ];

        Cache::put("file_meta:" . md5($this->filePath), $metadata, 3600);
    }

    private function updateMetrics(string $status): void
    {
        $total = Cache::get('files_processed_total', 0);
        Cache::put('files_processed_total', $total + 1, 86400);

        if ($status === 'completed') {
            $completed = Cache::get('files_completed_total', 0);
            Cache::put('files_completed_total', $completed + 1, 86400);
        } elseif ($status === 'failed') {
            $failed = Cache::get('files_failed_total', 0);
            Cache::put('files_failed_total', $failed + 1, 86400);
        }

        // Update average processing time
        $currentAvg = Cache::get('avg_processing_time', 0);
        $count = Cache::get('processing_time_count', 0);
        $newTime = microtime(true) - LARAVEL_START;
        
        $newAvg = (($currentAvg * $count) + $newTime) / ($count + 1);
        
        Cache::put('avg_processing_time', $newAvg, 86400);
        Cache::put('processing_time_count', $count + 1, 86400);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('BackgroundFileProcessingJob failed permanently', [
            'file_path' => $this->filePath,
            'error' => $exception->getMessage()
        ]);

        $this->updateMetrics('failed');
    }
}
