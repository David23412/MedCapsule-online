<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Exception;

class InputValidationService
{
    // Security limits to prevent abuse
    private const MAX_STRING_LENGTH = 10000;
    private const MAX_ARRAY_SIZE = 1000;
    private const MAX_NESTED_DEPTH = 10;
    private const MAX_FILE_SIZE_MB = 50;
    
    // Dangerous patterns to reject
    private const DANGEROUS_PATTERNS = [
        '/\<script/i',
        '/javascript:/i',
        '/vbscript:/i',
        '/onload\s*=/i',
        '/onerror\s*=/i',
        '/onclick\s*=/i',
        '/eval\s*\(/i',
        '/expression\s*\(/i',
        '/url\s*\(/i',
        '/import\s*\(/i',
        '/@@\w+/i',  // SQL Server variables
        '/\$\$\w+/i', // PostgreSQL variables
    ];

    /**
     * Validate input with comprehensive security checks
     */
    public static function validateInput(array $data, array $rules, array $messages = [], array $context = []): array
    {
        try {
            // Pre-validation security checks
            self::performSecurityChecks($data, $context);
            
            // Sanitize input data
            $sanitizedData = self::sanitizeInput($data);
            
            // Create validator with custom rules
            $validator = Validator::make($sanitizedData, $rules, $messages);
            
            // Add custom validation rules
            self::addCustomRules($validator);
            
            // Validate
            if ($validator->fails()) {
                Log::warning('Input validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'rules' => $rules,
                    'context' => $context
                ]);
                
                throw new ValidationException($validator);
            }
            
            // Post-validation checks
            $validatedData = $validator->validated();
            self::performPostValidationChecks($validatedData, $context);
            
            return $validatedData;
            
        } catch (ValidationException $e) {
            throw $e; // Re-throw validation exceptions
        } catch (Exception $e) {
            Log::error('Input validation system error', [
                'error' => $e->getMessage(),
                'context' => $context,
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new ValidationException(
                Validator::make([], ['system' => 'required'], ['system.required' => 'Validation system error'])
            );
        }
    }

    /**
     * Security pre-checks for malicious input
     */
    private static function performSecurityChecks(array $data, array $context = []): void
    {
        // Check for deeply nested data (possible DoS attack)
        $depth = self::getArrayDepth($data);
        if ($depth > self::MAX_NESTED_DEPTH) {
            Log::critical('Deeply nested input detected - possible DoS attack', [
                'depth' => $depth,
                'max_allowed' => self::MAX_NESTED_DEPTH,
                'context' => $context,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
            throw new Exception('Input structure too complex');
        }

        // Check for extremely large payloads
        $serializedSize = strlen(serialize($data));
        if ($serializedSize > 1048576) { // 1MB limit
            Log::critical('Extremely large input payload detected', [
                'size_bytes' => $serializedSize,
                'context' => $context,
                'ip' => request()->ip()
            ]);
            throw new Exception('Input payload too large');
        }

        // Check for suspicious patterns
        self::scanForMaliciousPatterns($data, $context);
    }

    /**
     * Scan for malicious patterns in input data
     */
    private static function scanForMaliciousPatterns($data, array $context, string $path = ''): void
    {
        if (is_array($data)) {
            if (count($data) > self::MAX_ARRAY_SIZE) {
                Log::warning('Large array detected in input', [
                    'size' => count($data),
                    'path' => $path,
                    'context' => $context
                ]);
                throw new Exception('Array size exceeds limits');
            }

            foreach ($data as $key => $value) {
                $currentPath = $path ? "{$path}.{$key}" : $key;
                self::scanForMaliciousPatterns($value, $context, $currentPath);
            }
        } elseif (is_string($data)) {
            if (strlen($data) > self::MAX_STRING_LENGTH) {
                Log::warning('Extremely long string detected', [
                    'length' => strlen($data),
                    'path' => $path,
                    'context' => $context
                ]);
                throw new Exception('String length exceeds limits');
            }

            // Check for dangerous patterns
            foreach (self::DANGEROUS_PATTERNS as $pattern) {
                if (preg_match($pattern, $data)) {
                    Log::critical('Malicious pattern detected in input', [
                        'pattern' => $pattern,
                        'path' => $path,
                        'value_preview' => substr($data, 0, 100),
                        'context' => $context,
                        'ip' => request()->ip(),
                        'user_agent' => request()->userAgent()
                    ]);
                    throw new Exception('Potentially malicious input detected');
                }
            }
        }
    }

    /**
     * Sanitize input data
     */
    private static function sanitizeInput(array $data): array
    {
        return array_map(function ($value) {
            if (is_string($value)) {
                // Remove null bytes
                $value = str_replace("\0", '', $value);
                
                // Normalize line endings
                $value = str_replace(["\r\n", "\r"], "\n", $value);
                
                // Trim whitespace
                $value = trim($value);
                
                return $value;
            } elseif (is_array($value)) {
                return self::sanitizeInput($value);
            }
            
            return $value;
        }, $data);
    }

    /**
     * Add custom validation rules
     */
    private static function addCustomRules($validator): void
    {
        // Safe HTML rule
        $validator->addExtension('safe_html', function ($attribute, $value, $parameters, $validator) {
            if (!is_string($value)) {
                return true;
            }
            
            // Check for dangerous tags
            $dangerousTags = ['script', 'iframe', 'object', 'embed', 'form', 'input', 'meta', 'link'];
            foreach ($dangerousTags as $tag) {
                if (stripos($value, "<$tag") !== false) {
                    return false;
                }
            }
            
            return true;
        });

        // Safe filename rule
        $validator->addExtension('safe_filename', function ($attribute, $value, $parameters, $validator) {
            if (!is_string($value)) {
                return true;
            }
            
            // Check for directory traversal
            if (strpos($value, '..') !== false || strpos($value, '/') !== false || strpos($value, '\\') !== false) {
                return false;
            }
            
            // Check for dangerous extensions
            $dangerousExts = ['php', 'exe', 'bat', 'cmd', 'sh', 'js', 'jar', 'scr', 'vbs'];
            $ext = strtolower(pathinfo($value, PATHINFO_EXTENSION));
            
            return !in_array($ext, $dangerousExts);
        });

        // SQL injection protection
        $validator->addExtension('no_sql_injection', function ($attribute, $value, $parameters, $validator) {
            if (!is_string($value)) {
                return true;
            }
            
            $sqlPatterns = [
                '/(\b(select|insert|update|delete|drop|create|alter|exec|execute|union|script)\b)/i',
                '/(\-\-|\#|\/\*|\*\/)/i',
                '/(\bor\b|\band\b)(\s+\d+\s*=\s*\d+)/i',
                '/(\bor\b|\band\b)(\s+\'\w*\'\s*=\s*\'\w*\')/i'
            ];
            
            foreach ($sqlPatterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    return false;
                }
            }
            
            return true;
        });

        // Transaction ID validation
        $validator->addExtension('valid_transaction_id', function ($attribute, $value, $parameters, $validator) {
            if (!is_string($value)) {
                return false;
            }
            
            // Must be 10-50 characters, alphanumeric plus some special chars
            if (!preg_match('/^[A-Za-z0-9\-_]{10,50}$/', $value)) {
                return false;
            }
            
            // Must not be sequential or repetitive
            if (preg_match('/^(.)\1{9,}$/', $value) || preg_match('/^(012|123|234|345|456|567|678|789|890)+$/', $value)) {
                return false;
            }
            
            return true;
        });
    }

    /**
     * Post-validation security checks
     */
    private static function performPostValidationChecks(array $data, array $context = []): void
    {
        // Check for suspicious combinations
        if (isset($data['email']) && isset($data['password'])) {
            // Check for common credential stuffing patterns
            $email = strtolower($data['email']);
            if (in_array($email, ['admin@admin.com', 'test@test.com', 'user@user.com'])) {
                Log::warning('Suspicious credential pattern detected', [
                    'email' => $email,
                    'context' => $context,
                    'ip' => request()->ip()
                ]);
            }
        }

        // Check for repeated identical submissions
        if (isset($context['user_id'])) {
            $submissionHash = md5(serialize($data));
            $cacheKey = "submission_hash_{$context['user_id']}_" . $submissionHash;
            
            if (ResilientCacheService::safeGet($cacheKey)) {
                Log::warning('Duplicate submission detected', [
                    'user_id' => $context['user_id'],
                    'hash' => $submissionHash,
                    'context' => $context
                ]);
                throw new Exception('Duplicate submission detected');
            }
            
            // Cache this submission hash for 5 minutes
            ResilientCacheService::safePut($cacheKey, true, 300);
        }
    }

    /**
     * Validate file uploads with security checks
     */
    public static function validateFileUpload($file, array $allowedTypes = [], int $maxSizeMB = null): array
    {
        $maxSizeMB = $maxSizeMB ?? self::MAX_FILE_SIZE_MB;
        
        try {
            if (!$file || !$file->isValid()) {
                throw new Exception('Invalid file upload');
            }

            // Check file size
            $sizeBytes = $file->getSize();
            $maxSizeBytes = $maxSizeMB * 1024 * 1024;
            
            if ($sizeBytes > $maxSizeBytes) {
                throw new Exception("File size exceeds {$maxSizeMB}MB limit");
            }

            // Check MIME type
            $mimeType = $file->getMimeType();
            $allowedMimes = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                'application/pdf', 'text/plain'
            ];
            
            if (!empty($allowedTypes)) {
                $allowedMimes = array_intersect($allowedMimes, $allowedTypes);
            }
            
            if (!in_array($mimeType, $allowedMimes)) {
                throw new Exception('File type not allowed');
            }

            // Check file extension
            $extension = strtolower($file->getClientOriginalExtension());
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'txt'];
            
            if (!in_array($extension, $allowedExtensions)) {
                throw new Exception('File extension not allowed');
            }

            // Security checks for image files
            if (in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                $imageInfo = getimagesize($file->getPathname());
                if (!$imageInfo) {
                    throw new Exception('Invalid image file');
                }
                
                // Check for reasonable dimensions
                if ($imageInfo[0] > 5000 || $imageInfo[1] > 5000) {
                    throw new Exception('Image dimensions too large');
                }
            }

            return [
                'valid' => true,
                'mime_type' => $mimeType,
                'extension' => $extension,
                'size_bytes' => $sizeBytes,
                'original_name' => $file->getClientOriginalName()
            ];
            
        } catch (Exception $e) {
            Log::warning('File upload validation failed', [
                'error' => $e->getMessage(),
                'file_info' => [
                    'original_name' => $file->getClientOriginalName() ?? 'unknown',
                    'size' => $file->getSize() ?? 0,
                    'mime_type' => $file->getMimeType() ?? 'unknown'
                ],
                'ip' => request()->ip()
            ]);
            
            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get array depth
     */
    private static function getArrayDepth(array $array): int
    {
        $maxDepth = 1;
        
        foreach ($array as $value) {
            if (is_array($value)) {
                $depth = self::getArrayDepth($value) + 1;
                if ($depth > $maxDepth) {
                    $maxDepth = $depth;
                }
            }
        }
        
        return $maxDepth;
    }

    /**
     * Rate limiting for validation requests
     */
    public static function checkRateLimit(string $key, int $maxAttempts = 60, int $timeWindowSeconds = 60): bool
    {
        $attempts = ResilientCacheService::safeGet($key, 0);
        
        if ($attempts >= $maxAttempts) {
            Log::warning('Rate limit exceeded for validation', [
                'key' => $key,
                'attempts' => $attempts,
                'max_attempts' => $maxAttempts,
                'ip' => request()->ip()
            ]);
            return false;
        }
        
        ResilientCacheService::safePut($key, $attempts + 1, $timeWindowSeconds);
        return true;
    }

    /**
     * Comprehensive quiz answer validation
     */
    public static function validateQuizAnswers(array $answers, array $questionIds): array
    {
        $validated = [];
        $errors = [];
        
        foreach ($questionIds as $questionId) {
            $answer = $answers[$questionId] ?? null;
            
            // Allow null/empty answers (unanswered questions)
            if ($answer === null || $answer === '') {
                $validated[$questionId] = 'unanswered';
                continue;
            }
            
            // Validate answer format
            if (!is_string($answer) && !is_numeric($answer)) {
                $errors[$questionId] = 'Answer must be a string or number';
                continue;
            }
            
            // Convert to string and validate length
            $answer = (string)$answer;
            if (strlen($answer) > 2000) {
                $errors[$questionId] = 'Answer too long (max 2000 characters)';
                continue;
            }
            
            // Security check
            foreach (self::DANGEROUS_PATTERNS as $pattern) {
                if (preg_match($pattern, $answer)) {
                    $errors[$questionId] = 'Answer contains invalid content';
                    continue 2;
                }
            }
            
            $validated[$questionId] = trim($answer);
        }
        
        return [
            'validated' => $validated,
            'errors' => $errors
        ];
    }
}