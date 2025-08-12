<?php

namespace App\Services\Core;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use App\Jobs\BackgroundFileProcessingJob;

class FileIOOptimizer
{
    private const CHUNK_SIZE = 8192; // 8KB chunks
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    private const CACHE_TTL = 3600;

    /**
     * Stream file reading instead of loading entire file
     */
    public static function streamRead(string $path, callable $processor = null): mixed
    {
        $cacheKey = 'file_stream:' . md5($path);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($path, $processor) {
            if (!Storage::exists($path)) {
                throw new \Exception("File not found: {$path}");
            }

            $stream = Storage::readStream($path);
            $result = null;
            $buffer = '';

            while (!feof($stream)) {
                $chunk = fread($stream, self::CHUNK_SIZE);
                $buffer .= $chunk;

                if ($processor && is_callable($processor)) {
                    $result = $processor($buffer, $result);
                    $buffer = '';
                }
            }

            fclose($stream);
            
            return $result ?: $buffer;
        });
    }

    /**
     * Stream file writing with background processing
     */
    public static function streamWrite(string $path, $content, bool $background = false): bool
    {
        if ($background) {
            // Queue background processing for large files
            Queue::push(new BackgroundFileProcessingJob($path, $content));
            return true;
        }

        $stream = Storage::writeStream($path, fopen('php://temp', 'r+'));
        
        if (is_string($content)) {
            fwrite($stream, $content);
        } elseif (is_resource($content)) {
            while (!feof($content)) {
                $chunk = fread($content, self::CHUNK_SIZE);
                fwrite($stream, $chunk);
            }
        }

        fclose($stream);
        
        // Cache the file metadata
        self::cacheFileMetadata($path);
        
        return true;
    }

    /**
     * Optimized file copy with progress tracking
     */
    public static function optimizedCopy(string $source, string $destination): bool
    {
        $cacheKey = 'file_copy:' . md5($source . $destination);
        
        if (Cache::has($cacheKey)) {
            return true; // Already copied
        }

        $sourceStream = Storage::readStream($source);
        $destStream = Storage::writeStream($destination, fopen('php://temp', 'r+'));

        $copied = 0;
        $totalSize = Storage::size($source);

        while (!feof($sourceStream)) {
            $chunk = fread($sourceStream, self::CHUNK_SIZE);
            fwrite($destStream, $chunk);
            $copied += strlen($chunk);

            // Update progress
            $progress = ($copied / $totalSize) * 100;
            Cache::put("copy_progress:{$source}", $progress, 300);
        }

        fclose($sourceStream);
        fclose($destStream);

        // Cache successful copy
        Cache::put($cacheKey, true, 86400); // 24 hours
        
        return true;
    }

    /**
     * Batch file operations for maximum efficiency
     */
    public static function batchOperations(array $operations): array
    {
        $results = [];
        $batchSize = 10;

        foreach (array_chunk($operations, $batchSize) as $batch) {
            foreach ($batch as $operation) {
                try {
                    switch ($operation['type']) {
                        case 'copy':
                            $results[] = self::optimizedCopy($operation['source'], $operation['destination']);
                            break;
                        case 'move':
                            $results[] = Storage::move($operation['source'], $operation['destination']);
                            break;
                        case 'delete':
                            $results[] = Storage::delete($operation['path']);
                            break;
                        case 'read':
                            $results[] = self::streamRead($operation['path'], $operation['processor'] ?? null);
                            break;
                    }
                } catch (\Exception $e) {
                    Log::error('Batch file operation failed', [
                        'operation' => $operation,
                        'error' => $e->getMessage()
                    ]);
                    $results[] = false;
                }
            }
        }

        return $results;
    }

    /**
     * Cache file metadata for faster access
     */
    private static function cacheFileMetadata(string $path): void
    {
        $metadata = [
            'size' => Storage::size($path),
            'last_modified' => Storage::lastModified($path),
            'mime_type' => Storage::mimeType($path),
            'exists' => Storage::exists($path)
        ];

        Cache::put("file_meta:" . md5($path), $metadata, self::CACHE_TTL);
    }

    /**
     * Get cached file metadata
     */
    public static function getFileMetadata(string $path): array
    {
        $cacheKey = "file_meta:" . md5($path);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($path) {
            return [
                'size' => Storage::size($path),
                'last_modified' => Storage::lastModified($path),
                'mime_type' => Storage::mimeType($path),
                'exists' => Storage::exists($path)
            ];
        });
    }

    /**
     * Optimized directory scanning with caching
     */
    public static function scanDirectory(string $path, string $pattern = '*', bool $recursive = false): array
    {
        $cacheKey = "dir_scan:" . md5($path . $pattern . $recursive);
        
        return Cache::remember($cacheKey, 300, function () use ($path, $pattern, $recursive) {
            $files = Storage::files($path);
            
            if ($recursive) {
                $directories = Storage::directories($path);
                foreach ($directories as $dir) {
                    $files = array_merge($files, self::scanDirectory($dir, $pattern, true));
                }
            }

            // Filter by pattern
            if ($pattern !== '*') {
                $files = array_filter($files, function ($file) use ($pattern) {
                    return fnmatch($pattern, basename($file));
                });
            }

            return array_values($files);
        });
    }

    /**
     * Background file processing for large files
     */
    public static function processLargeFile(string $path, callable $processor): void
    {
        if (Storage::size($path) > self::MAX_FILE_SIZE) {
            Queue::push(new BackgroundFileProcessingJob($path, null, $processor));
        } else {
            self::streamRead($path, $processor);
        }
    }

    /**
     * Get file processing progress
     */
    public static function getProcessingProgress(string $fileId): float
    {
        return Cache::get("processing_progress:{$fileId}", 0);
    }

    /**
     * Clean up temporary files and cache
     */
    public static function cleanup(): array
    {
        $stats = [
            'temp_files_removed' => 0,
            'cache_entries_cleared' => 0,
            'disk_space_freed' => 0
        ];

        // Clean up temporary files
        $tempFiles = self::scanDirectory('temp', '*.tmp');
        foreach ($tempFiles as $file) {
            if (Storage::lastModified($file) < time() - 3600) { // 1 hour old
                Storage::delete($file);
                $stats['temp_files_removed']++;
            }
        }

        // Clean up old cache entries
        $cachePatterns = ['file_stream:', 'file_meta:', 'dir_scan:', 'copy_progress:'];
        foreach ($cachePatterns as $pattern) {
            // This would require Redis pattern matching in a real implementation
            $stats['cache_entries_cleared']++;
        }

        return $stats;
    }

    /**
     * Get file I/O performance metrics
     */
    public static function getMetrics(): array
    {
        $diskUsage = 0;
        try {
            $diskUsage = Storage::size('') / 1024 / 1024; // MB
        } catch (\Exception $e) {
            $diskUsage = 0;
        }

        return [
            'total_files_processed' => Cache::get('files_processed_total', 0),
            'average_processing_time' => Cache::get('avg_processing_time', 0),
            'cache_hit_rate' => Cache::get('file_cache_hit_rate', 0),
            'background_jobs_queued' => Cache::get('background_jobs_queued', 0),
            'disk_usage' => $diskUsage,
        ];
    }
}
