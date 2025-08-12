<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SqlInjectionProtectionMiddleware
{
    /**
     * SQL injection patterns to detect various attack vectors
     */
    private array $sqlPatterns = [
        // Union-based injections
        '/(\s|^)union\s+(all\s+)?select/i',
        '/(\s|^)union\s+(distinct\s+)?select/i',
        
        // Boolean-based blind injections  
        '/(\s|^)(true|false)\s*[\)]*\s*(and|or)/i',
        '/(\s|^)(1|0)\s*=\s*(1|0)\s*(and|or)/i',
        
        // Time-based blind injections
        '/(\s|^)if\s*\(\s*[\w\s=\'\"]+\s*,\s*sleep\s*\(\s*\d+\s*\)\s*,/i',
        '/(\s|^)(pg_sleep|sleep|delay|waitfor)\s*\(/i',
        
        // Error-based injections
        '/(\s|^)(extractvalue|updatexml|exp|floor|rand)\s*\(/i',
        '/(\s|^)(cast|convert|char|ascii|substring|mid)\s*\(/i',
        
        // Stacked queries
        '/;\s*(drop|create|alter|delete|insert|update)\s+/i',
        '/;\s*(exec|execute|sp_)\s*/i',
        
        // Comment-based
        '/\/\*.*\*\//s',
        '/--\s*.*/s',
        '/#.*$/m',
        
        // Database specific functions
        '/(\s|^)(load_file|into\s+outfile|dumpfile)\s*\(/i',
        '/(\s|^)(information_schema|mysql|pg_catalog)\./i',
        '/(\s|^)(sysobjects|syscolumns|systables)\s/i',
        
        // Advanced patterns
        '/(\s|^)(benchmark|heavy_query)\s*\(/i',
        '/(\s|^)(user|database|version|@@)\s*\(/i',
        '/(\s|^)0x[0-9a-f]+/i', // Hex encoding
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check all input vectors
        $inputData = $this->getAllInputData($request);
        
        foreach ($inputData as $source => $data) {
            if (is_string($data)) {
                $threat = $this->detectSqlInjection($data);
                if ($threat) {
                    $this->logThreat($request, $source, $data, $threat);
                    return $this->blockRequest($threat['severity']);
                }
            } elseif (is_array($data)) {
                $threat = $this->scanArrayRecursively($data);
                if ($threat) {
                    $this->logThreat($request, $source, $data, $threat);
                    return $this->blockRequest($threat['severity']);
                }
            }
        }

        return $next($request);
    }

    /**
     * Get all input data from various sources
     */
    private function getAllInputData(Request $request): array
    {
        return [
            'query' => $request->query(),
            'post' => $request->post(),
            'json' => $request->json() ? $request->json()->all() : [],
            'headers' => $request->headers->all(),
            'path' => $request->path(),
            'url' => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer'),
        ];
    }

    /**
     * Detect SQL injection patterns in a string
     */
    private function detectSqlInjection(string $input): ?array
    {
        // Normalize input for better detection
        $normalized = strtolower(trim($input));
        
        // Skip empty strings
        if (empty($normalized)) {
            return null;
        }

        // Check each pattern
        foreach ($this->sqlPatterns as $pattern) {
            if (preg_match($pattern, $normalized)) {
                return [
                    'pattern' => $pattern,
                    'severity' => $this->getSeverity($pattern),
                    'input' => substr($input, 0, 500), // Limit logged input
                ];
            }
        }

        // Additional heuristics
        return $this->checkHeuristics($normalized, $input);
    }

    /**
     * Scan array data recursively
     */
    private function scanArrayRecursively(array $data): ?array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $threat = $this->detectSqlInjection($value);
                if ($threat) {
                    return $threat;
                }
            } elseif (is_array($value)) {
                $threat = $this->scanArrayRecursively($value);
                if ($threat) {
                    return $threat;
                }
            }
        }
        return null;
    }

    /**
     * Additional heuristic checks
     */
    private function checkHeuristics(string $normalized, string $original): ?array
    {
        // Check for suspicious keyword combinations
        $suspiciousKeywords = ['select', 'union', 'from', 'where', 'drop', 'insert', 'update', 'delete'];
        $keywordCount = 0;
        
        foreach ($suspiciousKeywords as $keyword) {
            if (strpos($normalized, $keyword) !== false) {
                $keywordCount++;
            }
        }
        
        // If multiple SQL keywords present, flag as suspicious
        if ($keywordCount >= 3) {
            return [
                'pattern' => 'heuristic_multiple_keywords',
                'severity' => 'medium',
                'input' => substr($original, 0, 500),
                'keyword_count' => $keywordCount,
            ];
        }

        // Check for encoded patterns
        if (preg_match('/(%27|%22|%2d|%2f|%5c)/i', $original)) {
            return [
                'pattern' => 'url_encoded_injection',
                'severity' => 'high',
                'input' => substr($original, 0, 500),
            ];
        }

        return null;
    }

    /**
     * Determine severity based on pattern
     */
    private function getSeverity(string $pattern): string
    {
        $highSeverityPatterns = [
            'union',
            'drop',
            'delete',
            'alter',
            'exec',
            'load_file',
            'into\s+outfile',
        ];

        foreach ($highSeverityPatterns as $highPattern) {
            if (stripos($pattern, $highPattern) !== false) {
                return 'critical';
            }
        }

        return 'high';
    }

    /**
     * Log security threat
     */
    private function logThreat(Request $request, string $source, $data, array $threat): void
    {
        Log::warning('SQL Injection Attempt Detected', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'source' => $source,
            'pattern' => $threat['pattern'],
            'severity' => $threat['severity'],
            'input_preview' => $threat['input'],
            'user_id' => auth()->id(),
            'session_id' => session()->getId(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Block request based on severity
     */
    private function blockRequest(string $severity): Response
    {
        $statusCode = match($severity) {
            'critical' => 403,
            'high' => 403,
            'medium' => 422,
            default => 400,
        };

        $message = match($severity) {
            'critical' => 'Request blocked due to critical security threat',
            'high' => 'Request blocked due to security violation',
            'medium' => 'Request contains suspicious content',
            default => 'Invalid request format',
        };

        return response()->json([
            'error' => $message,
            'code' => 'SECURITY_VIOLATION',
            'severity' => $severity,
        ], $statusCode);
    }
}