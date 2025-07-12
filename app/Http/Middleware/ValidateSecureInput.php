<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\SecurityService;

class ValidateSecureInput
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip validation for certain routes
        $skipRoutes = [
            'api/health', 
            'api/ping',
            'login',
            'register'
        ];
        
        $currentRoute = $request->route() ? $request->route()->getName() : $request->path();
        
        if (in_array($currentRoute, $skipRoutes)) {
            return $next($request);
        }
        
        // Validate all input data
        $this->validateInputData($request);
        
        // Check for suspicious patterns
        $this->checkSuspiciousPatterns($request);
        
        // Validate file uploads
        $this->validateFileUploads($request);
        
        // Rate limiting check
        $this->checkRateLimit($request);
        
        return $next($request);
    }
    
    /**
     * Validate input data sizes and content
     *
     * @param Request $request
     * @throws \Exception
     */
    private function validateInputData(Request $request): void
    {
        $allInput = $request->all();
        
        foreach ($allInput as $key => $value) {
            if (is_string($value)) {
                // Size validation
                if (strlen($value) > 50000) {
                    Log::warning('Input too large', [
                        'key' => $key,
                        'size' => strlen($value),
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent()
                    ]);
                    
                    throw new \Exception('Input size exceeds maximum allowed length');
                }
                
                // Content validation
                if (!SecurityService::validateInput($value, 50000, false)) {
                    Log::warning('Suspicious input detected', [
                        'key' => $key,
                        'content' => substr($value, 0, 100) . '...',
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent()
                    ]);
                    
                    throw new \Exception('Invalid input content detected');
                }
            }
            
            // Validate arrays
            if (is_array($value)) {
                $this->validateArrayInput($key, $value, $request);
            }
        }
    }
    
    /**
     * Validate array input recursively
     *
     * @param string $key
     * @param array $array
     * @param Request $request
     * @throws \Exception
     */
    private function validateArrayInput(string $key, array $array, Request $request): void
    {
        // Prevent array size attacks
        if (count($array) > 1000) {
            Log::warning('Array too large', [
                'key' => $key,
                'size' => count($array),
                'ip' => $request->ip()
            ]);
            
            throw new \Exception('Array size exceeds maximum allowed length');
        }
        
        foreach ($array as $subKey => $subValue) {
            if (is_string($subValue)) {
                if (strlen($subValue) > 10000) {
                    throw new \Exception('Array element too large');
                }
                
                if (!SecurityService::validateInput($subValue, 10000, false)) {
                    throw new \Exception('Invalid array element content');
                }
            }
            
            if (is_array($subValue)) {
                $this->validateArrayInput($key . '.' . $subKey, $subValue, $request);
            }
        }
    }
    
    /**
     * Check for suspicious patterns in request
     *
     * @param Request $request
     * @throws \Exception
     */
    private function checkSuspiciousPatterns(Request $request): void
    {
        $suspiciousHeaders = [
            'X-Forwarded-For',
            'X-Real-IP',
            'X-Originating-IP',
            'Referer',
            'User-Agent'
        ];
        
        foreach ($suspiciousHeaders as $header) {
            $headerValue = $request->header($header);
            if ($headerValue && strlen($headerValue) > 500) {
                Log::warning('Suspicious header detected', [
                    'header' => $header,
                    'value' => substr($headerValue, 0, 100) . '...',
                    'ip' => $request->ip()
                ]);
                
                throw new \Exception('Suspicious request header detected');
            }
        }
        
        // Check for SQL injection patterns in URL
        $url = $request->fullUrl();
        $sqlPatterns = [
            'union.*select',
            'select.*from',
            'drop.*table',
            'delete.*from',
            'update.*set',
            'insert.*into',
            'exec.*sp_',
            'execute.*sp_'
        ];
        
        foreach ($sqlPatterns as $pattern) {
            if (SecurityService::safeRegexMatch('/' . $pattern . '/i', $url)) {
                Log::warning('SQL injection attempt detected in URL', [
                    'url' => $url,
                    'pattern' => $pattern,
                    'ip' => $request->ip()
                ]);
                
                throw new \Exception('Malicious request detected');
            }
        }
    }
    
    /**
     * Validate file uploads
     *
     * @param Request $request
     * @throws \Exception
     */
    private function validateFileUploads(Request $request): void
    {
        if (!$request->hasFile('file') && !$request->hasFile('files')) {
            return;
        }
        
        $files = $request->allFiles();
        
        foreach ($files as $key => $file) {
            if (is_array($file)) {
                foreach ($file as $subFile) {
                    $this->validateSingleFile($subFile, $request);
                }
            } else {
                $this->validateSingleFile($file, $request);
            }
        }
    }
    
    /**
     * Validate a single file upload
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param Request $request
     * @throws \Exception
     */
    private function validateSingleFile($file, Request $request): void
    {
        if (!$file->isValid()) {
            throw new \Exception('Invalid file upload');
        }
        
        // Size check (10MB max)
        if ($file->getSize() > 10485760) {
            Log::warning('File too large', [
                'size' => $file->getSize(),
                'name' => $file->getClientOriginalName(),
                'ip' => $request->ip()
            ]);
            
            throw new \Exception('File size exceeds maximum allowed size');
        }
        
        // Extension check
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'];
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, $allowedExtensions)) {
            Log::warning('Unauthorized file extension', [
                'extension' => $extension,
                'name' => $file->getClientOriginalName(),
                'ip' => $request->ip()
            ]);
            
            throw new \Exception('File type not allowed');
        }
        
        // MIME type check
        $allowedMimeTypes = [
            'image/jpeg', 'image/png', 'image/gif',
            'application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain'
        ];
        
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            Log::warning('Unauthorized MIME type', [
                'mime' => $file->getMimeType(),
                'name' => $file->getClientOriginalName(),
                'ip' => $request->ip()
            ]);
            
            throw new \Exception('File MIME type not allowed');
        }
    }
    
    /**
     * Check rate limiting
     *
     * @param Request $request
     * @throws \Exception
     */
    private function checkRateLimit(Request $request): void
    {
        $ip = $request->ip();
        $key = 'rate_limit_' . $ip;
        
        $attempts = cache()->get($key, 0);
        
        if ($attempts > 100) { // 100 requests per minute
            Log::warning('Rate limit exceeded', [
                'ip' => $ip,
                'attempts' => $attempts,
                'url' => $request->fullUrl()
            ]);
            
            throw new \Exception('Rate limit exceeded');
        }
        
        cache()->put($key, $attempts + 1, 60); // 1 minute window
    }
}