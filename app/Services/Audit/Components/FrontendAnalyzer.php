<?php

namespace App\Services\Audit\Components;

use App\Services\Audit\Models\Finding;
use App\Services\Audit\Reports\PhaseResult;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Frontend Analyzer with Performance Metrics
 * 
 * Technical analysis of frontend scripts, Blade templates, and runtime behavior
 * Focuses on performance, security, and code quality without UI design feedback
 */
class FrontendAnalyzer
{
    private FileScanner $fileScanner;
    private array $findings = [];
    private int $findingCounter = 1;
    private array $frontendMetrics = [];
    private array $assetAnalysis = [];

    public function __construct(FileScanner $fileScanner)
    {
        $this->fileScanner = $fileScanner;
    }

    /**
     * Execute comprehensive frontend analysis
     */
    public function executeFrontendAnalysis(string $projectPath): PhaseResult
    {
        Log::info('🔍 Starting Frontend Technical Analysis');
        
        $startTime = microtime(true);
        $this->findings = [];
        
        try {
            // Scan directory first to populate file lists
            $this->fileScanner->scanDirectory($projectPath);
            
            // 1. Blade Template Analysis
            $this->analyzeBladeTemplates();
            
            // 2. Asset Optimization Measurement
            $this->measureAssetOptimization();
            
            // 3. JavaScript Quality Assessment
            $this->analyzeJavaScriptQuality();
            
            // 4. CSS Efficiency Analysis
            $this->analyzeCSSEfficiency();
            
            // 5. Client-Side Security Review
            $this->assessClientSideSecurity();
            
            // 6. Frontend Performance Measurement
            $this->measureFrontendPerformance();
            
            $executionTime = microtime(true) - $startTime;
            
            $phaseResult = new PhaseResult(
                'frontend_analysis',
                $this->findings,
                $this->generateFrontendMetrics(),
                $executionTime
            );
            
            Log::info('✅ Frontend Analysis Complete', [
                'findings' => count($this->findings),
                'execution_time' => round($executionTime, 2) . 's'
            ]);
            
            return $phaseResult;
            
        } catch (\Exception $e) {
            Log::error('❌ Frontend Analysis Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Analyze Blade templates with CONCRETE EVIDENCE
     */
    public function analyzeBladeTemplates(): void
    {
        Log::info('🔍 Analyzing Blade Templates');
        
        $bladeFiles = $this->fileScanner->getBladeFiles();
        $templateIssues = 0;
        
        foreach ($bladeFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            $lines = explode("\n", $content);
            
            // Check for embedded queries in templates
            foreach ($lines as $lineNumber => $line) {
                if (preg_match('/\{\{\s*\$[^}]*->(?:where|find|get|all|first)\s*\([^}]*\}\}/', $line)) {
                    $templateIssues++;
                    
                    $finding = new Finding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'high',
                        'Database query embedded in Blade template - performance issue',
                        'frontend',
                        'blade_query'
                    );
                    
                    $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                    $finding->addMetric('issue_type', 'Embedded Query', 'performance_issue');
                    $finding->addMetric('performance_impact', 8.0, 'score_out_of_10');
                    
                    $finding->addEvidence('static_analysis', [
                        'template_line' => trim($line),
                        'file_path' => $file['path'],
                        'issue_type' => 'embedded_database_query'
                    ], 'Database query found in Blade template');
                    
                    $finding->setRecommendation('Move database queries to controller and pass data to view');
                    $finding->setEstimatedHours(2);
                    
                    $this->findings[] = $finding;
                }
            }
        }
        
        $this->frontendMetrics['blade_template_issues'] = $templateIssues;
        Log::info("Found {$templateIssues} Blade template issues");
    }

    /**
     * Measure asset optimization with CONCRETE EVIDENCE
     */
    public function measureAssetOptimization(): void
    {
        Log::info('🔍 Measuring Asset Optimization');
        
        $jsFiles = $this->fileScanner->getJavaScriptFiles();
        $cssFiles = $this->fileScanner->getCSSFiles();
        
        $totalJSSize = 0;
        $totalCSSSize = 0;
        $assetIssues = 0;
        
        // Analyze JavaScript files
        foreach ($jsFiles as $file) {
            $fileSize = $file['size'];
            $totalJSSize += $fileSize;
            
            // Check for large unminified JS files
            if ($fileSize > 100000 && !strpos($file['filename'], '.min.')) { // > 100KB
                $assetIssues++;
                
                $finding = new Finding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    'medium',
                    "Large JavaScript file ({$file['size_human']}) should be minified",
                    'frontend',
                    'asset_optimization'
                );
                
                $finding->addFileReference($file['path'], 1, "File size: {$file['size_human']}");
                $finding->addMetric('file_size_bytes', $fileSize, 'bytes');
                $finding->addMetric('optimization_potential', 70, 'percentage');
                
                $finding->addEvidence('static_analysis', [
                    'file_path' => $file['path'],
                    'file_size' => $fileSize,
                    'is_minified' => false,
                    'optimization_type' => 'minification'
                ], 'Large unminified JavaScript file detected');
                
                $finding->setRecommendation('Minify JavaScript file to reduce bundle size');
                $finding->setEstimatedHours(1);
                
                $this->findings[] = $finding;
            }
        }
        
        // Analyze CSS files
        foreach ($cssFiles as $file) {
            $fileSize = $file['size'];
            $totalCSSSize += $fileSize;
            
            // Check for large unminified CSS files
            if ($fileSize > 50000 && !strpos($file['filename'], '.min.')) { // > 50KB
                $assetIssues++;
                
                $finding = new Finding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    'medium',
                    "Large CSS file ({$file['size_human']}) should be minified",
                    'frontend',
                    'asset_optimization'
                );
                
                $finding->addFileReference($file['path'], 1, "File size: {$file['size_human']}");
                $finding->addMetric('file_size_bytes', $fileSize, 'bytes');
                $finding->addMetric('optimization_potential', 60, 'percentage');
                
                $finding->addEvidence('static_analysis', [
                    'file_path' => $file['path'],
                    'file_size' => $fileSize,
                    'is_minified' => false,
                    'optimization_type' => 'minification'
                ], 'Large unminified CSS file detected');
                
                $finding->setRecommendation('Minify CSS file to reduce bundle size');
                $finding->setEstimatedHours(1);
                
                $this->findings[] = $finding;
            }
        }
        
        $this->assetAnalysis = [
            'total_js_size' => $totalJSSize,
            'total_css_size' => $totalCSSSize,
            'js_files_count' => count($jsFiles),
            'css_files_count' => count($cssFiles),
            'asset_issues' => $assetIssues
        ];
        
        $this->frontendMetrics['asset_optimization_issues'] = $assetIssues;
        Log::info("Found {$assetIssues} asset optimization issues");
    }

    /**
     * Analyze JavaScript quality with CONCRETE EVIDENCE
     */
    public function analyzeJavaScriptQuality(): void
    {
        Log::info('🔍 Analyzing JavaScript Quality');
        
        $jsFiles = $this->fileScanner->getJavaScriptFiles();
        $jsIssues = 0;
        
        foreach ($jsFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            $lines = explode("\n", $content);
            
            foreach ($lines as $lineNumber => $line) {
                // Check for console.log statements
                if (preg_match('/console\.(log|debug|info|warn|error)/', $line)) {
                    $jsIssues++;
                    
                    $finding = new Finding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'low',
                        'Console statement found - should be removed in production',
                        'frontend',
                        'js_console'
                    );
                    
                    $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                    $finding->addMetric('issue_type', 'Console Statement', 'code_quality');
                    $finding->addMetric('production_impact', 2.0, 'score_out_of_10');
                    
                    $finding->addEvidence('static_analysis', [
                        'js_line' => trim($line),
                        'file_path' => $file['path'],
                        'issue_type' => 'console_statement'
                    ], 'Console statement found in JavaScript');
                    
                    $finding->setRecommendation('Remove console statements or use proper logging in production');
                    $finding->setEstimatedHours(1);
                    
                    $this->findings[] = $finding;
                }
            }
        }
        
        $this->frontendMetrics['javascript_quality_issues'] = $jsIssues;
        Log::info("Found {$jsIssues} JavaScript quality issues");
    }

    /**
     * Assess JavaScript quality (alias for analyzeJavaScriptQuality)
     */
    public function assessJavaScriptQuality(): void
    {
        $this->analyzeJavaScriptQuality();
    }

    /**
     * Analyze CSS efficiency with CONCRETE EVIDENCE
     */
    public function analyzeCSSEfficiency(): void
    {
        Log::info('🔍 Analyzing CSS Efficiency');
        
        $cssFiles = $this->fileScanner->getCSSFiles();
        $cssIssues = 0;
        
        foreach ($cssFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            $lines = explode("\n", $content);
            
            foreach ($lines as $lineNumber => $line) {
                // Check for !important usage
                if (preg_match('/!important/', $line)) {
                    $cssIssues++;
                    
                    $finding = new Finding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'low',
                        '!important usage detected - CSS architecture issue',
                        'frontend',
                        'css_important'
                    );
                    
                    $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                    $finding->addMetric('issue_type', '!important Usage', 'architecture');
                    $finding->addMetric('maintainability_impact', 3.5, 'score_out_of_10');
                    
                    $finding->addEvidence('static_analysis', [
                        'css_line' => trim($line),
                        'file_path' => $file['path'],
                        'issue_type' => 'important_usage'
                    ], '!important usage in CSS detected');
                    
                    $finding->setRecommendation('Refactor CSS to avoid !important usage');
                    $finding->setEstimatedHours(2);
                    
                    $this->findings[] = $finding;
                }
            }
        }
        
        $this->frontendMetrics['css_efficiency_issues'] = $cssIssues;
        Log::info("Found {$cssIssues} CSS efficiency issues");
    }

    /**
     * Assess client-side security with CONCRETE EVIDENCE
     */
    public function assessClientSideSecurity(): void
    {
        Log::info('🔍 Assessing Client-Side Security');
        
        $jsFiles = $this->fileScanner->getJavaScriptFiles();
        $securityIssues = 0;
        
        foreach ($jsFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            $lines = explode("\n", $content);
            
            foreach ($lines as $lineNumber => $line) {
                // Check for eval() usage
                if (preg_match('/eval\s*\(/', $line)) {
                    $securityIssues++;
                    
                    $finding = new Finding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'high',
                        'eval() usage detected - security and performance risk',
                        'frontend',
                        'client_security'
                    );
                    
                    $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                    $finding->addMetric('vulnerability_type', 'Code Injection Risk', 'security_risk');
                    $finding->addMetric('risk_score', 8.5, 'score_out_of_10');
                    
                    $finding->addEvidence('static_analysis', [
                        'js_line' => trim($line),
                        'file_path' => $file['path'],
                        'function_used' => 'eval'
                    ], 'eval() usage detected in JavaScript');
                    
                    $finding->setRecommendation('Replace eval() with safer alternatives like JSON.parse() or proper function calls');
                    $finding->setEstimatedHours(4);
                    
                    $this->findings[] = $finding;
                }
            }
        }
        
        $this->frontendMetrics['client_security_issues'] = $securityIssues;
        Log::info("Found {$securityIssues} client-side security issues");
    }

    /**
     * Measure frontend performance with CONCRETE EVIDENCE
     */
    public function measureFrontendPerformance(): void
    {
        Log::info('🔍 Measuring Frontend Performance');
        
        $bladeFiles = $this->fileScanner->getBladeFiles();
        $performanceIssues = 0;
        
        foreach ($bladeFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            
            // Check for render-blocking resources
            if (preg_match_all('/<link[^>]*rel=["\']stylesheet["\'][^>]*>/', $content, $matches)) {
                $stylesheetCount = count($matches[0]);
                
                if ($stylesheetCount > 5) {
                    $performanceIssues++;
                    
                    $finding = new Finding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'medium',
                        "Too many stylesheets ({$stylesheetCount}) - render-blocking issue",
                        'frontend',
                        'render_blocking'
                    );
                    
                    $finding->addFileReference($file['path'], 1, "Stylesheets count: {$stylesheetCount}");
                    $finding->addMetric('stylesheet_count', $stylesheetCount, 'files');
                    $finding->addMetric('performance_impact', 6.5, 'score_out_of_10');
                    
                    $finding->addEvidence('static_analysis', [
                        'file_path' => $file['path'],
                        'stylesheet_count' => $stylesheetCount,
                        'issue_type' => 'render_blocking_resources'
                    ], 'Multiple render-blocking stylesheets detected');
                    
                    $finding->setRecommendation('Bundle stylesheets or use critical CSS loading');
                    $finding->setEstimatedHours(3);
                    
                    $this->findings[] = $finding;
                }
            }
        }
        
        $this->frontendMetrics['frontend_performance_issues'] = $performanceIssues;
        Log::info("Found {$performanceIssues} frontend performance issues");
    }

    /**
     * Generate frontend metrics
     */
    private function generateFrontendMetrics(): array
    {
        return [
            'total_frontend_findings' => count($this->findings),
            'blade_template_issues' => $this->frontendMetrics['blade_template_issues'] ?? 0,
            'asset_optimization_issues' => $this->frontendMetrics['asset_optimization_issues'] ?? 0,
            'javascript_quality_issues' => $this->frontendMetrics['javascript_quality_issues'] ?? 0,
            'css_efficiency_issues' => $this->frontendMetrics['css_efficiency_issues'] ?? 0,
            'client_security_issues' => $this->frontendMetrics['client_security_issues'] ?? 0,
            'frontend_performance_issues' => $this->frontendMetrics['frontend_performance_issues'] ?? 0,
            'total_js_size' => $this->assetAnalysis['total_js_size'] ?? 0,
            'total_css_size' => $this->assetAnalysis['total_css_size'] ?? 0,
            'js_files_count' => $this->assetAnalysis['js_files_count'] ?? 0,
            'css_files_count' => $this->assetAnalysis['css_files_count'] ?? 0,
            'high_priority_issues' => count(array_filter($this->findings, function($finding) {
                return $finding->getSeverity() === 'high';
            })),
            'medium_priority_issues' => count(array_filter($this->findings, function($finding) {
                return $finding->getSeverity() === 'medium';
            }))
        ];
    }
}