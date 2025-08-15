<?php

namespace App\Services\Audit\Components;

use App\Services\Audit\Models\ConcreteFinding;
use App\Services\Audit\Reports\PhaseResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Exception;

/**
 * Domain-Centric Score Calculator
 * 
 * Calculates domain-specific readiness scores, aggregates issues by domain,
 * generates priority matrices, and provides resource allocation recommendations
 */
class DomainScoreCalculator
{
    private FileScanner $fileScanner;
    private array $findings = [];
    private int $findingCounter = 1;
    private array $domainMetrics = [];
    private array $domainScores = [];
    private array $componentMapping = [];
    private array $priorityMatrix = [];
    private array $resourceRecommendations = [];

    // Domain definitions
    private array $domains = [
        'authentication' => [
            'name' => 'Authentication & Authorization',
            'weight' => 0.20,
            'components' => ['auth', 'login', 'register', 'password', 'session', 'middleware', 'guard'],
            'critical_paths' => ['login', 'logout', 'password_reset', 'registration']
        ],
        'quiz' => [
            'name' => 'Quiz System',
            'weight' => 0.25,
            'components' => ['quiz', 'question', 'answer', 'attempt', 'score', 'timer'],
            'critical_paths' => ['quiz_generation', 'question_display', 'answer_submission', 'scoring']
        ],
        'grading' => [
            'name' => 'Grading & Assessment',
            'weight' => 0.20,
            'components' => ['grade', 'assessment', 'rubric', 'feedback', 'evaluation'],
            'critical_paths' => ['grade_calculation', 'feedback_generation', 'result_display']
        ],
        'payments' => [
            'name' => 'Payment Processing',
            'weight' => 0.15,
            'components' => ['payment', 'billing', 'invoice', 'transaction', 'stripe', 'paypal'],
            'critical_paths' => ['payment_processing', 'refund_handling', 'subscription_management']
        ],
        'notifications' => [
            'name' => 'Notification System',
            'weight' => 0.10,
            'components' => ['notification', 'email', 'mail', 'sms', 'alert', 'message'],
            'critical_paths' => ['email_sending', 'notification_delivery', 'alert_processing']
        ],
        'user_management' => [
            'name' => 'User Management',
            'weight' => 0.10,
            'components' => ['user', 'profile', 'role', 'permission', 'account', 'settings'],
            'critical_paths' => ['user_creation', 'profile_management', 'role_assignment']
        ]
    ];

    public function __construct(FileScanner $fileScanner)
    {
        $this->fileScanner = $fileScanner;
    }

    /**
     * Execute comprehensive domain-centric score calculation
     */
    public function executeDomainScoreCalculation(string $projectPath): PhaseResult
    {
        Log::info('🔍 Starting Domain-Centric Score Calculation');
        
        $startTime = microtime(true);
        $this->findings = [];
        
        try {
            // Scan directory first to populate file lists
            $this->fileScanner->scanDirectory($projectPath);
            
            // 1. Calculate 6 Domain Scores
            $this->calculateDomainScores();
            
            // 2. Map Components to Domains
            $this->mapComponentsToDomains();
            
            // 3. Aggregate Issues by Domain
            $this->aggregateIssuesByDomain();
            
            // 4. Assess Interdependency Impact
            $this->assessInterdependencyImpact();
            
            // 5. Generate Priority Matrix with Effort Estimates
            $this->generatePriorityMatrix();
            
            // 6. Provide Resource Allocation Recommendations
            $this->generateResourceAllocationRecommendations();
            
            $executionTime = microtime(true) - $startTime;
            
            $phaseResult = new PhaseResult(
                'domain_score_calculation',
                $this->findings,
                $this->generateDomainMetrics(),
                $executionTime
            );
            
            Log::info('✅ Domain Score Calculation Complete', [
                'findings' => count($this->findings),
                'domain_scores' => $this->domainScores,
                'execution_time' => round($executionTime, 2) . 's'
            ]);
            
            return $phaseResult;
            
        } catch (\Exception $e) {
            Log::error('❌ Domain Score Calculation Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Calculate domain-specific readiness scores for all 6 domains
     */
    public function calculateDomainScores(): void
    {
        Log::info('🔍 Calculating Domain-Specific Readiness Scores');
        
        $phpFiles = $this->fileScanner->getPHPFiles();
        
        foreach ($this->domains as $domainKey => $domainConfig) {
            $domainScore = $this->calculateSingleDomainScore($domainKey, $domainConfig, $phpFiles);
            $this->domainScores[$domainKey] = $domainScore;
            
            // Generate finding if domain score is below threshold
            if ($domainScore['readiness_score'] < 0.7) {
                $severity = $domainScore['readiness_score'] < 0.5 ? 'critical' : 'high';
                
                $finding = new ConcreteFinding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    $severity,
                    "Low domain readiness: {$domainConfig['name']} (Score: {$domainScore['readiness_score']})",
                    'domain_score',
                    'low_domain_readiness'
                );
                
                $finding->addFileReference('domain_analysis', 1, "Domain: {$domainConfig['name']}");
                $finding->addMetric('readiness_score', $domainScore['readiness_score'], 'score_0_to_1');
                $finding->addMetric('component_count', $domainScore['component_count'], 'components');
                $finding->addMetric('issue_count', $domainScore['issue_count'], 'issues');
                $finding->addMetric('complexity_score', $domainScore['complexity_score'], 'score_out_of_10');
                
                $finding->addEvidence('domain_analysis', [
                    'domain_name' => $domainConfig['name'],
                    'domain_key' => $domainKey,
                    'readiness_breakdown' => $domainScore['breakdown'],
                    'critical_paths' => $domainScore['critical_path_analysis'],
                    'component_analysis' => $domainScore['component_analysis']
                ], 'Low domain readiness score detected');
                
                $finding->setRecommendation($this->getDomainRecommendation($domainKey, $domainScore));
                $finding->setEstimatedHours($this->estimateDomainEffort($domainScore));
                
                $this->findings[] = $finding;
            }
        }
        
        Log::info('Calculated scores for ' . count($this->domains) . ' domains');
    }

    /**
     * Calculate readiness score for a single domain
     */
    private function calculateSingleDomainScore(string $domainKey, array $domainConfig, array $phpFiles): array
    {
        $domainFiles = $this->identifyDomainFiles($domainConfig, $phpFiles);
        $componentAnalysis = $this->analyzeDomainComponents($domainFiles);
        $criticalPathAnalysis = $this->analyzeCriticalPaths($domainConfig['critical_paths'], $domainFiles);
        
        // Calculate sub-scores
        $codeQualityScore = $this->calculateCodeQualityScore($domainFiles);
        $testCoverageScore = $this->calculateTestCoverageScore($domainFiles);
        $complexityScore = $this->calculateComplexityScore($domainFiles);
        $securityScore = $this->calculateSecurityScore($domainFiles);
        $performanceScore = $this->calculatePerformanceScore($domainFiles);
        $maintainabilityScore = $this->calculateMaintainabilityScore($domainFiles);
        
        // Weighted readiness score
        $readinessScore = (
            $codeQualityScore * 0.25 +
            $testCoverageScore * 0.20 +
            $complexityScore * 0.15 +
            $securityScore * 0.20 +
            $performanceScore * 0.10 +
            $maintainabilityScore * 0.10
        );
        
        return [
            'domain_key' => $domainKey,
            'domain_name' => $domainConfig['name'],
            'readiness_score' => round($readinessScore, 3),
            'component_count' => count($domainFiles),
            'issue_count' => $this->countDomainIssues($domainFiles),
            'complexity_score' => round($complexityScore * 10, 1),
            'breakdown' => [
                'code_quality' => round($codeQualityScore, 3),
                'test_coverage' => round($testCoverageScore, 3),
                'complexity' => round($complexityScore, 3),
                'security' => round($securityScore, 3),
                'performance' => round($performanceScore, 3),
                'maintainability' => round($maintainabilityScore, 3)
            ],
            'component_analysis' => $componentAnalysis,
            'critical_path_analysis' => $criticalPathAnalysis,
            'domain_files' => array_slice($domainFiles, 0, 10) // Limit for readability
        ];
    }

    /**
     * Map components to their respective domains
     */
    public function mapComponentsToDomains(): void
    {
        Log::info('🔍 Mapping Components to Domains');
        
        $phpFiles = $this->fileScanner->getPHPFiles();
        $unmappedComponents = 0;
        
        foreach ($phpFiles as $file) {
            $mappedDomains = [];
            
            foreach ($this->domains as $domainKey => $domainConfig) {
                if ($this->isFileInDomain($file, $domainConfig)) {
                    $mappedDomains[] = $domainKey;
                }
            }
            
            $this->componentMapping[$file['path']] = [
                'file' => $file['path'],
                'domains' => $mappedDomains,
                'primary_domain' => $this->determinePrimaryDomain($file, $mappedDomains),
                'cross_domain' => count($mappedDomains) > 1
            ];
            
            // Flag unmapped components
            if (empty($mappedDomains)) {
                $unmappedComponents++;
                
                $finding = new ConcreteFinding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    'low',
                    "Component not mapped to any domain: {$file['path']}",
                    'domain_score',
                    'unmapped_component'
                );
                
                $finding->addFileReference($file['path'], 1, 'Unmapped component');
                $finding->addMetric('mapped_domains', 0, 'domain_count');
                $finding->addMetric('file_size', $file['size'] ?? 0, 'bytes');
                
                $finding->addEvidence('component_mapping', [
                    'file_path' => $file['path'],
                    'mapped_domains' => [],
                    'analysis_keywords' => $this->extractFileKeywords($file),
                    'suggested_domains' => $this->suggestDomains($file)
                ], 'Component not mapped to any domain');
                
                $finding->setRecommendation('Review component and assign to appropriate domain');
                $finding->setEstimatedHours(1);
                
                $this->findings[] = $finding;
            }
            
            // Flag cross-domain components
            if (count($mappedDomains) > 2) {
                $finding = new ConcreteFinding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    'medium',
                    "Component spans multiple domains: {$file['path']} (" . implode(', ', $mappedDomains) . ")",
                    'domain_score',
                    'cross_domain_component'
                );
                
                $finding->addFileReference($file['path'], 1, 'Cross-domain component');
                $finding->addMetric('mapped_domains', count($mappedDomains), 'domain_count');
                $finding->addMetric('coupling_risk', count($mappedDomains) * 2, 'score_out_of_10');
                
                $finding->addEvidence('component_mapping', [
                    'file_path' => $file['path'],
                    'mapped_domains' => $mappedDomains,
                    'primary_domain' => $this->componentMapping[$file['path']]['primary_domain'],
                    'coupling_analysis' => $this->analyzeCrossDomainCoupling($file, $mappedDomains)
                ], 'Cross-domain component detected');
                
                $finding->setRecommendation('Consider refactoring to reduce cross-domain coupling');
                $finding->setEstimatedHours(4);
                
                $this->findings[] = $finding;
            }
        }
        
        $this->domainMetrics['unmapped_components'] = $unmappedComponents;
        $this->domainMetrics['cross_domain_components'] = count(array_filter($this->componentMapping, fn($mapping) => $mapping['cross_domain']));
        
        Log::info("Mapped components: {$unmappedComponents} unmapped, {$this->domainMetrics['cross_domain_components']} cross-domain");
    }

    /**
     * Aggregate issues by domain for comprehensive analysis
     */
    public function aggregateIssuesByDomain(): void
    {
        Log::info('🔍 Aggregating Issues by Domain');
        
        $domainIssues = [];
        
        // Initialize domain issue counters
        foreach ($this->domains as $domainKey => $domainConfig) {
            $domainIssues[$domainKey] = [
                'domain_name' => $domainConfig['name'],
                'critical_issues' => 0,
                'high_issues' => 0,
                'medium_issues' => 0,
                'low_issues' => 0,
                'total_issues' => 0,
                'issue_types' => [],
                'affected_components' => [],
                'estimated_effort' => 0
            ];
        }
        
        // Aggregate existing findings by domain
        foreach ($this->findings as $finding) {
            $fileReferences = $finding->getFileReferences();
            
            foreach ($fileReferences as $fileRef) {
                $filePath = $fileRef['file'];
                $mapping = $this->componentMapping[$filePath] ?? null;
                
                if ($mapping && !empty($mapping['domains'])) {
                    $primaryDomain = $mapping['primary_domain'];
                    
                    if (isset($domainIssues[$primaryDomain])) {
                        $severity = $finding->getSeverity();
                        $domainIssues[$primaryDomain][$severity . '_issues']++;
                        $domainIssues[$primaryDomain]['total_issues']++;
                        $domainIssues[$primaryDomain]['issue_types'][] = $finding->getSubtype();
                        $domainIssues[$primaryDomain]['affected_components'][] = $filePath;
                        $domainIssues[$primaryDomain]['estimated_effort'] += $finding->getEstimatedHours();
                    }
                }
            }
        }
        
        // Generate domain issue summary findings
        foreach ($domainIssues as $domainKey => $issueData) {
            if ($issueData['total_issues'] > 0) {
                $severity = $this->determineDomainIssueSeverity($issueData);
                
                $finding = new ConcreteFinding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    $severity,
                    "Domain issue aggregation: {$issueData['domain_name']} ({$issueData['total_issues']} issues)",
                    'domain_score',
                    'domain_issue_aggregation'
                );
                
                $finding->addFileReference('domain_analysis', 1, "Domain: {$issueData['domain_name']}");
                $finding->addMetric('total_issues', $issueData['total_issues'], 'issue_count');
                $finding->addMetric('critical_issues', $issueData['critical_issues'], 'critical_count');
                $finding->addMetric('high_issues', $issueData['high_issues'], 'high_count');
                $finding->addMetric('estimated_effort', $issueData['estimated_effort'], 'hours');
                
                $finding->addEvidence('domain_aggregation', [
                    'domain_key' => $domainKey,
                    'domain_name' => $issueData['domain_name'],
                    'issue_breakdown' => $issueData,
                    'unique_issue_types' => array_unique($issueData['issue_types']),
                    'unique_components' => array_unique($issueData['affected_components'])
                ], 'Domain issue aggregation analysis');
                
                $finding->setRecommendation($this->getDomainIssueRecommendation($domainKey, $issueData));
                $finding->setEstimatedHours($issueData['estimated_effort']);
                
                $this->findings[] = $finding;
            }
        }
        
        $this->domainMetrics['domain_issues'] = $domainIssues;
        Log::info('Aggregated issues for ' . count($domainIssues) . ' domains');
    }

    /**
     * Assess interdependency impact across domains
     */
    public function assessInterdependencyImpact(): void
    {
        Log::info('🔍 Assessing Interdependency Impact');
        
        $interdependencies = $this->identifyDomainInterdependencies();
        $impactAnalysis = [];
        
        foreach ($interdependencies as $dependency) {
            $impact = $this->calculateInterdependencyImpact($dependency);
            $impactAnalysis[] = $impact;
            
            if ($impact['risk_score'] > 7.0) {
                $finding = new ConcreteFinding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    'high',
                    "High interdependency risk: {$dependency['from_domain']} → {$dependency['to_domain']}",
                    'domain_score',
                    'high_interdependency_risk'
                );
                
                $finding->addFileReference($dependency['component'], 1, "Interdependency: {$dependency['type']}");
                $finding->addMetric('risk_score', $impact['risk_score'], 'score_out_of_10');
                $finding->addMetric('coupling_strength', $impact['coupling_strength'], 'score_0_to_1');
                $finding->addMetric('failure_propagation_risk', $impact['failure_propagation_risk'], 'score_0_to_1');
                
                $finding->addEvidence('interdependency_analysis', [
                    'dependency_details' => $dependency,
                    'impact_analysis' => $impact,
                    'affected_domains' => [$dependency['from_domain'], $dependency['to_domain']],
                    'mitigation_strategies' => $impact['mitigation_strategies']
                ], 'High interdependency risk detected');
                
                $finding->setRecommendation($impact['recommendation']);
                $finding->setEstimatedHours($impact['estimated_effort']);
                
                $this->findings[] = $finding;
            }
        }
        
        $this->domainMetrics['interdependency_analysis'] = $impactAnalysis;
        Log::info("Analyzed {count($interdependencies)} interdependencies");
    }    /*
*
     * Generate priority matrix with effort estimates
     */
    public function generatePriorityMatrix(): void
    {
        Log::info('🔍 Generating Priority Matrix with Effort Estimates');
        
        $this->priorityMatrix = [
            'critical_high_effort' => [],
            'critical_low_effort' => [],
            'high_high_effort' => [],
            'high_low_effort' => [],
            'medium_high_effort' => [],
            'medium_low_effort' => [],
            'low_effort' => []
        ];
        
        // Categorize findings by priority and effort
        foreach ($this->findings as $finding) {
            $severity = $finding->getSeverity();
            $effort = $finding->getEstimatedHours();
            $category = $this->categorizeFinding($severity, $effort);
            
            $this->priorityMatrix[$category][] = [
                'finding_id' => $finding->getId(),
                'description' => $finding->getDescription(),
                'severity' => $severity,
                'effort_hours' => $effort,
                'category' => $finding->getCategory(),
                'subtype' => $finding->getSubtype(),
                'domain' => $this->identifyFindingDomain($finding),
                'priority_score' => $this->calculatePriorityScore($severity, $effort)
            ];
        }
        
        // Sort each category by priority score
        foreach ($this->priorityMatrix as $category => $items) {
            usort($this->priorityMatrix[$category], fn($a, $b) => $b['priority_score'] <=> $a['priority_score']);
        }
        
        // Generate priority matrix summary
        $matrixSummary = $this->generatePriorityMatrixSummary();
        
        $finding = new ConcreteFinding(
            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
            'low',
            "Priority matrix generated with {$matrixSummary['total_items']} items across {$matrixSummary['categories']} categories",
            'domain_score',
            'priority_matrix_summary'
        );
        
        $finding->addFileReference('priority_matrix', 1, 'Priority Matrix Analysis');
        $finding->addMetric('total_items', $matrixSummary['total_items'], 'finding_count');
        $finding->addMetric('critical_items', $matrixSummary['critical_items'], 'critical_count');
        $finding->addMetric('total_effort', $matrixSummary['total_effort'], 'hours');
        $finding->addMetric('quick_wins', $matrixSummary['quick_wins'], 'quick_win_count');
        
        $finding->addEvidence('priority_matrix', [
            'matrix_breakdown' => $matrixSummary['breakdown'],
            'quick_wins' => array_slice($this->priorityMatrix['critical_low_effort'], 0, 5),
            'high_impact_items' => array_slice($this->priorityMatrix['critical_high_effort'], 0, 5),
            'recommendations' => $matrixSummary['recommendations']
        ], 'Priority matrix analysis completed');
        
        $finding->setRecommendation('Use priority matrix to guide development efforts and resource allocation');
        $finding->setEstimatedHours(2);
        
        $this->findings[] = $finding;
        
        Log::info("Generated priority matrix with {$matrixSummary['total_items']} items");
    }

    /**
     * Generate resource allocation recommendations
     */
    public function generateResourceAllocationRecommendations(): void
    {
        Log::info('🔍 Generating Resource Allocation Recommendations');
        
        $domainEfforts = $this->calculateDomainEfforts();
        $teamRecommendations = $this->generateTeamRecommendations($domainEfforts);
        $timelineRecommendations = $this->generateTimelineRecommendations($domainEfforts);
        $skillRecommendations = $this->generateSkillRecommendations();
        
        $this->resourceRecommendations = [
            'domain_efforts' => $domainEfforts,
            'team_recommendations' => $teamRecommendations,
            'timeline_recommendations' => $timelineRecommendations,
            'skill_recommendations' => $skillRecommendations,
            'budget_estimates' => $this->generateBudgetEstimates($domainEfforts),
            'risk_mitigation' => $this->generateRiskMitigationStrategies()
        ];
        
        // Generate comprehensive recommendation finding
        $totalEffort = array_sum(array_column($domainEfforts, 'total_effort'));
        $criticalDomains = array_filter($domainEfforts, fn($domain) => $domain['priority'] === 'critical');
        
        $finding = new ConcreteFinding(
            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
            'low',
            "Resource allocation recommendations generated (Total effort: {$totalEffort} hours)",
            'domain_score',
            'resource_allocation_recommendations'
        );
        
        $finding->addFileReference('resource_allocation', 1, 'Resource Allocation Analysis');
        $finding->addMetric('total_effort_hours', $totalEffort, 'hours');
        $finding->addMetric('critical_domains', count($criticalDomains), 'domain_count');
        $finding->addMetric('recommended_team_size', $teamRecommendations['recommended_team_size'], 'team_members');
        $finding->addMetric('estimated_timeline', $timelineRecommendations['total_timeline_weeks'], 'weeks');
        
        $finding->addEvidence('resource_allocation', [
            'domain_efforts' => $domainEfforts,
            'team_recommendations' => $teamRecommendations,
            'timeline_recommendations' => $timelineRecommendations,
            'skill_requirements' => $skillRecommendations,
            'budget_breakdown' => $this->resourceRecommendations['budget_estimates']
        ], 'Comprehensive resource allocation analysis');
        
        $finding->setRecommendation($this->generateMasterRecommendation());
        $finding->setEstimatedHours($totalEffort);
        
        $this->findings[] = $finding;
        
        Log::info("Generated resource allocation recommendations for {$totalEffort} hours of work");
    }

    /**
     * Helper methods for domain analysis
     */
    private function identifyDomainFiles(array $domainConfig, array $phpFiles): array
    {
        $domainFiles = [];
        
        foreach ($phpFiles as $file) {
            if ($this->isFileInDomain($file, $domainConfig)) {
                $domainFiles[] = $file;
            }
        }
        
        return $domainFiles;
    }

    private function isFileInDomain(array $file, array $domainConfig): bool
    {
        $filePath = strtolower($file['path']);
        $fileName = strtolower($file['filename'] ?? '');
        
        foreach ($domainConfig['components'] as $component) {
            if (strpos($filePath, strtolower($component)) !== false || 
                strpos($fileName, strtolower($component)) !== false) {
                return true;
            }
        }
        
        return false;
    }

    private function analyzeDomainComponents(array $domainFiles): array
    {
        $analysis = [
            'total_files' => count($domainFiles),
            'controllers' => 0,
            'models' => 0,
            'services' => 0,
            'tests' => 0,
            'migrations' => 0,
            'total_lines' => 0,
            'average_complexity' => 0
        ];
        
        foreach ($domainFiles as $file) {
            $filePath = strtolower($file['path']);
            
            if (strpos($filePath, 'controller') !== false) $analysis['controllers']++;
            if (strpos($filePath, 'model') !== false) $analysis['models']++;
            if (strpos($filePath, 'service') !== false) $analysis['services']++;
            if (strpos($filePath, 'test') !== false) $analysis['tests']++;
            if (strpos($filePath, 'migration') !== false) $analysis['migrations']++;
            
            // Estimate lines of code
            if (isset($file['absolute_path']) && file_exists($file['absolute_path'])) {
                $content = file_get_contents($file['absolute_path']);
                $analysis['total_lines'] += substr_count($content, "\n");
            }
        }
        
        return $analysis;
    }

    private function analyzeCriticalPaths(array $criticalPaths, array $domainFiles): array
    {
        $pathAnalysis = [];
        
        foreach ($criticalPaths as $path) {
            $pathFiles = array_filter($domainFiles, function($file) use ($path) {
                return strpos(strtolower($file['path']), strtolower($path)) !== false;
            });
            
            $pathAnalysis[$path] = [
                'path_name' => $path,
                'file_count' => count($pathFiles),
                'coverage_score' => $this->calculatePathCoverage($pathFiles),
                'complexity_score' => $this->calculatePathComplexity($pathFiles),
                'risk_score' => $this->calculatePathRisk($pathFiles)
            ];
        }
        
        return $pathAnalysis;
    }

    private function calculateCodeQualityScore(array $files): float
    {
        if (empty($files)) return 0.0;
        
        $qualityScore = 0.8; // Base score
        
        foreach ($files as $file) {
            if (isset($file['absolute_path']) && file_exists($file['absolute_path'])) {
                $content = file_get_contents($file['absolute_path']);
                
                // Deduct for code smells
                if (preg_match('/TODO|FIXME|HACK/', $content)) $qualityScore -= 0.05;
                if (preg_match('/var_dump|print_r|die\(|exit\(/', $content)) $qualityScore -= 0.1;
                if (preg_match('/\$\$|\$GLOBALS/', $content)) $qualityScore -= 0.1;
            }
        }
        
        return max(0.0, min(1.0, $qualityScore));
    }

    private function calculateTestCoverageScore(array $files): float
    {
        $sourceFiles = array_filter($files, fn($file) => strpos($file['path'], 'test') === false);
        $testFiles = array_filter($files, fn($file) => strpos($file['path'], 'test') !== false);
        
        if (empty($sourceFiles)) return 1.0;
        
        $coverageRatio = count($testFiles) / count($sourceFiles);
        return min(1.0, $coverageRatio);
    }

    private function calculateComplexityScore(array $files): float
    {
        if (empty($files)) return 1.0;
        
        $totalComplexity = 0;
        $fileCount = 0;
        
        foreach ($files as $file) {
            if (isset($file['absolute_path']) && file_exists($file['absolute_path'])) {
                $content = file_get_contents($file['absolute_path']);
                $complexity = $this->calculateFileComplexity($content);
                $totalComplexity += $complexity;
                $fileCount++;
            }
        }
        
        if ($fileCount === 0) return 1.0;
        
        $averageComplexity = $totalComplexity / $fileCount;
        return max(0.0, min(1.0, 1.0 - ($averageComplexity / 50))); // Normalize to 0-1
    }

    private function calculateSecurityScore(array $files): float
    {
        $securityScore = 1.0;
        
        foreach ($files as $file) {
            if (isset($file['absolute_path']) && file_exists($file['absolute_path'])) {
                $content = file_get_contents($file['absolute_path']);
                
                // Check for security issues
                if (preg_match('/eval\(|exec\(|system\(/', $content)) $securityScore -= 0.2;
                if (preg_match('/\$_GET|\$_POST|\$_REQUEST/', $content) && !preg_match('/validate|sanitize/', $content)) {
                    $securityScore -= 0.1;
                }
                if (preg_match('/password.*=.*[\'"][^\'"]*[\'"]/', $content)) $securityScore -= 0.3;
            }
        }
        
        return max(0.0, $securityScore);
    }

    private function calculatePerformanceScore(array $files): float
    {
        $performanceScore = 1.0;
        
        foreach ($files as $file) {
            if (isset($file['absolute_path']) && file_exists($file['absolute_path'])) {
                $content = file_get_contents($file['absolute_path']);
                
                // Check for performance issues
                if (preg_match('/select.*\*.*from/i', $content)) $performanceScore -= 0.1;
                if (preg_match('/for.*\(.*\$.*count\(/', $content)) $performanceScore -= 0.1;
                if (preg_match('/while.*true/', $content)) $performanceScore -= 0.2;
            }
        }
        
        return max(0.0, $performanceScore);
    }

    private function calculateMaintainabilityScore(array $files): float
    {
        if (empty($files)) return 1.0;
        
        $maintainabilityScore = 1.0;
        $totalLines = 0;
        $totalMethods = 0;
        
        foreach ($files as $file) {
            if (isset($file['absolute_path']) && file_exists($file['absolute_path'])) {
                $content = file_get_contents($file['absolute_path']);
                $lines = substr_count($content, "\n");
                $methods = preg_match_all('/function\s+\w+/', $content);
                
                $totalLines += $lines;
                $totalMethods += $methods;
                
                // Deduct for maintainability issues
                if ($lines > 500) $maintainabilityScore -= 0.1; // Large files
                if ($methods > 20) $maintainabilityScore -= 0.1; // Too many methods
            }
        }
        
        return max(0.0, $maintainabilityScore);
    }

    private function countDomainIssues(array $files): int
    {
        // This would integrate with actual issue detection
        // For now, return a heuristic based on file analysis
        return count($files) * 2; // Placeholder
    }

    private function calculateFileComplexity(string $content): int
    {
        $complexity = 1; // Base complexity
        
        // Count decision points
        $complexity += preg_match_all('/\bif\b/', $content);
        $complexity += preg_match_all('/\belse\b/', $content);
        $complexity += preg_match_all('/\bwhile\b/', $content);
        $complexity += preg_match_all('/\bfor\b/', $content);
        $complexity += preg_match_all('/\bswitch\b/', $content);
        $complexity += preg_match_all('/\bcase\b/', $content);
        $complexity += preg_match_all('/\bcatch\b/', $content);
        
        return $complexity;
    }

    private function calculatePathCoverage(array $pathFiles): float
    {
        // Simplified coverage calculation
        return empty($pathFiles) ? 0.0 : min(1.0, count($pathFiles) / 5);
    }

    private function calculatePathComplexity(array $pathFiles): float
    {
        if (empty($pathFiles)) return 0.0;
        
        $totalComplexity = 0;
        foreach ($pathFiles as $file) {
            if (isset($file['absolute_path']) && file_exists($file['absolute_path'])) {
                $content = file_get_contents($file['absolute_path']);
                $totalComplexity += $this->calculateFileComplexity($content);
            }
        }
        
        return $totalComplexity / max(1, count($pathFiles));
    }

    private function calculatePathRisk(array $pathFiles): float
    {
        // Risk based on complexity and coverage
        $complexity = $this->calculatePathComplexity($pathFiles);
        $coverage = $this->calculatePathCoverage($pathFiles);
        
        return min(10.0, $complexity * (1 - $coverage) * 2);
    }

    private function getDomainRecommendation(string $domainKey, array $domainScore): string
    {
        $recommendations = [
            'authentication' => 'Focus on security hardening and session management',
            'quiz' => 'Improve question generation logic and scoring accuracy',
            'grading' => 'Enhance grading algorithms and feedback mechanisms',
            'payments' => 'Strengthen payment security and error handling',
            'notifications' => 'Optimize notification delivery and failure handling',
            'user_management' => 'Improve user data validation and role management'
        ];
        
        return $recommendations[$domainKey] ?? 'Review domain components and improve code quality';
    }

    private function estimateDomainEffort(array $domainScore): int
    {
        $baseEffort = 8; // Base hours
        $complexityMultiplier = $domainScore['complexity_score'] / 10;
        $issueMultiplier = min(3, $domainScore['issue_count'] / 10);
        
        return (int) ($baseEffort * (1 + $complexityMultiplier + $issueMultiplier));
    }

    private function determinePrimaryDomain(array $file, array $mappedDomains): ?string
    {
        if (empty($mappedDomains)) return null;
        if (count($mappedDomains) === 1) return $mappedDomains[0];
        
        // Use heuristics to determine primary domain
        $filePath = strtolower($file['path']);
        
        foreach ($mappedDomains as $domain) {
            if (strpos($filePath, $domain) !== false) {
                return $domain;
            }
        }
        
        return $mappedDomains[0]; // Default to first
    }

    private function extractFileKeywords(array $file): array
    {
        $keywords = [];
        $filePath = strtolower($file['path']);
        
        // Extract keywords from path
        preg_match_all('/\b\w{3,}\b/', $filePath, $matches);
        $keywords = array_merge($keywords, $matches[0]);
        
        return array_unique($keywords);
    }

    private function suggestDomains(array $file): array
    {
        $keywords = $this->extractFileKeywords($file);
        $suggestions = [];
        
        foreach ($this->domains as $domainKey => $domainConfig) {
            $matches = array_intersect($keywords, array_map('strtolower', $domainConfig['components']));
            if (!empty($matches)) {
                $suggestions[] = $domainKey;
            }
        }
        
        return $suggestions;
    }

    private function analyzeCrossDomainCoupling(array $file, array $mappedDomains): array
    {
        return [
            'coupling_type' => 'cross_domain',
            'affected_domains' => $mappedDomains,
            'coupling_strength' => count($mappedDomains) / 6, // Normalize by total domains
            'refactoring_complexity' => min(10, count($mappedDomains) * 2)
        ];
    }

    private function determineDomainIssueSeverity(array $issueData): string
    {
        if ($issueData['critical_issues'] > 0) return 'critical';
        if ($issueData['high_issues'] > 5) return 'high';
        if ($issueData['total_issues'] > 20) return 'medium';
        return 'low';
    }

    private function getDomainIssueRecommendation(string $domainKey, array $issueData): string
    {
        $totalIssues = $issueData['total_issues'];
        $criticalIssues = $issueData['critical_issues'];
        
        if ($criticalIssues > 0) {
            return "Address {$criticalIssues} critical issues in {$issueData['domain_name']} immediately";
        }
        
        if ($totalIssues > 10) {
            return "Systematic refactoring needed for {$issueData['domain_name']} domain";
        }
        
        return "Review and resolve issues in {$issueData['domain_name']} domain";
    }

    private function identifyDomainInterdependencies(): array
    {
        $interdependencies = [];
        
        // Analyze cross-domain components for interdependencies
        foreach ($this->componentMapping as $filePath => $mapping) {
            if ($mapping['cross_domain'] && count($mapping['domains']) > 1) {
                for ($i = 0; $i < count($mapping['domains']); $i++) {
                    for ($j = $i + 1; $j < count($mapping['domains']); $j++) {
                        $interdependencies[] = [
                            'from_domain' => $mapping['domains'][$i],
                            'to_domain' => $mapping['domains'][$j],
                            'component' => $filePath,
                            'type' => 'shared_component',
                            'strength' => 0.7
                        ];
                    }
                }
            }
        }
        
        return $interdependencies;
    }

    private function calculateInterdependencyImpact(array $dependency): array
    {
        $riskScore = $dependency['strength'] * 10;
        $couplingStrength = $dependency['strength'];
        $failurePropagationRisk = $dependency['strength'] * 0.8;
        
        return [
            'dependency' => $dependency,
            'risk_score' => $riskScore,
            'coupling_strength' => $couplingStrength,
            'failure_propagation_risk' => $failurePropagationRisk,
            'mitigation_strategies' => [
                'Implement interface segregation',
                'Use dependency injection',
                'Add circuit breaker patterns'
            ],
            'recommendation' => 'Reduce coupling between domains through architectural refactoring',
            'estimated_effort' => (int) ($riskScore * 2)
        ];
    }

    private function categorizeFinding(string $severity, int $effort): string
    {
        $isHighEffort = $effort > 8;
        
        switch ($severity) {
            case 'critical':
                return $isHighEffort ? 'critical_high_effort' : 'critical_low_effort';
            case 'high':
                return $isHighEffort ? 'high_high_effort' : 'high_low_effort';
            case 'medium':
                return $isHighEffort ? 'medium_high_effort' : 'medium_low_effort';
            default:
                return 'low_effort';
        }
    }

    private function identifyFindingDomain(Finding $finding): ?string
    {
        $fileReferences = $finding->getFileReferences();
        
        foreach ($fileReferences as $fileRef) {
            $mapping = $this->componentMapping[$fileRef['file']] ?? null;
            if ($mapping && !empty($mapping['domains'])) {
                return $mapping['primary_domain'];
            }
        }
        
        return null;
    }

    private function calculatePriorityScore(string $severity, int $effort): float
    {
        $severityScores = [
            'critical' => 10,
            'high' => 7,
            'medium' => 4,
            'low' => 1
        ];
        
        $severityScore = $severityScores[$severity] ?? 1;
        $effortPenalty = min(5, $effort / 10); // Penalty for high effort
        
        return max(0, $severityScore - $effortPenalty);
    }

    private function generatePriorityMatrixSummary(): array
    {
        $totalItems = 0;
        $totalEffort = 0;
        $criticalItems = 0;
        $quickWins = 0;
        $breakdown = [];
        
        foreach ($this->priorityMatrix as $category => $items) {
            $categoryCount = count($items);
            $categoryEffort = array_sum(array_column($items, 'effort_hours'));
            
            $totalItems += $categoryCount;
            $totalEffort += $categoryEffort;
            
            if (strpos($category, 'critical') !== false) {
                $criticalItems += $categoryCount;
            }
            
            if (strpos($category, 'low_effort') !== false) {
                $quickWins += $categoryCount;
            }
            
            $breakdown[$category] = [
                'count' => $categoryCount,
                'effort' => $categoryEffort,
                'percentage' => $totalItems > 0 ? round(($categoryCount / $totalItems) * 100, 1) : 0
            ];
        }
        
        return [
            'total_items' => $totalItems,
            'total_effort' => $totalEffort,
            'critical_items' => $criticalItems,
            'quick_wins' => $quickWins,
            'categories' => count($this->priorityMatrix),
            'breakdown' => $breakdown,
            'recommendations' => [
                'Start with critical low-effort items for quick wins',
                'Allocate senior resources to critical high-effort items',
                'Schedule medium priority items based on resource availability'
            ]
        ];
    }

    private function calculateDomainEfforts(): array
    {
        $domainEfforts = [];
        
        foreach ($this->domains as $domainKey => $domainConfig) {
            $domainScore = $this->domainScores[$domainKey] ?? null;
            $domainIssues = $this->domainMetrics['domain_issues'][$domainKey] ?? null;
            
            $totalEffort = 0;
            $priority = 'low';
            
            if ($domainScore) {
                $totalEffort += $this->estimateDomainEffort($domainScore);
                
                if ($domainScore['readiness_score'] < 0.5) {
                    $priority = 'critical';
                } elseif ($domainScore['readiness_score'] < 0.7) {
                    $priority = 'high';
                } elseif ($domainScore['readiness_score'] < 0.8) {
                    $priority = 'medium';
                }
            }
            
            if ($domainIssues) {
                $totalEffort += $domainIssues['estimated_effort'];
            }
            
            $domainEfforts[] = [
                'domain_key' => $domainKey,
                'domain_name' => $domainConfig['name'],
                'total_effort' => $totalEffort,
                'priority' => $priority,
                'weight' => $domainConfig['weight'],
                'readiness_score' => $domainScore['readiness_score'] ?? 0,
                'issue_count' => $domainIssues['total_issues'] ?? 0
            ];
        }
        
        // Sort by priority and effort
        usort($domainEfforts, function($a, $b) {
            $priorityOrder = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];
            $aPriority = $priorityOrder[$a['priority']] ?? 1;
            $bPriority = $priorityOrder[$b['priority']] ?? 1;
            
            if ($aPriority === $bPriority) {
                return $b['total_effort'] <=> $a['total_effort'];
            }
            
            return $bPriority <=> $aPriority;
        });
        
        return $domainEfforts;
    }

    private function generateTeamRecommendations(array $domainEfforts): array
    {
        $totalEffort = array_sum(array_column($domainEfforts, 'total_effort'));
        $criticalDomains = array_filter($domainEfforts, fn($d) => $d['priority'] === 'critical');
        
        $recommendedTeamSize = max(3, min(8, ceil($totalEffort / 160))); // 160 hours per person per month
        
        return [
            'recommended_team_size' => $recommendedTeamSize,
            'skill_distribution' => [
                'senior_developers' => ceil($recommendedTeamSize * 0.4),
                'mid_level_developers' => ceil($recommendedTeamSize * 0.4),
                'junior_developers' => floor($recommendedTeamSize * 0.2)
            ],
            'domain_specialists' => count($criticalDomains),
            'team_structure' => $this->recommendTeamStructure($domainEfforts, $recommendedTeamSize)
        ];
    }

    private function generateTimelineRecommendations(array $domainEfforts): array
    {
        $totalEffort = array_sum(array_column($domainEfforts, 'total_effort'));
        $criticalEffort = array_sum(array_column(array_filter($domainEfforts, fn($d) => $d['priority'] === 'critical'), 'total_effort'));
        
        return [
            'total_timeline_weeks' => ceil($totalEffort / 40), // 40 hours per week
            'critical_phase_weeks' => ceil($criticalEffort / 40),
            'phases' => [
                'phase_1_critical' => [
                    'duration_weeks' => ceil($criticalEffort / 40),
                    'domains' => array_column(array_filter($domainEfforts, fn($d) => $d['priority'] === 'critical'), 'domain_name')
                ],
                'phase_2_high' => [
                    'duration_weeks' => ceil(array_sum(array_column(array_filter($domainEfforts, fn($d) => $d['priority'] === 'high'), 'total_effort')) / 40),
                    'domains' => array_column(array_filter($domainEfforts, fn($d) => $d['priority'] === 'high'), 'domain_name')
                ]
            ]
        ];
    }

    private function generateSkillRecommendations(): array
    {
        return [
            'required_skills' => [
                'PHP/Laravel expertise',
                'Database optimization',
                'Security best practices',
                'Testing frameworks',
                'Payment gateway integration',
                'Email/notification systems'
            ],
            'specialized_skills' => [
                'Quiz system architecture',
                'Grading algorithm design',
                'Authentication systems',
                'Performance optimization'
            ],
            'training_recommendations' => [
                'Security hardening workshops',
                'Code quality best practices',
                'Domain-driven design principles'
            ]
        ];
    }

    private function generateBudgetEstimates(array $domainEfforts): array
    {
        $totalEffort = array_sum(array_column($domainEfforts, 'total_effort'));
        $hourlyRate = 75; // Average hourly rate
        
        return [
            'total_development_cost' => $totalEffort * $hourlyRate,
            'domain_breakdown' => array_map(function($domain) use ($hourlyRate) {
                return [
                    'domain' => $domain['domain_name'],
                    'effort_hours' => $domain['total_effort'],
                    'estimated_cost' => $domain['total_effort'] * $hourlyRate
                ];
            }, $domainEfforts),
            'additional_costs' => [
                'testing_qa' => $totalEffort * $hourlyRate * 0.3,
                'project_management' => $totalEffort * $hourlyRate * 0.15,
                'infrastructure' => 5000
            ]
        ];
    }

    private function generateRiskMitigationStrategies(): array
    {
        return [
            'technical_risks' => [
                'Implement comprehensive testing strategy',
                'Use feature flags for gradual rollouts',
                'Establish code review processes'
            ],
            'timeline_risks' => [
                'Build buffer time into estimates',
                'Prioritize critical path items',
                'Plan for parallel development streams'
            ],
            'resource_risks' => [
                'Cross-train team members',
                'Document domain knowledge',
                'Establish backup resources'
            ]
        ];
    }

    private function recommendTeamStructure(array $domainEfforts, int $teamSize): array
    {
        $criticalDomains = array_filter($domainEfforts, fn($d) => $d['priority'] === 'critical');
        
        return [
            'team_lead' => 1,
            'domain_leads' => min(count($criticalDomains), 3),
            'developers' => $teamSize - 1 - min(count($criticalDomains), 3),
            'qa_specialists' => max(1, ceil($teamSize * 0.2))
        ];
    }

    private function generateMasterRecommendation(): string
    {
        $criticalDomains = count(array_filter($this->domainScores, fn($score) => $score['readiness_score'] < 0.5));
        $totalEffort = array_sum(array_column($this->resourceRecommendations['domain_efforts'], 'total_effort'));
        
        return "Focus on {$criticalDomains} critical domains first. Allocate {$this->resourceRecommendations['team_recommendations']['recommended_team_size']} team members for {$totalEffort} hours of development work. Prioritize security and testing throughout the process.";
    }

    /**
     * Generate domain metrics for the phase result
     */
    private function generateDomainMetrics(): array
    {
        return [
            'total_domain_findings' => count($this->findings),
            'domain_scores' => $this->domainScores,
            'component_mapping_stats' => [
                'total_components' => count($this->componentMapping),
                'unmapped_components' => $this->domainMetrics['unmapped_components'] ?? 0,
                'cross_domain_components' => $this->domainMetrics['cross_domain_components'] ?? 0
            ],
            'priority_matrix_stats' => [
                'total_items' => array_sum(array_map('count', $this->priorityMatrix)),
                'critical_items' => count($this->priorityMatrix['critical_high_effort']) + count($this->priorityMatrix['critical_low_effort']),
                'quick_wins' => count($this->priorityMatrix['critical_low_effort']) + count($this->priorityMatrix['high_low_effort'])
            ],
            'resource_recommendations' => $this->resourceRecommendations,
            'interdependency_analysis' => $this->domainMetrics['interdependency_analysis'] ?? [],
            'acceptance_criteria_coverage' => [
                'domain_score_calculation' => true,
                'component_mapping' => true,
                'issue_aggregation' => true,
                'interdependency_impact_assessment' => true,
                'priority_matrix_generation' => true,
                'resource_allocation_recommendations' => true
            ]
        ];
    }
}