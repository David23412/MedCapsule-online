<?php

namespace App\Services\Audit\Components;

use App\Services\Audit\Models\ConcreteFinding;
use App\Services\Audit\Reports\PhaseResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\File;
use Exception;

/**
 * Infrastructure Assessor with LETHAL GOLDEN STANDARD Implementation
 * 
 * Comprehensive infrastructure analysis with fail-safe simulation capabilities
 * Tests system resilience, configuration integrity, and disaster recovery scenarios
 */
class InfrastructureAssessor
{
    private FileScanner $fileScanner;
    private FailSafeSimulator $failSafeSimulator;
    private FailSafeScenarioDocumenter $scenarioDocumenter;
    private array $findings = [];
    private int $findingCounter = 1;
    private array $infrastructureMetrics = [];
    private array $configurationAudit = [];
    private array $systemResources = [];
    private array $scenarioDocumentation = [];

    public function __construct(FileScanner $fileScanner)
    {
        $this->fileScanner = $fileScanner;
        $this->failSafeSimulator = new FailSafeSimulator();
        $this->scenarioDocumenter = new FailSafeScenarioDocumenter($this->failSafeSimulator);
    }

    /**
     * Execute comprehensive infrastructure assessment (alias for orchestrator compatibility)
     */
    public function assessInfrastructure(string $projectPath): PhaseResult
    {
        return $this->executeInfrastructureAssessment($projectPath);
    }

    /**
     * Execute comprehensive infrastructure assessment with LETHAL PRECISION
     */
    public function executeInfrastructureAssessment(string $projectPath): PhaseResult
    {
        Log::info('🔍 Starting LETHAL Infrastructure Assessment');
        
        $startTime = microtime(true);
        $this->findings = [];
        
        try {
            // 1. Environment Configuration Audit
            $this->auditEnvironmentConfiguration($projectPath);
            
            // 2. System Resource Analysis
            $this->analyzeSystemResources();
            
            // 3. Monitoring System Verification
            $this->verifyMonitoringSystem($projectPath);
            
            // 4. Fail-Safe Simulation Testing
            $this->executeFailSafeSimulations();
            
            // 5. Disaster Recovery Testing
            $this->testDisasterRecovery($projectPath);
            
            // 6. Infrastructure Security Assessment
            $this->assessInfrastructureSecurity($projectPath);
            
            $executionTime = microtime(true) - $startTime;
            
            $phaseResult = new PhaseResult(
                'infrastructure_assessment',
                $this->findings,
                $this->generateInfrastructureMetrics(),
                $executionTime
            );
            
            Log::info('✅ Infrastructure Assessment Complete', [
                'findings' => count($this->findings),
                'execution_time' => round($executionTime, 2) . 's'
            ]);
            
            return $phaseResult;
            
        } catch (\Exception $e) {
            Log::error('❌ Infrastructure Assessment Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * LETHAL: Environment Configuration Audit
     */
    public function auditEnvironmentConfiguration(string $projectPath): void
    {
        Log::info('🔍 Auditing Environment Configuration');
        
        $configIssues = 0;
        $criticalConfigs = [
            'APP_ENV' => ['local', 'staging', 'production'],
            'APP_DEBUG' => ['true', 'false'],
            'APP_KEY' => null, // Should exist and be 32+ chars
            'DB_CONNECTION' => ['mysql', 'pgsql', 'sqlite', 'sqlsrv'],
            'CACHE_DRIVER' => ['file', 'redis', 'memcached', 'array'],
            'QUEUE_CONNECTION' => ['sync', 'database', 'redis', 'sqs'],
            'SESSION_DRIVER' => ['file', 'cookie', 'database', 'redis'],
            'MAIL_MAILER' => ['smtp', 'sendmail', 'mailgun', 'ses', 'postmark']
        ];
        
        foreach ($criticalConfigs as $configKey => $allowedValues) {
            $configValue = config(strtolower(str_replace('_', '.', $configKey))) ?? env($configKey);
            
            if ($configValue === null) {
                $configIssues++;
                
                $finding = new ConcreteFinding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    'critical',
                    "Critical configuration missing: {$configKey}",
                    'infrastructure',
                    'config_missing'
                );
                
                $finding->addFileReference('.env', 1, "Missing: {$configKey}");
                $finding->addMetric('config_key', $configKey, 'configuration');
                $finding->addMetric('severity_score', 9.0, 'score_out_of_10');
                
                $finding->addEvidence('static_analysis', [
                    'config_key' => $configKey,
                    'expected_values' => $allowedValues,
                    'actual_value' => null,
                    'issue_type' => 'missing_configuration'
                ], 'Critical configuration key missing');
                
                $finding->setRecommendation("Add {$configKey} to .env file with appropriate value");
                $finding->setEstimatedHours(1);
                
                $this->findings[] = $finding;
            } elseif ($allowedValues && !in_array($configValue, $allowedValues)) {
                $configIssues++;
                
                $finding = new ConcreteFinding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    'high',
                    "Invalid configuration value for {$configKey}: {$configValue}",
                    'infrastructure',
                    'config_invalid'
                );
                
                $finding->addFileReference('.env', 1, "{$configKey}={$configValue}");
                $finding->addMetric('config_key', $configKey, 'configuration');
                $finding->addMetric('severity_score', 7.5, 'score_out_of_10');
                
                $finding->addEvidence('static_analysis', [
                    'config_key' => $configKey,
                    'expected_values' => $allowedValues,
                    'actual_value' => $configValue,
                    'issue_type' => 'invalid_configuration'
                ], 'Invalid configuration value detected');
                
                $finding->setRecommendation("Set {$configKey} to one of: " . implode(', ', $allowedValues));
                $finding->setEstimatedHours(1);
                
                $this->findings[] = $finding;
            }
        }
        
        // Special checks
        $this->checkAppKeyStrength();
        $this->checkProductionSafety();
        $this->checkDatabaseConfiguration();
        
        $this->configurationAudit = [
            'total_configs_checked' => count($criticalConfigs),
            'config_issues' => $configIssues,
            'app_env' => env('APP_ENV'),
            'debug_mode' => env('APP_DEBUG'),
            'database_driver' => env('DB_CONNECTION')
        ];
        
        $this->infrastructureMetrics['config_issues'] = $configIssues;
        Log::info("Found {$configIssues} configuration issues");
    }

    /**
     * Check APP_KEY strength
     */
    private function checkAppKeyStrength(): void
    {
        $appKey = env('APP_KEY');
        
        if ($appKey && strlen($appKey) < 32) {
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'critical',
                'APP_KEY is too short - security vulnerability',
                'infrastructure',
                'weak_app_key'
            );
            
            $finding->addFileReference('.env', 1, 'APP_KEY length: ' . strlen($appKey));
            $finding->addMetric('key_length', strlen($appKey), 'characters');
            $finding->addMetric('security_risk', 9.5, 'score_out_of_10');
            
            $finding->addEvidence('static_analysis', [
                'key_length' => strlen($appKey),
                'minimum_required' => 32,
                'security_issue' => 'weak_encryption_key'
            ], 'Weak APP_KEY detected');
            
            $finding->setRecommendation('Generate new APP_KEY using: php artisan key:generate');
            $finding->setEstimatedHours(1);
            
            $this->findings[] = $finding;
        }
    }

    /**
     * Check production safety
     */
    private function checkProductionSafety(): void
    {
        $appEnv = env('APP_ENV');
        $appDebug = env('APP_DEBUG');
        
        if ($appEnv === 'production' && $appDebug === 'true') {
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'critical',
                'Debug mode enabled in production - security risk',
                'infrastructure',
                'production_debug'
            );
            
            $finding->addFileReference('.env', 1, 'APP_DEBUG=true in production');
            $finding->addMetric('security_risk', 9.0, 'score_out_of_10');
            
            $finding->addEvidence('static_analysis', [
                'app_env' => $appEnv,
                'app_debug' => $appDebug,
                'security_issue' => 'debug_mode_in_production'
            ], 'Debug mode enabled in production environment');
            
            $finding->setRecommendation('Set APP_DEBUG=false in production environment');
            $finding->setEstimatedHours(1);
            
            $this->findings[] = $finding;
        }
    }

    /**
     * Check database configuration
     */
    private function checkDatabaseConfiguration(): void
    {
        try {
            $connection = DB::connection();
            $pdo = $connection->getPdo();
            
            // Test database connectivity
            $result = DB::select('SELECT 1 as test');
            
            if (empty($result) || $result[0]->test !== 1) {
                $finding = new ConcreteFinding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    'critical',
                    'Database connectivity test failed',
                    'infrastructure',
                    'db_connectivity'
                );
                
                $finding->addFileReference('.env', 1, 'Database configuration');
                $finding->addMetric('connectivity_test', 0, 'boolean');
                
                $finding->addEvidence('runtime_log', [
                    'test_query' => 'SELECT 1 as test',
                    'result' => $result,
                    'connection_status' => 'failed'
                ], 'Database connectivity test failed');
                
                $finding->setRecommendation('Check database credentials and server availability');
                $finding->setEstimatedHours(2);
                
                $this->findings[] = $finding;
            }
        } catch (\Exception $e) {
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'critical',
                'Database connection failed: ' . $e->getMessage(),
                'infrastructure',
                'db_connection_error'
            );
            
            $finding->addFileReference('.env', 1, 'Database configuration');
            $finding->addMetric('connection_error', 1, 'boolean');
            
            $finding->addEvidence('runtime_log', [
                'error_message' => $e->getMessage(),
                'connection_status' => 'error'
            ], 'Database connection error');
            
            $finding->setRecommendation('Fix database configuration and ensure server is running');
            $finding->setEstimatedHours(3);
            
            $this->findings[] = $finding;
        }
    }

    /**
     * LETHAL: Analyze System Resources
     */
    public function analyzeSystemResources(): void
    {
        Log::info('🔍 Analyzing System Resources');
        
        $resourceIssues = 0;
        
        // Memory analysis
        $memoryLimit = ini_get('memory_limit');
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        
        $memoryLimitBytes = $this->parseMemoryLimit($memoryLimit);
        $memoryUsagePercent = ($memoryUsage / $memoryLimitBytes) * 100;
        
        if ($memoryUsagePercent > 80) {
            $resourceIssues++;
            
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'high',
                "High memory usage: {$memoryUsagePercent}% of limit",
                'infrastructure',
                'high_memory_usage'
            );
            
            $finding->addFileReference('php.ini', 1, "memory_limit = {$memoryLimit}");
            $finding->addMetric('memory_usage_percent', $memoryUsagePercent, 'percentage');
            $finding->addMetric('memory_usage_bytes', $memoryUsage, 'bytes');
            $finding->addMetric('memory_limit_bytes', $memoryLimitBytes, 'bytes');
            
            $finding->addEvidence('runtime_log', [
                'memory_limit' => $memoryLimit,
                'memory_usage' => $memoryUsage,
                'memory_peak' => $memoryPeak,
                'usage_percentage' => $memoryUsagePercent
            ], 'High memory usage detected');
            
            $finding->setRecommendation('Optimize memory usage or increase memory_limit');
            $finding->setEstimatedHours(4);
            
            $this->findings[] = $finding;
        }
        
        // Disk space analysis
        $this->analyzeDiskSpace();
        
        // PHP configuration analysis
        $this->analyzePhpConfiguration();
        
        $this->systemResources = [
            'memory_limit' => $memoryLimit,
            'memory_usage' => $memoryUsage,
            'memory_peak' => $memoryPeak,
            'memory_usage_percent' => $memoryUsagePercent,
            'resource_issues' => $resourceIssues
        ];
        
        $this->infrastructureMetrics['resource_issues'] = $resourceIssues;
        Log::info("Found {$resourceIssues} resource issues");
    }

    /**
     * Parse memory limit string to bytes
     */
    private function parseMemoryLimit(string $memoryLimit): int
    {
        $memoryLimit = trim($memoryLimit);
        $last = strtolower($memoryLimit[strlen($memoryLimit) - 1]);
        $value = (int) $memoryLimit;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }

    /**
     * Analyze disk space
     */
    private function analyzeDiskSpace(): void
    {
        $projectPath = base_path();
        $freeBytes = disk_free_space($projectPath);
        $totalBytes = disk_total_space($projectPath);
        
        if ($freeBytes && $totalBytes) {
            $usedPercent = (($totalBytes - $freeBytes) / $totalBytes) * 100;
            
            if ($usedPercent > 90) {
                $finding = new ConcreteFinding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    'critical',
                    "Disk space critically low: {$usedPercent}% used",
                    'infrastructure',
                    'low_disk_space'
                );
                
                $finding->addFileReference($projectPath, 1, "Disk usage: {$usedPercent}%");
                $finding->addMetric('disk_usage_percent', $usedPercent, 'percentage');
                $finding->addMetric('free_bytes', $freeBytes, 'bytes');
                $finding->addMetric('total_bytes', $totalBytes, 'bytes');
                
                $finding->addEvidence('runtime_log', [
                    'free_space' => $freeBytes,
                    'total_space' => $totalBytes,
                    'used_percentage' => $usedPercent,
                    'path' => $projectPath
                ], 'Critical disk space shortage');
                
                $finding->setRecommendation('Free up disk space or expand storage capacity');
                $finding->setEstimatedHours(2);
                
                $this->findings[] = $finding;
            }
        }
    }

    /**
     * Analyze PHP configuration
     */
    private function analyzePhpConfiguration(): void
    {
        $criticalSettings = [
            'max_execution_time' => ['min' => 30, 'recommended' => 300],
            'max_input_vars' => ['min' => 1000, 'recommended' => 5000],
            'post_max_size' => ['min' => '8M', 'recommended' => '64M'],
            'upload_max_filesize' => ['min' => '2M', 'recommended' => '32M']
        ];
        
        foreach ($criticalSettings as $setting => $limits) {
            $value = ini_get($setting);
            
            if ($setting === 'max_execution_time' || $setting === 'max_input_vars') {
                $numericValue = (int) $value;
                if ($numericValue < $limits['min']) {
                    $this->createPhpConfigFinding($setting, $value, $limits);
                }
            } else {
                // Handle memory/size settings
                $bytes = $this->parseMemoryLimit($value);
                $minBytes = $this->parseMemoryLimit($limits['min']);
                
                if ($bytes < $minBytes) {
                    $this->createPhpConfigFinding($setting, $value, $limits);
                }
            }
        }
    }

    /**
     * Create PHP configuration finding
     */
    private function createPhpConfigFinding(string $setting, string $value, array $limits): void
    {
        $finding = new ConcreteFinding(
            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
            'medium',
            "PHP setting {$setting} is too low: {$value}",
            'infrastructure',
            'php_config_low'
        );
        
        $finding->addFileReference('php.ini', 1, "{$setting} = {$value}");
        $finding->addMetric('setting_name', $setting, 'configuration');
        $finding->addMetric('current_value', $value, 'setting');
        $finding->addMetric('recommended_value', $limits['recommended'], 'setting');
        
        $finding->addEvidence('static_analysis', [
            'setting' => $setting,
            'current_value' => $value,
            'minimum_required' => $limits['min'],
            'recommended_value' => $limits['recommended']
        ], 'PHP configuration below recommended values');
        
        $finding->setRecommendation("Increase {$setting} to at least {$limits['recommended']}");
        $finding->setEstimatedHours(1);
        
        $this->findings[] = $finding;
    }

    /**
     * Generate infrastructure metrics
     */
    private function generateInfrastructureMetrics(): array
    {
        $metrics = [
            [
                'name' => 'total_infrastructure_findings',
                'value' => count($this->findings),
                'unit' => 'issues'
            ],
            [
                'name' => 'config_issues',
                'value' => $this->infrastructureMetrics['config_issues'] ?? 0,
                'unit' => 'issues'
            ],
            [
                'name' => 'resource_issues',
                'value' => $this->infrastructureMetrics['resource_issues'] ?? 0,
                'unit' => 'issues'
            ],
            [
                'name' => 'memory_usage_percent',
                'value' => $this->systemResources['memory_usage_percent'] ?? 0,
                'unit' => 'percentage'
            ],
            [
                'name' => 'critical_issues',
                'value' => count(array_filter($this->findings, fn($f) => $f->getSeverity() === 'critical')),
                'unit' => 'issues'
            ],
            [
                'name' => 'high_priority_issues',
                'value' => count(array_filter($this->findings, fn($f) => $f->getSeverity() === 'high')),
                'unit' => 'issues'
            ]
        ];
        
        // Add scenario documentation metrics if available
        if (!empty($this->scenarioDocumentation)) {
            $executiveSummary = $this->scenarioDocumentation['executive_summary'] ?? [];
            
            $metrics[] = [
                'name' => 'scenarios_documented',
                'value' => $executiveSummary['total_scenarios_tested'] ?? 0,
                'unit' => 'scenarios'
            ];
            
            $metrics[] = [
                'name' => 'successful_recoveries',
                'value' => $executiveSummary['successful_recoveries'] ?? 0,
                'unit' => 'scenarios'
            ];
            
            $metrics[] = [
                'name' => 'resilience_score',
                'value' => $executiveSummary['resilience_score'] ?? 0,
                'unit' => 'percentage'
            ];
            
            $metrics[] = [
                'name' => 'average_recovery_time_ms',
                'value' => $executiveSummary['average_recovery_time_ms'] ?? 0,
                'unit' => 'milliseconds'
            ];
            
            $metrics[] = [
                'name' => 'screenshots_captured',
                'value' => $this->scenarioDocumentation['documentation_metadata']['total_screenshots'] ?? 0,
                'unit' => 'screenshots'
            ];
        }
        
        return $metrics;
    }

    /**
     * LETHAL: Verify Monitoring System
     */
    public function verifyMonitoringSystem(string $projectPath): void
    {
        Log::info('🔍 Verifying Monitoring System');
        
        $monitoringIssues = 0;
        
        // Check for logging configuration
        $logChannels = config('logging.channels', []);
        
        if (empty($logChannels)) {
            $monitoringIssues++;
            
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'high',
                'No logging channels configured - monitoring blind spot',
                'infrastructure',
                'no_logging'
            );
            
            $finding->addFileReference('config/logging.php', 1, 'No logging channels');
            $finding->addMetric('log_channels_count', 0, 'channels');
            
            $finding->addEvidence('static_analysis', [
                'log_channels' => $logChannels,
                'monitoring_issue' => 'no_logging_configured'
            ], 'No logging channels configured');
            
            $finding->setRecommendation('Configure logging channels for proper monitoring');
            $finding->setEstimatedHours(2);
            
            $this->findings[] = $finding;
        }
        
        // Check for error reporting
        $errorReporting = error_reporting();
        
        if ($errorReporting === 0 && env('APP_ENV') !== 'production') {
            $monitoringIssues++;
            
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'medium',
                'Error reporting disabled in non-production environment',
                'infrastructure',
                'error_reporting_off'
            );
            
            $finding->addFileReference('php.ini', 1, 'error_reporting = 0');
            $finding->addMetric('error_reporting_level', $errorReporting, 'level');
            
            $finding->addEvidence('runtime_log', [
                'error_reporting' => $errorReporting,
                'app_env' => env('APP_ENV'),
                'monitoring_issue' => 'error_reporting_disabled'
            ], 'Error reporting disabled in development');
            
            $finding->setRecommendation('Enable error reporting in development environments');
            $finding->setEstimatedHours(1);
            
            $this->findings[] = $finding;
        }
        
        $this->infrastructureMetrics['monitoring_issues'] = $monitoringIssues;
        Log::info("Found {$monitoringIssues} monitoring issues");
    }

    /**
     * LETHAL: Execute Fail-Safe Simulations
     */
    public function executeFailSafeSimulations(): void
    {
        Log::info('🔥 Executing Fail-Safe Simulations');
        
        $simulationResults = [];
        
        // Execute all failure scenarios
        $simulationResults['queue_overload'] = $this->failSafeSimulator->simulateQueueOverload();
        $simulationResults['database_lock'] = $this->failSafeSimulator->simulateDatabaseLock();
        $simulationResults['disk_exhaustion'] = $this->failSafeSimulator->simulateDiskExhaustion();
        $simulationResults['memory_breach'] = $this->failSafeSimulator->simulateMemoryBreach();
        $simulationResults['cascade_failure'] = $this->failSafeSimulator->simulateCascadeFailure();
        
        // Analyze simulation results
        $failedSimulations = 0;
        $totalRecoveryTime = 0;
        
        foreach ($simulationResults as $scenario => $result) {
            if (!$result['success']) {
                $failedSimulations++;
                
                $finding = new ConcreteFinding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    'high',
                    "Fail-safe simulation failed: {$scenario}",
                    'infrastructure',
                    'failsafe_failure'
                );
                
                $finding->addFileReference('infrastructure', 1, "Scenario: {$scenario}");
                $finding->addMetric('scenario', $scenario, 'simulation');
                $finding->addMetric('recovery_time_ms', $result['recovery_time_ms'], 'milliseconds');
                $finding->addMetric('success', $result['success'] ? 1 : 0, 'boolean');
                
                $finding->addEvidence('runtime_log', [
                    'scenario' => $scenario,
                    'simulation_result' => $result,
                    'failure_type' => 'failsafe_simulation_failure'
                ], 'Fail-safe simulation failed');
                
                $finding->setRecommendation("Improve system resilience for {$scenario} scenario");
                $finding->setEstimatedHours(4);
                
                $this->findings[] = $finding;
            }
            
            $totalRecoveryTime += $result['recovery_time_ms'];
        }
        
        $avgRecoveryTime = $totalRecoveryTime / count($simulationResults);
        
        $this->infrastructureMetrics['failed_simulations'] = $failedSimulations;
        $this->infrastructureMetrics['avg_recovery_time'] = $avgRecoveryTime;
        
        Log::info("Completed fail-safe simulations: {$failedSimulations} failures, avg recovery: {$avgRecoveryTime}ms");
        
        // Document all failure scenarios with screenshots
        $this->documentFailureScenariosWithScreenshots();
    }

    /**
     * LETHAL: Document All Failure Scenarios with Screenshots
     */
    public function documentFailureScenariosWithScreenshots(): void
    {
        Log::info('📸 Documenting all failure scenarios with screenshots');
        
        try {
            // Execute comprehensive scenario documentation
            $this->scenarioDocumentation = $this->scenarioDocumenter->documentAllFailureScenarios();
            
            // Create findings for any critical documentation issues
            $this->analyzeScenarioDocumentation();
            
            Log::info('✅ Failure scenario documentation completed', [
                'scenarios_documented' => count($this->scenarioDocumentation['documented_scenarios'] ?? []),
                'resilience_score' => $this->scenarioDocumentation['executive_summary']['resilience_score'] ?? 0,
                'screenshots_captured' => $this->scenarioDocumentation['documentation_metadata']['total_screenshots'] ?? 0
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Failure scenario documentation failed', [
                'error' => $e->getMessage()
            ]);
            
            // Create finding for documentation failure
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'high',
                'Failure scenario documentation failed: ' . $e->getMessage(),
                'infrastructure',
                'scenario_documentation_failure'
            );
            
            $finding->addFileReference('infrastructure', 1, 'Scenario documentation');
            $finding->addMetric('documentation_failed', 1, 'boolean');
            
            $finding->addEvidence('runtime_log', [
                'error_message' => $e->getMessage(),
                'documentation_status' => 'failed'
            ], 'Failure scenario documentation failed');
            
            $finding->setRecommendation('Fix scenario documentation system and re-run documentation');
            $finding->setEstimatedHours(4);
            
            $this->findings[] = $finding;
        }
    }

    /**
     * Analyze scenario documentation for critical issues
     */
    private function analyzeScenarioDocumentation(): void
    {
        if (empty($this->scenarioDocumentation)) {
            return;
        }
        
        $executiveSummary = $this->scenarioDocumentation['executive_summary'] ?? [];
        $resilienceScore = $executiveSummary['resilience_score'] ?? 0;
        $successfulRecoveries = $executiveSummary['successful_recoveries'] ?? 0;
        $totalScenarios = $executiveSummary['total_scenarios_tested'] ?? 0;
        
        // Check resilience score
        if ($resilienceScore < 70) {
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'critical',
                "Low system resilience score: {$resilienceScore}%",
                'infrastructure',
                'low_resilience_score'
            );
            
            $finding->addFileReference('infrastructure', 1, 'Resilience analysis');
            $finding->addMetric('resilience_score', $resilienceScore, 'percentage');
            $finding->addMetric('threshold', 70, 'percentage');
            
            $finding->addEvidence('runtime_log', [
                'resilience_score' => $resilienceScore,
                'successful_recoveries' => $successfulRecoveries,
                'total_scenarios' => $totalScenarios,
                'critical_findings' => $executiveSummary['critical_findings'] ?? []
            ], 'Low system resilience detected');
            
            $finding->setRecommendation('Improve system resilience mechanisms and error handling');
            $finding->setEstimatedHours(16);
            
            $this->findings[] = $finding;
        }
        
        // Check for failed recoveries
        $failedRecoveries = $totalScenarios - $successfulRecoveries;
        if ($failedRecoveries > 0) {
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'high',
                "{$failedRecoveries} failure scenarios failed to recover properly",
                'infrastructure',
                'failed_recovery_scenarios'
            );
            
            $finding->addFileReference('infrastructure', 1, 'Recovery analysis');
            $finding->addMetric('failed_recoveries', $failedRecoveries, 'scenarios');
            $finding->addMetric('total_scenarios', $totalScenarios, 'scenarios');
            
            $finding->addEvidence('runtime_log', [
                'failed_recoveries' => $failedRecoveries,
                'successful_recoveries' => $successfulRecoveries,
                'recovery_rate' => $totalScenarios > 0 ? ($successfulRecoveries / $totalScenarios) * 100 : 0
            ], 'Failed recovery scenarios detected');
            
            $finding->setRecommendation('Implement proper recovery mechanisms for failed scenarios');
            $finding->setEstimatedHours(8);
            
            $this->findings[] = $finding;
        }
        
        // Check average recovery time
        $avgRecoveryTime = $executiveSummary['average_recovery_time_ms'] ?? 0;
        if ($avgRecoveryTime > 30000) { // 30 seconds
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'medium',
                "Average recovery time too high: {$avgRecoveryTime}ms",
                'infrastructure',
                'slow_recovery_time'
            );
            
            $finding->addFileReference('infrastructure', 1, 'Recovery time analysis');
            $finding->addMetric('avg_recovery_time_ms', $avgRecoveryTime, 'milliseconds');
            $finding->addMetric('threshold_ms', 30000, 'milliseconds');
            
            $finding->addEvidence('runtime_log', [
                'average_recovery_time' => $avgRecoveryTime,
                'threshold' => 30000,
                'performance_impact' => 'high'
            ], 'Slow recovery time detected');
            
            $finding->setRecommendation('Optimize recovery mechanisms to reduce recovery time');
            $finding->setEstimatedHours(6);
            
            $this->findings[] = $finding;
        }
    }

    /**
     * Get scenario documentation results
     */
    public function getScenarioDocumentation(): array
    {
        return $this->scenarioDocumentation;
    }

    /**
     * LETHAL: Test Disaster Recovery
     */
    public function testDisasterRecovery(string $projectPath): void
    {
        Log::info('🔍 Testing Disaster Recovery');
        
        $recoveryIssues = 0;
        
        // Check for backup configurations
        $backupConfig = config('backup', []);
        
        if (empty($backupConfig)) {
            $recoveryIssues++;
            
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'critical',
                'No backup configuration found - disaster recovery risk',
                'infrastructure',
                'no_backup_config'
            );
            
            $finding->addFileReference('config/backup.php', 1, 'Backup configuration missing');
            $finding->addMetric('backup_configured', 0, 'boolean');
            
            $finding->addEvidence('static_analysis', [
                'backup_config' => $backupConfig,
                'disaster_recovery_issue' => 'no_backup_configuration'
            ], 'No backup configuration found');
            
            $finding->setRecommendation('Configure backup system for disaster recovery');
            $finding->setEstimatedHours(6);
            
            $this->findings[] = $finding;
        }
        
        // Check for .env.example file
        $envExamplePath = $projectPath . '/.env.example';
        
        if (!file_exists($envExamplePath)) {
            $recoveryIssues++;
            
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'medium',
                '.env.example file missing - deployment recovery issue',
                'infrastructure',
                'no_env_example'
            );
            
            $finding->addFileReference('.env.example', 1, 'File missing');
            $finding->addMetric('env_example_exists', 0, 'boolean');
            
            $finding->addEvidence('static_analysis', [
                'file_path' => $envExamplePath,
                'file_exists' => false,
                'recovery_issue' => 'missing_env_template'
            ], '.env.example file missing');
            
            $finding->setRecommendation('Create .env.example file for deployment reference');
            $finding->setEstimatedHours(1);
            
            $this->findings[] = $finding;
        }
        
        $this->infrastructureMetrics['recovery_issues'] = $recoveryIssues;
        Log::info("Found {$recoveryIssues} disaster recovery issues");
    }

    /**
     * LETHAL: Assess Infrastructure Security
     */
    public function assessInfrastructureSecurity(string $projectPath): void
    {
        Log::info('🔍 Assessing Infrastructure Security');
        
        $securityIssues = 0;
        
        // Check for .env file in version control
        $gitignorePath = $projectPath . '/.gitignore';
        
        if (file_exists($gitignorePath)) {
            $gitignoreContent = file_get_contents($gitignorePath);
            
            if (strpos($gitignoreContent, '.env') === false) {
                $securityIssues++;
                
                $finding = new ConcreteFinding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    'critical',
                    '.env file not in .gitignore - security risk',
                    'infrastructure',
                    'env_not_ignored'
                );
                
                $finding->addFileReference('.gitignore', 1, '.env not ignored');
                $finding->addMetric('security_risk', 9.5, 'score_out_of_10');
                
                $finding->addEvidence('static_analysis', [
                    'gitignore_content' => substr($gitignoreContent, 0, 500),
                    'env_ignored' => false,
                    'security_issue' => 'env_file_not_ignored'
                ], '.env file not in .gitignore');
                
                $finding->setRecommendation('Add .env to .gitignore to prevent credential exposure');
                $finding->setEstimatedHours(1);
                
                $this->findings[] = $finding;
            }
        }
        
        // Check for debug mode in production
        if (env('APP_ENV') === 'production' && env('APP_DEBUG') === 'true') {
            $securityIssues++;
            // This is already handled in checkProductionSafety()
        }
        
        $this->infrastructureMetrics['security_issues'] = $securityIssues;
        Log::info("Found {$securityIssues} infrastructure security issues");
    }
}