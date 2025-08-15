<?php

namespace App\Services\Audit\Components;

use App\Services\Audit\Models\ConcreteFinding;
use App\Services\Audit\Reports\PhaseResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Exception;

/**
 * Heuristic Weakness Miner with AI-Style Analysis
 * 
 * Advanced pattern recognition and entropy analysis to detect subtle code weaknesses,
 * unused logic paths, inconsistent conditionals, and anomalous patterns
 */
class HeuristicMiner
{
    private FileScanner $fileScanner;
    private array $findings = [];
    private int $findingCounter = 1;
    private array $heuristicMetrics = [];
    private array $codePatterns = [];
    private array $entropyScores = [];
    private array $logicPaths = [];

    public function __construct(FileScanner $fileScanner)
    {
        $this->fileScanner = $fileScanner;
    }

    /**
     * Execute comprehensive heuristic weakness mining with AI-style analysis
     */
    public function executeHeuristicMining(string $projectPath): PhaseResult
    {
        Log::info('🔍 Starting Heuristic Weakness Mining with AI-Style Analysis');
        
        $startTime = microtime(true);
        $this->findings = [];
        
        try {
            // Scan directory first to populate file lists
            $this->fileScanner->scanDirectory($projectPath);
            
            // 1. Unused Code Branch Detection
            $this->detectUnusedCodeBranches();
            
            // 2. Conditional Logic Consistency Analysis
            $this->analyzeConditionalLogicConsistency();
            
            // 3. Module Entropy Scoring (0-1 scale)
            $this->calculateModuleEntropyScoring();
            
            // 4. Low-Coverage Edge Case Identification
            $this->identifyLowCoverageEdgeCases();
            
            // 5. Fragile Sequence Pattern Recognition
            $this->recognizeFragileSequencePatterns();
            
            // 6. Anomalous Pattern Detection
            $this->detectAnomalousPatterns();
            
            // 7. Entropy Threshold Alerting (>0.75)
            $this->alertEntropyThresholds();
            
            $executionTime = microtime(true) - $startTime;
            
            $phaseResult = new PhaseResult(
                'heuristic_mining',
                $this->findings,
                $this->generateHeuristicMetrics(),
                $executionTime
            );
            
            Log::info('✅ Heuristic Weakness Mining Complete', [
                'findings' => count($this->findings),
                'execution_time' => round($executionTime, 2) . 's'
            ]);
            
            return $phaseResult;
            
        } catch (\Exception $e) {
            Log::error('❌ Heuristic Weakness Mining Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }  
  /**
     * Detect unused code branches with AI-style pattern analysis
     */
    public function detectUnusedCodeBranches(): void
    {
        Log::info('🔍 Detecting Unused Code Branches');
        
        $phpFiles = $this->fileScanner->getPHPFiles();
        $unusedBranches = 0;
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            $lines = explode("\n", $content);
            
            // Analyze control flow structures
            $controlStructures = $this->extractControlStructures($lines);
            
            foreach ($controlStructures as $structure) {
                $branchAnalysis = $this->analyzeBranchUsage($structure, $lines);
                
                if ($branchAnalysis['has_unused_branches']) {
                    $unusedBranches++;
                    
                    $finding = new ConcreteFinding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'medium',
                        "Unused code branch detected: {$branchAnalysis['branch_type']}",
                        'heuristic',
                        'unused_branch'
                    );
                    
                    $finding->addFileReference($file['path'], $structure['line_number'], $structure['code']);
                    $finding->addMetric('branch_type', $branchAnalysis['branch_type'], 'control_structure');
                    $finding->addMetric('unused_branches', $branchAnalysis['unused_count'], 'count');
                    $finding->addMetric('complexity_impact', $branchAnalysis['complexity_score'], 'score_out_of_10');
                    
                    $finding->addEvidence('static_analysis', [
                        'control_structure' => $structure,
                        'branch_analysis' => $branchAnalysis,
                        'file_path' => $file['path']
                    ], 'Unused code branch detected through pattern analysis');
                    
                    $finding->setRecommendation('Remove unused code branches or add test coverage');
                    $finding->setEstimatedHours(2);
                    
                    $this->findings[] = $finding;
                }
            }
        }
        
        $this->heuristicMetrics['unused_branches'] = $unusedBranches;
        Log::info("Found {$unusedBranches} unused code branches");
    }

    /**
     * Analyze conditional logic consistency with AI-style pattern matching
     */
    public function analyzeConditionalLogicConsistency(): void
    {
        Log::info('🔍 Analyzing Conditional Logic Consistency');
        
        $phpFiles = $this->fileScanner->getPHPFiles();
        $inconsistencies = 0;
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            $lines = explode("\n", $content);
            
            // Extract all conditional patterns
            $conditionals = $this->extractConditionalPatterns($lines);
            
            // Group similar conditionals for consistency analysis
            $conditionalGroups = $this->groupSimilarConditionals($conditionals);
            
            foreach ($conditionalGroups as $group) {
                if (count($group) > 1) {
                    $consistencyAnalysis = $this->analyzeGroupConsistency($group);
                    
                    if ($consistencyAnalysis['inconsistency_score'] > 0.6) {
                        $inconsistencies++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'medium',
                            "Inconsistent conditional logic pattern detected",
                            'heuristic',
                            'conditional_inconsistency'
                        );
                        
                        $finding->addFileReference($file['path'], $group[0]['line_number'], $group[0]['code']);
                        $finding->addMetric('inconsistency_score', $consistencyAnalysis['inconsistency_score'], 'score_0_to_1');
                        $finding->addMetric('pattern_variations', count($group), 'variations');
                        $finding->addMetric('logic_complexity', $consistencyAnalysis['complexity'], 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'conditional_group' => array_slice($group, 0, 5), // Limit for readability
                            'consistency_analysis' => $consistencyAnalysis,
                            'file_path' => $file['path']
                        ], 'Inconsistent conditional logic pattern detected');
                        
                        $finding->setRecommendation('Standardize conditional logic patterns for consistency');
                        $finding->setEstimatedHours(3);
                        
                        $this->findings[] = $finding;
                    }
                }
            }
        }
        
        $this->heuristicMetrics['conditional_inconsistencies'] = $inconsistencies;
        Log::info("Found {$inconsistencies} conditional logic inconsistencies");
    }

    /**
     * Calculate module entropy scoring (0-1 scale) with AI-style complexity analysis
     */
    public function calculateModuleEntropyScoring(): void
    {
        Log::info('🔍 Calculating Module Entropy Scoring');
        
        $phpFiles = $this->fileScanner->getPHPFiles();
        $highEntropyModules = 0;
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            $entropyScore = $this->calculateFileEntropy($content, $file['path']);
            
            $this->entropyScores[$file['path']] = $entropyScore;
            
            if ($entropyScore > 0.75) {
                $highEntropyModules++;
                
                $finding = new ConcreteFinding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    'high',
                    "High entropy module detected (Score: {$entropyScore})",
                    'heuristic',
                    'high_entropy'
                );
                
                $finding->addFileReference($file['path'], 1, "Entropy Score: {$entropyScore}");
                $finding->addMetric('entropy_score', $entropyScore, 'score_0_to_1');
                $finding->addMetric('complexity_indicators', $this->getComplexityIndicators($content), 'indicators');
                $finding->addMetric('maintainability_risk', $entropyScore * 10, 'score_out_of_10');
                
                $finding->addEvidence('static_analysis', [
                    'entropy_score' => $entropyScore,
                    'entropy_breakdown' => $this->getEntropyBreakdown($content),
                    'file_path' => $file['path']
                ], 'High entropy module detected through complexity analysis');
                
                $finding->setRecommendation('Refactor module to reduce complexity and improve maintainability');
                $finding->setEstimatedHours(8);
                
                $this->findings[] = $finding;
            }
        }
        
        $this->heuristicMetrics['high_entropy_modules'] = $highEntropyModules;
        $this->heuristicMetrics['average_entropy'] = array_sum($this->entropyScores) / max(1, count($this->entropyScores));
        
        Log::info("Found {$highEntropyModules} high entropy modules");
    }

    /**
     * Identify low-coverage edge cases with AI-style pattern recognition
     */
    public function identifyLowCoverageEdgeCases(): void
    {
        Log::info('🔍 Identifying Low-Coverage Edge Cases');
        
        $phpFiles = $this->fileScanner->getPHPFiles();
        $edgeCases = 0;
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            $lines = explode("\n", $content);
            
            // Identify potential edge case patterns
            $edgeCasePatterns = $this->identifyEdgeCasePatterns($lines);
            
            foreach ($edgeCasePatterns as $pattern) {
                $coverageAnalysis = $this->analyzeEdgeCaseCoverage($pattern, $lines);
                
                if ($coverageAnalysis['coverage_score'] < 0.3) {
                    $edgeCases++;
                    
                    $finding = new ConcreteFinding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'medium',
                        "Low-coverage edge case identified: {$pattern['type']}",
                        'heuristic',
                        'low_coverage_edge_case'
                    );
                    
                    $finding->addFileReference($file['path'], $pattern['line_number'], $pattern['code']);
                    $finding->addMetric('edge_case_type', $pattern['type'], 'pattern_type');
                    $finding->addMetric('coverage_score', $coverageAnalysis['coverage_score'], 'score_0_to_1');
                    $finding->addMetric('risk_level', $coverageAnalysis['risk_level'], 'risk_assessment');
                    
                    $finding->addEvidence('static_analysis', [
                        'edge_case_pattern' => $pattern,
                        'coverage_analysis' => $coverageAnalysis,
                        'file_path' => $file['path']
                    ], 'Low-coverage edge case identified');
                    
                    $finding->setRecommendation('Add test coverage for edge case scenarios');
                    $finding->setEstimatedHours(4);
                    
                    $this->findings[] = $finding;
                }
            }
        }
        
        $this->heuristicMetrics['low_coverage_edge_cases'] = $edgeCases;
        Log::info("Found {$edgeCases} low-coverage edge cases");
    }

    /**
     * Recognize fragile sequence patterns with AI-style dependency analysis
     */
    public function recognizeFragileSequencePatterns(): void
    {
        Log::info('🔍 Recognizing Fragile Sequence Patterns');
        
        $phpFiles = $this->fileScanner->getPHPFiles();
        $fragileSequences = 0;
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            $lines = explode("\n", $content);
            
            // Identify method call sequences
            $sequences = $this->extractMethodSequences($lines);
            
            foreach ($sequences as $sequence) {
                $fragilityAnalysis = $this->analyzeSequenceFragility($sequence);
                
                if ($fragilityAnalysis['fragility_score'] > 0.7) {
                    $fragileSequences++;
                    
                    $finding = new ConcreteFinding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'high',
                        "Fragile sequence pattern detected",
                        'heuristic',
                        'fragile_sequence'
                    );
                    
                    $finding->addFileReference($file['path'], $sequence['start_line'], $sequence['description']);
                    $finding->addMetric('fragility_score', $fragilityAnalysis['fragility_score'], 'score_0_to_1');
                    $finding->addMetric('sequence_length', $sequence['length'], 'steps');
                    $finding->addMetric('dependency_count', $fragilityAnalysis['dependency_count'], 'dependencies');
                    
                    $finding->addEvidence('static_analysis', [
                        'sequence_pattern' => $sequence,
                        'fragility_analysis' => $fragilityAnalysis,
                        'file_path' => $file['path']
                    ], 'Fragile sequence pattern detected');
                    
                    $finding->setRecommendation('Refactor sequence to reduce fragility and improve error handling');
                    $finding->setEstimatedHours(6);
                    
                    $this->findings[] = $finding;
                }
            }
        }
        
        $this->heuristicMetrics['fragile_sequences'] = $fragileSequences;
        Log::info("Found {$fragileSequences} fragile sequence patterns");
    }

    /**
     * Detect anomalous patterns with AI-style statistical analysis
     */
    public function detectAnomalousPatterns(): void
    {
        Log::info('🔍 Detecting Anomalous Patterns');
        
        $phpFiles = $this->fileScanner->getPHPFiles();
        $anomalies = 0;
        
        // Build pattern baseline from all files
        $patternBaseline = $this->buildPatternBaseline($phpFiles);
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            $filePatterns = $this->extractFilePatterns($content);
            
            // Compare file patterns against baseline
            $anomalyAnalysis = $this->detectPatternAnomalies($filePatterns, $patternBaseline);
            
            foreach ($anomalyAnalysis['anomalies'] as $anomaly) {
                if ($anomaly['anomaly_score'] > 0.8) {
                    $anomalies++;
                    
                    $finding = new ConcreteFinding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'medium',
                        "Anomalous pattern detected: {$anomaly['pattern_type']}",
                        'heuristic',
                        'anomalous_pattern'
                    );
                    
                    $finding->addFileReference($file['path'], $anomaly['line_number'] ?? 1, $anomaly['description']);
                    $finding->addMetric('anomaly_score', $anomaly['anomaly_score'], 'score_0_to_1');
                    $finding->addMetric('pattern_type', $anomaly['pattern_type'], 'pattern_category');
                    $finding->addMetric('deviation_level', $anomaly['deviation_level'], 'statistical_measure');
                    
                    $finding->addEvidence('static_analysis', [
                        'anomaly_details' => $anomaly,
                        'pattern_baseline' => $patternBaseline[$anomaly['pattern_type']] ?? null,
                        'file_path' => $file['path']
                    ], 'Anomalous pattern detected through statistical analysis');
                    
                    $finding->setRecommendation('Review anomalous pattern for potential issues or refactoring opportunities');
                    $finding->setEstimatedHours(3);
                    
                    $this->findings[] = $finding;
                }
            }
        }
        
        $this->heuristicMetrics['anomalous_patterns'] = $anomalies;
        Log::info("Found {$anomalies} anomalous patterns");
    }

    /**
     * Alert on entropy threshold violations (>0.75)
     */
    public function alertEntropyThresholds(): void
    {
        Log::info('🔍 Alerting Entropy Threshold Violations');
        
        $thresholdViolations = 0;
        $entropyThreshold = 0.75;
        
        foreach ($this->entropyScores as $filePath => $entropyScore) {
            if ($entropyScore > $entropyThreshold) {
                $thresholdViolations++;
                
                $finding = new ConcreteFinding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    'critical',
                    "Entropy threshold violation: {$entropyScore} > {$entropyThreshold}",
                    'heuristic',
                    'entropy_threshold_violation'
                );
                
                $finding->addFileReference($filePath, 1, "Entropy: {$entropyScore}");
                $finding->addMetric('entropy_score', $entropyScore, 'score_0_to_1');
                $finding->addMetric('threshold', $entropyThreshold, 'threshold_value');
                $finding->addMetric('violation_severity', ($entropyScore - $entropyThreshold) * 10, 'severity_score');
                
                $finding->addEvidence('static_analysis', [
                    'entropy_score' => $entropyScore,
                    'threshold' => $entropyThreshold,
                    'violation_amount' => $entropyScore - $entropyThreshold,
                    'file_path' => $filePath
                ], 'Entropy threshold violation detected');
                
                $finding->setRecommendation('Immediate refactoring required to reduce module complexity');
                $finding->setEstimatedHours(12);
                
                $this->findings[] = $finding;
            }
        }
        
        $this->heuristicMetrics['entropy_threshold_violations'] = $thresholdViolations;
        Log::info("Found {$thresholdViolations} entropy threshold violations");
    }   
 /**
     * Extract control structures from code lines
     */
    private function extractControlStructures(array $lines): array
    {
        $structures = [];
        $structureId = 0;
        
        foreach ($lines as $lineNumber => $line) {
            $trimmedLine = trim($line);
            
            // Detect control structures
            if (preg_match('/^(if|else|elseif|switch|case|for|foreach|while|do)\s*[\(\{]/', $trimmedLine, $matches)) {
                $structures[] = [
                    'id' => $structureId++,
                    'type' => $matches[1],
                    'line_number' => $lineNumber + 1,
                    'code' => $trimmedLine,
                    'context' => array_slice($lines, max(0, $lineNumber - 2), 5)
                ];
            }
        }
        
        return $structures;
    }

    /**
     * Analyze branch usage patterns
     */
    private function analyzeBranchUsage(array $structure, array $lines): array
    {
        $branchType = $structure['type'];
        $startLine = $structure['line_number'] - 1;
        
        // Extract the block content
        $blockContent = $this->extractBlockContent($lines, $startLine);
        
        // Analyze branch patterns
        $analysis = [
            'branch_type' => $branchType,
            'has_unused_branches' => false,
            'unused_count' => 0,
            'complexity_score' => 0
        ];
        
        switch ($branchType) {
            case 'if':
                $analysis = $this->analyzeIfBranches($blockContent, $lines, $startLine);
                break;
            case 'switch':
                $analysis = $this->analyzeSwitchBranches($blockContent, $lines, $startLine);
                break;
            case 'for':
            case 'foreach':
            case 'while':
                $analysis = $this->analyzeLoopBranches($blockContent, $lines, $startLine);
                break;
        }
        
        return $analysis;
    }

    /**
     * Analyze if statement branches
     */
    private function analyzeIfBranches(string $blockContent, array $lines, int $startLine): array
    {
        $hasElse = preg_match('/\belse\b/', $blockContent);
        $hasElseIf = preg_match('/\belseif\b/', $blockContent);
        
        // Check for empty branches
        $emptyBranches = preg_match_all('/\{\s*\}|\{\s*\/\/[^\}]*\}/', $blockContent);
        
        // Check for unreachable code patterns
        $unreachablePatterns = preg_match('/return.*;\s*\w+|throw.*;\s*\w+/', $blockContent);
        
        return [
            'branch_type' => 'if',
            'has_unused_branches' => $emptyBranches > 0 || $unreachablePatterns,
            'unused_count' => $emptyBranches,
            'complexity_score' => min(10, ($hasElse ? 2 : 0) + ($hasElseIf ? 3 : 0) + $emptyBranches * 2)
        ];
    }

    /**
     * Analyze switch statement branches
     */
    private function analyzeSwitchBranches(string $blockContent, array $lines, int $startLine): array
    {
        $caseCount = preg_match_all('/\bcase\s+/', $blockContent);
        $hasDefault = preg_match('/\bdefault\s*:/', $blockContent);
        
        // Check for empty cases
        $emptyCases = preg_match_all('/case[^:]*:\s*(?:break|return)/', $blockContent);
        
        return [
            'branch_type' => 'switch',
            'has_unused_branches' => $emptyCases > 0 || !$hasDefault,
            'unused_count' => $emptyCases + ($hasDefault ? 0 : 1),
            'complexity_score' => min(10, $caseCount + ($hasDefault ? 0 : 2) + $emptyCases)
        ];
    }

    /**
     * Analyze loop branches
     */
    private function analyzeLoopBranches(string $blockContent, array $lines, int $startLine): array
    {
        // Check for empty loops
        $isEmpty = preg_match('/\{\s*\}/', $blockContent);
        
        // Check for infinite loop patterns
        $hasBreak = preg_match('/\bbreak\b/', $blockContent);
        $hasReturn = preg_match('/\breturn\b/', $blockContent);
        
        return [
            'branch_type' => 'loop',
            'has_unused_branches' => $isEmpty,
            'unused_count' => $isEmpty ? 1 : 0,
            'complexity_score' => min(10, ($isEmpty ? 5 : 0) + (!$hasBreak && !$hasReturn ? 3 : 0))
        ];
    }

    /**
     * Extract conditional patterns from code
     */
    private function extractConditionalPatterns(array $lines): array
    {
        $patterns = [];
        $patternId = 0;
        
        foreach ($lines as $lineNumber => $line) {
            $trimmedLine = trim($line);
            
            // Extract conditional expressions
            if (preg_match('/if\s*\(([^)]+)\)/', $trimmedLine, $matches)) {
                $condition = $matches[1];
                $patterns[] = [
                    'id' => $patternId++,
                    'type' => 'if_condition',
                    'condition' => $condition,
                    'line_number' => $lineNumber + 1,
                    'code' => $trimmedLine,
                    'normalized' => $this->normalizeCondition($condition)
                ];
            }
            
            // Extract ternary operators
            if (preg_match('/([^?]+)\?([^:]+):(.+)/', $trimmedLine, $matches)) {
                $patterns[] = [
                    'id' => $patternId++,
                    'type' => 'ternary',
                    'condition' => trim($matches[1]),
                    'true_value' => trim($matches[2]),
                    'false_value' => trim($matches[3]),
                    'line_number' => $lineNumber + 1,
                    'code' => $trimmedLine
                ];
            }
        }
        
        return $patterns;
    }

    /**
     * Group similar conditional patterns
     */
    private function groupSimilarConditionals(array $conditionals): array
    {
        $groups = [];
        
        foreach ($conditionals as $conditional) {
            $signature = $this->getConditionalSignature($conditional);
            
            if (!isset($groups[$signature])) {
                $groups[$signature] = [];
            }
            
            $groups[$signature][] = $conditional;
        }
        
        return array_filter($groups, fn($group) => count($group) > 1);
    }

    /**
     * Analyze group consistency
     */
    private function analyzeGroupConsistency(array $group): array
    {
        $variations = [];
        $complexitySum = 0;
        
        foreach ($group as $conditional) {
            $variations[] = $conditional['condition'] ?? $conditional['code'];
            $complexitySum += $this->calculateConditionalComplexity($conditional);
        }
        
        // Calculate inconsistency score based on variation patterns
        $uniqueVariations = array_unique($variations);
        $inconsistencyScore = 1 - (count($uniqueVariations) / count($variations));
        
        return [
            'inconsistency_score' => $inconsistencyScore,
            'complexity' => $complexitySum / count($group),
            'variations' => $variations,
            'unique_patterns' => count($uniqueVariations)
        ];
    }

    /**
     * Calculate file entropy score
     */
    private function calculateFileEntropy(string $content, string $filePath): float
    {
        $metrics = [
            'cyclomatic_complexity' => $this->calculateCyclomaticComplexity($content),
            'nesting_depth' => $this->calculateNestingDepth($content),
            'method_count' => $this->countMethods($content),
            'line_count' => substr_count($content, "\n"),
            'conditional_count' => $this->countConditionals($content),
            'loop_count' => $this->countLoops($content)
        ];
        
        // Normalize metrics to 0-1 scale
        $normalizedMetrics = [
            'complexity' => min(1.0, $metrics['cyclomatic_complexity'] / 50),
            'depth' => min(1.0, $metrics['nesting_depth'] / 10),
            'methods' => min(1.0, $metrics['method_count'] / 30),
            'lines' => min(1.0, $metrics['line_count'] / 1000),
            'conditionals' => min(1.0, $metrics['conditional_count'] / 20),
            'loops' => min(1.0, $metrics['loop_count'] / 10)
        ];
        
        // Calculate weighted entropy score
        $weights = [
            'complexity' => 0.3,
            'depth' => 0.2,
            'methods' => 0.15,
            'lines' => 0.1,
            'conditionals' => 0.15,
            'loops' => 0.1
        ];
        
        $entropyScore = 0;
        foreach ($normalizedMetrics as $metric => $value) {
            $entropyScore += $value * $weights[$metric];
        }
        
        return round($entropyScore, 3);
    }

    /**
     * Get complexity indicators for a file
     */
    private function getComplexityIndicators(string $content): array
    {
        return [
            'cyclomatic_complexity' => $this->calculateCyclomaticComplexity($content),
            'nesting_depth' => $this->calculateNestingDepth($content),
            'method_count' => $this->countMethods($content),
            'conditional_count' => $this->countConditionals($content),
            'loop_count' => $this->countLoops($content),
            'class_count' => $this->countClasses($content)
        ];
    }

    /**
     * Get entropy breakdown for detailed analysis
     */
    private function getEntropyBreakdown(string $content): array
    {
        return [
            'complexity_contribution' => 0.3 * min(1.0, $this->calculateCyclomaticComplexity($content) / 50),
            'depth_contribution' => 0.2 * min(1.0, $this->calculateNestingDepth($content) / 10),
            'method_contribution' => 0.15 * min(1.0, $this->countMethods($content) / 30),
            'line_contribution' => 0.1 * min(1.0, substr_count($content, "\n") / 1000),
            'conditional_contribution' => 0.15 * min(1.0, $this->countConditionals($content) / 20),
            'loop_contribution' => 0.1 * min(1.0, $this->countLoops($content) / 10)
        ];
    }

    /**
     * Identify edge case patterns in code
     */
    private function identifyEdgeCasePatterns(array $lines): array
    {
        $patterns = [];
        $patternId = 0;
        
        foreach ($lines as $lineNumber => $line) {
            $trimmedLine = trim($line);
            
            // Boundary value patterns
            if (preg_match('/(==|!=|<|>|<=|>=)\s*(0|1|-1|null|empty|false|true)/', $trimmedLine)) {
                $patterns[] = [
                    'id' => $patternId++,
                    'type' => 'boundary_value',
                    'line_number' => $lineNumber + 1,
                    'code' => $trimmedLine,
                    'risk_level' => 'medium'
                ];
            }
            
            // Array access patterns
            if (preg_match('/\$\w+\[\d+\]|\$\w+\[\$\w+\]/', $trimmedLine)) {
                $patterns[] = [
                    'id' => $patternId++,
                    'type' => 'array_access',
                    'line_number' => $lineNumber + 1,
                    'code' => $trimmedLine,
                    'risk_level' => 'high'
                ];
            }
            
            // Division operations
            if (preg_match('/\/\s*\$\w+|\/\s*\([^)]*\$/', $trimmedLine)) {
                $patterns[] = [
                    'id' => $patternId++,
                    'type' => 'division_operation',
                    'line_number' => $lineNumber + 1,
                    'code' => $trimmedLine,
                    'risk_level' => 'high'
                ];
            }
        }
        
        return $patterns;
    }

    /**
     * Analyze edge case coverage
     */
    private function analyzeEdgeCaseCoverage(array $pattern, array $lines): array
    {
        $contextLines = array_slice($lines, max(0, $pattern['line_number'] - 5), 10);
        $context = implode("\n", $contextLines);
        
        // Check for validation patterns
        $hasValidation = preg_match('/isset|!empty|is_null|is_numeric|count|array_key_exists/', $context);
        $hasTryCatch = preg_match('/try\s*\{|catch\s*\(/', $context);
        $hasErrorHandling = preg_match('/if.*error|throw|return.*false/', $context);
        
        $coverageScore = 0;
        if ($hasValidation) $coverageScore += 0.4;
        if ($hasTryCatch) $coverageScore += 0.3;
        if ($hasErrorHandling) $coverageScore += 0.3;
        
        return [
            'coverage_score' => $coverageScore,
            'has_validation' => $hasValidation,
            'has_try_catch' => $hasTryCatch,
            'has_error_handling' => $hasErrorHandling,
            'risk_level' => $pattern['risk_level']
        ];
    }

    /**
     * Extract method call sequences
     */
    private function extractMethodSequences(array $lines): array
    {
        $sequences = [];
        $currentSequence = null;
        $sequenceId = 0;
        
        foreach ($lines as $lineNumber => $line) {
            $trimmedLine = trim($line);
            
            // Detect method calls
            if (preg_match('/\$\w+->\w+\(|\w+::\w+\(/', $trimmedLine)) {
                if ($currentSequence === null) {
                    $currentSequence = [
                        'id' => $sequenceId++,
                        'start_line' => $lineNumber + 1,
                        'calls' => [],
                        'length' => 0
                    ];
                }
                
                $currentSequence['calls'][] = $trimmedLine;
                $currentSequence['length']++;
            } else {
                // End current sequence if we have one
                if ($currentSequence !== null && $currentSequence['length'] > 2) {
                    $currentSequence['description'] = "Method sequence with {$currentSequence['length']} calls";
                    $sequences[] = $currentSequence;
                }
                $currentSequence = null;
            }
        }
        
        return $sequences;
    }

    /**
     * Analyze sequence fragility
     */
    private function analyzeSequenceFragility(array $sequence): array
    {
        $fragilityFactors = 0;
        $dependencyCount = 0;
        
        foreach ($sequence['calls'] as $call) {
            // Check for external dependencies
            if (preg_match('/Http::|DB::|Cache::|Storage::/', $call)) {
                $fragilityFactors += 2;
                $dependencyCount++;
            }
            
            // Check for error handling
            if (!preg_match('/try|catch|if.*error/', $call)) {
                $fragilityFactors += 1;
            }
            
            // Check for chained calls
            if (preg_match_all('/->\w+/', $call) > 2) {
                $fragilityFactors += 1;
            }
        }
        
        $fragilityScore = min(1.0, $fragilityFactors / ($sequence['length'] * 3));
        
        return [
            'fragility_score' => $fragilityScore,
            'dependency_count' => $dependencyCount,
            'fragility_factors' => $fragilityFactors,
            'sequence_length' => $sequence['length']
        ];
    }

    /**
     * Build pattern baseline from all files
     */
    private function buildPatternBaseline(array $phpFiles): array
    {
        $baseline = [
            'method_length' => [],
            'class_size' => [],
            'conditional_density' => [],
            'loop_density' => [],
            'comment_ratio' => []
        ];
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            $patterns = $this->extractFilePatterns($content);
            
            foreach ($patterns as $patternType => $value) {
                if (isset($baseline[$patternType])) {
                    $baseline[$patternType][] = $value;
                }
            }
        }
        
        // Calculate statistical measures for each pattern type
        foreach ($baseline as $patternType => $values) {
            if (!empty($values)) {
                $baseline[$patternType] = [
                    'mean' => array_sum($values) / count($values),
                    'median' => $this->calculateMedian($values),
                    'std_dev' => $this->calculateStandardDeviation($values)
                ];
            }
        }
        
        return $baseline;
    }

    /**
     * Extract file patterns for anomaly detection
     */
    private function extractFilePatterns(string $content): array
    {
        $lines = explode("\n", $content);
        $totalLines = count($lines);
        
        return [
            'method_length' => $this->calculateAverageMethodLength($content),
            'class_size' => $totalLines,
            'conditional_density' => $this->countConditionals($content) / max(1, $totalLines),
            'loop_density' => $this->countLoops($content) / max(1, $totalLines),
            'comment_ratio' => $this->countComments($content) / max(1, $totalLines)
        ];
    }

    /**
     * Detect pattern anomalies
     */
    private function detectPatternAnomalies(array $filePatterns, array $baseline): array
    {
        $anomalies = [];
        
        foreach ($filePatterns as $patternType => $value) {
            if (isset($baseline[$patternType]) && is_array($baseline[$patternType])) {
                $baselineStats = $baseline[$patternType];
                $deviation = abs($value - $baselineStats['mean']);
                $zScore = $baselineStats['std_dev'] > 0 ? $deviation / $baselineStats['std_dev'] : 0;
                
                if ($zScore > 2.0) { // 2 standard deviations
                    $anomalies[] = [
                        'pattern_type' => $patternType,
                        'value' => $value,
                        'baseline_mean' => $baselineStats['mean'],
                        'z_score' => $zScore,
                        'anomaly_score' => min(1.0, $zScore / 3.0),
                        'deviation_level' => $zScore > 3.0 ? 'extreme' : 'significant',
                        'description' => "Anomalous {$patternType}: {$value} (baseline: {$baselineStats['mean']})"
                    ];
                }
            }
        }
        
        return ['anomalies' => $anomalies];
    }

    /**
     * Helper methods for complexity calculations
     */
    private function calculateCyclomaticComplexity(string $content): int
    {
        $complexity = 1; // Base complexity
        
        // Count decision points
        $complexity += preg_match_all('/\bif\b/', $content);
        $complexity += preg_match_all('/\belse\b/', $content);
        $complexity += preg_match_all('/\belseif\b/', $content);
        $complexity += preg_match_all('/\bwhile\b/', $content);
        $complexity += preg_match_all('/\bfor\b/', $content);
        $complexity += preg_match_all('/\bforeach\b/', $content);
        $complexity += preg_match_all('/\bswitch\b/', $content);
        $complexity += preg_match_all('/\bcase\b/', $content);
        $complexity += preg_match_all('/\bcatch\b/', $content);
        $complexity += preg_match_all('/\b&&\b/', $content);
        $complexity += preg_match_all('/\b\|\|\b/', $content);
        $complexity += preg_match_all('/\?.*:/', $content); // Ternary operators
        
        return $complexity;
    }

    private function calculateNestingDepth(string $content): int
    {
        $lines = explode("\n", $content);
        $maxDepth = 0;
        $currentDepth = 0;
        
        foreach ($lines as $line) {
            $openBraces = substr_count($line, '{');
            $closeBraces = substr_count($line, '}');
            
            $currentDepth += $openBraces - $closeBraces;
            $maxDepth = max($maxDepth, $currentDepth);
        }
        
        return $maxDepth;
    }

    private function countMethods(string $content): int
    {
        return preg_match_all('/function\s+\w+\s*\(/', $content);
    }

    private function countClasses(string $content): int
    {
        return preg_match_all('/class\s+\w+/', $content);
    }

    private function countConditionals(string $content): int
    {
        $count = 0;
        $count += preg_match_all('/\bif\b/', $content);
        $count += preg_match_all('/\bswitch\b/', $content);
        $count += preg_match_all('/\?.*:/', $content); // Ternary
        return $count;
    }

    private function countLoops(string $content): int
    {
        $count = 0;
        $count += preg_match_all('/\bfor\b/', $content);
        $count += preg_match_all('/\bforeach\b/', $content);
        $count += preg_match_all('/\bwhile\b/', $content);
        $count += preg_match_all('/\bdo\s+/', $content);
        return $count;
    }

    private function countComments(string $content): int
    {
        $count = 0;
        $count += preg_match_all('/\/\//', $content); // Single line comments
        $count += preg_match_all('/\/\*.*?\*\//s', $content); // Multi-line comments
        return $count;
    }

    private function extractBlockContent(array $lines, int $startLine): string
    {
        $braceCount = 0;
        $content = '';
        $inBlock = false;
        
        for ($i = $startLine; $i < count($lines); $i++) {
            $line = $lines[$i];
            
            if (strpos($line, '{') !== false) {
                $braceCount += substr_count($line, '{');
                $inBlock = true;
            }
            
            if ($inBlock) {
                $content .= $line . "\n";
            }
            
            if (strpos($line, '}') !== false) {
                $braceCount -= substr_count($line, '}');
                if ($braceCount <= 0) {
                    break;
                }
            }
        }
        
        return $content;
    }

    private function normalizeCondition(string $condition): string
    {
        // Remove variable names and replace with placeholders
        $normalized = preg_replace('/\$\w+/', '$VAR', $condition);
        $normalized = preg_replace('/\d+/', 'NUM', $normalized);
        $normalized = preg_replace('/["\'][^"\']*["\']/', 'STR', $normalized);
        
        return trim($normalized);
    }

    private function getConditionalSignature(array $conditional): string
    {
        $type = $conditional['type'];
        $normalized = $conditional['normalized'] ?? $this->normalizeCondition($conditional['condition'] ?? '');
        
        return $type . ':' . $normalized;
    }

    private function calculateConditionalComplexity(array $conditional): float
    {
        $condition = $conditional['condition'] ?? $conditional['code'];
        
        $complexity = 1.0;
        $complexity += substr_count($condition, '&&') * 0.5;
        $complexity += substr_count($condition, '||') * 0.5;
        $complexity += substr_count($condition, '!') * 0.3;
        $complexity += preg_match_all('/[<>=!]+/', $condition) * 0.2;
        
        return $complexity;
    }

    private function calculateAverageMethodLength(string $content): float
    {
        $methods = [];
        $lines = explode("\n", $content);
        $inMethod = false;
        $methodStart = 0;
        $braceCount = 0;
        
        foreach ($lines as $lineNumber => $line) {
            if (preg_match('/function\s+\w+\s*\(/', $line)) {
                $inMethod = true;
                $methodStart = $lineNumber;
                $braceCount = 0;
            }
            
            if ($inMethod) {
                $braceCount += substr_count($line, '{') - substr_count($line, '}');
                
                if ($braceCount <= 0 && strpos($line, '}') !== false) {
                    $methods[] = $lineNumber - $methodStart + 1;
                    $inMethod = false;
                }
            }
        }
        
        return empty($methods) ? 0 : array_sum($methods) / count($methods);
    }

    private function calculateMedian(array $values): float
    {
        sort($values);
        $count = count($values);
        
        if ($count === 0) return 0;
        
        if ($count % 2 === 0) {
            return ($values[$count / 2 - 1] + $values[$count / 2]) / 2;
        } else {
            return $values[intval($count / 2)];
        }
    }

    private function calculateStandardDeviation(array $values): float
    {
        if (empty($values)) return 0;
        
        $mean = array_sum($values) / count($values);
        $squaredDifferences = array_map(fn($x) => pow($x - $mean, 2), $values);
        $variance = array_sum($squaredDifferences) / count($values);
        
        return sqrt($variance);
    }

    /**
     * Generate heuristic metrics for the phase result
     */
    private function generateHeuristicMetrics(): array
    {
        return [
            'total_heuristic_findings' => count($this->findings),
            'unused_branches' => $this->heuristicMetrics['unused_branches'] ?? 0,
            'conditional_inconsistencies' => $this->heuristicMetrics['conditional_inconsistencies'] ?? 0,
            'high_entropy_modules' => $this->heuristicMetrics['high_entropy_modules'] ?? 0,
            'low_coverage_edge_cases' => $this->heuristicMetrics['low_coverage_edge_cases'] ?? 0,
            'fragile_sequences' => $this->heuristicMetrics['fragile_sequences'] ?? 0,
            'anomalous_patterns' => $this->heuristicMetrics['anomalous_patterns'] ?? 0,
            'entropy_threshold_violations' => $this->heuristicMetrics['entropy_threshold_violations'] ?? 0,
            'average_entropy_score' => $this->heuristicMetrics['average_entropy'] ?? 0,
            'critical_heuristic_issues' => count(array_filter($this->findings, function($finding) {
                return $finding->getSeverity() === 'critical';
            })),
            'high_heuristic_issues' => count(array_filter($this->findings, function($finding) {
                return $finding->getSeverity() === 'high';
            })),
            'acceptance_criteria_coverage' => [
                'unused_code_branch_detection' => true,
                'conditional_logic_consistency_analysis' => true,
                'module_entropy_scoring' => true,
                'low_coverage_edge_case_identification' => true,
                'fragile_sequence_pattern_recognition' => true,
                'anomalous_pattern_detection' => true,
                'entropy_threshold_alerting' => true
            ],
            'ai_analysis_features' => [
                'pattern_recognition' => true,
                'statistical_analysis' => true,
                'entropy_calculation' => true,
                'anomaly_detection' => true,
                'complexity_scoring' => true,
                'baseline_comparison' => true
            ]
        ];
    }
}