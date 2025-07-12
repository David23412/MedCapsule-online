<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class SecurityService
{
    /**
     * Maximum regex execution time in seconds
     */
    private const MAX_REGEX_TIME = 5;
    
    /**
     * Maximum backtrack limit for regex operations
     */
    private const MAX_BACKTRACK_LIMIT = 100000;
    
    /**
     * Safe regex replace with timeout protection
     *
     * @param string|array $pattern
     * @param string|array $replacement
     * @param string $subject
     * @param int $limit
     * @return string
     * @throws Exception
     */
    public static function safeRegexReplace($pattern, $replacement, string $subject, int $limit = -1): string
    {
        // Input validation
        if (empty($subject)) {
            return $subject;
        }
        
        // Length protection
        if (strlen($subject) > 50000) {
            throw new Exception('Input too long for regex processing');
        }
        
        // Store original settings
        $originalBacktrackLimit = ini_get('pcre.backtrack_limit');
        $originalTimeLimit = ini_get('max_execution_time');
        
        try {
            // Set safe limits
            ini_set('pcre.backtrack_limit', self::MAX_BACKTRACK_LIMIT);
            
            // Set timeout alarm if available
            if (function_exists('pcntl_alarm')) {
                pcntl_alarm(self::MAX_REGEX_TIME);
            }
            
            // Perform regex operation
            $result = preg_replace($pattern, $replacement, $subject, $limit);
            
            // Check for regex errors
            if ($result === null) {
                $error = preg_last_error();
                throw new Exception("Regex operation failed with error code: $error");
            }
            
            // Disable alarm
            if (function_exists('pcntl_alarm')) {
                pcntl_alarm(0);
            }
            
            return $result;
            
        } catch (Exception $e) {
            Log::error('Regex operation failed', [
                'error' => $e->getMessage(),
                'pattern' => is_array($pattern) ? implode('|', $pattern) : $pattern,
                'subject_length' => strlen($subject)
            ]);
            
            // Return original subject on error (fail-safe)
            return $subject;
            
        } finally {
            // Restore original settings
            ini_set('pcre.backtrack_limit', $originalBacktrackLimit);
            
            // Disable alarm in case of exception
            if (function_exists('pcntl_alarm')) {
                pcntl_alarm(0);
            }
        }
    }
    
    /**
     * Safe regex match with timeout protection
     *
     * @param string $pattern
     * @param string $subject
     * @param array &$matches
     * @param int $flags
     * @return bool
     * @throws Exception
     */
    public static function safeRegexMatch(string $pattern, string $subject, array &$matches = [], int $flags = 0): bool
    {
        // Input validation
        if (empty($subject)) {
            return false;
        }
        
        // Length protection
        if (strlen($subject) > 50000) {
            throw new Exception('Input too long for regex processing');
        }
        
        // Store original settings
        $originalBacktrackLimit = ini_get('pcre.backtrack_limit');
        
        try {
            // Set safe limits
            ini_set('pcre.backtrack_limit', self::MAX_BACKTRACK_LIMIT);
            
            // Set timeout alarm if available
            if (function_exists('pcntl_alarm')) {
                pcntl_alarm(self::MAX_REGEX_TIME);
            }
            
            // Perform regex operation
            $result = preg_match($pattern, $subject, $matches, $flags);
            
            // Check for regex errors
            if ($result === false) {
                $error = preg_last_error();
                throw new Exception("Regex match failed with error code: $error");
            }
            
            // Disable alarm
            if (function_exists('pcntl_alarm')) {
                pcntl_alarm(0);
            }
            
            return $result === 1;
            
        } catch (Exception $e) {
            Log::error('Regex match failed', [
                'error' => $e->getMessage(),
                'pattern' => $pattern,
                'subject_length' => strlen($subject)
            ]);
            
            // Return false on error (fail-safe)
            return false;
            
        } finally {
            // Restore original settings
            ini_set('pcre.backtrack_limit', $originalBacktrackLimit);
            
            // Disable alarm in case of exception
            if (function_exists('pcntl_alarm')) {
                pcntl_alarm(0);
            }
        }
    }
    
    /**
     * Validate input size and content
     *
     * @param string $input
     * @param int $maxLength
     * @param bool $allowHtml
     * @return bool
     */
    public static function validateInput(string $input, int $maxLength = 10000, bool $allowHtml = false): bool
    {
        // Length check
        if (strlen($input) > $maxLength) {
            return false;
        }
        
        // HTML check
        if (!$allowHtml && $input !== strip_tags($input)) {
            return false;
        }
        
        // Check for obvious injection attempts
        $suspiciousPatterns = [
            '/<script[^>]*>.*?<\/script>/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/on\w+\s*=/i',
            '/expression\s*\(/i',
            '/eval\s*\(/i',
            '/exec\s*\(/i',
            '/system\s*\(/i',
            '/shell_exec\s*\(/i',
            '/passthru\s*\(/i',
            '/union\s+select/i',
            '/drop\s+table/i',
            '/truncate\s+table/i'
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (self::safeRegexMatch($pattern, $input)) {
                Log::warning('Suspicious input detected', [
                    'pattern' => $pattern,
                    'input' => substr($input, 0, 100) . '...'
                ]);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Sanitize text input safely
     *
     * @param string $input
     * @param int $maxLength
     * @return string
     */
    public static function sanitizeInput(string $input, int $maxLength = 10000): string
    {
        // Trim and limit length
        $input = trim($input);
        if (strlen($input) > $maxLength) {
            $input = substr($input, 0, $maxLength);
        }
        
        // Remove null bytes
        $input = str_replace("\0", '', $input);
        
        // Remove control characters except newlines and tabs
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
        
        // HTML encode dangerous characters
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $input;
    }
}