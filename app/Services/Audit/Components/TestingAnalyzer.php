<?php

namespace App\Services\Audit\Components;

use App\Services\Audit\Models\ConcreteFinding;
use App\Services\Audit\Reports\PhaseResult;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Testing Intelligence Analyzer
 * 
 * Comprehensive analysis of test coverage, quality, CI/CD pipeline,
 * and regression protection mechanisms
 */
class TestingAnalyzer
{
    private FileScanner $fileScanner;
    private array $findings = [];
    private int $findingCounter = 1;
    private array $testingMetrics = [];

    public function __construct(FileScanner $fileScanner)
    {
        $this->fileScanner = $fileScanner;
    }

    /**
     * Execute comprehensive testing analysis
     */
    public function executeTestingAnalysis(string $projectPath): PhaseResult
    {
        Log::info('🔍 Starting Testing Intelligence Analysis');
        
        $startTime = microtime(true);
        $this->findings = [];
        
        try {
            // Scan directory first to populate file lists
            $this->fileScanner->scanDirectory($projectPath);
            
            // 1. Test Coverage Calculation
            $this->calculateTestCoverage();
            
            // 2. Test Quality Assessment
            $this->assessTestQuality();
            
            // 3. CI/CD Pipeline Analysis
            $this->analyzeCICDPipeline();
            
            // 4. Flaky Test Detection
            $this->detectFlakyTests();
            
            // 5. Test Data Management Analysis
            $this->analyzeTestDataManagement();
            
            // 6. Mock Strategy Evaluation
            $this->evaluateMockStrategy();
            
            // 7. Test Isolation Verification
            $this->verifyTestIsolation();
            
            $executionTime = microtime(true) - $startTime;
            
            $phaseResult = new PhaseResult(
                'testing_analysis',
                $this->findings,
                $this->generateTestingMetrics(),
                $executionTime
            );
            
            Log::info('✅ Testing Analysis Complete', [
                'findings' => count($this->findings),
                'execution_time' => round($executionTime, 2) . 's'
            ]);
            
            return $phaseResult;    
        
        } catch (\Exception $e) {
            Log::error('❌ Testing Analysis Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Calculate test coverage with CONCRETE EVIDENCE
     */
    public function calculateTestCoverage(): void
    {
        Log::info('🔍 Calculating Test Coverage');
        
        $testFiles = $this->findTestFiles();
        $sourceFiles = $this->findSourceFiles();
        $coverageIssues = 0;
        
        // Analyze test file distribution
        $testTypes = [
            'unit' => 0,
            'feature' => 0,
            'integration' => 0,
            'browser' => 0
        ];
        
        foreach ($testFiles as $testFile) {
            $content = file_get_contents($testFile['absolute_path']);
            
            // Categorize test types
            if (strpos($testFile['path'], 'Unit') !== false) {
                $testTypes['unit']++;
            } elseif (strpos($testFile['path'], 'Feature') !== false) {
                $testTypes['feature']++;
            } elseif (strpos($testFile['path'], 'Integration') !== false) {
                $testTypes['integration']++;
            } elseif (strpos($testFile['path'], 'Browser') !== false) {
                $testTypes['browser']++;
            }
            
            // Check for untested critical paths
            if (preg_match('/class\s+(\w+)Test/', $content, $matches)) {
                $testClassName = $matches[1];
                $sourceClassName = str_replace('Test', '', $testClassName);
                
                // Look for corresponding source file
                $sourceFile = $this->findSourceFile($sourceClassName);
                if (!$sourceFile) {
                    $coverageIssues++;
                    
                    $finding = new ConcreteFinding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'medium',
                        "Test class '{$testClassName}' has no corresponding source class",
                        'testing',
                        'test_coverage'
                    );
                    
                    $finding->addFileReference($testFile['path'], 1, "Test class: {$testClassName}");
                    $finding->addMetric('issue_type', 'Orphaned Test', 'coverage_issue');
                    $finding->addMetric('coverage_impact', 5.0, 'score_out_of_10');
                    
                    $finding->addEvidence('static_analysis', [
                        'test_class' => $testClassName,
                        'expected_source_class' => $sourceClassName,
                        'file_path' => $testFile['path']
                    ], 'Test class without corresponding source class detected');
                    
                    $finding->setRecommendation('Remove orphaned test or create corresponding source class');
                    $finding->setEstimatedHours(1);
                    
                    $this->findings[] = $finding;
                }
            }
        }
        
        // Check for low test coverage indicators
        $totalTests = count($testFiles);
        $totalSources = count($sourceFiles);
        $coverageRatio = $totalSources > 0 ? ($totalTests / $totalSources) : 0;
        
        if ($coverageRatio < 0.5) { // Less than 50% coverage
            $coverageIssues++;
            
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'high',
                "Low test coverage ratio: {$totalTests} tests for {$totalSources} source files",
                'testing',
                'low_coverage'
            );
            
            $finding->addFileReference('tests/', 1, "Coverage ratio: " . round($coverageRatio * 100, 1) . "%");
            $finding->addMetric('test_count', $totalTests, 'files');
            $finding->addMetric('source_count', $totalSources, 'files');
            $finding->addMetric('coverage_ratio', round($coverageRatio * 100, 1), 'percentage');
            $finding->addMetric('coverage_impact', 8.0, 'score_out_of_10');
            
            $finding->addEvidence('static_analysis', [
                'total_tests' => $totalTests,
                'total_sources' => $totalSources,
                'coverage_ratio' => $coverageRatio,
                'test_types' => $testTypes
            ], 'Low test coverage ratio detected');
            
            $finding->setRecommendation('Increase test coverage to at least 70% by adding unit and feature tests');
            $finding->setEstimatedHours(20);
            
            $this->findings[] = $finding;
        }
        
        $this->testingMetrics['test_coverage_issues'] = $coverageIssues;
        $this->testingMetrics['test_types'] = $testTypes;
        $this->testingMetrics['coverage_ratio'] = $coverageRatio;
        
        Log::info("Found {$coverageIssues} test coverage issues");
    }

    /**
     * Assess test quality with CONCRETE EVIDENCE
     */
    public function assessTestQuality(): void
    {
        Log::info('🔍 Assessing Test Quality');
        
        $testFiles = $this->findTestFiles();
        $qualityIssues = 0;
        
        foreach ($testFiles as $testFile) {
            $content = file_get_contents($testFile['absolute_path']);
            $lines = explode("\n", $content);
            
            foreach ($lines as $lineNumber => $line) {
                // Check for poor test practices
                
                // 1. Tests without assertions
                if (preg_match('/public function test\w+\(/', $line)) {
                    $testMethod = trim($line);
                    $methodContent = $this->extractMethodContent($lines, $lineNumber);
                    
                    if (!preg_match('/assert|expect|should/', $methodContent)) {
                        $qualityIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'medium',
                            'Test method without assertions detected',
                            'testing',
                            'test_quality'
                        );
                        
                        $finding->addFileReference($testFile['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('issue_type', 'No Assertions', 'quality_issue');
                        $finding->addMetric('quality_impact', 6.0, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'test_method' => $testMethod,
                            'file_path' => $testFile['path'],
                            'has_assertions' => false
                        ], 'Test method without assertions detected');
                        
                        $finding->setRecommendation('Add proper assertions to verify test expectations');
                        $finding->setEstimatedHours(1);
                        
                        $this->findings[] = $finding;
                    }
                }
                
                // 2. Hard-coded test data
                if (preg_match('/["\']test@example\.com["\']|["\']password123["\']|["\']John Doe["\']/', $line)) {
                    $qualityIssues++;
                    
                    $finding = new ConcreteFinding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'low',
                        'Hard-coded test data detected - use factories instead',
                        'testing',
                        'hardcoded_data'
                    );
                    
                    $finding->addFileReference($testFile['path'], $lineNumber + 1, trim($line));
                    $finding->addMetric('issue_type', 'Hard-coded Data', 'maintainability');
                    $finding->addMetric('maintainability_impact', 4.0, 'score_out_of_10');
                    
                    $finding->addEvidence('static_analysis', [
                        'hardcoded_line' => trim($line),
                        'file_path' => $testFile['path'],
                        'data_type' => 'hardcoded_test_data'
                    ], 'Hard-coded test data detected');
                    
                    $finding->setRecommendation('Use model factories or faker for dynamic test data');
                    $finding->setEstimatedHours(1);
                    
                    $this->findings[] = $finding;
                }
                
                // 3. Sleep statements in tests
                if (preg_match('/sleep\s*\(|usleep\s*\(/', $line)) {
                    $qualityIssues++;
                    
                    $finding = new ConcreteFinding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'high',
                        'Sleep statement in test - indicates timing dependency',
                        'testing',
                        'timing_dependency'
                    );
                    
                    $finding->addFileReference($testFile['path'], $lineNumber + 1, trim($line));
                    $finding->addMetric('issue_type', 'Timing Dependency', 'reliability');
                    $finding->addMetric('reliability_impact', 7.5, 'score_out_of_10');
                    
                    $finding->addEvidence('static_analysis', [
                        'sleep_line' => trim($line),
                        'file_path' => $testFile['path'],
                        'issue_type' => 'timing_dependency'
                    ], 'Sleep statement in test detected');
                    
                    $finding->setRecommendation('Replace sleep with proper event waiting or mocking');
                    $finding->setEstimatedHours(2);
                    
                    $this->findings[] = $finding;
                }
            }
        }
        
        $this->testingMetrics['test_quality_issues'] = $qualityIssues;
        Log::info("Found {$qualityIssues} test quality issues");
    }

    /**
     * Analyze CI/CD pipeline with CONCRETE EVIDENCE
     */
    public function analyzeCICDPipeline(): void
    {
        Log::info('🔍 Analyzing CI/CD Pipeline');
        
        $cicdIssues = 0;
        $cicdFiles = [];
        
        // Check for CI/CD configuration files
        $possibleCIFiles = [
            '.github/workflows',
            '.gitlab-ci.yml',
            'Jenkinsfile',
            '.travis.yml',
            'azure-pipelines.yml',
            '.circleci/config.yml'
        ];
        
        foreach ($possibleCIFiles as $ciFile) {
            $fullPath = base_path($ciFile);
            if (file_exists($fullPath)) {
                $cicdFiles[] = $ciFile;
            }
        }
        
        if (empty($cicdFiles)) {
            $cicdIssues++;
            
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'high',
                'No CI/CD pipeline configuration found',
                'testing',
                'missing_cicd'
            );
            
            $finding->addFileReference('/', 1, 'No CI/CD files detected');
            $finding->addMetric('issue_type', 'Missing CI/CD', 'deployment_risk');
            $finding->addMetric('deployment_risk', 8.0, 'score_out_of_10');
            
            $finding->addEvidence('static_analysis', [
                'checked_files' => $possibleCIFiles,
                'found_files' => [],
                'cicd_present' => false
            ], 'No CI/CD pipeline configuration detected');
            
            $finding->setRecommendation('Implement CI/CD pipeline with automated testing and deployment');
            $finding->setEstimatedHours(8);
            
            $this->findings[] = $finding;
        } else {
            // Analyze existing CI/CD files
            foreach ($cicdFiles as $ciFile) {
                $fullPath = base_path($ciFile);
                
                if (is_file($fullPath)) {
                    $content = file_get_contents($fullPath);
                    
                    // Check for test execution in CI
                    if (!preg_match('/phpunit|pest|test|spec/', $content)) {
                        $cicdIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'medium',
                            "CI/CD pipeline missing test execution: {$ciFile}",
                            'testing',
                            'cicd_no_tests'
                        );
                        
                        $finding->addFileReference($ciFile, 1, 'No test execution found');
                        $finding->addMetric('issue_type', 'No Test Execution', 'quality_gate');
                        $finding->addMetric('quality_impact', 6.5, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'cicd_file' => $ciFile,
                            'has_test_execution' => false,
                            'content_preview' => substr($content, 0, 200)
                        ], 'CI/CD pipeline without test execution detected');
                        
                        $finding->setRecommendation('Add test execution step to CI/CD pipeline');
                        $finding->setEstimatedHours(2);
                        
                        $this->findings[] = $finding;
                    }
                }
            }
        }
        
        $this->testingMetrics['cicd_issues'] = $cicdIssues;
        $this->testingMetrics['cicd_files'] = $cicdFiles;
        
        Log::info("Found {$cicdIssues} CI/CD pipeline issues");
    }

    /**
     * Detect flaky tests with CONCRETE EVIDENCE
     */
    public function detectFlakyTests(): void
    {
        Log::info('🔍 Detecting Flaky Tests');
        
        $testFiles = $this->findTestFiles();
        $flakyIssues = 0;
        
        foreach ($testFiles as $testFile) {
            $content = file_get_contents($testFile['absolute_path']);
            $lines = explode("\n", $content);
            
            foreach ($lines as $lineNumber => $line) {
                // Check for flaky test indicators
                
                // 1. Random values without seeding
                if (preg_match('/rand\s*\(|random|shuffle/', $line) && !preg_match('/seed|srand/', $content)) {
                    $flakyIssues++;
                    
                    $finding = new ConcreteFinding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'medium',
                        'Unseeded random values in test - potential flaky test',
                        'testing',
                        'flaky_test'
                    );
                    
                    $finding->addFileReference($testFile['path'], $lineNumber + 1, trim($line));
                    $finding->addMetric('issue_type', 'Unseeded Random', 'reliability');
                    $finding->addMetric('flakiness_risk', 6.0, 'score_out_of_10');
                    
                    $finding->addEvidence('static_analysis', [
                        'random_line' => trim($line),
                        'file_path' => $testFile['path'],
                        'has_seeding' => false
                    ], 'Unseeded random values in test detected');
                    
                    $finding->setRecommendation('Add proper seeding or use deterministic test data');
                    $finding->setEstimatedHours(1);
                    
                    $this->findings[] = $finding;
                }
                
                // 2. Time-dependent tests
                if (preg_match('/now\(\)|time\(\)|date\(|Carbon::/', $line)) {
                    $contextLines = array_slice($lines, max(0, $lineNumber - 3), 7);
                    $context = implode("\n", $contextLines);
                    
                    if (!preg_match('/freeze|travel|mock|fake/', $context)) {
                        $flakyIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'medium',
                            'Time-dependent test without time mocking',
                            'testing',
                            'time_dependent'
                        );
                        
                        $finding->addFileReference($testFile['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('issue_type', 'Time Dependency', 'reliability');
                        $finding->addMetric('flakiness_risk', 7.0, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'time_line' => trim($line),
                            'file_path' => $testFile['path'],
                            'has_time_mocking' => false
                        ], 'Time-dependent test without mocking detected');
                        
                        $finding->setRecommendation('Use Carbon::setTestNow() or similar time mocking');
                        $finding->setEstimatedHours(1);
                        
                        $this->findings[] = $finding;
                    }
                }
            }
        }
        
        $this->testingMetrics['flaky_test_issues'] = $flakyIssues;
        Log::info("Found {$flakyIssues} potential flaky test issues");
    }
    /*
*
     * Analyze test data management with CONCRETE EVIDENCE
     */
    public function analyzeTestDataManagement(): void
    {
        Log::info('🔍 Analyzing Test Data Management');
        
        $dataIssues = 0;
        
        // Check for factory files
        $factoryPath = base_path('database/factories');
        $factoryFiles = [];
        
        if (is_dir($factoryPath)) {
            $factoryFiles = File::allFiles($factoryPath);
        }
        
        // Check for seeder files
        $seederPath = base_path('database/seeders');
        $seederFiles = [];
        
        if (is_dir($seederPath)) {
            $seederFiles = File::allFiles($seederPath);
        }
        
        $testFiles = $this->findTestFiles();
        
        foreach ($testFiles as $testFile) {
            $content = file_get_contents($testFile['absolute_path']);
            
            // Check for direct model creation without factories
            if (preg_match_all('/(\w+)::create\s*\(/', $content, $matches)) {
                foreach ($matches[1] as $modelName) {
                    // Check if corresponding factory exists
                    $factoryExists = false;
                    foreach ($factoryFiles as $factoryFile) {
                        if (strpos($factoryFile->getFilename(), $modelName) !== false) {
                            $factoryExists = true;
                            break;
                        }
                    }
                    
                    if (!$factoryExists && !in_array($modelName, ['User', 'Model'])) {
                        $dataIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'medium',
                            "Model creation without factory: {$modelName}",
                            'testing',
                            'no_factory'
                        );
                        
                        $finding->addFileReference($testFile['path'], 1, "{$modelName}::create() usage");
                        $finding->addMetric('model_name', $modelName, 'model');
                        $finding->addMetric('has_factory', false, 'boolean');
                        $finding->addMetric('maintainability_impact', 5.0, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'model_name' => $modelName,
                            'file_path' => $testFile['path'],
                            'factory_exists' => false,
                            'creation_method' => 'direct'
                        ], 'Model creation without factory detected');
                        
                        $finding->setRecommendation("Create factory for {$modelName} model to improve test maintainability");
                        $finding->setEstimatedHours(2);
                        
                        $this->findings[] = $finding;
                    }
                }
            }
            
            // Check for database truncation in tests
            if (preg_match('/truncate|delete.*from|drop.*table/i', $content)) {
                $dataIssues++;
                
                $finding = new ConcreteFinding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    'high',
                    'Direct database manipulation in test - use transactions instead',
                    'testing',
                    'database_manipulation'
                );
                
                $finding->addFileReference($testFile['path'], 1, 'Direct database manipulation');
                $finding->addMetric('issue_type', 'Database Manipulation', 'isolation');
                $finding->addMetric('isolation_impact', 7.5, 'score_out_of_10');
                
                $finding->addEvidence('static_analysis', [
                    'file_path' => $testFile['path'],
                    'manipulation_type' => 'direct_sql',
                    'isolation_risk' => true
                ], 'Direct database manipulation in test detected');
                
                $finding->setRecommendation('Use database transactions or RefreshDatabase trait');
                $finding->setEstimatedHours(1);
                
                $this->findings[] = $finding;
            }
        }
        
        $this->testingMetrics['test_data_issues'] = $dataIssues;
        $this->testingMetrics['factory_count'] = count($factoryFiles);
        $this->testingMetrics['seeder_count'] = count($seederFiles);
        
        Log::info("Found {$dataIssues} test data management issues");
    }

    /**
     * Evaluate mock strategy with CONCRETE EVIDENCE
     */
    public function evaluateMockStrategy(): void
    {
        Log::info('🔍 Evaluating Mock Strategy');
        
        $testFiles = $this->findTestFiles();
        $mockIssues = 0;
        
        foreach ($testFiles as $testFile) {
            $content = file_get_contents($testFile['absolute_path']);
            $lines = explode("\n", $content);
            
            foreach ($lines as $lineNumber => $line) {
                // Check for external service calls without mocking
                if (preg_match('/Http::get|Http::post|curl_exec|file_get_contents.*http/', $line)) {
                    $contextLines = array_slice($lines, max(0, $lineNumber - 5), 10);
                    $context = implode("\n", $contextLines);
                    
                    if (!preg_match('/mock|fake|stub|Http::fake/', $context)) {
                        $mockIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'high',
                            'External HTTP call in test without mocking',
                            'testing',
                            'unmocked_http'
                        );
                        
                        $finding->addFileReference($testFile['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('issue_type', 'Unmocked HTTP', 'reliability');
                        $finding->addMetric('reliability_impact', 8.0, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'http_line' => trim($line),
                            'file_path' => $testFile['path'],
                            'is_mocked' => false
                        ], 'Unmocked HTTP call in test detected');
                        
                        $finding->setRecommendation('Mock external HTTP calls using Http::fake() or similar');
                        $finding->setEstimatedHours(2);
                        
                        $this->findings[] = $finding;
                    }
                }
                
                // Check for database queries in unit tests
                if (preg_match('/DB::|Eloquent|Model::/', $line) && strpos($testFile['path'], 'Unit') !== false) {
                    $contextLines = array_slice($lines, max(0, $lineNumber - 5), 10);
                    $context = implode("\n", $contextLines);
                    
                    if (!preg_match('/mock|fake|stub/', $context)) {
                        $mockIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'medium',
                            'Database access in unit test without mocking',
                            'testing',
                            'unit_test_database'
                        );
                        
                        $finding->addFileReference($testFile['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('issue_type', 'Unit Test Database', 'isolation');
                        $finding->addMetric('isolation_impact', 6.0, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'database_line' => trim($line),
                            'file_path' => $testFile['path'],
                            'test_type' => 'unit',
                            'is_mocked' => false
                        ], 'Database access in unit test without mocking detected');
                        
                        $finding->setRecommendation('Mock database interactions in unit tests or move to feature tests');
                        $finding->setEstimatedHours(2);
                        
                        $this->findings[] = $finding;
                    }
                }
            }
        }
        
        $this->testingMetrics['mock_strategy_issues'] = $mockIssues;
        Log::info("Found {$mockIssues} mock strategy issues");
    }

    /**
     * Verify test isolation with CONCRETE EVIDENCE
     */
    public function verifyTestIsolation(): void
    {
        Log::info('🔍 Verifying Test Isolation');
        
        $testFiles = $this->findTestFiles();
        $isolationIssues = 0;
        
        foreach ($testFiles as $testFile) {
            $content = file_get_contents($testFile['absolute_path']);
            $lines = explode("\n", $content);
            
            // Check for shared state between tests
            $hasStaticVariables = false;
            $hasGlobalState = false;
            
            foreach ($lines as $lineNumber => $line) {
                // Check for static variables
                if (preg_match('/static\s+\$\w+/', $line)) {
                    $hasStaticVariables = true;
                    
                    $isolationIssues++;
                    
                    $finding = new ConcreteFinding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'medium',
                        'Static variable in test class - potential isolation issue',
                        'testing',
                        'static_variable'
                    );
                    
                    $finding->addFileReference($testFile['path'], $lineNumber + 1, trim($line));
                    $finding->addMetric('issue_type', 'Static Variable', 'isolation');
                    $finding->addMetric('isolation_impact', 6.5, 'score_out_of_10');
                    
                    $finding->addEvidence('static_analysis', [
                        'static_line' => trim($line),
                        'file_path' => $testFile['path'],
                        'isolation_risk' => true
                    ], 'Static variable in test class detected');
                    
                    $finding->setRecommendation('Avoid static variables or reset them in setUp/tearDown methods');
                    $finding->setEstimatedHours(1);
                    
                    $this->findings[] = $finding;
                }
                
                // Check for global variable usage
                if (preg_match('/global\s+\$|\$GLOBALS/', $line)) {
                    $hasGlobalState = true;
                    
                    $isolationIssues++;
                    
                    $finding = new ConcreteFinding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'high',
                        'Global variable usage in test - breaks isolation',
                        'testing',
                        'global_variable'
                    );
                    
                    $finding->addFileReference($testFile['path'], $lineNumber + 1, trim($line));
                    $finding->addMetric('issue_type', 'Global Variable', 'isolation');
                    $finding->addMetric('isolation_impact', 8.0, 'score_out_of_10');
                    
                    $finding->addEvidence('static_analysis', [
                        'global_line' => trim($line),
                        'file_path' => $testFile['path'],
                        'isolation_risk' => true
                    ], 'Global variable usage in test detected');
                    
                    $finding->setRecommendation('Eliminate global variable usage and use dependency injection');
                    $finding->setEstimatedHours(3);
                    
                    $this->findings[] = $finding;
                }
                
                // Check for file system operations without cleanup
                if (preg_match('/file_put_contents|fwrite|mkdir/', $line)) {
                    $contextLines = array_slice($lines, max(0, $lineNumber - 10), 20);
                    $context = implode("\n", $contextLines);
                    
                    if (!preg_match('/tearDown|unlink|rmdir|Storage::fake/', $context)) {
                        $isolationIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'medium',
                            'File system operation without cleanup in test',
                            'testing',
                            'filesystem_cleanup'
                        );
                        
                        $finding->addFileReference($testFile['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('issue_type', 'No Cleanup', 'isolation');
                        $finding->addMetric('isolation_impact', 5.5, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'filesystem_line' => trim($line),
                            'file_path' => $testFile['path'],
                            'has_cleanup' => false
                        ], 'File system operation without cleanup detected');
                        
                        $finding->setRecommendation('Add cleanup in tearDown method or use Storage::fake()');
                        $finding->setEstimatedHours(1);
                        
                        $this->findings[] = $finding;
                    }
                }
            }
        }
        
        $this->testingMetrics['test_isolation_issues'] = $isolationIssues;
        Log::info("Found {$isolationIssues} test isolation issues");
    }

    /**
     * Helper methods
     */
    private function findTestFiles(): array
    {
        $testFiles = [];
        $testPath = base_path('tests');
        
        if (is_dir($testPath)) {
            $files = File::allFiles($testPath);
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $testFiles[] = [
                        'path' => str_replace(base_path() . '/', '', $file->getPathname()),
                        'absolute_path' => $file->getPathname(),
                        'filename' => $file->getFilename(),
                        'size' => $file->getSize()
                    ];
                }
            }
        }
        
        return $testFiles;
    }

    private function findSourceFiles(): array
    {
        $sourceFiles = [];
        $appPath = base_path('app');
        
        if (is_dir($appPath)) {
            $files = File::allFiles($appPath);
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $sourceFiles[] = [
                        'path' => str_replace(base_path() . '/', '', $file->getPathname()),
                        'absolute_path' => $file->getPathname(),
                        'filename' => $file->getFilename(),
                        'size' => $file->getSize()
                    ];
                }
            }
        }
        
        return $sourceFiles;
    }

    private function findSourceFile(string $className): ?array
    {
        $sourceFiles = $this->findSourceFiles();
        
        foreach ($sourceFiles as $file) {
            if (strpos($file['filename'], $className . '.php') !== false) {
                return $file;
            }
        }
        
        return null;
    }

    private function extractMethodContent(array $lines, int $startLine): string
    {
        $content = '';
        $braceCount = 0;
        $inMethod = false;
        
        for ($i = $startLine; $i < count($lines); $i++) {
            $line = $lines[$i];
            
            if (strpos($line, '{') !== false) {
                $braceCount += substr_count($line, '{');
                $inMethod = true;
            }
            
            if ($inMethod) {
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

    /**
     * Generate testing metrics
     */
    private function generateTestingMetrics(): array
    {
        return [
            'total_testing_findings' => count($this->findings),
            'test_coverage_issues' => $this->testingMetrics['test_coverage_issues'] ?? 0,
            'test_quality_issues' => $this->testingMetrics['test_quality_issues'] ?? 0,
            'cicd_issues' => $this->testingMetrics['cicd_issues'] ?? 0,
            'flaky_test_issues' => $this->testingMetrics['flaky_test_issues'] ?? 0,
            'test_data_issues' => $this->testingMetrics['test_data_issues'] ?? 0,
            'mock_strategy_issues' => $this->testingMetrics['mock_strategy_issues'] ?? 0,
            'test_isolation_issues' => $this->testingMetrics['test_isolation_issues'] ?? 0,
            'coverage_ratio' => $this->testingMetrics['coverage_ratio'] ?? 0,
            'test_types' => $this->testingMetrics['test_types'] ?? [],
            'factory_count' => $this->testingMetrics['factory_count'] ?? 0,
            'seeder_count' => $this->testingMetrics['seeder_count'] ?? 0,
            'cicd_files' => $this->testingMetrics['cicd_files'] ?? [],
            'critical_testing_issues' => count(array_filter($this->findings, function($finding) {
                return $finding->getSeverity() === 'critical';
            })),
            'high_testing_issues' => count(array_filter($this->findings, function($finding) {
                return $finding->getSeverity() === 'high';
            }))
        ];
    }
}