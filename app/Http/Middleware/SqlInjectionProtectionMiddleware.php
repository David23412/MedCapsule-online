<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SqlInjectionProtectionMiddleware
{
    /**
     * GOLDEN STANDARD: Comprehensive SQL injection patterns
     */
    private const SQL_INJECTION_PATTERNS = [
        // Basic SQL injection patterns
        '/(\s|^)(union|select|insert|update|delete|drop|create|alter|exec|execute)\s+/i',
        '/(\s|^)(or|and)\s+[\'"]*\s*[\d\w]+\s*[\'"]*\s*=\s*[\'"]*\s*[\d\w]+/i',
        '/(\s|^)(or|and)\s+[\'"]*\s*\d+\s*[\'"]*\s*=\s*[\'"]*\s*\d+/i',
        
        // Advanced injection patterns
        '/[\'"]\s*;\s*(drop|delete|update|insert|create|alter)/i',
        '/(\s|^)(0x[a-f0-9]+|char\(|ascii\(|substring\()/i',
        '/(\s|^)(waitfor\s+delay|benchmark\(|sleep\()/i',
        '/(\s|^)(information_schema|mysql\.|sys\.)/i',
        
        // Comment-based injections
        '/\/\*[\s\S]*?\*\//',
        '/--[\s\S]*$/',
        '/(\s|^)#[\s\S]*$/',
        
        // Union-based injections
        '/(\s|^)union\s+(all\s+)?select/i',
        '/(\s|^)order\s+by\s+\d+/i',
        '/(\s|^)group\s+by\s+[\w\s,]+\s+having/i',
        
        // Boolean-based blind injections
        '/(\s|^)(true|false)\s*[\)]*\s*(and|or)/i',
        '/(\s|^)\d+\s*=\s*\d+(\s+(and|or)|\s*--)/i',
        '/(\s|^)[\'"]\s*[\'"]\s*=\s*[\'"]\s*[\'"](\s+(and|or)|\s*--)/i',
        
        // Time-based blind injections
        '/(\s|^)if\s*\(\s*[\w\s=\'\"]+\s*,\s*sleep\s*\(\s*\d+\s*\)\s*,/i',
        '/(\s|^)case\s+when\s+[\w\s=\'\"]+\s+then\s+sleep\s*\(\s*\d+\s*\)/i',
        
        // Error-based injections
        '/(\s|^)(extractvalue|updatexml|exp|floor|rand)\s*\(/i',
        '/(\s|^)convert\s*\(\s*int\s*,/i',
        '/(\s|^)cast\s*\(\s*[\w\s]+\s+as\s+int\s*\)/i',
        
        // Stacked queries
        '/;\s*(select|insert|update|delete|drop|create|alter)/i',
        
        // Hex encoding attempts
        '/0x[a-f0-9]{2,}/i',
        
        // PHP function calls that could be dangerous
        '/(\s|^)(eval|exec|system|shell_exec|passthru|file_get_contents|fopen|file|readfile)\s*\(/i',
        
        // Database-specific functions
        '/(\s|^)(load_file|into\s+outfile|into\s+dumpfile)/i',
        '/(\s|^)(pg_sleep|pg_version|version\(\))/i',
        '/(\s|^)(user\(\)|database\(\)|@@version)/i'
    ];
    
    /**
     * Suspicious character sequences
     */
    private const SUSPICIOUS_SEQUENCES = [
        "';", '";', "' or ", '" or ', "' and ", '" and ',
        "1=1", "1=0", "true=true", "false=false",
        "../", "..\\", "<script", "</script>",
        "javascript:", "vbscript:", "onload=", "onerror="
    ];
    
    /**
     * Maximum allowed special characters ratio (Increased for medical formulas)
     */
    private const MAX_SPECIAL_CHAR_RATIO = 0.45;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = uniqid('sql_protect_');
        
        // Skip protection for safe routes (if configured)
        if ($this->shouldSkipProtection($request)) {
            return $next($request);
        }
        
        Log::info('SQL injection protection check started', [
            'request_id' => $requestId,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => auth()->id(),
            'ip' => $request->ip()
        ]);
        
        // Check all input data
        $threatDetected = $this->scanForThreats($request, $requestId);
        
        if ($threatDetected['detected']) {
            return $this->blockRequest($request, $threatDetected, $requestId);
        }
        
        Log::info('SQL injection protection check passed', [
            'request_id' => $requestId
        ]);
        
        return $next($request);
    }
    
    /**
     * Check if protection should be skipped for this route
     */
    private function shouldSkipProtection(Request $request): bool
    {
        $skipRoutes = [
            // Add routes that need to be skipped if any
            // Example: 'api/file-upload'
        ];
        
        $currentRoute = $request->path();
        return in_array($currentRoute, $skipRoutes);
    }
    
    /**
     * Scan all request data for SQL injection threats
     */
    private function scanForThreats(Request $request, string $requestId): array
    {
        $threats = [];
        
        // Scan URL parameters
        $urlThreats = $this->scanArray($request->query->all(), 'url_parameters');
        if (!empty($urlThreats)) {
            $threats = array_merge($threats, $urlThreats);
        }
        
        // Scan POST data
        $postThreats = $this->scanArray($request->request->all(), 'post_data');
        if (!empty($postThreats)) {
            $threats = array_merge($threats, $postThreats);
        }
        
        // Scan JSON payload
        if ($request->isJson()) {
            $jsonData = $request->json()->all();
            $jsonThreats = $this->scanArray($jsonData, 'json_payload');
            if (!empty($jsonThreats)) {
                $threats = array_merge($threats, $jsonThreats);
            }
        }
        
        // Scan headers for injection attempts
        $headerThreats = $this->scanHeaders($request);
        if (!empty($headerThreats)) {
            $threats = array_merge($threats, $headerThreats);
        }
        
        // Scan raw content
        $rawContent = $request->getContent();
        if (!empty($rawContent) && !$request->isJson()) {
            $contentThreats = $this->scanString($rawContent, 'raw_content');
            if (!empty($contentThreats)) {
                $threats = array_merge($threats, $contentThreats);
            }
        }
        
        return [
            'detected' => !empty($threats),
            'threats' => $threats,
            'count' => count($threats)
        ];
    }
    
    /**
     * Scan array data recursively
     */
    private function scanArray(array $data, string $source, string $path = ''): array
    {
        $threats = [];
        
        foreach ($data as $key => $value) {
            $currentPath = $path ? "{$path}.{$key}" : $key;
            
            if (is_array($value)) {
                $subThreats = $this->scanArray($value, $source, $currentPath);
                $threats = array_merge($threats, $subThreats);
            } elseif (is_string($value)) {
                $stringThreats = $this->scanString($value, $source, $currentPath);
                $threats = array_merge($threats, $stringThreats);
            }
            
            // Also scan the key itself
            if (is_string($key)) {
                $keyThreats = $this->scanString($key, $source, "{$currentPath}_key");
                $threats = array_merge($threats, $keyThreats);
            }
        }
        
        return $threats;
    }
    
    /**
     * Scan string for SQL injection patterns
     */
    private function scanString(string $value, string $source, string $path = ''): array
    {
        $threats = [];
        
        // Skip empty or very short strings
        if (strlen($value) < 2) {
            return $threats;
        }
        
        // Check against SQL injection patterns
        foreach (self::SQL_INJECTION_PATTERNS as $pattern) {
            if (preg_match($pattern, $value, $matches)) {
                $threats[] = [
                    'type' => 'sql_injection_pattern',
                    'source' => $source,
                    'path' => $path,
                    'pattern' => $pattern,
                    'match' => $matches[0] ?? '',
                    'value_snippet' => $this->createSnippet($value, $matches[0] ?? ''),
                    'severity' => 'high'
                ];
            }
        }
        
        // Check for suspicious character sequences
        foreach (self::SUSPICIOUS_SEQUENCES as $sequence) {
            if (stripos($value, $sequence) !== false) {
                $threats[] = [
                    'type' => 'suspicious_sequence',
                    'source' => $source,
                    'path' => $path,
                    'sequence' => $sequence,
                    'value_snippet' => $this->createSnippet($value, $sequence),
                    'severity' => 'medium'
                ];
            }
        }
        
        // Check special character ratio (with scientific text awareness)
        $charMeta = $this->calculateSpecialCharRatio($value);
        $specialCharRatio = $charMeta['ratio'];
        
        if ($specialCharRatio > self::MAX_SPECIAL_CHAR_RATIO && strlen($value) > 15 && !$charMeta['is_scientific']) {
            $threats[] = [
                'type' => 'high_special_char_ratio',
                'source' => $source,
                'path' => $path,
                'ratio' => $specialCharRatio,
                'threshold' => self::MAX_SPECIAL_CHAR_RATIO,
                'value_snippet' => substr($value, 0, 100),
                'severity' => 'low'
            ];
        }
        
        return $threats;
    }
    
    /**
     * Scan HTTP headers for injection attempts
     */
    private function scanHeaders(Request $request): array
    {
        $threats = [];
        $dangerousHeaders = ['user-agent', 'referer', 'x-forwarded-for', 'cookie'];
        
        foreach ($dangerousHeaders as $header) {
            $value = $request->header($header);
            if ($value) {
                $headerThreats = $this->scanString($value, 'header', $header);
                $threats = array_merge($threats, $headerThreats);
            }
        }
        
        return $threats;
    }
    
    /**
     * Calculate the ratio of special characters in a string
     */
    private function calculateSpecialCharRatio(string $value): array
    {
        if (empty($value)) {
            return ['ratio' => 0, 'is_scientific' => false];
        }
        
        // Detect scientific/medical notation which often has high special char density
        // e.g. "Na+ / Cl- [conc] > 10%"
        $scientificPattern = '/[a-zA-Z]{1,2}[\+\-\d]|\d+[\%\+]|\[[a-zA-Z\s]+\]/i';
        $isScientific = (bool) preg_match($scientificPattern, $value);

        // Standard medical/math symbols should not count towards "Attack Density"
        $safeSymbols = ['+', '-', '/', '*', '=', '>', '<', '[', ']', '(', ')', '%', '.', ',', ':', ';'];
        $cleanValue = str_replace($safeSymbols, '', $value);
        
        $specialChars = preg_match_all('/[^a-zA-Z0-9\s]/', $cleanValue);
        $ratio = $specialChars / strlen($value);

        return [
            'ratio' => $ratio,
            'is_scientific' => $isScientific
        ];
    }
    
    /**
     * Create a snippet showing the problematic part of the value
     */
    private function createSnippet(string $value, string $match): string
    {
        $position = stripos($value, $match);
        if ($position === false) {
            return substr($value, 0, 100);
        }
        
        $start = max(0, $position - 20);
        $length = min(100, strlen($value) - $start);
        
        return substr($value, $start, $length);
    }
    
    /**
     * Block the request and log the threat
     */
    private function blockRequest(Request $request, array $threatData, string $requestId): Response
    {
        Log::critical('SQL injection attempt detected and blocked', [
            'request_id' => $requestId,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'threats' => $threatData['threats'],
            'threat_count' => $threatData['count'],
            'timestamp' => now()->toISOString()
        ]);
        
        // Count high severity threats
        $highSeverityCount = count(array_filter($threatData['threats'], function($threat) {
            return ($threat['severity'] ?? 'low') === 'high';
        }));
        
        // Determine response based on threat severity
        if ($highSeverityCount > 0) {
            // High severity - immediate block
            return response()->json([
                'error' => 'Request blocked for security reasons',
                'code' => 'SQL_INJECTION_DETECTED',
                'request_id' => $requestId
            ], 403);
        } else {
            // Medium/Low severity - warning but allow
            Log::warning('Potential SQL injection patterns detected but allowed', [
                'request_id' => $requestId,
                'threat_count' => $threatData['count']
            ]);
            
            // Could implement stricter rules here if needed
            return response()->json([
                'error' => 'Request contains potentially dangerous content',
                'code' => 'SUSPICIOUS_CONTENT_DETECTED',
                'request_id' => $requestId
            ], 400);
        }
    }
}