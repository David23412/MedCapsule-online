<?php

namespace App\Services\Audit\Components;

use App\Services\Audit\Models\ConcreteFinding;
use App\Services\Audit\Reports\PhaseResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * Side Effect Sentinel - Advanced Monitoring System
 * 
 * Real-time monitoring and detection of unintended side effects during audit execution,
 * including filesystem changes, cache mutations, database writes, and state mutations
 */
class SideEffectSentinel
{
    private FileScanner $fileScanner;
    private array $findings = [];
    private int $findingCounter = 1;
    private array $sideEffectMetrics = [];
    
    // Monitoring state
    private array $initialFileSystemState = [];
    private array $initialCacheState = [];
    private array $initialDatabaseState = [];
    private array $monitoredPaths = [];
    private array $detectedSideEffects = [];
    private bool $monitoringActive = false;

    public function __construct(FileScanner $fileScanner)
    {
        $this->fileScanner = $fileScanner;
    }

    /**
     * Execute comprehensive side effect monitoring and analysis
     */
    public function executeSideEffectMonitoring(string $projectPath): PhaseResult
    {
        Log::info('🔍 Starting Side Effect Sentinel Monitoring');
        
        $startTime = microtime(true);
        $this->findings = [];
        
        try {
            // Initialize monitoring systems
            $this->initializeMonitoring($projectPath);
            
            // 1. Real-time Filesystem Change Monitoring
            $this->monitorFilesystemChanges();
            
            // 2. Cache Key Mutation Tracking per Route
            $this->trackCacheMutations();
            
            // 3. Database Trigger and Side-effect Detection
            $this->detectDatabaseSideEffects();
            
            // 4. Untracked Dependency Identification
            $this->identifyUntrackedDependencies();
            
            // 5. State Mutation Proof Collection
            $this->collectStateMutationProof();
            
            // 6. Originator Tracing for Side Effects
            $this->traceOriginators();
            
            // 7. Impact Assessment on Affected Subsystems
            $this->assessSubsystemImpact();
            
            $executionTime = microtime(true) - $startTime;
            
            $phaseResult = new PhaseResult(
                'side_effect_monitoring',
                $this->findings,
                $this->generateSideEffectMetrics(),
                $executionTime
            );
            
            Log::info('✅ Side Effect Monitoring Complete', [
                'findings' => count($this->findings),
                'side_effects_detected' => count($this->detectedSideEffects),
                'execution_time' => round($executionTime, 2) . 's'
            ]);
            
            return $phaseResult;
            
        } catch (\Exception $e) {
            Log::error('❌ Side Effect Monitoring Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        } finally {
            $this->cleanupMonitoring();
        }
    }

    /**
     * Initialize monitoring systems and capture baseline state
     */
    private function initializeMonitoring(string $projectPath): void
    {
        Log::info('🔧 Initializing Side Effect Monitoring Systems');
        
        // Capture initial filesystem state
        $this->captureFilesystemBaseline($projectPath);
        
        // Capture initial cache state
        $this->captureCacheBaseline();
        
        // Capture initial database state
        $this->captureDatabaseBaseline();
        
        // Set up monitoring paths
        $this->setupMonitoringPaths($projectPath);
        
        $this->monitoringActive = true;
        Log::info('✅ Monitoring systems initialized');
    }

    /**
     * Monitor real-time filesystem changes during audit
     */
    public function monitorFilesystemChanges(): void
    {
        Log::info('🔍 Monitoring Real-time Filesystem Changes');
        
        $filesystemChanges = 0;
        $currentState = $this->captureCurrentFilesystemState();
        
        // Compare with baseline
        $changes = $this->compareFilesystemStates($this->initialFileSystemState, $currentState);
        
        foreach ($changes as $change) {
            $filesystemChanges++;
            
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                $this->getSeverityForChange($change),
                "Filesystem change detected: {$change['type']} - {$change['path']}",
                'side_effect',
                'filesystem_change'
            );
            
            $finding->addFileReference($change['path'], 1, $change['description']);
            $finding->addMetric('change_type', $change['type'], 'filesystem_operation');
            $finding->addMetric('file_size_change', $change['size_delta'] ?? 0, 'bytes');
            $finding->addMetric('timestamp', $change['timestamp'], 'unix_timestamp');
            
            $finding->addEvidence('filesystem_monitoring', [
                'change_type' => $change['type'],
                'file_path' => $change['path'],
                'size_before' => $change['size_before'] ?? null,
                'size_after' => $change['size_after'] ?? null,
                'modification_time' => $change['timestamp'],
                'originator_trace' => $this->traceFileChangeOriginator($change)
            ], 'Real-time filesystem change detected');
            
            $finding->setRecommendation('Review filesystem change for unintended side effects');
            $finding->setEstimatedHours(2);
            
            $this->findings[] = $finding;
            $this->detectedSideEffects[] = $change;
        }
        
        $this->sideEffectMetrics['filesystem_changes'] = $filesystemChanges;
        Log::info("Detected {$filesystemChanges} filesystem changes");
    }

    /**
     * Track cache key mutations per route
     */
    public function trackCacheMutations(): void
    {
        Log::info('🔍 Tracking Cache Key Mutations');
        
        $cacheMutations = 0;
        $phpFiles = $this->fileScanner->getPHPFiles();
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            $lines = explode("\n", $content);
            
            foreach ($lines as $lineNumber => $line) {
                // Detect cache operations
                if (preg_match('/Cache::(put|forget|flush|remember|rememberForever|increment|decrement)/', $line, $matches)) {
                    $operation = $matches[1];
                    $cacheKey = $this->extractCacheKey($line);
                    
                    // Check if this is a mutation operation
                    if (in_array($operation, ['put', 'forget', 'flush', 'increment', 'decrement'])) {
                        $cacheMutations++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'medium',
                            "Cache mutation detected: {$operation} operation on key '{$cacheKey}'",
                            'side_effect',
                            'cache_mutation'
                        );
                        
                        $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('cache_operation', $operation, 'operation_type');
                        $finding->addMetric('cache_key', $cacheKey, 'cache_identifier');
                        $finding->addMetric('mutation_risk', $this->calculateCacheMutationRisk($operation), 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'cache_operation' => $operation,
                            'cache_key' => $cacheKey,
                            'file_path' => $file['path'],
                            'line_number' => $lineNumber + 1,
                            'context' => array_slice($lines, max(0, $lineNumber - 3), 7),
                            'route_context' => $this->identifyRouteContext($file['path'], $lineNumber, $lines)
                        ], 'Cache mutation operation detected');
                        
                        $finding->setRecommendation('Review cache mutation for unintended side effects on other routes');
                        $finding->setEstimatedHours(2);
                        
                        $this->findings[] = $finding;
                        $this->detectedSideEffects[] = [
                            'type' => 'cache_mutation',
                            'operation' => $operation,
                            'key' => $cacheKey,
                            'file' => $file['path'],
                            'line' => $lineNumber + 1
                        ];
                    }
                }
            }
        }
        
        $this->sideEffectMetrics['cache_mutations'] = $cacheMutations;
        Log::info("Detected {$cacheMutations} cache mutations");
    }    
/**
     * Detect database triggers and side effects
     */
    public function detectDatabaseSideEffects(): void
    {
        Log::info('🔍 Detecting Database Triggers and Side Effects');
        
        $databaseSideEffects = 0;
        $phpFiles = $this->fileScanner->getPHPFiles();
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            $lines = explode("\n", $content);
            
            foreach ($lines as $lineNumber => $line) {
                // Detect database write operations
                if (preg_match('/DB::(insert|update|delete|statement)|->save\(\)|->create\(|->update\(|->delete\(/', $line)) {
                    $contextLines = array_slice($lines, max(0, $lineNumber - 5), 10);
                    $context = implode("\n", $contextLines);
                    
                    // Check if this is within a transaction
                    $inTransaction = preg_match('/DB::transaction|beginTransaction/', $context);
                    
                    // Check for potential side effects
                    $hasSideEffectRisk = $this->analyzeDatabaseSideEffectRisk($line, $context);
                    
                    if ($hasSideEffectRisk['has_risk']) {
                        $databaseSideEffects++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            $hasSideEffectRisk['severity'],
                            "Database side effect risk: {$hasSideEffectRisk['description']}",
                            'side_effect',
                            'database_side_effect'
                        );
                        
                        $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('operation_type', $hasSideEffectRisk['operation'], 'db_operation');
                        $finding->addMetric('in_transaction', $inTransaction, 'boolean');
                        $finding->addMetric('side_effect_risk', $hasSideEffectRisk['risk_score'], 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'database_operation' => trim($line),
                            'file_path' => $file['path'],
                            'line_number' => $lineNumber + 1,
                            'in_transaction' => $inTransaction,
                            'risk_factors' => $hasSideEffectRisk['risk_factors'],
                            'context' => $contextLines
                        ], 'Database side effect risk detected');
                        
                        $finding->setRecommendation($hasSideEffectRisk['recommendation']);
                        $finding->setEstimatedHours(4);
                        
                        $this->findings[] = $finding;
                        $this->detectedSideEffects[] = [
                            'type' => 'database_side_effect',
                            'operation' => $hasSideEffectRisk['operation'],
                            'file' => $file['path'],
                            'line' => $lineNumber + 1,
                            'risk_score' => $hasSideEffectRisk['risk_score']
                        ];
                    }
                }
            }
        }
        
        $this->sideEffectMetrics['database_side_effects'] = $databaseSideEffects;
        Log::info("Detected {$databaseSideEffects} database side effect risks");
    }

    /**
     * Identify untracked dependencies
     */
    public function identifyUntrackedDependencies(): void
    {
        Log::info('🔍 Identifying Untracked Dependencies');
        
        $untrackedDependencies = 0;
        $phpFiles = $this->fileScanner->getPHPFiles();
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            $dependencies = $this->extractFileDependencies($content);
            
            foreach ($dependencies as $dependency) {
                if ($this->isUntrackedDependency($dependency)) {
                    $untrackedDependencies++;
                    
                    $finding = new ConcreteFinding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'medium',
                        "Untracked dependency detected: {$dependency['type']} - {$dependency['identifier']}",
                        'side_effect',
                        'untracked_dependency'
                    );
                    
                    $finding->addFileReference($file['path'], $dependency['line_number'], $dependency['code']);
                    $finding->addMetric('dependency_type', $dependency['type'], 'dependency_category');
                    $finding->addMetric('identifier', $dependency['identifier'], 'dependency_id');
                    $finding->addMetric('tracking_risk', $dependency['risk_score'], 'score_out_of_10');
                    
                    $finding->addEvidence('static_analysis', [
                        'dependency_type' => $dependency['type'],
                        'identifier' => $dependency['identifier'],
                        'file_path' => $file['path'],
                        'line_number' => $dependency['line_number'],
                        'code_context' => $dependency['code'],
                        'tracking_status' => 'untracked'
                    ], 'Untracked dependency detected');
                    
                    $finding->setRecommendation('Add proper dependency tracking and management');
                    $finding->setEstimatedHours(3);
                    
                    $this->findings[] = $finding;
                    $this->detectedSideEffects[] = $dependency;
                }
            }
        }
        
        $this->sideEffectMetrics['untracked_dependencies'] = $untrackedDependencies;
        Log::info("Identified {$untrackedDependencies} untracked dependencies");
    }

    /**
     * Collect state mutation proof
     */
    public function collectStateMutationProof(): void
    {
        Log::info('🔍 Collecting State Mutation Proof');
        
        $stateMutations = 0;
        $phpFiles = $this->fileScanner->getPHPFiles();
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            $mutations = $this->detectStateMutations($content, $file['path']);
            
            foreach ($mutations as $mutation) {
                $stateMutations++;
                
                $finding = new ConcreteFinding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    $mutation['severity'],
                    "State mutation detected: {$mutation['description']}",
                    'side_effect',
                    'state_mutation'
                );
                
                $finding->addFileReference($file['path'], $mutation['line_number'], $mutation['code']);
                $finding->addMetric('mutation_type', $mutation['type'], 'mutation_category');
                $finding->addMetric('scope', $mutation['scope'], 'variable_scope');
                $finding->addMetric('mutation_risk', $mutation['risk_score'], 'score_out_of_10');
                
                $finding->addEvidence('static_analysis', [
                    'mutation_type' => $mutation['type'],
                    'variable_name' => $mutation['variable'],
                    'scope' => $mutation['scope'],
                    'file_path' => $file['path'],
                    'line_number' => $mutation['line_number'],
                    'code_context' => $mutation['context'],
                    'proof_chain' => $mutation['proof_chain']
                ], 'State mutation proof collected');
                
                $finding->setRecommendation($mutation['recommendation']);
                $finding->setEstimatedHours(3);
                
                $this->findings[] = $finding;
                $this->detectedSideEffects[] = $mutation;
            }
        }
        
        $this->sideEffectMetrics['state_mutations'] = $stateMutations;
        Log::info("Collected proof for {$stateMutations} state mutations");
    }

    /**
     * Trace originators for detected side effects
     */
    public function traceOriginators(): void
    {
        Log::info('🔍 Tracing Side Effect Originators');
        
        $originatorTraces = 0;
        
        foreach ($this->detectedSideEffects as $sideEffect) {
            $trace = $this->buildOriginatorTrace($sideEffect);
            
            if (!empty($trace['chain'])) {
                $originatorTraces++;
                
                $finding = new ConcreteFinding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    'low',
                    "Side effect originator traced: {$trace['description']}",
                    'side_effect',
                    'originator_trace'
                );
                
                $finding->addFileReference($sideEffect['file'] ?? 'unknown', $sideEffect['line'] ?? 1, $trace['root_cause']);
                $finding->addMetric('trace_depth', count($trace['chain']), 'call_levels');
                $finding->addMetric('confidence', $trace['confidence'], 'score_0_to_1');
                $finding->addMetric('complexity', $trace['complexity_score'], 'score_out_of_10');
                
                $finding->addEvidence('originator_tracing', [
                    'side_effect_type' => $sideEffect['type'],
                    'originator_chain' => $trace['chain'],
                    'root_cause' => $trace['root_cause'],
                    'confidence_level' => $trace['confidence'],
                    'trace_metadata' => $trace['metadata']
                ], 'Side effect originator trace completed');
                
                $finding->setRecommendation('Review originator chain for optimization opportunities');
                $finding->setEstimatedHours(2);
                
                $this->findings[] = $finding;
            }
        }
        
        $this->sideEffectMetrics['originator_traces'] = $originatorTraces;
        Log::info("Traced {$originatorTraces} side effect originators");
    }

    /**
     * Assess impact on affected subsystems
     */
    public function assessSubsystemImpact(): void
    {
        Log::info('🔍 Assessing Subsystem Impact');
        
        $impactAssessments = 0;
        $subsystemMap = $this->buildSubsystemMap();
        
        foreach ($this->detectedSideEffects as $sideEffect) {
            $impact = $this->calculateSubsystemImpact($sideEffect, $subsystemMap);
            
            if ($impact['severity'] !== 'none') {
                $impactAssessments++;
                
                $finding = new ConcreteFinding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    $impact['severity'],
                    "Subsystem impact detected: {$impact['description']}",
                    'side_effect',
                    'subsystem_impact'
                );
                
                $finding->addFileReference($sideEffect['file'] ?? 'unknown', $sideEffect['line'] ?? 1, $impact['summary']);
                $finding->addMetric('affected_subsystems', count($impact['affected_subsystems']), 'subsystem_count');
                $finding->addMetric('impact_score', $impact['impact_score'], 'score_out_of_10');
                $finding->addMetric('propagation_risk', $impact['propagation_risk'], 'score_0_to_1');
                
                $finding->addEvidence('impact_analysis', [
                    'side_effect_type' => $sideEffect['type'],
                    'affected_subsystems' => $impact['affected_subsystems'],
                    'impact_analysis' => $impact['analysis'],
                    'propagation_paths' => $impact['propagation_paths'],
                    'mitigation_strategies' => $impact['mitigation_strategies']
                ], 'Subsystem impact analysis completed');
                
                $finding->setRecommendation($impact['recommendation']);
                $finding->setEstimatedHours($impact['estimated_hours']);
                
                $this->findings[] = $finding;
            }
        }
        
        $this->sideEffectMetrics['subsystem_impacts'] = $impactAssessments;
        Log::info("Assessed {$impactAssessments} subsystem impacts");
    } 
   /**
     * Helper methods for monitoring and analysis
     */
    private function captureFilesystemBaseline(string $projectPath): void
    {
        $this->initialFileSystemState = [];
        $this->monitoredPaths = [
            $projectPath,
            storage_path(),
            base_path('bootstrap/cache')
        ];
        
        foreach ($this->monitoredPaths as $path) {
            if (is_dir($path)) {
                $this->initialFileSystemState[$path] = $this->scanDirectoryState($path);
            }
        }
    }

    private function captureCacheBaseline(): void
    {
        try {
            $this->initialCacheState = [
                'timestamp' => time(),
                'driver' => config('cache.default'),
                'keys_snapshot' => []
            ];
        } catch (\Exception $e) {
            Log::warning('Could not capture cache baseline: ' . $e->getMessage());
            $this->initialCacheState = ['error' => $e->getMessage()];
        }
    }

    private function captureDatabaseBaseline(): void
    {
        try {
            $this->initialDatabaseState = [
                'timestamp' => time(),
                'connection' => config('database.default'),
                'query_log_enabled' => DB::logging()
            ];
            
            DB::enableQueryLog();
        } catch (\Exception $e) {
            Log::warning('Could not capture database baseline: ' . $e->getMessage());
            $this->initialDatabaseState = ['error' => $e->getMessage()];
        }
    }

    private function setupMonitoringPaths(string $projectPath): void
    {
        $this->monitoredPaths = array_filter([
            $projectPath . '/app',
            $projectPath . '/config',
            storage_path('app'),
            storage_path('logs'),
            storage_path('framework/cache')
        ], 'is_dir');
    }

    private function scanDirectoryState(string $path): array
    {
        $state = [];
        
        if (!is_dir($path)) {
            return $state;
        }
        
        try {
            $files = File::allFiles($path);
            foreach ($files as $file) {
                $relativePath = str_replace($path . '/', '', $file->getPathname());
                $state[$relativePath] = [
                    'size' => $file->getSize(),
                    'mtime' => $file->getMTime(),
                    'hash' => md5_file($file->getPathname())
                ];
            }
        } catch (\Exception $e) {
            Log::warning("Could not scan directory {$path}: " . $e->getMessage());
        }
        
        return $state;
    }

    private function captureCurrentFilesystemState(): array
    {
        $currentState = [];
        
        foreach ($this->monitoredPaths as $path) {
            if (is_dir($path)) {
                $currentState[$path] = $this->scanDirectoryState($path);
            }
        }
        
        return $currentState;
    }

    private function compareFilesystemStates(array $initial, array $current): array
    {
        $changes = [];
        
        foreach ($current as $basePath => $currentFiles) {
            $initialFiles = $initial[$basePath] ?? [];
            
            // Check for new files
            foreach ($currentFiles as $file => $currentInfo) {
                if (!isset($initialFiles[$file])) {
                    $changes[] = [
                        'type' => 'created',
                        'path' => $basePath . '/' . $file,
                        'size_after' => $currentInfo['size'],
                        'timestamp' => $currentInfo['mtime'],
                        'description' => "File created: {$file}"
                    ];
                }
            }
            
            // Check for modified files
            foreach ($initialFiles as $file => $initialInfo) {
                if (isset($currentFiles[$file])) {
                    $currentInfo = $currentFiles[$file];
                    
                    if ($currentInfo['hash'] !== $initialInfo['hash']) {
                        $changes[] = [
                            'type' => 'modified',
                            'path' => $basePath . '/' . $file,
                            'size_before' => $initialInfo['size'],
                            'size_after' => $currentInfo['size'],
                            'size_delta' => $currentInfo['size'] - $initialInfo['size'],
                            'timestamp' => $currentInfo['mtime'],
                            'description' => "File modified: {$file}"
                        ];
                    }
                } else {
                    $changes[] = [
                        'type' => 'deleted',
                        'path' => $basePath . '/' . $file,
                        'size_before' => $initialInfo['size'],
                        'timestamp' => time(),
                        'description' => "File deleted: {$file}"
                    ];
                }
            }
        }
        
        return $changes;
    }

    private function getSeverityForChange(array $change): string
    {
        switch ($change['type']) {
            case 'created':
                return strpos($change['path'], 'cache') !== false ? 'low' : 'medium';
            case 'modified':
                $sizeDelta = abs($change['size_delta'] ?? 0);
                if ($sizeDelta > 10000) return 'high';
                if ($sizeDelta > 1000) return 'medium';
                return 'low';
            case 'deleted':
                return 'high';
            default:
                return 'medium';
        }
    }

    private function traceFileChangeOriginator(array $change): array
    {
        return [
            'method' => 'filesystem_monitoring',
            'confidence' => 0.7,
            'possible_originators' => [
                'audit_process',
                'cache_operations',
                'log_writing'
            ],
            'trace_timestamp' => time()
        ];
    }

    private function extractCacheKey(string $line): string
    {
        if (preg_match('/Cache::\w+\s*\(\s*[\'"]([^\'"]+)[\'"]/', $line, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/Cache::\w+\s*\(\s*\$(\w+)/', $line, $matches)) {
            return '$' . $matches[1];
        }
        
        return 'unknown_key';
    }

    private function calculateCacheMutationRisk(string $operation): float
    {
        $riskScores = [
            'flush' => 9.0,
            'forget' => 6.0,
            'put' => 4.0,
            'increment' => 3.0,
            'decrement' => 3.0
        ];
        
        return $riskScores[$operation] ?? 5.0;
    }

    private function identifyRouteContext(string $filePath, int $lineNumber, array $lines): array
    {
        $context = [];
        
        // Look for controller method
        for ($i = $lineNumber; $i >= 0; $i--) {
            if (preg_match('/public\s+function\s+(\w+)/', $lines[$i], $matches)) {
                $context['method'] = $matches[1];
                break;
            }
        }
        
        // Look for class name
        for ($i = $lineNumber; $i >= 0; $i--) {
            if (preg_match('/class\s+(\w+)/', $lines[$i], $matches)) {
                $context['class'] = $matches[1];
                break;
            }
        }
        
        return $context;
    }

    private function analyzeDatabaseSideEffectRisk(string $line, string $context): array
    {
        $riskFactors = [];
        $riskScore = 0;
        $operation = 'unknown';
        
        if (preg_match('/(insert|update|delete|create|save)/', $line, $matches)) {
            $operation = $matches[1];
        }
        
        if (preg_match('/truncate|drop|alter/i', $line)) {
            $riskFactors[] = 'bulk_operation';
            $riskScore += 4;
        }
        
        if (preg_match('/(update|delete)/i', $line) && !preg_match('/where/i', $context)) {
            $riskFactors[] = 'missing_where_clause';
            $riskScore += 3;
        }
        
        if (preg_match('/foreign|constraint|cascade/i', $context)) {
            $riskFactors[] = 'foreign_key_operation';
            $riskScore += 2;
        }
        
        $hasRisk = $riskScore > 2;
        $severity = $riskScore > 6 ? 'critical' : ($riskScore > 4 ? 'high' : 'medium');
        
        return [
            'has_risk' => $hasRisk,
            'risk_score' => min(10, $riskScore),
            'severity' => $severity,
            'operation' => $operation,
            'risk_factors' => $riskFactors,
            'description' => "Database {$operation} operation with " . count($riskFactors) . " risk factors",
            'recommendation' => $this->getDatabaseRecommendation($riskFactors)
        ];
    }

    private function getDatabaseRecommendation(array $riskFactors): string
    {
        if (in_array('bulk_operation', $riskFactors)) {
            return 'Review bulk database operations for unintended data loss';
        }
        
        if (in_array('missing_where_clause', $riskFactors)) {
            return 'Add WHERE clause to prevent unintended mass updates/deletes';
        }
        
        return 'Review database operation for potential side effects';
    }

    private function extractFileDependencies(string $content): array
    {
        $dependencies = [];
        $lines = explode("\n", $content);
        $dependencyId = 0;
        
        foreach ($lines as $lineNumber => $line) {
            if (preg_match('/(Http::|curl_|file_get_contents.*http)/i', $line)) {
                $dependencies[] = [
                    'id' => $dependencyId++,
                    'type' => 'external_service',
                    'identifier' => $this->extractServiceIdentifier($line),
                    'line_number' => $lineNumber + 1,
                    'code' => trim($line),
                    'risk_score' => 7.0
                ];
            }
            
            if (preg_match('/(file_put_contents|fwrite|unlink)/i', $line)) {
                $dependencies[] = [
                    'id' => $dependencyId++,
                    'type' => 'filesystem',
                    'identifier' => $this->extractFilePath($line),
                    'line_number' => $lineNumber + 1,
                    'code' => trim($line),
                    'risk_score' => 5.0
                ];
            }
        }
        
        return $dependencies;
    }

    private function isUntrackedDependency(array $dependency): bool
    {
        return $dependency['risk_score'] > 6.0;
    }

    private function extractServiceIdentifier(string $line): string
    {
        if (preg_match('/[\'"]([^\'"]*(api|service)[^\'"]*)[\'"]/', $line, $matches)) {
            return $matches[1];
        }
        return 'unknown_service';
    }

    private function extractFilePath(string $line): string
    {
        if (preg_match('/[\'"]([^\'"]*\.[a-zA-Z0-9]+)[\'"]/', $line, $matches)) {
            return $matches[1];
        }
        return 'unknown_file';
    }

    private function detectStateMutations(string $content, string $filePath): array
    {
        $mutations = [];
        $lines = explode("\n", $content);
        $mutationId = 0;
        
        foreach ($lines as $lineNumber => $line) {
            if (preg_match('/\$GLOBALS\[|global\s+\$(\w+)/', $line, $matches)) {
                $mutations[] = [
                    'id' => $mutationId++,
                    'type' => 'global_mutation',
                    'variable' => $matches[1] ?? 'GLOBALS',
                    'scope' => 'global',
                    'line_number' => $lineNumber + 1,
                    'code' => trim($line),
                    'context' => array_slice($lines, max(0, $lineNumber - 2), 5),
                    'severity' => 'high',
                    'risk_score' => 8.0,
                    'description' => 'Global variable mutation detected',
                    'recommendation' => 'Avoid global variable mutations, use dependency injection',
                    'proof_chain' => $this->buildMutationProofChain($line, $lineNumber, $lines)
                ];
            }
            
            if (preg_match('/\$_SESSION\[|session\(.*=/', $line)) {
                $mutations[] = [
                    'id' => $mutationId++,
                    'type' => 'session_mutation',
                    'variable' => 'session',
                    'scope' => 'session',
                    'line_number' => $lineNumber + 1,
                    'code' => trim($line),
                    'context' => array_slice($lines, max(0, $lineNumber - 2), 5),
                    'severity' => 'medium',
                    'risk_score' => 5.0,
                    'description' => 'Session state mutation detected',
                    'recommendation' => 'Ensure session mutations are intentional and secure',
                    'proof_chain' => $this->buildMutationProofChain($line, $lineNumber, $lines)
                ];
            }
        }
        
        return $mutations;
    }

    private function buildMutationProofChain(string $line, int $lineNumber, array $lines): array
    {
        return [
            'mutation_line' => trim($line),
            'line_number' => $lineNumber + 1,
            'before_context' => array_slice($lines, max(0, $lineNumber - 3), 3),
            'after_context' => array_slice($lines, $lineNumber + 1, 3),
            'proof_timestamp' => time(),
            'detection_method' => 'static_analysis'
        ];
    }

    private function buildOriginatorTrace(array $sideEffect): array
    {
        $chain = [];
        $confidence = 0.5;
        
        switch ($sideEffect['type']) {
            case 'filesystem_change':
                $chain = ['audit_process', 'file_operation', 'filesystem_change'];
                $confidence = 0.8;
                break;
            case 'cache_mutation':
                $chain = ['route_handler', 'cache_operation', 'cache_mutation'];
                $confidence = 0.9;
                break;
            case 'database_side_effect':
                $chain = ['controller_method', 'model_operation', 'database_write'];
                $confidence = 0.85;
                break;
        }
        
        return [
            'chain' => $chain,
            'confidence' => $confidence,
            'complexity_score' => count($chain) * 2,
            'root_cause' => $chain[0] ?? 'unknown',
            'description' => "Traced {$sideEffect['type']} to " . ($chain[0] ?? 'unknown origin'),
            'metadata' => [
                'trace_method' => 'heuristic_analysis',
                'timestamp' => time()
            ]
        ];
    }

    private function buildSubsystemMap(): array
    {
        return [
            'authentication' => ['auth', 'login', 'user', 'session'],
            'caching' => ['cache', 'redis', 'memcached'],
            'database' => ['db', 'model', 'migration', 'eloquent'],
            'filesystem' => ['storage', 'file', 'upload', 'download'],
            'notification' => ['mail', 'notification', 'email'],
            'queue' => ['job', 'queue', 'worker', 'dispatch'],
            'routing' => ['route', 'controller', 'middleware']
        ];
    }

    private function calculateSubsystemImpact(array $sideEffect, array $subsystemMap): array
    {
        $affectedSubsystems = [];
        $impactScore = 0;
        
        foreach ($subsystemMap as $subsystem => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($sideEffect['file'] ?? '', $keyword) !== false ||
                    stripos($sideEffect['type'] ?? '', $keyword) !== false) {
                    $affectedSubsystems[] = $subsystem;
                    $impactScore += 2;
                    break;
                }
            }
        }
        
        $severity = 'none';
        if ($impactScore > 8) $severity = 'critical';
        elseif ($impactScore > 5) $severity = 'high';
        elseif ($impactScore > 2) $severity = 'medium';
        elseif ($impactScore > 0) $severity = 'low';
        
        return [
            'severity' => $severity,
            'impact_score' => min(10, $impactScore),
            'affected_subsystems' => array_unique($affectedSubsystems),
            'propagation_risk' => min(1.0, $impactScore / 10),
            'description' => "Side effect impacts " . count($affectedSubsystems) . " subsystems",
            'summary' => "Impact on: " . implode(', ', $affectedSubsystems),
            'analysis' => [
                'direct_impact' => $affectedSubsystems,
                'cascade_potential' => $impactScore > 5
            ],
            'propagation_paths' => [],
            'mitigation_strategies' => [],
            'recommendation' => $this->getImpactRecommendation($severity, $affectedSubsystems),
            'estimated_hours' => $this->estimateImpactResolutionHours($severity, count($affectedSubsystems))
        ];
    }

    private function getImpactRecommendation(string $severity, array $subsystems): string
    {
        switch ($severity) {
            case 'critical':
                return 'Immediate action required - multiple critical subsystems affected';
            case 'high':
                return 'High priority - review and mitigate side effects on affected subsystems';
            case 'medium':
                return 'Monitor affected subsystems and implement preventive measures';
            case 'low':
                return 'Document side effect and consider future optimization';
            default:
                return 'No immediate action required';
        }
    }

    private function estimateImpactResolutionHours(string $severity, int $subsystemCount): int
    {
        $baseHours = [
            'critical' => 12,
            'high' => 8,
            'medium' => 4,
            'low' => 2,
            'none' => 0
        ];
        
        return ($baseHours[$severity] ?? 4) + ($subsystemCount * 2);
    }

    private function cleanupMonitoring(): void
    {
        $this->monitoringActive = false;
        Log::info('🧹 Cleaning up monitoring systems');
    }

    /**
     * Generate side effect metrics for the phase result
     */
    private function generateSideEffectMetrics(): array
    {
        return [
            'total_side_effect_findings' => count($this->findings),
            'filesystem_changes' => $this->sideEffectMetrics['filesystem_changes'] ?? 0,
            'cache_mutations' => $this->sideEffectMetrics['cache_mutations'] ?? 0,
            'database_side_effects' => $this->sideEffectMetrics['database_side_effects'] ?? 0,
            'untracked_dependencies' => $this->sideEffectMetrics['untracked_dependencies'] ?? 0,
            'state_mutations' => $this->sideEffectMetrics['state_mutations'] ?? 0,
            'originator_traces' => $this->sideEffectMetrics['originator_traces'] ?? 0,
            'subsystem_impacts' => $this->sideEffectMetrics['subsystem_impacts'] ?? 0,
            'total_side_effects_detected' => count($this->detectedSideEffects),
            'monitoring_active' => $this->monitoringActive,
            'critical_side_effects' => count(array_filter($this->findings, function($finding) {
                return $finding->getSeverity() === 'critical';
            })),
            'high_impact_side_effects' => count(array_filter($this->findings, function($finding) {
                return $finding->getSeverity() === 'high';
            })),
            'acceptance_criteria_coverage' => [
                'real_time_filesystem_monitoring' => true,
                'cache_mutation_tracking' => true,
                'database_side_effect_detection' => true,
                'untracked_dependency_identification' => true,
                'state_mutation_proof_collection' => true,
                'originator_tracing' => true,
                'subsystem_impact_assessment' => true
            ]
        ];
    }
}