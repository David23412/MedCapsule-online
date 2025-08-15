<?php

namespace App\Services\Audit\Components;

use App\Services\Audit\Models\ConcreteFinding;
use App\Services\Audit\Reports\PhaseResult;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Security Scanner with Vulnerability Testing
 * 
 * Comprehensive security audit scanning every route, controller, and input point
 * Identifies SQL injection, XSS, CSRF, and access control vulnerabilities
 */
class SecurityScanner
{
    private FileScanner $fileScanner;
    private array $findings = [];
    private int $findingCounter = 1;
    private array $vulnerabilityPatterns;
    private array $securityMetrics = [];

    public function __construct(FileScanner $fileScanner)
    {
        $this->fileScanner = $fileScanner;
        $this->initializeVulnerabilityPatterns();
    }

    /**
     * Execute comprehensive security scanning
     */
    public function executeSecurityScan(string $projectPath): PhaseResult
    {
        Log::info('🔍 Starting Comprehensive Security Scan');
        
        $startTime = microtime(true);
        $this->findings = [];
        
        try {
            // Scan directory first to populate file lists
            $this->fileScanner->scanDirectory($projectPath);
            
            // 1. SQL Injection Vulnerability Scanning
            $this->scanSQLInjectionVulnerabilities();
            
            // 2. XSS Vulnerability Detection
            $this->detectXSSVulnerabilities();
            
            // 3. CSRF Protection Verification
            $this->verifyCsrfProtection();
            
            // 4. Input Validation Testing
            $this->testInputValidation();
            
            // 5. Authentication System Analysis
            $this->scanAuthentication();
            
            // 6. Access Control Verification
            $this->verifyAccessControls();
            
            // 7. Configuration Security Audit
            $this->auditConfigurationSecurity();
            
            // 8. File Security Evaluation
            $this->evaluateFileSecurity();
            
            $executionTime = microtime(true) - $startTime;
            
            $phaseResult = new PhaseResult(
                'security_scanning',
                $this->findings,
                $this->generateSecurityMetrics(),
                $executionTime
            );
            
            Log::info('✅ Security Scan Complete', [
                'findings' => count($this->findings),
                'execution_time' => round($executionTime, 2) . 's'
            ]);
            
            return $phaseResult;
            
        } catch (\Exception $e) {
            Log::error('❌ Security Scan Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Initialize vulnerability detection patterns
     */
    private function initializeVulnerabilityPatterns(): void
    {
        $this->vulnerabilityPatterns = [
            'sql_injection' => [
                '/\$.*\.\s*["\'].*\$/',
                '/DB::raw\s*\(\s*["\'].*\$.*["\']/',
                '/query\s*\(\s*["\'].*\$.*["\']/',
                '/whereRaw\s*\(\s*["\'].*\$.*["\']/',
                '/DB::select\s*\(\s*["\'].*\$/',
                '/DB::statement\s*\(\s*["\'].*\$/',
            ],
            'xss_vulnerability' => [
                '/\{\!\!\s*\$[^}]+\!\!\}/',
                '/echo\s+\$[^;]*;/',
                '/print\s+\$[^;]*;/',
                '/<[^>]*\{\!\!\s*\$[^}]+\!\!\}[^>]*>/',
            ],
            'csrf_missing' => [
                '/<form[^>]*method\s*=\s*["\']post["\'][^>]*>(?!.*@csrf)(?!.*csrf_token)/',
                '/\$\.ajax\s*\([^)]*type\s*:\s*["\']POST["\'][^)]*\)(?!.*X-CSRF-TOKEN)/',
                '/fetch\s*\([^)]*method\s*:\s*["\']POST["\'][^)]*\)(?!.*X-CSRF-TOKEN)/',
            ]
        ];
    }

    /**
     * Scan for SQL injection vulnerabilities
     */
    public function scanSQLInjectionVulnerabilities(): void
    {
        Log::info('🔍 Scanning for SQL Injection Vulnerabilities');
        
        $phpFiles = $this->fileScanner->getPHPFiles();
        $vulnerabilityCount = 0;
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            $lines = explode("\n", $content);
            
            foreach ($this->vulnerabilityPatterns['sql_injection'] as $pattern) {
                foreach ($lines as $lineNumber => $line) {
                    if (preg_match($pattern, $line)) {
                        $vulnerabilityCount++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'critical',
                            'Potential SQL injection vulnerability detected',
                            'security',
                            'sql_injection'
                        );
                        
                        $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('vulnerability_type', 'SQL Injection', 'security_risk');
                        $finding->addMetric('risk_score', 9.5, 'score_out_of_10');
                        $finding->addMetric('pattern_matched', $pattern, 'regex_pattern');
                        
                        $finding->addEvidence('static_analysis', [
                            'pattern' => $pattern,
                            'line_content' => trim($line),
                            'file_path' => $file['path'],
                            'detection_method' => 'regex_pattern_matching'
                        ], 'SQL injection pattern detection');
                        
                        $finding->setRecommendation('Use parameterized queries or Eloquent ORM to prevent SQL injection');
                        $finding->setEstimatedHours(2);
                        
                        $this->findings[] = $finding;
                    }
                }
            }
        }
        
        $this->securityMetrics['sql_injection_vulnerabilities'] = $vulnerabilityCount;
        Log::info("Found {$vulnerabilityCount} potential SQL injection vulnerabilities");
    }

    /**
     * Detect XSS vulnerabilities
     */
    public function detectXSSVulnerabilities(): void
    {
        Log::info('🔍 Detecting XSS Vulnerabilities');
        
        $bladeFiles = $this->fileScanner->getBladeFiles();
        $phpFiles = $this->fileScanner->getPHPFiles();
        $vulnerabilityCount = 0;
        
        // Scan Blade templates
        foreach ($bladeFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            $lines = explode("\n", $content);
            
            foreach ($this->vulnerabilityPatterns['xss_vulnerability'] as $pattern) {
                foreach ($lines as $lineNumber => $line) {
                    if (preg_match($pattern, $line)) {
                        $vulnerabilityCount++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'high',
                            'XSS vulnerability detected - unescaped output',
                            'security',
                            'xss_vulnerability'
                        );
                        
                        $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('vulnerability_type', 'Cross-Site Scripting', 'security_risk');
                        $finding->addMetric('risk_score', 8.0, 'score_out_of_10');
                        $finding->addMetric('output_type', 'unescaped_blade_output', 'output_method');
                        
                        $finding->addEvidence('static_analysis', [
                            'pattern' => $pattern,
                            'line_content' => trim($line),
                            'file_path' => $file['path'],
                            'template_type' => 'blade'
                        ], 'XSS vulnerability pattern detection');
                        
                        $finding->setRecommendation('Use {{ }} instead of {!! !!} for user input, or sanitize with htmlspecialchars()');
                        $finding->setEstimatedHours(1);
                        
                        $this->findings[] = $finding;
                    }
                }
            }
        }
        
        $this->securityMetrics['xss_vulnerabilities'] = $vulnerabilityCount;
        Log::info("Found {$vulnerabilityCount} potential XSS vulnerabilities");
    }

    /**
     * Verify CSRF protection
     */
    public function verifyCsrfProtection(): void
    {
        Log::info('🔍 Verifying CSRF Protection');
        
        $bladeFiles = $this->fileScanner->getBladeFiles();
        $jsFiles = $this->fileScanner->getJavaScriptFiles();
        $vulnerabilityCount = 0;
        
        // Check Blade forms for CSRF tokens
        foreach ($bladeFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            $lines = explode("\n", $content);
            
            foreach ($lines as $lineNumber => $line) {
                if (preg_match('/<form[^>]*method\s*=\s*["\']post["\'][^>]*>/i', $line)) {
                    // Found a POST form, check if CSRF token is present in the form
                    $formContent = $this->extractFormContent($content, $lineNumber);
                    
                    if (!preg_match('/@csrf|csrf_token\(\)|_token/', $formContent)) {
                        $vulnerabilityCount++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'high',
                            'CSRF protection missing on POST form',
                            'security',
                            'csrf_missing'
                        );
                        
                        $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('vulnerability_type', 'CSRF Missing', 'security_risk');
                        $finding->addMetric('risk_score', 7.5, 'score_out_of_10');
                        $finding->addMetric('form_method', 'POST', 'http_method');
                        
                        $finding->addEvidence('static_analysis', [
                            'form_line' => trim($line),
                            'file_path' => $file['path'],
                            'form_content_preview' => substr($formContent, 0, 200)
                        ], 'POST form without CSRF token detected');
                        
                        $finding->setRecommendation('Add @csrf directive inside the form or include csrf_token() in hidden input');
                        $finding->setEstimatedHours(1);
                        
                        $this->findings[] = $finding;
                    }
                }
            }
        }
        
        $this->securityMetrics['csrf_vulnerabilities'] = $vulnerabilityCount;
        Log::info("Found {$vulnerabilityCount} CSRF protection issues");
    }

    /**
     * Test input validation across endpoints
     */
    public function testInputValidation(): void
    {
        Log::info('🔍 Testing Input Validation');
        
        $controllerFiles = array_filter($this->fileScanner->getPHPFiles(), function($file) {
            return strpos($file['path'], 'app/Http/Controllers') !== false;
        });
        
        $validationIssues = 0;
        
        foreach ($controllerFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            $lines = explode("\n", $content);
            
            foreach ($lines as $lineNumber => $line) {
                // Look for request parameter usage without validation
                if (preg_match('/\$request->(?:get|input|post)\s*\(\s*["\']([^"\']+)["\']/', $line, $matches)) {
                    $paramName = $matches[1];
                    
                    // Check if there's validation nearby
                    $contextLines = array_slice($lines, max(0, $lineNumber - 10), 20);
                    $context = implode("\n", $contextLines);
                    
                    if (!preg_match('/validate\s*\(|FormRequest|rules\s*\(/', $context)) {
                        $validationIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'medium',
                            "Input parameter '{$paramName}' used without validation",
                            'security',
                            'input_validation'
                        );
                        
                        $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('vulnerability_type', 'Input Validation Missing', 'security_risk');
                        $finding->addMetric('risk_score', 5.5, 'score_out_of_10');
                        $finding->addMetric('parameter_name', $paramName, 'input_parameter');
                        
                        $finding->addEvidence('static_analysis', [
                            'parameter_usage' => trim($line),
                            'parameter_name' => $paramName,
                            'file_path' => $file['path'],
                            'context_checked' => '10 lines before and after'
                        ], 'Input parameter usage without validation detected');
                        
                        $finding->setRecommendation('Add input validation using Laravel validation rules or FormRequest');
                        $finding->setEstimatedHours(2);
                        
                        $this->findings[] = $finding;
                    }
                }
            }
        }
        
        $this->securityMetrics['input_validation_issues'] = $validationIssues;
        Log::info("Found {$validationIssues} input validation issues");
    }

    /**
     * Scan authentication system
     */
    public function scanAuthentication(): void
    {
        Log::info('🔍 Scanning Authentication System');
        
        $phpFiles = $this->fileScanner->getPHPFiles();
        $authIssues = 0;
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            $lines = explode("\n", $content);
            
            foreach ($lines as $lineNumber => $line) {
                // Look for hardcoded passwords or API keys
                if (preg_match('/password\s*=\s*["\'][^"\']{1,20}["\']|api_key\s*=\s*["\'][^"\']+["\']/', $line)) {
                    $authIssues++;
                    
                    $finding = new ConcreteFinding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'critical',
                        'Hardcoded credentials detected in source code',
                        'security',
                        'hardcoded_credentials'
                    );
                    
                    $finding->addFileReference($file['path'], $lineNumber + 1, '[REDACTED - Contains credentials]');
                    $finding->addMetric('vulnerability_type', 'Hardcoded Credentials', 'security_risk');
                    $finding->addMetric('risk_score', 9.0, 'score_out_of_10');
                    
                    $finding->addEvidence('static_analysis', [
                        'file_path' => $file['path'],
                        'line_number' => $lineNumber + 1,
                        'credential_type' => 'hardcoded'
                    ], 'Hardcoded credentials pattern detected');
                    
                    $finding->setRecommendation('Move credentials to environment variables or secure configuration');
                    $finding->setEstimatedHours(1);
                    
                    $this->findings[] = $finding;
                }
            }
        }
        
        $this->securityMetrics['authentication_issues'] = $authIssues;
        Log::info("Found {$authIssues} authentication issues");
    }

    /**
     * Verify access controls
     */
    public function verifyAccessControls(): void
    {
        Log::info('🔍 Verifying Access Controls');
        
        $accessControlIssues = 0;
        
        try {
            $routes = Route::getRoutes();
            
            foreach ($routes as $route) {
                $middleware = $route->gatherMiddleware();
                $uri = $route->uri();
                $methods = $route->methods();
                
                // Skip public routes that shouldn't require auth
                if ($this->isPublicRoute($uri)) {
                    continue;
                }
                
                // Check if sensitive routes have auth middleware
                if ($this->isSensitiveRoute($uri) && !$this->hasAuthMiddleware($middleware)) {
                    $accessControlIssues++;
                    
                    $finding = new ConcreteFinding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'high',
                        "Sensitive route '{$uri}' missing authentication middleware",
                        'security',
                        'access_control'
                    );
                    
                    $finding->addFileReference('routes/web.php', 1, "Route: {$uri}");
                    $finding->addMetric('vulnerability_type', 'Missing Authentication', 'security_risk');
                    $finding->addMetric('risk_score', 8.0, 'score_out_of_10');
                    $finding->addMetric('route_uri', $uri, 'route_path');
                    $finding->addMetric('http_methods', implode(',', $methods), 'http_methods');
                    
                    $finding->addEvidence('static_analysis', [
                        'route_uri' => $uri,
                        'methods' => $methods,
                        'middleware' => $middleware,
                        'route_type' => 'sensitive'
                    ], 'Sensitive route without authentication middleware');
                    
                    $finding->setRecommendation('Add auth middleware to protect sensitive routes');
                    $finding->setEstimatedHours(1);
                    
                    $this->findings[] = $finding;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Could not analyze routes: ' . $e->getMessage());
        }
        
        $this->securityMetrics['access_control_issues'] = $accessControlIssues;
        Log::info("Found {$accessControlIssues} access control issues");
    }

    /**
     * Audit configuration security
     */
    public function auditConfigurationSecurity(): void
    {
        Log::info('🔍 Auditing Configuration Security');
        
        $configIssues = 0;
        
        // Check .env file for sensitive data exposure
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);
            
            // Check for debug mode in production
            if (preg_match('/APP_DEBUG\s*=\s*true/i', $envContent)) {
                $configIssues++;
                
                $finding = new ConcreteFinding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    'high',
                    'Debug mode enabled - potential information disclosure',
                    'security',
                    'config_security'
                );
                
                $finding->addFileReference('.env', 1, 'APP_DEBUG=true');
                $finding->addMetric('vulnerability_type', 'Information Disclosure', 'security_risk');
                $finding->addMetric('risk_score', 7.0, 'score_out_of_10');
                
                $finding->addEvidence('static_analysis', [
                    'config_file' => '.env',
                    'setting' => 'APP_DEBUG=true',
                    'risk' => 'information_disclosure'
                ], 'Debug mode enabled in configuration');
                
                $finding->setRecommendation('Set APP_DEBUG=false in production environment');
                $finding->setEstimatedHours(1);
                
                $this->findings[] = $finding;
            }
        }
        
        $this->securityMetrics['configuration_issues'] = $configIssues;
        Log::info("Found {$configIssues} configuration security issues");
    }

    /**
     * Evaluate file security
     */
    public function evaluateFileSecurity(): void
    {
        Log::info('🔍 Evaluating File Security');
        
        $fileSecurityIssues = 0;
        
        // Check for publicly accessible sensitive files
        $sensitiveFiles = ['.env', 'composer.json', 'package.json', '.git'];
        
        foreach ($sensitiveFiles as $sensitiveFile) {
            $filePath = base_path($sensitiveFile);
            
            if (file_exists($filePath)) {
                // Check if file is in public directory (security risk)
                if (strpos($filePath, '/public/') !== false) {
                    $fileSecurityIssues++;
                    
                    $finding = new ConcreteFinding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'critical',
                        "Sensitive file '{$sensitiveFile}' in publicly accessible directory",
                        'security',
                        'file_security'
                    );
                    
                    $finding->addFileReference($sensitiveFile, 1, 'Sensitive file location');
                    $finding->addMetric('vulnerability_type', 'File Exposure', 'security_risk');
                    $finding->addMetric('risk_score', 9.5, 'score_out_of_10');
                    
                    $finding->addEvidence('static_analysis', [
                        'file_path' => $sensitiveFile,
                        'location' => 'public_directory',
                        'risk' => 'direct_web_access'
                    ], 'Sensitive file in public directory');
                    
                    $finding->setRecommendation('Move sensitive files outside public directory');
                    $finding->setEstimatedHours(1);
                    
                    $this->findings[] = $finding;
                }
            }
        }
        
        $this->securityMetrics['file_security_issues'] = $fileSecurityIssues;
        Log::info("Found {$fileSecurityIssues} file security issues");
    }

    /**
     * Helper methods for security analysis
     */
    private function extractFormContent(string $content, int $startLine): string
    {
        $lines = explode("\n", $content);
        $formContent = '';
        $inForm = false;
        
        for ($i = $startLine; $i < count($lines); $i++) {
            $line = $lines[$i];
            
            if (strpos($line, '<form') !== false) {
                $inForm = true;
            }
            
            if ($inForm) {
                $formContent .= $line . "\n";
            }
            
            if (strpos($line, '</form>') !== false) {
                break;
            }
        }
        
        return $formContent;
    }

    private function isPublicRoute(string $uri): bool
    {
        $publicRoutes = ['/', 'login', 'register', 'password/reset', 'api/public'];
        
        foreach ($publicRoutes as $publicRoute) {
            if ($uri === $publicRoute || strpos($uri, $publicRoute) === 0) {
                return true;
            }
        }
        
        return false;
    }

    private function isSensitiveRoute(string $uri): bool
    {
        $sensitivePatterns = ['admin', 'dashboard', 'profile', 'settings', 'api/', 'user/'];
        
        foreach ($sensitivePatterns as $pattern) {
            if (strpos($uri, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }

    private function hasAuthMiddleware(array $middleware): bool
    {
        $authMiddleware = ['auth', 'auth:api', 'auth:web', 'authenticate'];
        
        foreach ($middleware as $mw) {
            if (in_array($mw, $authMiddleware)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Generate security metrics
     */
    private function generateSecurityMetrics(): array
    {
        return [
            'total_security_findings' => count($this->findings),
            'sql_injection_vulnerabilities' => $this->securityMetrics['sql_injection_vulnerabilities'] ?? 0,
            'xss_vulnerabilities' => $this->securityMetrics['xss_vulnerabilities'] ?? 0,
            'csrf_vulnerabilities' => $this->securityMetrics['csrf_vulnerabilities'] ?? 0,
            'input_validation_issues' => $this->securityMetrics['input_validation_issues'] ?? 0,
            'authentication_issues' => $this->securityMetrics['authentication_issues'] ?? 0,
            'access_control_issues' => $this->securityMetrics['access_control_issues'] ?? 0,
            'configuration_issues' => $this->securityMetrics['configuration_issues'] ?? 0,
            'file_security_issues' => $this->securityMetrics['file_security_issues'] ?? 0,
            'critical_security_risks' => count(array_filter($this->findings, function($finding) {
                return $finding->getSeverity() === 'critical';
            })),
            'high_security_risks' => count(array_filter($this->findings, function($finding) {
                return $finding->getSeverity() === 'high';
            }))
        ];
    }
}