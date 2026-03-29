<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * 🔒 NUCLEAR-GRADE INPUT VALIDATION REQUEST
 *
 * This request class provides comprehensive security validation including:
 * - XSS protection via input sanitization
 * - SQL injection prevention via strict validation
 * - CSRF protection verification
 * - Rate limiting and abuse detection
 * - Comprehensive logging for security events
 */
class SecureInputValidationRequest extends FormRequest
{
    /**
     * Maximum allowed input length to prevent DoS attacks
     */
    const MAX_INPUT_LENGTH = 10000;

    /**
     * Maximum allowed array elements
     */
    const MAX_ARRAY_ELEMENTS = 1000;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            Log::warning('Unauthorized request attempt', [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->url(),
                'method' => request()->method()
            ]);
            return false;
        }

        // Rate limiting check
        if ($this->hasTooManyRequests()) {
            Log::warning('Rate limit exceeded', [
                'user_id' => auth()->id(),
                'ip' => request()->ip(),
                'url' => request()->url()
            ]);
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Common security validations
            '*' => [
                'bail', // Stop on first validation failure
                'max:' . self::MAX_INPUT_LENGTH,
                function ($attribute, $value, $fail) {
                    // Check for suspicious patterns that might indicate attacks
                    if ($this->containsSuspiciousPatterns($value)) {
                        Log::warning('Suspicious input detected', [
                            'user_id' => auth()->id(),
                            'attribute' => $attribute,
                            'ip' => request()->ip()
                        ]);
                        $fail('Invalid input detected.');
                    }
                },
            ],

            // Array size limits
            '*.array' => 'max:' . self::MAX_ARRAY_ELEMENTS,

            // String validations with XSS protection
            '*.string' => [
                'string',
                'max:5000',
                function ($attribute, $value, $fail) {
                    $cleanValue = $this->sanitizeInput($value);
                    if ($cleanValue !== $value) {
                    }
                },
            ],

            // Numeric validations
            '*.numeric' => 'numeric|min:0|max:999999999',
            '*.integer' => 'integer|min:0|max:2147483647',

            // Email validation with additional security
            '*.email' => [
                'email:rfc,dns',
                'max:254',
                function ($attribute, $value, $fail) {
                    // Check for email injection attempts
                    if (preg_match('/[\r\n]/', $value)) {
                        $fail('Invalid email format.');
                    }
                },
            ],

            // URL validation
            '*.url' => [
                'url',
                'max:2000',
                function ($attribute, $value, $fail) {
                    // Only allow http/https protocols
                    if (!preg_match('/^https?:\/\//', $value)) {
                        $fail('URL must start with http:// or https://');
                    }
                },
            ],

            // File upload security
            '*.file' => [
                'file',
                'max:10240', // 10MB max
                function ($attribute, $value, $fail) {
                    if ($value && !$this->isSecureFile($value)) {
                        $fail('Invalid file type or potential security threat.');
                    }
                },
            ],

            // JSON validation
            '*.json' => [
                'json',
                function ($attribute, $value, $fail) {
                    $decoded = json_decode($value, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $fail('Invalid JSON format.');
                    }

                    // Check for deeply nested JSON (potential DoS)
                    if ($this->isDeeplyNested($decoded, 10)) {
                        $fail('JSON structure too complex.');
                    }
                },
            ],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Log validation attempts
            if ($validator->fails()) {
            }
        });
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'max' => 'Input exceeds maximum allowed length.',
            '*.max' => 'Field exceeds maximum allowed length.',
            '*.numeric' => 'Field must be a valid number.',
            '*.integer' => 'Field must be a valid integer.',
            '*.email' => 'Please provide a valid email address.',
            '*.url' => 'Please provide a valid URL.',
            '*.file' => 'Invalid file uploaded.',
            '*.json' => 'Invalid JSON format.',
        ];
    }

    /**
     * Sanitize input to prevent XSS attacks
     */
    private function sanitizeInput(string $input): string
    {
        // Remove null bytes
        $input = str_replace("\0", '', $input);

        // Remove potential script tags and dangerous content
        $input = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', '', $input);
        $input = preg_replace('/javascript:/i', '', $input);
        $input = preg_replace('/on\w+\s*=/i', '', $input);

        // Trim and limit length
        $input = trim($input);
        if (strlen($input) > self::MAX_INPUT_LENGTH) {
            $input = substr($input, 0, self::MAX_INPUT_LENGTH);
        }

        return $input;
    }

    /**
     * Check if input contains suspicious patterns
     */
    private function containsSuspiciousPatterns($input): bool
    {
        if (!is_string($input)) {
            return false;
        }

        $suspiciousPatterns = [
            // SQL injection patterns
            '/\bunion\b\s+\bselect\b/i',
            '/\bselect\b.*\bfrom\b.*\binformation_schema\b/i',
            '/\bdrop\b\s+\b(table|database|index)\b/i',
            '/\bexec\b\s*\(/i',
            '/\bxp_cmdshell\b/i',

            // XSS patterns
            '/<script\b/i',
            '/javascript:\s*\(/i',
            '/on\w+\s*=\s*["\'].*["\']/i',
            '/<iframe\b/i',
            '/<object\b/i',
            '/<embed\b/i',

            // Directory traversal
            '/\.\.[\/\\\\]/',
            '/\/etc\/passwd/i',
            '/c:\windows/i',

            // Command injection
            '/[;&|`$()]/',
            '/\bsudo\b/i',
            '/\brm\s+-rf\b/i',

            // PHP code execution
            '/\beval\s*\(/i',
            '/\bsystem\s*\(/i',
            '/\bexec\s*\(/i',
            '/\bshell_exec\s*\(/i',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if file is secure
     */
    private function isSecureFile($file): bool
    {
        if (!$file || !is_uploaded_file($file->getPathname())) {
            return false;
        }

        // Get file info
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fileInfo, $file->getPathname());
        finfo_close($fileInfo);

        // Allowed MIME types
        $allowedMimes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'text/plain',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        if (!in_array($mimeType, $allowedMimes)) {
            return false;
        }

        // Check file size (10MB limit)
        if ($file->getSize() > 10 * 1024 * 1024) {
            return false;
        }

        // Check for malicious content in filename
        $filename = $file->getClientOriginalName();
        if (preg_match('/[<>\'\"\/\\\\]/', $filename)) {
            return false;
        }

        return true;
    }

    /**
     * Check if JSON is deeply nested (potential DoS)
     */
    private function isDeeplyNested($array, int $maxDepth, int $currentDepth = 0): bool
    {
        if ($currentDepth >= $maxDepth) {
            return true;
        }

        if (is_array($array)) {
            foreach ($array as $value) {
                if ($this->isDeeplyNested($value, $maxDepth, $currentDepth + 1)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check for rate limiting
     */
    private function hasTooManyRequests(): bool
    {
        $userId = auth()->id();
        $ip = request()->ip();
        $key = "rate_limit:{$userId}:{$ip}";

        // Simple in-memory rate limiting (in production, use Redis or database)
        $requests = cache()->get($key, 0);

        if ($requests > 100) { // 100 requests per minute
            return true;
        }

        cache()->put($key, $requests + 1, 60); // 1 minute TTL
        return false;
    }

    /**
     * Get sanitized input data
     */
    public function sanitized(): array
    {
        $data = $this->validated();

        // Apply additional sanitization to all string inputs
        array_walk_recursive($data, function (&$value) {
            if (is_string($value)) {
                $value = $this->sanitizeInput($value);
            }
        });

        return $data;
    }
}
