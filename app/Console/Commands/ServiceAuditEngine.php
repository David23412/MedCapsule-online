<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Carbon\Carbon;

class ServiceAuditEngine extends Command
{
    protected $signature = 'service:audit {--output=console : Output format (console|json|html)}';
    protected $description = 'Comprehensive service layer audit and analysis';
    
    private array $serviceInventory = [];
    private array $codeQualityMetrics = [];
    private array $dependencyAnalysis = [];
    private array $securityAnalysis = [];
    private array $performanceAnalysis = [];
    
    public function handle(): int
    {
        $this->info("🔍 STARTING COMPREHENSIVE SERVICE AUDIT ENGINE");
        $this->line("");
        
        // Phase 1: Service Discovery
        $this->discoverServices();
        
        // Phase 2: Code Quality Analysis
        $this->analyzeCodeQuality();
        
        // Phase 3: Dependency Analysis
        $this->analyzeDependencies();
        
        // Phase 4: Security Analysis
        $this->analyzeSecurityCompliance();
        
        // Phase 5: Performance Analysis
        $this->analyzePerformancePatterns();
        
        // Phase 6: Generate Reports
        $this->generateAuditReports();
        
        $this->info("✅ SERVICE AUDIT COMPLETE");
        
        return 0;
    }
    
    private function discoverServices(): void
    {
        $this->info("📋 PHASE 1: SERVICE DISCOVERY");
        
        $servicesPath = app_path('Services');
        
        if (!File::exists($servicesPath)) {
            $this->error("Services directory not found: {$servicesPath}");
            return;
        }
        
        $serviceFiles = File::allFiles($servicesPath);
        
        foreach ($serviceFiles as $file) {
            if ($file->getExtension() === 'php') {
                $this->analyzeServiceFile($file);
            }
        }
        
        $this->info("Discovered " . count($this->serviceInventory) . " services");
    }
    
    private function analyzeServiceFile($file): void
    {
        $relativePath = str_replace(app_path(), 'app', $file->getPathname());
        $className = $this->getClassNameFromFile($file->getPathname());
        
        if (!$className) {
            return;
        }
        
        try {
            $reflection = new ReflectionClass($className);
            
            $serviceInfo = [
                'file_path' => $relativePath,
                'class_name' => $className,
                'namespace' => $reflection->getNamespaceName(),
                'is_abstract' => $reflection->isAbstract(),
                'is_interface' => $reflection->isInterface(),
                'is_trait' => $reflection->isTrait(),
                'parent_class' => $reflection->getParentClass() ? $reflection->getParentClass()->getName() : null,
                'interfaces' => array_keys($reflection->getInterfaces()),
                'traits' => array_keys($reflection->getTraits()),
                'methods' => $this->analyzeMethods($reflection),
                'properties' => $this->analyzeProperties($reflection),
                'constants' => $reflection->getConstants(),
                'file_size' => $file->getSize(),
                'lines_of_code' => $this->countLinesOfCode($file->getPathname()),
                'last_modified' => Carbon::createFromTimestamp($file->getMTime()),
                'dependencies' => $this->extractDependencies($file->getPathname()),
                'complexity_score' => 0, // Will be calculated later
                'maintainability_index' => 0, // Will be calculated later
            ];
            
            $this->serviceInventory[$className] = $serviceInfo;
            
        } catch (\Exception $e) {
            $this->warn("Failed to analyze {$className}: " . $e->getMessage());
        }
    }
    
    private function getClassNameFromFile(string $filePath): ?string
    {
        $content = File::get($filePath);
        
        // Extract namespace
        preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatches);
        $namespace = $namespaceMatches[1] ?? '';
        
        // Extract class name
        preg_match('/class\s+(\w+)/', $content, $classMatches);
        $className = $classMatches[1] ?? null;
        
        if (!$className) {
            return null;
        }
        
        return $namespace ? $namespace . '\\' . $className : $className;
    }
    
    private function analyzeMethods(ReflectionClass $reflection): array
    {
        $methods = [];
        
        foreach ($reflection->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() === $reflection->getName()) {
                $methods[$method->getName()] = [
                    'visibility' => $this->getMethodVisibility($method),
                    'is_static' => $method->isStatic(),
                    'is_abstract' => $method->isAbstract(),
                    'is_final' => $method->isFinal(),
                    'parameters' => $this->analyzeParameters($method),
                    'return_type' => $method->getReturnType() ? $method->getReturnType()->getName() : null,
                    'has_doc_comment' => !empty($method->getDocComment()),
                    'lines_of_code' => $this->getMethodLinesOfCode($method),
                    'complexity' => $this->calculateMethodComplexity($method),
                ];
            }
        }
        
        return $methods;
    }
    
    private function analyzeProperties(ReflectionClass $reflection): array
    {
        $properties = [];
        
        foreach ($reflection->getProperties() as $property) {
            if ($property->getDeclaringClass()->getName() === $reflection->getName()) {
                $properties[$property->getName()] = [
                    'visibility' => $this->getPropertyVisibility($property),
                    'is_static' => $property->isStatic(),
                    'type' => $property->getType() ? $property->getType()->getName() : null,
                    'has_default_value' => $property->hasDefaultValue(),
                    'has_doc_comment' => !empty($property->getDocComment()),
                ];
            }
        }
        
        return $properties;
    }
    
    private function analyzeParameters(ReflectionMethod $method): array
    {
        $parameters = [];
        
        foreach ($method->getParameters() as $param) {
            $parameters[$param->getName()] = [
                'type' => $param->getType() ? $param->getType()->getName() : null,
                'has_default_value' => $param->isDefaultValueAvailable(),
                'is_optional' => $param->isOptional(),
                'is_variadic' => $param->isVariadic(),
            ];
        }
        
        return $parameters;
    }
    
    private function extractDependencies(string $filePath): array
    {
        $content = File::get($filePath);
        $dependencies = [];
        
        // Extract use statements
        preg_match_all('/use\s+([^;]+);/', $content, $useMatches);
        foreach ($useMatches[1] as $use) {
            $use = trim($use);
            if (!str_starts_with($use, 'Illuminate\\') && !str_starts_with($use, 'Carbon\\')) {
                $dependencies[] = $use;
            }
        }
        
        // Extract service injections in constructor
        preg_match('/public\s+function\s+__construct\s*\(([^)]*)\)/', $content, $constructorMatches);
        if (isset($constructorMatches[1])) {
            preg_match_all('/(\w+Service)\s+\$\w+/', $constructorMatches[1], $serviceMatches);
            $dependencies = array_merge($dependencies, $serviceMatches[1]);
        }
        
        return array_unique($dependencies);
    }
    
    private function countLinesOfCode(string $filePath): int
    {
        $content = File::get($filePath);
        $lines = explode("\n", $content);
        
        $codeLines = 0;
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (!empty($trimmed) && !str_starts_with($trimmed, '//') && !str_starts_with($trimmed, '/*') && !str_starts_with($trimmed, '*')) {
                $codeLines++;
            }
        }
        
        return $codeLines;
    }
    
    private function getMethodVisibility(ReflectionMethod $method): string
    {
        if ($method->isPublic()) return 'public';
        if ($method->isProtected()) return 'protected';
        if ($method->isPrivate()) return 'private';
        return 'unknown';
    }
    
    private function getPropertyVisibility(ReflectionProperty $property): string
    {
        if ($property->isPublic()) return 'public';
        if ($property->isProtected()) return 'protected';
        if ($property->isPrivate()) return 'private';
        return 'unknown';
    }
    
    private function getMethodLinesOfCode(ReflectionMethod $method): int
    {
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        
        return $endLine - $startLine + 1;
    }
    
    private function calculateMethodComplexity(ReflectionMethod $method): int
    {
        // Simple complexity calculation based on method length and control structures
        $lines = $this->getMethodLinesOfCode($method);
        
        // Base complexity
        $complexity = 1;
        
        // Add complexity for method length
        if ($lines > 20) $complexity += 2;
        if ($lines > 50) $complexity += 3;
        
        // This is a simplified calculation - in a real implementation,
        // you would parse the method body for control structures
        
        return $complexity;
    }
    
    private function analyzeCodeQuality(): void
    {
        $this->info("📊 PHASE 2: CODE QUALITY ANALYSIS");
        
        foreach ($this->serviceInventory as $className => $serviceInfo) {
            $qualityMetrics = [
                'maintainability_index' => $this->calculateMaintainabilityIndex($serviceInfo),
                'complexity_score' => $this->calculateComplexityScore($serviceInfo),
                'documentation_coverage' => $this->calculateDocumentationCoverage($serviceInfo),
                'type_hinting_coverage' => $this->calculateTypeHintingCoverage($serviceInfo),
                'method_count' => count($serviceInfo['methods']),
                'average_method_length' => $this->calculateAverageMethodLength($serviceInfo),
                'code_duplication_risk' => $this->assessCodeDuplicationRisk($serviceInfo),
                'naming_convention_score' => $this->assessNamingConventions($serviceInfo),
                'single_responsibility_score' => $this->assessSingleResponsibility($serviceInfo),
            ];
            
            $this->codeQualityMetrics[$className] = $qualityMetrics;
        }
    }
    
    private function calculateMaintainabilityIndex(array $serviceInfo): float
    {
        // Simplified maintainability index calculation
        $loc = $serviceInfo['lines_of_code'];
        $methodCount = count($serviceInfo['methods']);
        $complexity = array_sum(array_column($serviceInfo['methods'], 'complexity'));
        
        // Maintainability Index = 171 - 5.2 * ln(Halstead Volume) - 0.23 * (Cyclomatic Complexity) - 16.2 * ln(Lines of Code)
        // Simplified version
        $mi = 100 - ($loc / 10) - ($complexity * 2) - ($methodCount / 2);
        
        return max(0, min(100, $mi));
    }
    
    private function calculateComplexityScore(array $serviceInfo): int
    {
        $totalComplexity = 0;
        foreach ($serviceInfo['methods'] as $method) {
            $totalComplexity += $method['complexity'];
        }
        
        return $totalComplexity;
    }
    
    private function calculateDocumentationCoverage(array $serviceInfo): float
    {
        $totalMethods = count($serviceInfo['methods']);
        if ($totalMethods === 0) return 100;
        
        $documentedMethods = 0;
        foreach ($serviceInfo['methods'] as $method) {
            if ($method['has_doc_comment']) {
                $documentedMethods++;
            }
        }
        
        return ($documentedMethods / $totalMethods) * 100;
    }
    
    private function calculateTypeHintingCoverage(array $serviceInfo): float
    {
        $totalMethods = count($serviceInfo['methods']);
        if ($totalMethods === 0) return 100;
        
        $typedMethods = 0;
        foreach ($serviceInfo['methods'] as $method) {
            $hasReturnType = !is_null($method['return_type']);
            $hasParameterTypes = true;
            
            foreach ($method['parameters'] as $param) {
                if (is_null($param['type'])) {
                    $hasParameterTypes = false;
                    break;
                }
            }
            
            if ($hasReturnType && $hasParameterTypes) {
                $typedMethods++;
            }
        }
        
        return ($typedMethods / $totalMethods) * 100;
    }
    
    private function calculateAverageMethodLength(array $serviceInfo): float
    {
        $totalLines = 0;
        $methodCount = count($serviceInfo['methods']);
        
        if ($methodCount === 0) return 0;
        
        foreach ($serviceInfo['methods'] as $method) {
            $totalLines += $method['lines_of_code'];
        }
        
        return $totalLines / $methodCount;
    }
    
    private function assessCodeDuplicationRisk(array $serviceInfo): string
    {
        $methodCount = count($serviceInfo['methods']);
        $avgMethodLength = $this->calculateAverageMethodLength($serviceInfo);
        
        if ($methodCount > 20 && $avgMethodLength > 30) {
            return 'high';
        } elseif ($methodCount > 10 && $avgMethodLength > 20) {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    private function assessNamingConventions(array $serviceInfo): float
    {
        $score = 100;
        
        // Check class name
        if (!str_ends_with($serviceInfo['class_name'], 'Service')) {
            $score -= 20;
        }
        
        // Check method names
        foreach ($serviceInfo['methods'] as $methodName => $method) {
            if (!ctype_lower($methodName[0])) { // Should start with lowercase
                $score -= 5;
            }
        }
        
        return max(0, $score);
    }
    
    private function assessSingleResponsibility(array $serviceInfo): float
    {
        $methodCount = count($serviceInfo['methods']);
        
        // Services with too many methods likely violate SRP
        if ($methodCount > 15) {
            return 30;
        } elseif ($methodCount > 10) {
            return 60;
        } elseif ($methodCount > 5) {
            return 80;
        } else {
            return 100;
        }
    }
    
    private function analyzeDependencies(): void
    {
        $this->info("🔗 PHASE 3: DEPENDENCY ANALYSIS");
        
        $dependencyGraph = [];
        $circularDependencies = [];
        
        foreach ($this->serviceInventory as $className => $serviceInfo) {
            $dependencyGraph[$className] = $serviceInfo['dependencies'];
        }
        
        // Detect circular dependencies
        foreach ($dependencyGraph as $service => $dependencies) {
            $visited = [];
            $recursionStack = [];
            
            if ($this->hasCircularDependency($service, $dependencyGraph, $visited, $recursionStack)) {
                $circularDependencies[] = $service;
            }
        }
        
        $this->dependencyAnalysis = [
            'dependency_graph' => $dependencyGraph,
            'circular_dependencies' => $circularDependencies,
            'dependency_depth' => $this->calculateDependencyDepth($dependencyGraph),
            'most_dependent_services' => $this->findMostDependentServices($dependencyGraph),
            'least_dependent_services' => $this->findLeastDependentServices($dependencyGraph),
        ];
    }
    
    private function hasCircularDependency(string $service, array $graph, array &$visited, array &$recursionStack): bool
    {
        $visited[$service] = true;
        $recursionStack[$service] = true;
        
        if (isset($graph[$service])) {
            foreach ($graph[$service] as $dependency) {
                if (!isset($visited[$dependency])) {
                    if ($this->hasCircularDependency($dependency, $graph, $visited, $recursionStack)) {
                        return true;
                    }
                } elseif (isset($recursionStack[$dependency])) {
                    return true;
                }
            }
        }
        
        unset($recursionStack[$service]);
        return false;
    }
    
    private function calculateDependencyDepth(array $graph): array
    {
        $depths = [];
        
        foreach ($graph as $service => $dependencies) {
            $depths[$service] = $this->getServiceDepth($service, $graph, []);
        }
        
        return $depths;
    }
    
    private function getServiceDepth(string $service, array $graph, array $visited): int
    {
        if (in_array($service, $visited)) {
            return 0; // Circular dependency
        }
        
        $visited[] = $service;
        $maxDepth = 0;
        
        if (isset($graph[$service])) {
            foreach ($graph[$service] as $dependency) {
                $depth = $this->getServiceDepth($dependency, $graph, $visited);
                $maxDepth = max($maxDepth, $depth + 1);
            }
        }
        
        return $maxDepth;
    }
    
    private function findMostDependentServices(array $graph): array
    {
        $dependencyCounts = [];
        
        foreach ($graph as $service => $dependencies) {
            $dependencyCounts[$service] = count($dependencies);
        }
        
        arsort($dependencyCounts);
        
        return array_slice($dependencyCounts, 0, 5, true);
    }
    
    private function findLeastDependentServices(array $graph): array
    {
        $dependencyCounts = [];
        
        foreach ($graph as $service => $dependencies) {
            $dependencyCounts[$service] = count($dependencies);
        }
        
        asort($dependencyCounts);
        
        return array_slice($dependencyCounts, 0, 5, true);
    }
    
    private function analyzeSecurityCompliance(): void
    {
        $this->info("🔒 PHASE 4: SECURITY COMPLIANCE ANALYSIS");
        
        foreach ($this->serviceInventory as $className => $serviceInfo) {
            $securityScore = 100;
            $issues = [];
            
            // Check for potential security issues
            if (in_array('PaymentService', $serviceInfo['dependencies'])) {
                // Payment-related services need extra security
                if ($this->codeQualityMetrics[$className]['type_hinting_coverage'] < 90) {
                    $securityScore -= 20;
                    $issues[] = 'Insufficient type hinting for payment service';
                }
            }
            
            // Check for proper validation
            $hasValidation = false;
            foreach ($serviceInfo['methods'] as $methodName => $method) {
                if (str_contains($methodName, 'validate') || str_contains($methodName, 'sanitize')) {
                    $hasValidation = true;
                    break;
                }
            }
            
            if (!$hasValidation && count($serviceInfo['methods']) > 3) {
                $securityScore -= 15;
                $issues[] = 'No apparent validation methods';
            }
            
            $this->securityAnalysis[$className] = [
                'security_score' => max(0, $securityScore),
                'issues' => $issues,
                'compliance_level' => $securityScore >= 80 ? 'high' : ($securityScore >= 60 ? 'medium' : 'low')
            ];
        }
    }
    
    private function analyzePerformancePatterns(): void
    {
        $this->info("⚡ PHASE 5: PERFORMANCE PATTERN ANALYSIS");
        
        foreach ($this->serviceInventory as $className => $serviceInfo) {
            $performanceScore = 100;
            $recommendations = [];
            
            // Check for potential performance issues
            if (count($serviceInfo['methods']) > 20) {
                $performanceScore -= 20;
                $recommendations[] = 'Consider splitting large service into smaller, focused services';
            }
            
            if ($this->calculateAverageMethodLength($serviceInfo) > 50) {
                $performanceScore -= 15;
                $recommendations[] = 'Methods are too long - consider refactoring for better performance';
            }
            
            // Check for caching patterns
            $hasCaching = false;
            foreach ($serviceInfo['dependencies'] as $dependency) {
                if (str_contains($dependency, 'Cache')) {
                    $hasCaching = true;
                    break;
                }
            }
            
            if (!$hasCaching && count($serviceInfo['methods']) > 5) {
                $performanceScore -= 10;
                $recommendations[] = 'Consider implementing caching for better performance';
            }
            
            $this->performanceAnalysis[$className] = [
                'performance_score' => max(0, $performanceScore),
                'recommendations' => $recommendations,
                'optimization_priority' => $performanceScore < 70 ? 'high' : ($performanceScore < 85 ? 'medium' : 'low')
            ];
        }
    }
    
    private function generateAuditReports(): void
    {
        $this->info("📋 PHASE 6: GENERATING AUDIT REPORTS");
        
        $outputFormat = $this->option('output');
        
        $auditData = [
            'audit_timestamp' => Carbon::now()->toISOString(),
            'service_inventory' => $this->serviceInventory,
            'code_quality_metrics' => $this->codeQualityMetrics,
            'dependency_analysis' => $this->dependencyAnalysis,
            'security_analysis' => $this->securityAnalysis,
            'performance_analysis' => $this->performanceAnalysis,
            'summary' => $this->generateSummary()
        ];
        
        switch ($outputFormat) {
            case 'json':
                $this->generateJsonReport($auditData);
                break;
            case 'html':
                $this->generateHtmlReport($auditData);
                break;
            default:
                $this->displayConsoleReport($auditData);
                break;
        }
    }
    
    private function generateSummary(): array
    {
        $totalServices = count($this->serviceInventory);
        $avgMaintainability = 0;
        $avgComplexity = 0;
        $highRiskServices = 0;
        
        foreach ($this->codeQualityMetrics as $metrics) {
            $avgMaintainability += $metrics['maintainability_index'];
            $avgComplexity += $metrics['complexity_score'];
            
            if ($metrics['maintainability_index'] < 50 || $metrics['complexity_score'] > 20) {
                $highRiskServices++;
            }
        }
        
        $avgMaintainability = $totalServices > 0 ? $avgMaintainability / $totalServices : 0;
        $avgComplexity = $totalServices > 0 ? $avgComplexity / $totalServices : 0;
        
        return [
            'total_services' => $totalServices,
            'average_maintainability_index' => round($avgMaintainability, 2),
            'average_complexity_score' => round($avgComplexity, 2),
            'high_risk_services' => $highRiskServices,
            'circular_dependencies' => count($this->dependencyAnalysis['circular_dependencies'] ?? []),
            'security_compliance_rate' => $this->calculateSecurityComplianceRate(),
            'performance_optimization_needed' => $this->countServicesNeedingOptimization()
        ];
    }
    
    private function calculateSecurityComplianceRate(): float
    {
        if (empty($this->securityAnalysis)) return 100;
        
        $compliantServices = 0;
        foreach ($this->securityAnalysis as $analysis) {
            if ($analysis['compliance_level'] === 'high') {
                $compliantServices++;
            }
        }
        
        return ($compliantServices / count($this->securityAnalysis)) * 100;
    }
    
    private function countServicesNeedingOptimization(): int
    {
        $count = 0;
        foreach ($this->performanceAnalysis as $analysis) {
            if ($analysis['optimization_priority'] === 'high') {
                $count++;
            }
        }
        return $count;
    }
    
    private function generateJsonReport(array $auditData): void
    {
        $reportPath = storage_path('logs/service_audit_report_' . date('Y-m-d_H-i-s') . '.json');
        file_put_contents($reportPath, json_encode($auditData, JSON_PRETTY_PRINT));
        $this->info("📄 JSON report saved to: {$reportPath}");
    }
    
    private function generateHtmlReport(array $auditData): void
    {
        // HTML report generation would go here
        $this->info("📄 HTML report generation not implemented yet");
    }
    
    private function displayConsoleReport(array $auditData): void
    {
        $this->line("");
        $this->info("🎯 SERVICE AUDIT SUMMARY");
        $this->line("Total Services: " . $auditData['summary']['total_services']);
        $this->line("Average Maintainability: " . $auditData['summary']['average_maintainability_index']);
        $this->line("Average Complexity: " . $auditData['summary']['average_complexity_score']);
        $this->line("High Risk Services: " . $auditData['summary']['high_risk_services']);
        $this->line("Circular Dependencies: " . $auditData['summary']['circular_dependencies']);
        $this->line("Security Compliance: " . round($auditData['summary']['security_compliance_rate'], 1) . "%");
        $this->line("Services Needing Optimization: " . $auditData['summary']['performance_optimization_needed']);
        
        $this->line("");
        $this->info("🏆 TOP PERFORMING SERVICES");
        $topServices = [];
        foreach ($this->codeQualityMetrics as $service => $metrics) {
            $topServices[$service] = $metrics['maintainability_index'];
        }
        arsort($topServices);
        
        $count = 1;
        foreach (array_slice($topServices, 0, 5, true) as $service => $score) {
            $this->line("{$count}. " . class_basename($service) . ": " . round($score, 1));
            $count++;
        }
        
        $this->line("");
        $this->info("⚠️  SERVICES NEEDING ATTENTION");
        $problemServices = [];
        foreach ($this->codeQualityMetrics as $service => $metrics) {
            if ($metrics['maintainability_index'] < 60 || $metrics['complexity_score'] > 15) {
                $problemServices[] = class_basename($service);
            }
        }
        
        if (empty($problemServices)) {
            $this->line("✅ All services are in good condition!");
        } else {
            foreach ($problemServices as $service) {
                $this->line("- {$service}");
            }
        }
    }
}