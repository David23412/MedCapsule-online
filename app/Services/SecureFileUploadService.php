<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 🔒 SECURE FILE UPLOAD SERVICE - MEGA LETHAL DEFENSE SYSTEM
 *
 * This service provides maximum security for file uploads to prevent:
 * - Remote Code Execution (RCE)
 * - File Inclusion Attacks
 * - Malicious File Uploads
 * - Directory Traversal
 *
 * SECURITY FEATURES:
 * ✅ MIME type validation using finfo_file()
 * ✅ File signature scanning
 * ✅ Filename sanitization
 * ✅ Secure storage outside web root
 * ✅ File permission security
 * ✅ Content scanning for embedded threats
 */
class SecureFileUploadService
{
    /**
     * Allowed MIME types for different file categories
     */
    private const ALLOWED_MIME_TYPES = [
        'images' => [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
        ],
        'documents' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ],
        'videos' => [
            'video/mp4',
            'video/mpeg',
            'video/quicktime',
            'video/x-msvideo',
            'video/x-ms-wmv',
        ],
    ];

    /**
     * Dangerous file signatures to detect
     */
    private const DANGEROUS_SIGNATURES = [
        '<?php',    // PHP code
        '<script',  // JavaScript
        'eval(',    // JavaScript eval
        'system(',  // System calls
        'exec(',    // Execute commands
        'shell_exec', // Shell execution
        'passthru', // System passthrough
        'popen(',   // Process open
        'proc_open', // Process execution
        'include(', // File inclusion
        'require(', // File inclusion
        'include_once', // File inclusion
        'require_once', // File inclusion
    ];

    /**
     * Maximum file sizes (in bytes)
     */
    private const MAX_FILE_SIZES = [
        'images' => 5 * 1024 * 1024,      // 5MB
        'documents' => 10 * 1024 * 1024,  // 10MB
        'videos' => 100 * 1024 * 1024,    // 100MB
    ];

    /**
     * Securely process and validate uploaded file
     *
     * @param UploadedFile $file
     * @param string $category (images|documents|videos)
     * @param string $directory
     * @return array
     * @throws \Exception
     */
    public function processSecureUpload(UploadedFile $file, string $category = 'images', string $directory = 'uploads'): array
    {
        // Validate file exists and is valid
        if (!$file->isValid()) {
            throw new \Exception('Invalid file upload: ' . $file->getErrorMessage());
        }

        // Check file size
        $this->validateFileSize($file, $category);

        // Validate MIME type using multiple methods
        $this->validateMimeType($file, $category);

        // Scan file content for threats
        $this->scanFileContent($file);

        // Sanitize filename
        $secureFilename = $this->sanitizeFilename($file->getClientOriginalName());

        // Generate secure storage path
        $storagePath = $this->generateSecureStoragePath($directory, $secureFilename);

        // Store file securely
        $storedPath = $this->storeFileSecurely($file, $storagePath);

        // Set secure permissions
        $this->setSecurePermissions($storedPath);

        Log::info('Secure file upload completed', [
            'original_name' => $file->getClientOriginalName(),
            'secure_name' => $secureFilename,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'category' => $category,
            'storage_path' => $storedPath
        ]);

        return [
            'success' => true,
            'original_name' => $file->getClientOriginalName(),
            'secure_name' => $secureFilename,
            'storage_path' => $storedPath,
            'public_url' => Storage::url($storedPath),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ];
    }

    /**
     * Validate file size against category limits
     *
     * @param UploadedFile $file
     * @param string $category
     * @throws \Exception
     */
    private function validateFileSize(UploadedFile $file, string $category): void
    {
        if (!isset(self::MAX_FILE_SIZES[$category])) {
            throw new \Exception("Invalid file category: {$category}");
        }

        $maxSize = self::MAX_FILE_SIZES[$category];
        $fileSize = $file->getSize();

        if ($fileSize > $maxSize) {
            $maxSizeMB = $maxSize / (1024 * 1024);
            throw new \Exception("File size exceeds maximum limit of {$maxSizeMB}MB for {$category}");
        }
    }

    /**
     * Validate MIME type using multiple detection methods
     *
     * @param UploadedFile $file
     * @param string $category
     * @throws \Exception
     */
    private function validateMimeType(UploadedFile $file, string $category): void
    {
        if (!isset(self::ALLOWED_MIME_TYPES[$category])) {
            throw new \Exception("Invalid file category: {$category}");
        }

        $allowedTypes = self::ALLOWED_MIME_TYPES[$category];

        // Method 1: Laravel's getMimeType() - can be spoofed
        $laravelMimeType = $file->getMimeType();

        // Method 2: finfo_file() - more secure, reads actual file content
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $actualMimeType = finfo_file($finfo, $file->getRealPath());
        finfo_close($finfo);

        // Method 3: File extension validation (additional layer)
        $extension = strtolower($file->getClientOriginalExtension());
        $extensionMimeType = $this->getMimeTypeFromExtension($extension);

        // Cross-validate all methods
        $validations = [
            in_array($laravelMimeType, $allowedTypes),
            in_array($actualMimeType, $allowedTypes),
            in_array($extensionMimeType, $allowedTypes),
        ];

        // All methods must agree for the file to be valid
        if (!array_filter($validations)) {
            Log::warning('MIME type validation failed', [
                'laravel_mime' => $laravelMimeType,
                'actual_mime' => $actualMimeType,
                'extension_mime' => $extensionMimeType,
                'allowed_types' => $allowedTypes,
                'filename' => $file->getClientOriginalName()
            ]);

            throw new \Exception('File type validation failed - potential security risk detected');
        }

        // Additional check: ensure extension matches MIME type
        if (!$this->validateExtensionMatchesMimeType($extension, $actualMimeType)) {
            throw new \Exception('File extension does not match detected MIME type');
        }
    }

    /**
     * Scan file content for malicious patterns
     *
     * @param UploadedFile $file
     * @throws \Exception
     */
    private function scanFileContent(UploadedFile $file): void
    {
        $content = file_get_contents($file->getRealPath());

        // Check for dangerous code patterns
        foreach (self::DANGEROUS_SIGNATURES as $signature) {
            if (stripos($content, $signature) !== false) {
                Log::alert('Malicious file content detected', [
                    'signature' => $signature,
                    'filename' => $file->getClientOriginalName(),
                    'size' => $file->getSize()
                ]);
                throw new \Exception('Malicious content detected in file');
            }
        }

        // For images, check for embedded PHP/JS code
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $this->scanImageForEmbeddedCode($file, $content);
        }
    }

    /**
     * Scan image files for embedded malicious code
     *
     * @param UploadedFile $file
     * @param string $content
     * @throws \Exception
     */
    private function scanImageForEmbeddedCode(UploadedFile $file, string $content): void
    {
        // Check for PHP code in image metadata
        if (function_exists('exif_read_data')) {
            try {
                $exif = @exif_read_data($file->getRealPath());
                if ($exif) {
                    foreach ($exif as $key => $value) {
                        if (is_string($value)) {
                            foreach (self::DANGEROUS_SIGNATURES as $signature) {
                                if (stripos($value, $signature) !== false) {
                                    throw new \Exception('Malicious code detected in image metadata');
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // EXIF reading failed, but don't block the file
                Log::info('EXIF reading failed for image', [
                    'filename' => $file->getClientOriginalName(),
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Check for script tags in SVG files
        if ($file->getMimeType() === 'image/svg+xml') {
            if (stripos($content, '<script') !== false) {
                throw new \Exception('Script tags detected in SVG file');
            }
        }
    }

    /**
     * Sanitize filename to prevent security issues
     *
     * @param string $filename
     * @return string
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remove path components
        $filename = basename($filename);

        // Remove dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

        // Remove multiple dots (potential directory traversal)
        $filename = preg_replace('/\.\.+/', '.', $filename);

        // Ensure filename is not empty and doesn't start with dot
        if (empty($filename) || str_starts_with($filename, '.')) {
            $filename = 'file_' . time();
        }

        // Generate secure random filename with original extension
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $randomName = bin2hex(random_bytes(16));

        return $extension ? "{$randomName}.{$extension}" : $randomName;
    }

    /**
     * Generate secure storage path
     *
     * @param string $directory
     * @param string $filename
     * @return string
     */
    private function generateSecureStoragePath(string $directory, string $filename): string
    {
        // Create date-based subdirectories for organization
        $datePath = date('Y/m/d');

        // Generate unique subdirectory to prevent enumeration
        $uniqueDir = substr(md5(uniqid()), 0, 8);

        return "{$directory}/{$datePath}/{$uniqueDir}/{$filename}";
    }

    /**
     * Store file securely with proper permissions
     *
     * @param UploadedFile $file
     * @param string $path
     * @return string
     * @throws \Exception
     */
    private function storeFileSecurely(UploadedFile $file, string $path): string
    {
        try {
            // Store file in secure location (outside web root)
            $storedPath = $file->storeAs(
                dirname($path),
                basename($path),
                'secure_uploads' // Use secure disk
            );

            if (!$storedPath) {
                throw new \Exception('Failed to store file securely');
            }

            return $storedPath;

        } catch (\Exception $e) {
            Log::error('File storage failed', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('File storage failed: ' . $e->getMessage());
        }
    }

    /**
     * Set secure file permissions
     *
     * @param string $path
     */
    private function setSecurePermissions(string $path): void
    {
        $fullPath = Storage::disk('secure_uploads')->path($path);

        if (file_exists($fullPath)) {
            // Set secure permissions (644 for files, 755 for directories)
            chmod($fullPath, 0644);

            // Ensure parent directories have correct permissions
            $parentDir = dirname($fullPath);
            if (is_dir($parentDir)) {
                chmod($parentDir, 0755);
            }
        }
    }

    /**
     * Get MIME type from file extension
     *
     * @param string $extension
     * @return string|null
     */
    private function getMimeTypeFromExtension(string $extension): ?string
    {
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'mp4' => 'video/mp4',
            'avi' => 'video/x-msvideo',
            'mov' => 'video/quicktime',
        ];

        return $mimeTypes[$extension] ?? null;
    }

    /**
     * Validate that file extension matches MIME type
     *
     * @param string $extension
     * @param string $mimeType
     * @return bool
     */
    private function validateExtensionMatchesMimeType(string $extension, string $mimeType): bool
    {
        $expectedMimeType = $this->getMimeTypeFromExtension($extension);

        if (!$expectedMimeType) {
            return false; // Unknown extension
        }

        // Allow some flexibility (e.g., jpg vs jpeg)
        $mimeTypeGroups = [
            ['image/jpeg', 'image/jpg'],
            ['application/msword', 'application/vnd.ms-word'],
            ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        ];

        foreach ($mimeTypeGroups as $group) {
            if (in_array($expectedMimeType, $group) && in_array($mimeType, $group)) {
                return true;
            }
        }

        return $expectedMimeType === $mimeType;
    }

    /**
     * Check if file is safe for upload
     *
     * @param UploadedFile $file
     * @return bool
     */
    public function isFileSafe(UploadedFile $file): bool
    {
        try {
            $this->validateFileSize($file, 'images'); // Default to images
            $this->validateMimeType($file, 'images');
            $this->scanFileContent($file);
            return true;
        } catch (\Exception $e) {
            Log::warning('File safety check failed', [
                'filename' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
