<?php

namespace App\Services\Audit\Components;

use App\Services\Audit\Models\Finding;
use App\Services\Audit\Reports\PhaseResult;
use App\Services\Audit\Models\ComplexityMetrics;
use Illuminate\Support\Facades\Log;
// Simplified version without PhpParser dependency for immediate execution

/**
 * Code Analyzer with Complexity Metrics
 * 
 * Performs static analysis of PHP code with architectural pattern detection
 * Calculates complexity metrics and identifies anti-patterns
 */
class CodeAnalyzer
{
    private FileScanner $fileScanner;
    private array $findings = [];
    private array $complexityMetrics = [];
    private int $findingCounter = 1;

    public function __construct(FileScanner $fileScanner)
    {
        $this->fileScanner = $fileScanner;
    }

    /**
     * Analyze architecture and code quality
     */
    public function analyzeArchitecture(string $projectPath): PhaseResult
    {
        Log::info('Starting code architecture analysis');
        
        $startTime = microtime(true);
        $this->findings = [];
        $this->complexityMetrics = [];

        // Scan all PHP files
        $scanResult = $this->fileScanner->scanDirectory($projectPath);
        $phpFiles = $this->fileScanner->getPHPFiles();

        Log::info('Analyzing PHP files', ['count' => count($phpFiles)]);

        foreach ($phpFiles as $fileInfo) {
            $this->analyzeFile($fileInfo);
        }

        // Detect architectural violations
        $this->detectArchitecturalViolations($phpFiles);
        
        // Analyze Laravel patterns
        $this->analyzeLaravelPatterns($phpFiles);

        $executionTime = microtime(true) - $startTime;

        $phaseResult = new PhaseResult(
            'codebase_structural_analysis',
            $this->findings,
            $this->generateMetrics(),
            $executionTime
        );

        Log::info('Code architecture analysis completed', [
            'findings' => count($this->findings),
            'execution_time' => round($executionTime, 2) . 's'
        ]);

        return $phaseResult;
    }

    /**
     * Analyze individual PHP file
     */
    private function analyzeFile(array $fileInfo): void
    {
        try {
            $filePath = $fileInfo['absolute_path'];
            $code = file_get_contents($filePath);
            
            if (empty($code)) {
                return;
            }

            // Calculate complexity metrics
            $complexity = $this->calculateComplexity($filePath);
            $this->complexityMetrics[$fileInfo['path']] = $complexity;

            // Detect issues based on complexity
            $this->detectComplexityIssues($fileInfo, $complexity);
            
            // Detect anti-patterns
            $this->detectAntiPatterns($fileInfo, $code);
            
            // Check PSR-4 compliance
            $this->checkPSR4Compliance($fileInfo, $code);

        } catch (\Exception $e) {
            Log::warning('File analysis error', [
                'file' => $fileInfo['path'],
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            Log::error('File analysis error', [
                'file' => $fileInfo['path'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate cyclomatic complexity and other metrics (simplified version)
     */
    public function calculateComplexity(string $filePath): ComplexityMetrics
    {
        $code = file_get_contents($filePath);
        $lines = file($filePath);
        $linesOfCode = count($lines);
        
        // Calculate cyclomatic complexity using regex patterns
        $cyclomaticComplexity = 1; // Base complexity
        $evidenceTrace = [];
        
        // Count decision points
        $patterns = [
            'if' => '/\bif\s*\(/',
            'elseif' => '/\belseif\s*\(/',
            'else' => '/\belse\b/',
            'while' => '/\bwhile\s*\(/',
            'for' => '/\bfor\s*\(/',
            'foreach' => '/\bforeach\s*\(/',
            'switch' => '/\bswitch\s*\(/',
            'case' => '/\bcase\s+/',
            'catch' => '/\bcatch\s*\(/',
            'ternary' => '/\?.*:/',
            'logical_and' => '/&&/',
            'logical_or' => '/\|\|/'
        ];
        
        foreach ($patterns as $type => $pattern) {
            $matches = preg_match_all($pattern, $code);
            if ($matches > 0) {
                $cyclomaticComplexity += $matches;
                $evidenceTrace[] = [
                    'type' => $type,
                    'count' => $matches,
                    'complexity_increment' => $matches
                ];
            }
        }
        
        // Count methods and classes
        $methodCount = preg_match_all('/\bfunction\s+[a-zA-Z_][a-zA-Z0-9_]*\s*\(/', $code);
        $classCount = preg_match_all('/\bclass\s+[a-zA-Z_][a-zA-Z0-9_]*/', $code);
        $functionCount = preg_match_all('/\bfunction\s+[a-zA-Z_][a-zA-Z0-9_]*\s*\(/', $code);
        
        // Calculate maintainability index (simplified version)
        $maintainabilityIndex = max(0, 171 - 5.2 * log($linesOfCode) - 0.23 * $cyclomaticComplexity);

        return new ComplexityMetrics(
            $cyclomaticComplexity,
            $maintainabilityIndex,
            $linesOfCode,
            $methodCount,
            $classCount,
            $functionCount,
            $evidenceTrace
        );
    }

    /**
     * Detect complexity-based issues
     */
    private function detectComplexityIssues(array $fileInfo, ComplexityMetrics $complexity): void
    {
        $relativePath = $fileInfo['path'];

        // Fat controller detection (>200 lines)
        if (strpos($relativePath, 'Controller') !== false && $complexity->getLinesOfCode() > 200) {
            $finding = new Finding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'high',
                'Fat controller detected - exceeds 200 lines',
                'code_quality',
                'controller'
            );
            
            $finding->addFileReference($relativePath, 1, 'Controller class definition');
            $finding->addMetric('lines_of_code', $complexity->getLinesOfCode(), 'lines');
            $finding->addMetric('complexity_score', $complexity->getCyclomaticComplexity(), 'score');
            $finding->addEvidence('static_analysis', [
                'file_size' => $fileInfo['size'],
                'line_count' => $complexity->getLinesOfCode(),
                'method_count' => $complexity->getMethodCount()
            ], 'Controller size analysis');
            
            $finding->setRecommendation('Break down controller into smaller methods or extract logic to service classes');
            $finding->setEstimatedHours(4);
            
            $this->findings[] = $finding;
        }

        // Fat model detection (>500 lines)
        if (strpos($relativePath, 'Models/') !== false && $complexity->getLinesOfCode() > 500) {
            $finding = new Finding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'medium',
                'Fat model detected - exceeds 500 lines',
                'code_quality',
                'model'
            );
            
            $finding->addFileReference($relativePath, 1, 'Model class definition');
            $finding->addMetric('lines_of_code', $complexity->getLinesOfCode(), 'lines');
            $finding->addEvidence('static_analysis', $complexity->toArray(), 'Model complexity analysis');
            
            $finding->setRecommendation('Extract business logic to service classes or use traits');
            $finding->setEstimatedHours(6);
            
            $this->findings[] = $finding;
        }

        // High cyclomatic complexity (>10)
        if ($complexity->getCyclomaticComplexity() > 10) {
            $severity = $complexity->getCyclomaticComplexity() > 20 ? 'high' : 'medium';
            
            $finding = new Finding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                $severity,
                'High cyclomatic complexity detected',
                'code_quality',
                'complexity'
            );
            
            $finding->addFileReference($relativePath, 1, 'File with high complexity');
            $finding->addMetric('cyclomatic_complexity', $complexity->getCyclomaticComplexity(), 'score');
            $finding->addMetric('maintainability_index', round($complexity->getMaintainabilityIndex(), 2), 'index');
            $finding->addEvidence('static_analysis', $complexity->getEvidenceTrace(), 'Complexity calculation trace');
            
            $finding->setRecommendation('Refactor complex methods into smaller, more focused functions');
            $finding->setEstimatedHours($complexity->getCyclomaticComplexity() > 20 ? 8 : 4);
            
            $this->findings[] = $finding;
        }

        // Low maintainability index (<20)
        if ($complexity->getMaintainabilityIndex() < 20) {
            $finding = new Finding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'medium',
                'Low maintainability index detected',
                'code_quality',
                'maintainability'
            );
            
            $finding->addFileReference($relativePath, 1, 'File with low maintainability');
            $finding->addMetric('maintainability_index', round($complexity->getMaintainabilityIndex(), 2), 'index');
            $finding->addMetric('lines_of_code', $complexity->getLinesOfCode(), 'lines');
            $finding->addEvidence('static_analysis', $complexity->toArray(), 'Maintainability analysis');
            
            $finding->setRecommendation('Reduce complexity and improve code organization');
            $finding->setEstimatedHours(6);
            
            $this->findings[] = $finding;
        }
    }

    /**
     * Detect anti-patterns in code
     */
    public function detectAntiPatterns(array $fileInfo, string $code): array
    {
        $antiPatterns = [];
        $relativePath = $fileInfo['path'];

        try {
            $lines = explode("\n", $code);

            // God class detection (>1000 lines with >50 methods)
            if ($fileInfo['line_count'] > 1000) {
                $methodCount = substr_count($code, 'function ');
                if ($methodCount > 50) {
                    $finding = new Finding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'critical',
                        'God class anti-pattern detected',
                        'architecture',
                        'anti_pattern'
                    );
                    
                    $finding->addFileReference($relativePath, 1, 'God class definition');
                    $finding->addMetric('lines_of_code', $fileInfo['line_count'], 'lines');
                    $finding->addMetric('method_count', $methodCount, 'methods');
                    $finding->addEvidence('static_analysis', [
                        'pattern' => 'god_class',
                        'file_size' => $fileInfo['size'],
                        'method_density' => round($methodCount / ($fileInfo['line_count'] / 100), 2)
                    ], 'God class pattern analysis');
                    
                    $finding->setRecommendation('Break down into multiple focused classes following Single Responsibility Principle');
                    $finding->setEstimatedHours(16);
                    
                    $this->findings[] = $finding;
                    $antiPatterns[] = 'god_class';
                }
            }

            // Static facade overuse detection
            $staticCalls = preg_match_all('/[A-Z][a-zA-Z]*::[a-zA-Z]/', $code);
            if ($staticCalls > 20) {
                $finding = new Finding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    'medium',
                    'Excessive static facade usage detected',
                    'architecture',
                    'anti_pattern'
                );
                
                $finding->addFileReference($relativePath, 1, 'File with excessive static calls');
                $finding->addMetric('static_calls', $staticCalls, 'calls');
                $finding->addEvidence('static_analysis', [
                    'pattern' => 'static_overuse',
                    'static_call_count' => $staticCalls
                ], 'Static facade usage analysis');
                
                $finding->setRecommendation('Consider dependency injection instead of static facades');
                $finding->setEstimatedHours(3);
                
                $this->findings[] = $finding;
                $antiPatterns[] = 'static_overuse';
            }

            // Code duplication detection (simplified)
            $duplicateBlocks = $this->detectCodeDuplication($lines);
            if (!empty($duplicateBlocks)) {
                foreach ($duplicateBlocks as $block) {
                    $finding = new Finding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'medium',
                        'Code duplication detected',
                        'code_quality',
                        'duplication'
                    );
                    
                    $finding->addFileReference($relativePath, $block['start_line'], 'Duplicated code block');
                    $finding->addMetric('duplicate_lines', $block['line_count'], 'lines');
                    $finding->addEvidence('static_analysis', $block, 'Code duplication analysis');
                    
                    $finding->setRecommendation('Extract duplicated code into reusable methods or classes');
                    $finding->setEstimatedHours(2);
                    
                    $this->findings[] = $finding;
                }
                $antiPatterns[] = 'code_duplication';
            }

        } catch (\Exception $e) {
            Log::warning('Anti-pattern detection failed', [
                'file' => $relativePath,
                'error' => $e->getMessage()
            ]);
        }

        return $antiPatterns;
    }

    /**
     * Detect code duplication (simplified implementation)
     */
    private function detectCodeDuplication(array $lines): array
    {
        $duplicates = [];
        $minBlockSize = 10; // Minimum 10 lines to consider duplication
        
        // This is a simplified implementation
        // In a real system, you'd use more sophisticated algorithms
        for ($i = 0; $i < count($lines) - $minBlockSize; $i++) {
            $block = array_slice($lines, $i, $minBlockSize);
            $blockHash = md5(implode("\n", $block));
            
            // Look for the same block later in the file
            for ($j = $i + $minBlockSize; $j < count($lines) - $minBlockSize; $j++) {
                $compareBlock = array_slice($lines, $j, $minBlockSize);
                $compareHash = md5(implode("\n", $compareBlock));
                
                if ($blockHash === $compareHash) {
                    $duplicates[] = [
                        'start_line' => $i + 1,
                        'end_line' => $i + $minBlockSize,
                        'duplicate_at_line' => $j + 1,
                        'line_count' => $minBlockSize,
                        'hash' => $blockHash
                    ];
                    break; // Found one duplicate, move on
                }
            }
        }
        
        return $duplicates;
    }

    /**
     * Check PSR-4 compliance
     */
    private function checkPSR4Compliance(array $fileInfo, string $code): void
    {
        $relativePath = $fileInfo['path'];
        
        // Skip non-class files
        if (!strpos($relativePath, 'app/') === 0) {
            return;
        }

        try {
            $expectedNamespace = $this->getExpectedNamespace($relativePath);
            $expectedClassName = $fileInfo['basename'];
            
            // Check namespace declaration
            if (!preg_match('/namespace\s+([^;]+);/', $code, $namespaceMatch)) {
                $finding = new Finding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    'medium',
                    'Missing namespace declaration',
                    'psr4_compliance',
                    'namespace'
                );
                
                $finding->addFileReference($relativePath, 1, 'File missing namespace');
                $finding->addEvidence('static_analysis', [
                    'expected_namespace' => $expectedNamespace,
                    'file_path' => $relativePath
                ], 'PSR-4 namespace analysis');
                
                $finding->setRecommendation("Add namespace declaration: namespace {$expectedNamespace};");
                $finding->setEstimatedHours(1);
                
                $this->findings[] = $finding;
                return;
            }

            $actualNamespace = trim($namespaceMatch[1]);
            
            if ($actualNamespace !== $expectedNamespace) {
                $finding = new Finding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    'medium',
                    'Incorrect namespace for PSR-4 compliance',
                    'psr4_compliance',
                    'namespace'
                );
                
                $finding->addFileReference($relativePath, 1, 'Incorrect namespace declaration');
                $finding->addMetric('namespace_mismatch', 1, 'count');
                $finding->addEvidence('static_analysis', [
                    'expected_namespace' => $expectedNamespace,
                    'actual_namespace' => $actualNamespace,
                    'file_path' => $relativePath
                ], 'PSR-4 namespace compliance check');
                
                $finding->setRecommendation("Change namespace to: {$expectedNamespace}");
                $finding->setEstimatedHours(1);
                
                $this->findings[] = $finding;
            }

        } catch (\Exception $e) {
            Log::warning('PSR-4 compliance check failed', [
                'file' => $relativePath,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get expected namespace based on file path
     */
    private function getExpectedNamespace(string $relativePath): string
    {
        // Convert file path to namespace
        $path = str_replace('/', '\\', dirname($relativePath));
        $path = str_replace('app\\', 'App\\', $path);
        
        return $path;
    }

    /**
     * Detect architectural violations
     */
    private function detectArchitecturalViolations(array $phpFiles): void
    {
        // Logic in routes detection
        $this->detectLogicInRoutes();
        
        // Direct DB calls in controllers
        $this->detectDirectDBCallsInControllers($phpFiles);
        
        // Hidden queries in views
        $this->detectHiddenQueriesInViews();
    }

    /**
     * Detect logic in route files
     */
    private function detectLogicInRoutes(): void
    {
        $routeFiles = ['routes/web.php', 'routes/api.php'];
        
        foreach ($routeFiles as $routeFile) {
            $fullPath = base_path($routeFile);
            
            if (!file_exists($fullPath)) {
                continue;
            }

            $code = file_get_contents($fullPath);
            $lines = explode("\n", $code);
            
            foreach ($lines as $lineNumber => $line) {
                // Look for complex logic in routes (if statements, loops, etc.)
                if (preg_match('/Route::[a-z]+.*function.*\{/', $line) && 
                    (strpos($code, 'if (') !== false || strpos($code, 'foreach') !== false)) {
                    
                    $finding = new Finding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'medium',
                        'Business logic detected in route file',
                        'architecture',
                        'separation_of_concerns'
                    );
                    
                    $finding->addFileReference($routeFile, $lineNumber + 1, 'Route with embedded logic');
                    $finding->addEvidence('static_analysis', [
                        'route_file' => $routeFile,
                        'line_content' => trim($line)
                    ], 'Route logic analysis');
                    
                    $finding->setRecommendation('Move business logic to controller methods');
                    $finding->setEstimatedHours(2);
                    
                    $this->findings[] = $finding;
                    break; // One finding per file is enough
                }
            }
        }
    }

    /**
     * Detect direct database calls in controllers
     */
    private function detectDirectDBCallsInControllers(array $phpFiles): void
    {
        foreach ($phpFiles as $fileInfo) {
            if (strpos($fileInfo['path'], 'Controller') === false) {
                continue;
            }

            $code = file_get_contents($fileInfo['absolute_path']);
            $lines = explode("\n", $code);
            
            foreach ($lines as $lineNumber => $line) {
                // Look for direct DB facade usage
                if (preg_match('/DB::(select|insert|update|delete|raw)/', $line)) {
                    $finding = new Finding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'medium',
                        'Direct database call in controller',
                        'architecture',
                        'separation_of_concerns'
                    );
                    
                    $finding->addFileReference($fileInfo['path'], $lineNumber + 1, 'Direct DB call');
                    $finding->addEvidence('static_analysis', [
                        'line_content' => trim($line),
                        'db_method' => 'DB facade'
                    ], 'Direct DB usage analysis');
                    
                    $finding->setRecommendation('Use Eloquent models or move to repository/service layer');
                    $finding->setEstimatedHours(2);
                    
                    $this->findings[] = $finding;
                }
            }
        }
    }

    /**
     * Detect hidden queries in Blade views
     */
    private function detectHiddenQueriesInViews(): void
    {
        $bladeFiles = $this->fileScanner->getBladeFiles();
        
        foreach ($bladeFiles as $fileInfo) {
            $code = file_get_contents($fileInfo['absolute_path']);
            $lines = explode("\n", $code);
            
            foreach ($lines as $lineNumber => $line) {
                // Look for model queries in Blade templates
                if (preg_match('/\$[a-zA-Z_]+->(?:where|find|get|all|first)\(/', $line) ||
                    preg_match('/[A-Z][a-zA-Z]*::(?:where|find|get|all|first)\(/', $line)) {
                    
                    $finding = new Finding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'high',
                        'Database query in Blade template',
                        'architecture',
                        'separation_of_concerns'
                    );
                    
                    $finding->addFileReference($fileInfo['path'], $lineNumber + 1, 'Query in view');
                    $finding->addEvidence('static_analysis', [
                        'line_content' => trim($line),
                        'template_file' => $fileInfo['path']
                    ], 'View query analysis');
                    
                    $finding->setRecommendation('Move queries to controller and pass data to view');
                    $finding->setEstimatedHours(1);
                    
                    $this->findings[] = $finding;
                }
            }
        }
    }

    /**
     * Analyze Laravel-specific patterns
     */
    private function analyzeLaravelPatterns(array $phpFiles): void
    {
        $this->analyzeServiceProviders($phpFiles);
        $this->analyzeMiddleware($phpFiles);
        $this->analyzeArtisanCommands($phpFiles);
    }

    /**
     * Analyze service providers
     */
    private function analyzeServiceProviders(array $phpFiles): void
    {
        foreach ($phpFiles as $fileInfo) {
            if (strpos($fileInfo['path'], 'Providers/') === false) {
                continue;
            }

            $code = file_get_contents($fileInfo['absolute_path']);
            
            // Check for bloated boot methods (>50 lines)
            if (preg_match('/public function boot\(\).*?\{(.*?)\n\s*\}/s', $code, $bootMatch)) {
                $bootLines = count(explode("\n", $bootMatch[1]));
                
                if ($bootLines > 50) {
                    $finding = new Finding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'medium',
                        'Bloated service provider boot method',
                        'architecture',
                        'service_provider'
                    );
                    
                    $finding->addFileReference($fileInfo['path'], 1, 'Service provider with bloated boot method');
                    $finding->addMetric('boot_method_lines', $bootLines, 'lines');
                    $finding->addEvidence('static_analysis', [
                        'boot_method_size' => $bootLines,
                        'provider_file' => $fileInfo['path']
                    ], 'Service provider analysis');
                    
                    $finding->setRecommendation('Break down boot method into smaller, focused methods');
                    $finding->setEstimatedHours(3);
                    
                    $this->findings[] = $finding;
                }
            }
        }
    }

    /**
     * Analyze middleware
     */
    private function analyzeMiddleware(array $phpFiles): void
    {
        foreach ($phpFiles as $fileInfo) {
            if (strpos($fileInfo['path'], 'Middleware/') === false) {
                continue;
            }

            // Middleware analysis would go here
            // For now, just log that we found middleware
            Log::debug('Analyzing middleware', ['file' => $fileInfo['path']]);
        }
    }

    /**
     * Analyze Artisan commands
     */
    private function analyzeArtisanCommands(array $phpFiles): void
    {
        foreach ($phpFiles as $fileInfo) {
            if (strpos($fileInfo['path'], 'Console/Commands/') === false) {
                continue;
            }

            $code = file_get_contents($fileInfo['absolute_path']);
            
            // Check for long-running commands without progress indication
            if (strpos($code, 'handle()') !== false && 
                strpos($code, 'progressBar') === false && 
                $fileInfo['line_count'] > 100) {
                
                $finding = new Finding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    'low',
                    'Long-running command without progress indication',
                    'usability',
                    'artisan_command'
                );
                
                $finding->addFileReference($fileInfo['path'], 1, 'Command without progress bar');
                $finding->addMetric('command_lines', $fileInfo['line_count'], 'lines');
                $finding->addEvidence('static_analysis', [
                    'command_file' => $fileInfo['path'],
                    'has_progress_bar' => false
                ], 'Artisan command analysis');
                
                $finding->setRecommendation('Add progress bar for better user experience');
                $finding->setEstimatedHours(1);
                
                $this->findings[] = $finding;
            }
        }
    }

    /**
     * Generate metrics for the phase
     */
    private function generateMetrics(): array
    {
        $totalComplexity = array_sum(array_map(fn($m) => $m->getCyclomaticComplexity(), $this->complexityMetrics));
        $totalLines = array_sum(array_map(fn($m) => $m->getLinesOfCode(), $this->complexityMetrics));
        $avgComplexity = count($this->complexityMetrics) > 0 ? $totalComplexity / count($this->complexityMetrics) : 0;
        
        return [
            [
                'name' => 'total_files_analyzed',
                'value' => count($this->complexityMetrics),
                'unit' => 'files'
            ],
            [
                'name' => 'total_lines_of_code',
                'value' => $totalLines,
                'unit' => 'lines'
            ],
            [
                'name' => 'average_complexity',
                'value' => round($avgComplexity, 2),
                'unit' => 'score'
            ],
            [
                'name' => 'complexity_score',
                'value' => $totalComplexity,
                'unit' => 'score'
            ],
            [
                'name' => 'findings_count',
                'value' => count($this->findings),
                'unit' => 'issues'
            ]
        ];
    }
}