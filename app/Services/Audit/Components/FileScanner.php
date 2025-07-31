<?php

namespace App\Services\Audit\Components;

use Illuminate\Support\Facades\Log;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Basic File Scanner
 * 
 * Discovers and categorizes files for audit analysis
 * Provides file metadata extraction and directory structure analysis
 */
class FileScanner
{
    private array $excludePatterns = [
        '.git',
        'node_modules',
        'vendor',
        'storage/logs',
        'storage/framework/cache',
        'storage/framework/sessions',
        'storage/framework/views',
        '.DS_Store',
        'Thumbs.db',
        '*.log',
        '*.cache'
    ];

    private array $fileCategories = [
        'php' => ['php'],
        'javascript' => ['js', 'jsx', 'ts', 'tsx'],
        'css' => ['css', 'scss', 'sass', 'less'],
        'blade' => ['blade.php'],
        'config' => ['json', 'yaml', 'yml', 'xml', 'ini', 'env'],
        'migration' => ['migration.php'],
        'test' => ['test.php', 'Test.php'],
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'],
        'document' => ['md', 'txt', 'pdf', 'doc', 'docx']
    ];

    private array $scannedFiles = [];
    private array $directoryStructure = [];
    private array $fileStatistics = [];

    /**
     * Scan project directory recursively
     */
    public function scanDirectory(string $basePath): array
    {
        Log::info('Starting file system scan', ['base_path' => $basePath]);
        
        $startTime = microtime(true);
        $this->scannedFiles = [];
        $this->directoryStructure = [];
        $this->fileStatistics = [];

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($basePath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($this->shouldExcludeFile($file)) {
                    continue;
                }

                if ($file->isFile()) {
                    $fileInfo = $this->extractFileMetadata($file);
                    $this->scannedFiles[] = $fileInfo;
                    $this->updateStatistics($fileInfo);
                } elseif ($file->isDir()) {
                    $this->directoryStructure[] = $this->extractDirectoryInfo($file);
                }
            }

            $executionTime = microtime(true) - $startTime;
            
            Log::info('File system scan completed', [
                'files_found' => count($this->scannedFiles),
                'directories_found' => count($this->directoryStructure),
                'execution_time' => round($executionTime, 2) . 's'
            ]);

            return [
                'files' => $this->scannedFiles,
                'directories' => $this->directoryStructure,
                'statistics' => $this->fileStatistics,
                'execution_time' => $executionTime
            ];

        } catch (\Exception $e) {
            Log::error('File system scan failed', [
                'error' => $e->getMessage(),
                'base_path' => $basePath
            ]);
            throw $e;
        }
    }

    /**
     * Get files by category
     */
    public function getFilesByCategory(string $category): array
    {
        return array_filter($this->scannedFiles, function($file) use ($category) {
            return $file['category'] === $category;
        });
    }

    /**
     * Get PHP files specifically
     */
    public function getPHPFiles(): array
    {
        return $this->getFilesByCategory('php');
    }

    /**
     * Get Blade template files
     */
    public function getBladeFiles(): array
    {
        return $this->getFilesByCategory('blade');
    }

    /**
     * Get JavaScript files
     */
    public function getJavaScriptFiles(): array
    {
        return $this->getFilesByCategory('javascript');
    }

    /**
     * Get CSS files
     */
    public function getCSSFiles(): array
    {
        return $this->getFilesByCategory('css');
    }

    /**
     * Get configuration files
     */
    public function getConfigFiles(): array
    {
        return $this->getFilesByCategory('config');
    }

    /**
     * Get migration files
     */
    public function getMigrationFiles(): array
    {
        $migrations = [];
        
        foreach ($this->scannedFiles as $file) {
            if (strpos($file['path'], 'database/migrations') !== false && 
                $file['extension'] === 'php') {
                $migrations[] = $file;
            }
        }

        // Sort migrations by filename (which includes timestamp)
        usort($migrations, function($a, $b) {
            return strcmp($a['filename'], $b['filename']);
        });

        return $migrations;
    }

    /**
     * Get test files
     */
    public function getTestFiles(): array
    {
        $testFiles = [];
        
        foreach ($this->scannedFiles as $file) {
            if (strpos($file['path'], '/tests/') !== false || 
                strpos($file['filename'], 'Test.php') !== false ||
                strpos($file['filename'], '.test.') !== false) {
                $testFiles[] = $file;
            }
        }

        return $testFiles;
    }

    /**
     * Extract file metadata
     */
    private function extractFileMetadata(SplFileInfo $file): array
    {
        $relativePath = str_replace(base_path() . '/', '', $file->getPathname());
        $extension = $file->getExtension();
        $category = $this->categorizeFile($file);

        return [
            'path' => $relativePath,
            'absolute_path' => $file->getPathname(),
            'filename' => $file->getFilename(),
            'basename' => $file->getBasename('.' . $extension),
            'extension' => $extension,
            'category' => $category,
            'size' => $file->getSize(),
            'size_human' => $this->formatBytes($file->getSize()),
            'modified_time' => $file->getMTime(),
            'modified_date' => date('Y-m-d H:i:s', $file->getMTime()),
            'permissions' => substr(sprintf('%o', $file->getPerms()), -4),
            'is_readable' => $file->isReadable(),
            'is_writable' => $file->isWritable(),
            'line_count' => $this->countLines($file->getPathname()),
            'directory' => dirname($relativePath)
        ];
    }

    /**
     * Extract directory information
     */
    private function extractDirectoryInfo(SplFileInfo $dir): array
    {
        $relativePath = str_replace(base_path() . '/', '', $dir->getPathname());
        
        return [
            'path' => $relativePath,
            'absolute_path' => $dir->getPathname(),
            'name' => $dir->getFilename(),
            'permissions' => substr(sprintf('%o', $dir->getPerms()), -4),
            'is_readable' => $dir->isReadable(),
            'is_writable' => $dir->isWritable(),
            'modified_time' => $dir->getMTime(),
            'modified_date' => date('Y-m-d H:i:s', $dir->getMTime())
        ];
    }

    /**
     * Categorize file based on extension and path
     */
    private function categorizeFile(SplFileInfo $file): string
    {
        $extension = strtolower($file->getExtension());
        $filename = $file->getFilename();
        $path = $file->getPathname();

        // Special case for Blade templates
        if (strpos($filename, '.blade.php') !== false) {
            return 'blade';
        }

        // Special case for migrations
        if (strpos($path, 'database/migrations') !== false && $extension === 'php') {
            return 'migration';
        }

        // Special case for tests
        if (strpos($path, '/tests/') !== false || 
            strpos($filename, 'Test.php') !== false) {
            return 'test';
        }

        // Check standard categories
        foreach ($this->fileCategories as $category => $extensions) {
            if (in_array($extension, $extensions)) {
                return $category;
            }
        }

        return 'other';
    }

    /**
     * Check if file should be excluded from scan
     */
    private function shouldExcludeFile(SplFileInfo $file): bool
    {
        $path = $file->getPathname();
        $filename = $file->getFilename();

        foreach ($this->excludePatterns as $pattern) {
            // Handle wildcard patterns
            if (strpos($pattern, '*') !== false) {
                $regex = '/^' . str_replace('*', '.*', preg_quote($pattern, '/')) . '$/';
                if (preg_match($regex, $filename)) {
                    return true;
                }
            } else {
                // Handle directory patterns
                if (strpos($path, $pattern) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Count lines in a file
     */
    private function countLines(string $filePath): int
    {
        try {
            if (!is_readable($filePath)) {
                return 0;
            }

            $lineCount = 0;
            $handle = fopen($filePath, 'r');
            
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    $lineCount++;
                }
                fclose($handle);
            }

            return $lineCount;

        } catch (\Exception $e) {
            Log::warning('Failed to count lines in file', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Update file statistics
     */
    private function updateStatistics(array $fileInfo): void
    {
        $category = $fileInfo['category'];
        
        if (!isset($this->fileStatistics[$category])) {
            $this->fileStatistics[$category] = [
                'count' => 0,
                'total_size' => 0,
                'total_lines' => 0,
                'average_size' => 0,
                'average_lines' => 0
            ];
        }

        $this->fileStatistics[$category]['count']++;
        $this->fileStatistics[$category]['total_size'] += $fileInfo['size'];
        $this->fileStatistics[$category]['total_lines'] += $fileInfo['line_count'];
        
        // Calculate averages
        $count = $this->fileStatistics[$category]['count'];
        $this->fileStatistics[$category]['average_size'] = 
            round($this->fileStatistics[$category]['total_size'] / $count);
        $this->fileStatistics[$category]['average_lines'] = 
            round($this->fileStatistics[$category]['total_lines'] / $count);
    }

    /**
     * Get file statistics
     */
    public function getFileStatistics(): array
    {
        return $this->fileStatistics;
    }

    /**
     * Get directory structure
     */
    public function getDirectoryStructure(): array
    {
        return $this->directoryStructure;
    }

    /**
     * Get all scanned files
     */
    public function getScannedFiles(): array
    {
        return $this->scannedFiles;
    }

    /**
     * Add custom exclude pattern
     */
    public function addExcludePattern(string $pattern): void
    {
        if (!in_array($pattern, $this->excludePatterns)) {
            $this->excludePatterns[] = $pattern;
        }
    }

    /**
     * Remove exclude pattern
     */
    public function removeExcludePattern(string $pattern): void
    {
        $key = array_search($pattern, $this->excludePatterns);
        if ($key !== false) {
            unset($this->excludePatterns[$key]);
            $this->excludePatterns = array_values($this->excludePatterns);
        }
    }

    /**
     * Get exclude patterns
     */
    public function getExcludePatterns(): array
    {
        return $this->excludePatterns;
    }
}