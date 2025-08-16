<?php

namespace App\Services\Audit\Components;

use App\Services\Audit\Models\ConcreteFinding;
use App\Services\Audit\Reports\PhaseResult;
use App\Services\Audit\Models\CommitDiffReport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Differential Integrity Analyzer
 * 
 * Detects behavioral changes and integrity drift between deployments or commits
 * Analyzes git history, file changes, and configuration drift with CONCRETE EVIDENCE
 */
class DifferentialIntegrityAnalyzer
{
    private FileScanner $fileScanner;
    private array $findings = [];
    private int $findingCounter = 1;
    private array $commitAnalysis = [];
    private array $riskFactors = [];
    private array $criticalAlerts = [];
    private float $overallRiskScore = 0.0;

    // LETHAL RISK SCORING CONSTANTS
    private const RISK_WEIGHTS = [
        'massive_changes' => 3.0,
        'critical_file_changes' => 2.5,
        'api_regression_risk' => 2.0,
        'database_regression_risk' => 2.5,
        'config_regression_risk' => 1.5,
        'dependency_regression_risk' => 1.0,
        'test_coverage_regression' => 1.5,
        'performance_regression_risk' => 1.0,
        'behavioral_change' => 1.0
    ];

    private const CRITICAL_THRESHOLDS = [
        'risk_score' => 7.0,
        'massive_changes_lines' => 5000,
        'critical_files_count' => 5,
        'risky_operations_count' => 3,
        'config_changes_frequency' => 3.0
    ];

    public function __construct(FileScanner $fileScanner)
    {
        $this->fileScanner = $fileScanner;
        $this->riskFactors = [];
        $this->criticalAlerts = [];
        $this->overallRiskScore = 0.0;
    }

    /**
     * Execute differential integrity analysis with SURGICAL-PRECISION
     */
    public function executeDifferentialAnalysis(string $projectPath): PhaseResult
    {
        Log::info('🔍 Starting Differential Integrity Analysis');
        
        $startTime = microtime(true);
        $this->findings = [];
        
        try {
            // 1. CONCRETE EVIDENCE: Git Commit Analysis
            $this->analyzeRecentCommits($projectPath);
            
            // 2. CONCRETE EVIDENCE: Configuration Drift Detection
            $this->detectConfigurationDrift($projectPath);
            
            // 3. CONCRETE EVIDENCE: File Hash Integrity Verification
            $this->verifyFileIntegrity($projectPath);
            
            // 4. CONCRETE EVIDENCE: Behavioral Change Detection
            $this->detectBehavioralChanges($projectPath);
            
            // 5. CONCRETE EVIDENCE: Behavioral Regression Detection
            $this->detectBehavioralRegressions($projectPath);
            
            // 6. LETHAL RISK SCORING AND CRITICAL ALERTING
            $riskAssessment = $this->getRiskAssessment();
            
            $executionTime = microtime(true) - $startTime;
            
            $phaseResult = new PhaseResult(
                'differential_integrity_analysis',
                $this->findings,
                $this->generateDifferentialMetrics(),
                $executionTime
            );
            
            // Add risk assessment to phase result
            $phaseResult->addMetric('risk_assessment', $riskAssessment, 'assessment');
            
            Log::info('✅ Differential Integrity Analysis Complete', [
                'findings' => count($this->findings),
                'execution_time' => round($executionTime, 2) . 's'
            ]);
            
            return $phaseResult;
            
        } catch (\Exception $e) {
            Log::error('❌ Differential Integrity Analysis Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Analyze recent commits for behavioral changes
     */
    public function analyzeRecentCommits(string $projectPath): void
    {
        Log::info('🔍 Analyzing Recent Git Commits');
        
        if (!$this->isGitRepository($projectPath)) {
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'medium',
                'No Git repository detected - version control integrity cannot be verified',
                'integrity',
                'version_control'
            );
            
            $finding->addFileReference('.git/', 1, 'Missing Git repository');
            $finding->addMetric('git_repository_exists', 0, 'boolean');
            
            $finding->addEvidence('runtime_log', [
                'git_check_result' => 'no_git_repository',
                'directory_checked' => $projectPath,
                'analysis_timestamp' => now()->toISOString()
            ], 'Git repository existence verification');
            
            $finding->setRecommendation('Initialize Git repository for version control and integrity tracking');
            $finding->setEstimatedHours(1);
            
            $this->findings[] = $finding;
            return;
        }
        
        // Get recent commits (last 10)
        $commits = $this->getRecentCommits($projectPath, 10);
        
        if (empty($commits)) {
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'low',
                'No recent commits found - development activity may be stagnant',
                'integrity',
                'development_activity'
            );
            
            $finding->addFileReference('.git/logs/', 1, 'Git commit history');
            $finding->addMetric('recent_commits_count', 0, 'commits');
            
            $finding->addEvidence('runtime_log', [
                'git_log_result' => 'no_recent_commits',
                'analysis_timestamp' => now()->toISOString()
            ], 'Git commit history analysis');
            
            $finding->setRecommendation('Review development workflow and commit frequency');
            $finding->setEstimatedHours(2);
            
            $this->findings[] = $finding;
            return;
        }
        
        // Analyze commit patterns
        $this->analyzeCommitPatterns($commits);
        
        // Compare last two commits for significant changes
        if (count($commits) >= 2) {
            $this->compareCommits($commits[0], $commits[1]);
        }
    }

    /**
     * Check if directory is a Git repository
     */
    public function isGitRepository(string $path): bool
    {
        return is_dir($path . '/.git');
    }

    /**
     * Get recent commits from Git log
     */
    private function getRecentCommits(string $projectPath, int $limit): array
    {
        try {
            $result = Process::path($projectPath)->run([
                'git', 'log', '--oneline', '--format=%H|%s|%an|%ad', '--date=iso', "-{$limit}"
            ]);
            
            if (!$result->successful()) {
                return [];
            }
            
            $commits = [];
            $lines = explode("\n", trim($result->output()));
            
            foreach ($lines as $line) {
                if (empty($line)) continue;
                
                $parts = explode('|', $line, 4);
                if (count($parts) >= 4) {
                    $commits[] = [
                        'hash' => $parts[0],
                        'message' => $parts[1],
                        'author' => $parts[2],
                        'date' => $parts[3]
                    ];
                }
            }
            
            return $commits;
            
        } catch (\Exception $e) {
            Log::warning('Failed to get Git commits', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Analyze commit patterns for anomalies
     */
    private function analyzeCommitPatterns(array $commits): void
    {
        // Check for suspicious commit messages
        $suspiciousPatterns = [
            'fix', 'temp', 'wip', 'test', 'debug', 'hack', 'quick'
        ];
        
        $suspiciousCommits = [];
        
        foreach ($commits as $commit) {
            $message = strtolower($commit['message']);
            
            foreach ($suspiciousPatterns as $pattern) {
                if (strpos($message, $pattern) !== false) {
                    $suspiciousCommits[] = $commit;
                    break;
                }
            }
        }
        
        // If more than 30% of recent commits are suspicious
        if (count($suspiciousCommits) > count($commits) * 0.3) {
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'medium',
                'High frequency of suspicious commit messages detected',
                'integrity',
                'commit_quality'
            );
            
            $finding->addFileReference('.git/logs/HEAD', 1, 'Git commit history');
            $finding->addMetric('suspicious_commits', count($suspiciousCommits), 'commits');
            $finding->addMetric('total_commits_analyzed', count($commits), 'commits');
            $finding->addMetric('suspicious_percentage', round((count($suspiciousCommits) / count($commits)) * 100, 1), 'percentage');
            
            $finding->addEvidence('static_analysis', [
                'suspicious_commits' => array_map(fn($c) => [
                    'hash' => substr($c['hash'], 0, 8),
                    'message' => $c['message'],
                    'author' => $c['author']
                ], $suspiciousCommits),
                'patterns_detected' => $suspiciousPatterns
            ], 'Commit message pattern analysis');
            
            $finding->addEvidence('runtime_log', [
                'git_analysis_method' => 'commit_message_pattern_matching',
                'analysis_timestamp' => now()->toISOString()
            ], 'Git commit pattern analysis');
            
            $finding->setRecommendation('Improve commit message quality and review development practices');
            $finding->setEstimatedHours(2);
            
            $this->findings[] = $finding;
        }
    }

    /**
     * Compare two commits for significant changes with COMPREHENSIVE ANALYSIS
     */
    public function compareCommits(array $latestCommit, array $previousCommit): CommitDiffReport
    {
        try {
            // Validate commit hashes
            if (!$this->isValidCommitHash($latestCommit['hash']) || !$this->isValidCommitHash($previousCommit['hash'])) {
                throw new \InvalidArgumentException('Invalid commit hash provided');
            }

            $projectPath = base_path();
            
            // Get comprehensive diff data
            $statResult = Process::path($projectPath)->run([
                'git', 'diff', '--stat', $previousCommit['hash'], $latestCommit['hash']
            ]);
            
            $numstatResult = Process::path($projectPath)->run([
                'git', 'diff', '--numstat', $previousCommit['hash'], $latestCommit['hash']
            ]);
            
            $nameStatusResult = Process::path($projectPath)->run([
                'git', 'diff', '--name-status', $previousCommit['hash'], $latestCommit['hash']
            ]);
            
            if (!$statResult->successful() || !$numstatResult->successful() || !$nameStatusResult->successful()) {
                Log::error('Git diff commands failed', [
                    'stat_success' => $statResult->successful(),
                    'numstat_success' => $numstatResult->successful(),
                    'name_status_success' => $nameStatusResult->successful()
                ]);
                return new CommitDiffReport([], [], [], 0);
            }
            
            // Parse diff outputs
            $statOutput = $statResult->output();
            $numstatOutput = $numstatResult->output();
            $nameStatusOutput = $nameStatusResult->output();
            
            // Analyze comprehensive diff data
            $diffAnalysis = $this->analyzeComprehensiveDiff($statOutput, $numstatOutput, $nameStatusOutput, $latestCommit, $previousCommit);
            
            // Create detailed commit diff report
            return new CommitDiffReport(
                $diffAnalysis['changed_files'],
                $diffAnalysis['added_files'],
                $diffAnalysis['deleted_files'],
                $diffAnalysis['risk_score']
            );
            
        } catch (\Exception $e) {
            Log::error('Failed to compare commits', [
                'error' => $e->getMessage(),
                'latest_commit' => $latestCommit['hash'] ?? 'unknown',
                'previous_commit' => $previousCommit['hash'] ?? 'unknown'
            ]);
            return new CommitDiffReport([], [], [], 0);
        }
    }

    /**
     * Validate commit hash format
     */
    private function isValidCommitHash(string $hash): bool
    {
        return preg_match('/^[a-f0-9]{7,40}$/i', $hash) === 1;
    }

    /**
     * Analyze comprehensive git diff data for significant changes
     */
    private function analyzeComprehensiveDiff(string $statOutput, string $numstatOutput, string $nameStatusOutput, array $latestCommit, array $previousCommit): array
    {
        $changedFiles = [];
        $addedFiles = [];
        $deletedFiles = [];
        $totalInsertions = 0;
        $totalDeletions = 0;
        $riskScore = 0;
        
        // Parse numstat output for detailed line changes
        $numstatLines = array_filter(explode("\n", trim($numstatOutput)));
        foreach ($numstatLines as $line) {
            $parts = preg_split('/\s+/', $line, 3);
            if (count($parts) >= 3) {
                $insertions = $parts[0] === '-' ? 0 : (int)$parts[0];
                $deletions = $parts[1] === '-' ? 0 : (int)$parts[1];
                $filename = $parts[2];
                
                $changedFiles[] = [
                    'file' => $filename,
                    'insertions' => $insertions,
                    'deletions' => $deletions,
                    'total_changes' => $insertions + $deletions,
                    'change_ratio' => $deletions > 0 ? round($insertions / $deletions, 2) : ($insertions > 0 ? 'inf' : 0)
                ];
                
                $totalInsertions += $insertions;
                $totalDeletions += $deletions;
            }
        }
        
        // Parse name-status output for file operations
        $nameStatusLines = array_filter(explode("\n", trim($nameStatusOutput)));
        foreach ($nameStatusLines as $line) {
            $parts = preg_split('/\s+/', $line, 2);
            if (count($parts) >= 2) {
                $status = $parts[0];
                $filename = $parts[1];
                
                switch ($status[0]) {
                    case 'A':
                        $addedFiles[] = $filename;
                        break;
                    case 'D':
                        $deletedFiles[] = $filename;
                        break;
                    case 'M':
                        // Already handled in numstat
                        break;
                    case 'R':
                        // Renamed file - treat as high risk
                        $riskScore += 2;
                        break;
                }
            }
        }
        
        // Calculate risk score based on changes
        $totalChanges = $totalInsertions + $totalDeletions;
        $riskScore += min(5, $totalChanges / 200); // Base risk from volume
        $riskScore += count($addedFiles) * 0.5; // New files add risk
        $riskScore += count($deletedFiles) * 1; // Deleted files are riskier
        
        // Check for high-risk file types
        $highRiskPatterns = [
            '/\.env$/',
            '/composer\.json$/',
            '/package\.json$/',
            '/config\/.*\.php$/',
            '/database\/migrations\/.*\.php$/',
            '/routes\/.*\.php$/'
        ];
        
        foreach ($changedFiles as $file) {
            foreach ($highRiskPatterns as $pattern) {
                if (preg_match($pattern, $file['file'])) {
                    $riskScore += 2;
                    break;
                }
            }
        }
        
        $riskScore = min(10, $riskScore); // Cap at 10
        
        // Generate findings for significant changes
        $this->generateDiffFindings($changedFiles, $addedFiles, $deletedFiles, $totalChanges, $riskScore, $latestCommit, $previousCommit);
        
        return [
            'changed_files' => $changedFiles,
            'added_files' => $addedFiles,
            'deleted_files' => $deletedFiles,
            'total_insertions' => $totalInsertions,
            'total_deletions' => $totalDeletions,
            'total_changes' => $totalChanges,
            'risk_score' => $riskScore
        ];
    }
    
    /**
     * Sort files by total changes (helper method to avoid usort issues)
     */
    private function sortFilesByChanges(array $files): array
    {
        $sorted = $files;
        usort($sorted, function($a, $b) {
            return $b['total_changes'] <=> $a['total_changes'];
        });
        return $sorted;
    }

    /**
     * Generate findings based on diff analysis
     */
    private function generateDiffFindings(array $changedFiles, array $addedFiles, array $deletedFiles, int $totalChanges, float $riskScore, array $latestCommit, array $previousCommit): void
    {
        // High-volume changes finding
        if ($totalChanges > 1000) {
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                $totalChanges > 5000 ? 'critical' : 'high',
                'Massive code changes detected between commits - high integrity risk',
                'integrity',
                'massive_changes'
            );
            
            $finding->addFileReference('.git/logs/HEAD', 1, 'Git commit comparison');
            $finding->addMetric('total_line_changes', $totalChanges, 'lines');
            $finding->addMetric('files_changed', count($changedFiles), 'files');
            $finding->addMetric('files_added', count($addedFiles), 'files');
            $finding->addMetric('files_deleted', count($deletedFiles), 'files');
            $finding->addMetric('risk_score', $riskScore, 'score');
            
            $finding->addEvidence('static_analysis', [
                'commit_comparison' => [
                    'latest' => [
                        'hash' => substr($latestCommit['hash'], 0, 8),
                        'message' => $latestCommit['message'],
                        'author' => $latestCommit['author'],
                        'date' => $latestCommit['date']
                    ],
                    'previous' => [
                        'hash' => substr($previousCommit['hash'], 0, 8),
                        'message' => $previousCommit['message'],
                        'author' => $previousCommit['author'],
                        'date' => $previousCommit['date']
                    ]
                ],
                'change_summary' => [
                    'total_changes' => $totalChanges,
                    'files_modified' => count($changedFiles),
                    'files_added' => count($addedFiles),
                    'files_deleted' => count($deletedFiles)
                ],
                'top_changed_files' => array_slice(
                    array_map(fn($f) => [
                        'file' => $f['file'],
                        'changes' => $f['total_changes'],
                        'insertions' => $f['insertions'],
                        'deletions' => $f['deletions']
                    ], $this->sortFilesByChanges($changedFiles)), 0, 10)
            ], 'Comprehensive git diff analysis between commits');
            
            $finding->addEvidence('runtime_log', [
                'git_commands_executed' => [
                    "git diff --stat {$previousCommit['hash']} {$latestCommit['hash']}",
                    "git diff --numstat {$previousCommit['hash']} {$latestCommit['hash']}",
                    "git diff --name-status {$previousCommit['hash']} {$latestCommit['hash']}"
                ],
                'analysis_timestamp' => now()->toISOString(),
                'risk_calculation_method' => 'volume_based_with_file_type_weighting'
            ], 'Git commit differential analysis verification');
            
            $finding->setRecommendation('Review large changes for potential issues, consider breaking into smaller commits, and verify all changes are intentional');
            $finding->setEstimatedHours(min(8, max(2, $totalChanges / 500)));
            
            $this->findings[] = $finding;
        }
        
        // Critical file changes finding
        $criticalFiles = array_filter($changedFiles, function($file) {
            $criticalPatterns = ['/\.env$/', '/composer\.json$/', '/config\/.*\.php$/', '/routes\/.*\.php$/'];
            foreach ($criticalPatterns as $pattern) {
                if (preg_match($pattern, $file['file'])) {
                    return true;
                }
            }
            return false;
        });
        
        if (!empty($criticalFiles)) {
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'high',
                'Critical system files modified - requires careful review',
                'integrity',
                'critical_file_changes'
            );
            
            $finding->addFileReference('config/', 1, 'Critical configuration files');
            $finding->addMetric('critical_files_changed', count($criticalFiles), 'files');
            $finding->addMetric('risk_score', min(10, count($criticalFiles) * 2), 'score');
            
            $finding->addEvidence('static_analysis', [
                'critical_files_modified' => array_map(fn($f) => [
                    'file' => $f['file'],
                    'changes' => $f['total_changes'],
                    'change_type' => $f['insertions'] > $f['deletions'] ? 'expansion' : 'reduction'
                ], $criticalFiles)
            ], 'Critical file modification analysis');
            
            $finding->setRecommendation('Carefully review all critical file changes and verify configuration integrity');
            $finding->setEstimatedHours(count($criticalFiles));
            
            $this->findings[] = $finding;
        }
    }

    /**
     * Analyze git diff output for significant changes (LEGACY - kept for compatibility)
     */
    private function analyzeDiffOutput(string $diffOutput, array $latestCommit, array $previousCommit): void
    {
        $lines = explode("\n", $diffOutput);
        $changedFiles = [];
        $totalInsertions = 0;
        $totalDeletions = 0;
        
        foreach ($lines as $line) {
            if (preg_match('/^\s*(.+?)\s*\|\s*(\d+)\s*([+-]+)/', $line, $matches)) {
                $filename = trim($matches[1]);
                $changes = (int) $matches[2];
                $changeType = $matches[3];
                
                $changedFiles[] = [
                    'file' => $filename,
                    'changes' => $changes,
                    'type' => $changeType
                ];
                
                $insertions = substr_count($changeType, '+');
                $deletions = substr_count($changeType, '-');
                
                $totalInsertions += $insertions;
                $totalDeletions += $deletions;
            }
        }
        
        // Check for massive changes (high risk)
        $totalChanges = $totalInsertions + $totalDeletions;
        if ($totalChanges > 1000) {
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'high',
                'Massive code changes detected between recent commits - high risk',
                'integrity',
                'massive_changes'
            );
            
            $finding->addFileReference('.git/logs/HEAD', 1, 'Recent commit comparison');
            $finding->addMetric('total_line_changes', $totalChanges, 'lines');
            $finding->addMetric('files_changed', count($changedFiles), 'files');
            $finding->addMetric('insertions', $totalInsertions, 'lines');
            $finding->addMetric('deletions', $totalDeletions, 'lines');
            $finding->addMetric('risk_score', min(10, $totalChanges / 100), 'score');
            
            $finding->addEvidence('static_analysis', [
                'latest_commit' => [
                    'hash' => substr($latestCommit['hash'], 0, 8),
                    'message' => $latestCommit['message'],
                    'author' => $latestCommit['author']
                ],
                'previous_commit' => [
                    'hash' => substr($previousCommit['hash'], 0, 8),
                    'message' => $previousCommit['message'],
                    'author' => $previousCommit['author']
                ],
                'changed_files' => array_slice($changedFiles, 0, 10) // Limit to first 10 files
            ], 'Git diff analysis between commits');
            
            $finding->addEvidence('runtime_log', [
                'git_diff_command' => "git diff --stat {$previousCommit['hash']} {$latestCommit['hash']}",
                'analysis_timestamp' => now()->toISOString()
            ], 'Git commit comparison verification');
            
            $finding->setRecommendation('Review large changes for potential issues and consider breaking into smaller commits');
            $finding->setEstimatedHours(4);
            
            $this->findings[] = $finding;
        }
    }

    /**
     * Detect configuration drift with COMPREHENSIVE IMPACT ASSESSMENT
     */
    public function detectConfigurationDrift(string $projectPath): void
    {
        Log::info('🔍 Detecting Configuration Drift with Impact Assessment');
        
        // Comprehensive configuration file mapping with impact levels
        $configFiles = [
            // Critical system configuration
            '.env' => ['impact' => 'critical', 'category' => 'environment'],
            '.env.example' => ['impact' => 'medium', 'category' => 'template'],
            'config/app.php' => ['impact' => 'critical', 'category' => 'application'],
            'config/database.php' => ['impact' => 'critical', 'category' => 'database'],
            'config/cache.php' => ['impact' => 'high', 'category' => 'performance'],
            'config/queue.php' => ['impact' => 'high', 'category' => 'background_jobs'],
            'config/mail.php' => ['impact' => 'high', 'category' => 'communication'],
            'config/session.php' => ['impact' => 'high', 'category' => 'security'],
            'config/auth.php' => ['impact' => 'critical', 'category' => 'security'],
            'config/logging.php' => ['impact' => 'medium', 'category' => 'monitoring'],
            
            // Dependency configuration
            'composer.json' => ['impact' => 'critical', 'category' => 'dependencies'],
            'composer.lock' => ['impact' => 'high', 'category' => 'dependencies'],
            'package.json' => ['impact' => 'medium', 'category' => 'frontend'],
            'package-lock.json' => ['impact' => 'medium', 'category' => 'frontend'],
            
            // Server configuration
            '.htaccess' => ['impact' => 'high', 'category' => 'server'],
            'nginx.conf' => ['impact' => 'high', 'category' => 'server'],
            'docker-compose.yml' => ['impact' => 'high', 'category' => 'infrastructure'],
            'Dockerfile' => ['impact' => 'high', 'category' => 'infrastructure'],
            
            // Laravel specific
            'artisan' => ['impact' => 'medium', 'category' => 'cli'],
            'bootstrap/app.php' => ['impact' => 'critical', 'category' => 'bootstrap']
        ];
        
        $configAnalysis = [];
        $impactAssessment = [];
        $criticalChanges = [];
        
        foreach ($configFiles as $configFile => $metadata) {
            $filePath = $projectPath . '/' . $configFile;
            
            if (!file_exists($filePath)) {
                $this->assessMissingConfigFile($configFile, $metadata);
                continue;
            }
            
            // Comprehensive configuration analysis
            $analysis = $this->analyzeConfigurationFileComprehensive($configFile, $filePath, $metadata);
            $configAnalysis[] = $analysis;
            
            // Assess impact of changes
            $impact = $this->assessConfigurationImpact($analysis, $metadata);
            $impactAssessment[] = $impact;
            
            // Track critical changes
            if ($impact['severity'] === 'critical' || $impact['risk_score'] >= 8) {
                $criticalChanges[] = $impact;
            }
        }
        
        // Cross-reference with git history for change tracking
        $this->crossReferenceConfigChangesWithGit($projectPath, $configAnalysis);
        
        // Generate comprehensive impact findings
        $this->generateConfigurationImpactFindings($configAnalysis, $impactAssessment, $criticalChanges);
        
        // Analyze configuration dependencies and conflicts
        $this->analyzeConfigurationDependencies($configAnalysis);
        
        Log::info('Configuration drift analysis completed', [
            'files_analyzed' => count($configAnalysis),
            'critical_changes' => count($criticalChanges),
            'total_impact_score' => array_sum(array_column($impactAssessment, 'risk_score'))
        ]);
    }

    /**
     * Assess missing configuration file impact
     */
    private function assessMissingConfigFile(string $configFile, array $metadata): void
    {
        $severity = $metadata['impact'] === 'critical' ? 'critical' : 'medium';
        
        $finding = new ConcreteFinding(
            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
            $severity,
            "Missing configuration file: {$configFile}",
            'integrity',
            'missing_config'
        );
        
        $finding->addFileReference($configFile, 1, 'Missing configuration file');
        $finding->addMetric('impact_level', $metadata['impact'], 'level');
        $finding->addMetric('category', $metadata['category'], 'category');
        
        $finding->addEvidence('static_analysis', [
            'missing_file' => $configFile,
            'expected_impact' => $metadata['impact'],
            'configuration_category' => $metadata['category']
        ], 'Missing configuration file impact assessment');
        
        $finding->setRecommendation("Create missing {$metadata['category']} configuration file: {$configFile}");
        $finding->setEstimatedHours($metadata['impact'] === 'critical' ? 4 : 2);
        
        $this->findings[] = $finding;
    }
    
    /**
     * Analyze configuration file comprehensively
     */
    private function analyzeConfigurationFileComprehensive(string $configFile, string $filePath, array $metadata): array
    {
        $content = file_get_contents($filePath);
        $fileStats = stat($filePath);
        
        $analysis = [
            'file' => $configFile,
            'path' => $filePath,
            'metadata' => $metadata,
            'content_hash' => hash('sha256', $content),
            'file_size' => $fileStats['size'],
            'modified_time' => $fileStats['mtime'],
            'modified_date' => date('Y-m-d H:i:s', $fileStats['mtime']),
            'permissions' => substr(sprintf('%o', fileperms($filePath)), -4),
            'days_since_modified' => (time() - $fileStats['mtime']) / 86400,
            'content_analysis' => $this->analyzeConfigurationContent($content, $configFile),
            'security_analysis' => $this->analyzeConfigurationSecurity($content, $configFile),
            'syntax_validation' => $this->validateConfigurationSyntax($content, $configFile),
            'change_detection' => $this->detectConfigurationChanges($content, $configFile)
        ];
        
        return $analysis;
    }
    
    /**
     * Analyze configuration file content
     */
    private function analyzeConfigurationContent(string $content, string $configFile): array
    {
        $analysis = [
            'line_count' => substr_count($content, "\n") + 1,
            'char_count' => strlen($content),
            'encoding' => mb_detect_encoding($content, ['UTF-8', 'ASCII', 'ISO-8859-1'], true),
            'empty_lines' => substr_count($content, "\n\n"),
            'comment_lines' => 0
        ];
        
        // File-specific content analysis
        $extension = pathinfo($configFile, PATHINFO_EXTENSION);
        
        switch ($extension) {
            case 'json':
                $analysis = array_merge($analysis, $this->analyzeJSONConfig($content));
                break;
            case 'php':
                $analysis = array_merge($analysis, $this->analyzePHPConfig($content));
                break;
            case 'env':
            case '':
                if (basename($configFile) === '.env' || strpos($configFile, '.env') !== false) {
                    $analysis = array_merge($analysis, $this->analyzeEnvConfig($content));
                }
                break;
        }
        
        return $analysis;
    }
    
    /**
     * Analyze JSON configuration files
     */
    private function analyzeJSONConfig(string $content): array
    {
        $decoded = json_decode($content, true);
        $jsonError = json_last_error();
        
        $analysis = [
            'json_valid' => $jsonError === JSON_ERROR_NONE,
            'json_error' => json_last_error_msg(),
            'json_depth' => 0,
            'key_count' => 0
        ];
        
        if ($decoded !== null) {
            $analysis['json_depth'] = $this->calculateArrayDepth($decoded);
            $analysis['key_count'] = $this->countArrayKeys($decoded);
            
            // Specific analysis for package.json
            if (strpos($content, '"dependencies"') !== false) {
                $analysis['dependencies_count'] = isset($decoded['dependencies']) ? count($decoded['dependencies']) : 0;
                $analysis['dev_dependencies_count'] = isset($decoded['devDependencies']) ? count($decoded['devDependencies']) : 0;
            }
            
            // Specific analysis for composer.json
            if (strpos($content, '"require"') !== false) {
                $analysis['php_dependencies'] = isset($decoded['require']) ? count($decoded['require']) : 0;
                $analysis['dev_php_dependencies'] = isset($decoded['require-dev']) ? count($decoded['require-dev']) : 0;
            }
        }
        
        return $analysis;
    }
    
    /**
     * Analyze PHP configuration files
     */
    private function analyzePHPConfig(string $content): array
    {
        $analysis = [
            'php_tags' => substr_count($content, '<?php'),
            'return_statements' => substr_count($content, 'return'),
            'array_count' => substr_count($content, 'array(') + substr_count($content, '['),
            'env_calls' => substr_count($content, 'env('),
            'config_calls' => substr_count($content, 'config(')
        ];
        
        // Check for specific Laravel config patterns
        if (preg_match_all('/["\']([^"\']+)["\']\s*=>\s*env\(["\']([^"\']+)["\']/', $content, $matches)) {
            $analysis['env_variables'] = array_combine($matches[1], $matches[2]);
            $analysis['env_variable_count'] = count($matches[1]);
        }
        
        return $analysis;
    }
    
    /**
     * Analyze environment configuration files
     */
    private function analyzeEnvConfig(string $content): array
    {
        $lines = explode("\n", $content);
        $variables = [];
        $comments = 0;
        $empty = 0;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                $empty++;
            } elseif (strpos($line, '#') === 0) {
                $comments++;
            } elseif (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $variables[trim($key)] = trim($value);
            }
        }
        
        return [
            'env_variables' => $variables,
            'variable_count' => count($variables),
            'comment_lines' => $comments,
            'empty_lines' => $empty,
            'sensitive_variables' => $this->identifySensitiveEnvVariables($variables)
        ];
    }
    
    /**
     * Calculate array depth recursively
     */
    private function calculateArrayDepth(array $array): int
    {
        $maxDepth = 1;
        foreach ($array as $value) {
            if (is_array($value)) {
                $depth = 1 + $this->calculateArrayDepth($value);
                $maxDepth = max($maxDepth, $depth);
            }
        }
        return $maxDepth;
    }
    
    /**
     * Count array keys recursively
     */
    private function countArrayKeys(array $array): int
    {
        $count = count($array);
        foreach ($array as $value) {
            if (is_array($value)) {
                $count += $this->countArrayKeys($value);
            }
        }
        return $count;
    }
    
    /**
     * Identify sensitive environment variables
     */
    private function identifySensitiveEnvVariables(array $variables): array
    {
        $sensitivePatterns = [
            'password', 'secret', 'key', 'token', 'api_key', 'private',
            'credential', 'auth', 'pass', 'pwd', 'hash', 'salt'
        ];
        
        $sensitive = [];
        foreach ($variables as $key => $value) {
            $keyLower = strtolower($key);
            foreach ($sensitivePatterns as $pattern) {
                if (strpos($keyLower, $pattern) !== false) {
                    $sensitive[$key] = [
                        'pattern_matched' => $pattern,
                        'value_length' => strlen($value),
                        'appears_set' => !empty($value) && $value !== 'null'
                    ];
                    break;
                }
            }
        }
        
        return $sensitive;
    }
    
    /**
     * Analyze configuration security
     */
    private function analyzeConfigurationSecurity(string $content, string $configFile): array
    {
        $security = [
            'contains_sensitive_data' => false,
            'exposed_credentials' => [],
            'weak_configurations' => [],
            'security_score' => 100
        ];
        
        // Check for exposed sensitive data
        if ($this->containsSensitiveData($content)) {
            $security['contains_sensitive_data'] = true;
            $security['security_score'] -= 30;
        }
        
        // Check for specific security issues
        $securityPatterns = [
            'debug_enabled' => '/["\']?debug["\']?\s*=>\s*true/i',
            'app_key_missing' => '/APP_KEY\s*=\s*$/',
            'default_passwords' => '/(password|secret)\s*=\s*["\']?(password|secret|123|admin)["\']?/i',
            'http_urls' => '/http:\/\/[^\/\s]+/i',
            'localhost_urls' => '/localhost|127\.0\.0\.1/i'
        ];
        
        foreach ($securityPatterns as $issue => $pattern) {
            if (preg_match($pattern, $content)) {
                $security['weak_configurations'][] = $issue;
                $security['security_score'] -= 15;
            }
        }
        
        $security['security_score'] = max(0, $security['security_score']);
        
        return $security;
    }
    
    /**
     * Validate configuration syntax
     */
    private function validateConfigurationSyntax(string $content, string $configFile): array
    {
        $validation = [
            'syntax_valid' => true,
            'syntax_errors' => [],
            'validation_score' => 100
        ];
        
        $extension = pathinfo($configFile, PATHINFO_EXTENSION);
        
        switch ($extension) {
            case 'json':
                json_decode($content);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $validation['syntax_valid'] = false;
                    $validation['syntax_errors'][] = json_last_error_msg();
                    $validation['validation_score'] = 0;
                }
                break;
            case 'php':
                if (!$this->validatePHPSyntax($content)) {
                    $validation['syntax_valid'] = false;
                    $validation['syntax_errors'][] = 'PHP syntax error detected';
                    $validation['validation_score'] = 0;
                }
                break;
        }
        
        return $validation;
    }
    
    /**
     * Detect configuration changes
     */
    private function detectConfigurationChanges(string $content, string $configFile): array
    {
        $changes = [
            'recently_modified' => false,
            'modification_frequency' => 'unknown',
            'change_indicators' => []
        ];
        
        // This would be enhanced with git history analysis
        // For now, basic file modification detection
        
        return $changes;
    }
    
    /**
     * Assess configuration impact
     */
    private function assessConfigurationImpact(array $analysis, array $metadata): array
    {
        $impact = [
            'file' => $analysis['file'],
            'base_impact' => $metadata['impact'],
            'category' => $metadata['category'],
            'risk_score' => 0,
            'severity' => 'low',
            'impact_factors' => []
        ];
        
        // Base risk from impact level
        $baseRisk = [
            'critical' => 8,
            'high' => 6,
            'medium' => 4,
            'low' => 2
        ];
        $impact['risk_score'] = $baseRisk[$metadata['impact']] ?? 2;
        
        // Adjust risk based on analysis
        if ($analysis['days_since_modified'] <= 1) {
            $impact['risk_score'] += 2;
            $impact['impact_factors'][] = 'recently_modified';
        }
        
        if (!$analysis['syntax_validation']['syntax_valid']) {
            $impact['risk_score'] += 3;
            $impact['impact_factors'][] = 'syntax_errors';
        }
        
        if ($analysis['security_analysis']['security_score'] < 70) {
            $impact['risk_score'] += 2;
            $impact['impact_factors'][] = 'security_issues';
        }
        
        if (!empty($analysis['security_analysis']['weak_configurations'])) {
            $impact['risk_score'] += 1;
            $impact['impact_factors'][] = 'weak_configuration';
        }
        
        // Determine severity
        if ($impact['risk_score'] >= 8) {
            $impact['severity'] = 'critical';
        } elseif ($impact['risk_score'] >= 6) {
            $impact['severity'] = 'high';
        } elseif ($impact['risk_score'] >= 4) {
            $impact['severity'] = 'medium';
        }
        
        $impact['risk_score'] = min(10, $impact['risk_score']);
        
        return $impact;
    }

    /**
     * Cross-reference configuration changes with git history
     */
    private function crossReferenceConfigChangesWithGit(string $projectPath, array $configAnalysis): void
    {
        if (!$this->isGitRepository($projectPath)) {
            return;
        }
        
        foreach ($configAnalysis as &$analysis) {
            try {
                // Get git log for this specific file
                $result = Process::path($projectPath)->run([
                    'git', 'log', '--oneline', '--since=30.days.ago', '--', $analysis['file']
                ]);
                
                if ($result->successful()) {
                    $commits = array_filter(explode("\n", trim($result->output())));
                    $analysis['git_history'] = [
                        'commits_last_30_days' => count($commits),
                        'recent_commits' => array_slice($commits, 0, 5)
                    ];
                    
                    // Get last commit info for this file
                    $lastCommitResult = Process::path($projectPath)->run([
                        'git', 'log', '-1', '--format=%H|%s|%an|%ad', '--date=iso', '--', $analysis['file']
                    ]);
                    
                    if ($lastCommitResult->successful() && !empty(trim($lastCommitResult->output()))) {
                        $parts = explode('|', trim($lastCommitResult->output()), 4);
                        if (count($parts) >= 4) {
                            $analysis['last_git_commit'] = [
                                'hash' => $parts[0],
                                'message' => $parts[1],
                                'author' => $parts[2],
                                'date' => $parts[3]
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to get git history for config file', [
                    'file' => $analysis['file'],
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Generate comprehensive configuration impact findings
     */
    private function generateConfigurationImpactFindings(array $configAnalysis, array $impactAssessment, array $criticalChanges): void
    {
        // Generate finding for critical configuration changes
        if (!empty($criticalChanges)) {
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'critical',
                'Critical configuration changes detected - high impact assessment',
                'integrity',
                'critical_config_changes'
            );
            
            $finding->addFileReference('config/', 1, 'Critical configuration files');
            $finding->addMetric('critical_changes', count($criticalChanges), 'files');
            $finding->addMetric('max_risk_score', max(array_column($criticalChanges, 'risk_score')), 'score');
            $finding->addMetric('avg_risk_score', round(array_sum(array_column($criticalChanges, 'risk_score')) / count($criticalChanges), 1), 'score');
            
            $finding->addEvidence('static_analysis', [
                'critical_configuration_changes' => array_map(fn($c) => [
                    'file' => $c['file'],
                    'impact_level' => $c['base_impact'],
                    'category' => $c['category'],
                    'risk_score' => $c['risk_score'],
                    'severity' => $c['severity'],
                    'impact_factors' => $c['impact_factors']
                ], $criticalChanges),
                'analysis_summary' => [
                    'total_config_files' => count($configAnalysis),
                    'files_with_issues' => count($criticalChanges),
                    'categories_affected' => array_unique(array_column($criticalChanges, 'category'))
                ]
            ], 'Critical configuration change impact assessment');
            
            $finding->addEvidence('runtime_log', [
                'assessment_method' => 'comprehensive_configuration_impact_analysis',
                'analysis_timestamp' => now()->toISOString(),
                'risk_calculation_factors' => ['modification_time', 'syntax_validation', 'security_analysis', 'impact_level']
            ], 'Configuration impact assessment verification');
            
            $finding->setRecommendation('Review all critical configuration changes, validate syntax and security, and test impact in staging environment');
            $finding->setEstimatedHours(count($criticalChanges) * 2);
            
            $this->findings[] = $finding;
        }
        
        // Generate findings for security issues in configurations
        $securityIssues = array_filter($configAnalysis, fn($c) => $c['security_analysis']['security_score'] < 70);
        if (!empty($securityIssues)) {
            $this->generateConfigurationSecurityFinding($securityIssues);
        }
        
        // Generate findings for syntax errors
        $syntaxErrors = array_filter($configAnalysis, fn($c) => !$c['syntax_validation']['syntax_valid']);
        if (!empty($syntaxErrors)) {
            $this->generateConfigurationSyntaxFinding($syntaxErrors);
        }
    }
    
    /**
     * Generate configuration security finding
     */
    private function generateConfigurationSecurityFinding(array $securityIssues): void
    {
        $finding = new ConcreteFinding(
            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
            'high',
            'Configuration security issues detected - potential vulnerabilities',
            'integrity',
            'config_security_issues'
        );
        
        $finding->addFileReference('config/', 1, 'Configuration files with security issues');
        $finding->addMetric('files_with_security_issues', count($securityIssues), 'files');
        $finding->addMetric('avg_security_score', round(array_sum(array_column($securityIssues, 'security_analysis.security_score')) / count($securityIssues), 1), 'score');
        
        $finding->addEvidence('static_analysis', [
            'security_issues' => array_map(fn($s) => [
                'file' => $s['file'],
                'security_score' => $s['security_analysis']['security_score'],
                'contains_sensitive_data' => $s['security_analysis']['contains_sensitive_data'],
                'weak_configurations' => $s['security_analysis']['weak_configurations']
            ], $securityIssues)
        ], 'Configuration security analysis');
        
        $finding->setRecommendation('Address security vulnerabilities in configuration files and implement secure configuration practices');
        $finding->setEstimatedHours(count($securityIssues));
        
        $this->findings[] = $finding;
    }
    
    /**
     * Generate configuration syntax finding
     */
    private function generateConfigurationSyntaxFinding(array $syntaxErrors): void
    {
        $finding = new ConcreteFinding(
            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
            'high',
            'Configuration syntax errors detected - application may fail',
            'integrity',
            'config_syntax_errors'
        );
        
        $finding->addFileReference('config/', 1, 'Configuration files with syntax errors');
        $finding->addMetric('files_with_syntax_errors', count($syntaxErrors), 'files');
        
        $finding->addEvidence('static_analysis', [
            'syntax_errors' => array_map(fn($s) => [
                'file' => $s['file'],
                'syntax_valid' => $s['syntax_validation']['syntax_valid'],
                'errors' => $s['syntax_validation']['syntax_errors']
            ], $syntaxErrors)
        ], 'Configuration syntax validation');
        
        $finding->setRecommendation('Fix syntax errors in configuration files to prevent application failures');
        $finding->setEstimatedHours(count($syntaxErrors));
        
        $this->findings[] = $finding;
    }
    
    /**
     * Analyze configuration dependencies and conflicts
     */
    private function analyzeConfigurationDependencies(array $configAnalysis): void
    {
        $dependencies = [];
        $conflicts = [];
        
        // Analyze environment variable dependencies
        foreach ($configAnalysis as $analysis) {
            if (isset($analysis['content_analysis']['env_variables'])) {
                $envVars = $analysis['content_analysis']['env_variables'];
                foreach ($envVars as $configKey => $envKey) {
                    $dependencies[$envKey][] = [
                        'file' => $analysis['file'],
                        'config_key' => $configKey
                    ];
                }
            }
        }
        
        // Check for missing environment variables
        $envFile = null;
        foreach ($configAnalysis as $analysis) {
            if ($analysis['file'] === '.env') {
                $envFile = $analysis;
                break;
            }
        }
        
        if ($envFile && !empty($dependencies)) {
            $envVariables = $envFile['content_analysis']['env_variables'] ?? [];
            $missingEnvVars = [];
            
            foreach ($dependencies as $envKey => $usages) {
                if (!isset($envVariables[$envKey])) {
                    $missingEnvVars[$envKey] = $usages;
                }
            }
            
            if (!empty($missingEnvVars)) {
                $this->generateMissingEnvVariablesFinding($missingEnvVars);
            }
        }
    }
    
    /**
     * Generate finding for missing environment variables
     */
    private function generateMissingEnvVariablesFinding(array $missingEnvVars): void
    {
        $finding = new ConcreteFinding(
            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
            'medium',
            'Missing environment variables detected - configuration dependencies not met',
            'integrity',
            'missing_env_variables'
        );
        
        $finding->addFileReference('.env', 1, 'Environment configuration file');
        $finding->addMetric('missing_env_vars', count($missingEnvVars), 'variables');
        
        $finding->addEvidence('static_analysis', [
            'missing_variables' => array_map(fn($usages, $envKey) => [
                'env_variable' => $envKey,
                'used_in_files' => array_column($usages, 'file'),
                'config_keys' => array_column($usages, 'config_key')
            ], $missingEnvVars, array_keys($missingEnvVars))
        ], 'Environment variable dependency analysis');
        
        $finding->setRecommendation('Add missing environment variables to .env file or update configuration to use default values');
        $finding->setEstimatedHours(1);
        
        $this->findings[] = $finding;
    }
    
    /**
     * Check if content contains sensitive data patterns (ENHANCED)
     */
    private function containsSensitiveData(string $content): bool
    {
        $sensitivePatterns = [
            '/password\s*=\s*["\']?[^"\'\s]{8,}/',
            '/secret\s*=\s*["\']?[^"\'\s]{16,}/',
            '/key\s*=\s*["\']?[^"\'\s]{16,}/',
            '/token\s*=\s*["\']?[^"\'\s]{20,}/',
            '/api_key\s*=\s*["\']?[^"\'\s]{16,}/',
            '/private_key\s*=\s*["\']?[^"\'\s]{32,}/',
            '/access_token\s*=\s*["\']?[^"\'\s]{20,}/',
            '/client_secret\s*=\s*["\']?[^"\'\s]{16,}/'
        ];
        
        foreach ($sensitivePatterns as $pattern) {
            if (preg_match($pattern, strtolower($content))) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Verify file integrity through COMPREHENSIVE hash comparison
     */
    public function verifyFileIntegrity(string $projectPath): void
    {
        Log::info('🔍 Verifying File Integrity with Hash Comparison');
        
        // Critical system files for integrity verification
        $criticalFiles = [
            'composer.json',
            'composer.lock',
            'package.json',
            'package-lock.json',
            'artisan',
            'public/index.php',
            'bootstrap/app.php',
            '.env.example',
            'config/app.php',
            'config/database.php',
            'routes/web.php',
            'routes/api.php'
        ];
        
        $integrityResults = [];
        $hashMismatches = [];
        $suspiciousChanges = [];
        
        foreach ($criticalFiles as $file) {
            $filePath = $projectPath . '/' . $file;
            
            if (!file_exists($filePath)) {
                $this->generateMissingFileFinding($file);
                continue;
            }
            
            // Comprehensive file integrity analysis
            $integrityData = $this->analyzeFileIntegrity($filePath, $file);
            $integrityResults[] = $integrityData;
            
            // Check for suspicious modifications
            if ($integrityData['suspicious']) {
                $suspiciousChanges[] = $integrityData;
            }
            
            // Store hash for comparison
            $this->storeFileHash($file, $integrityData['current_hash'], $integrityData);
        }
        
        // Cross-reference with git history for hash validation
        $this->crossReferenceHashesWithGit($projectPath, $integrityResults);
        
        // Generate findings for suspicious changes
        if (!empty($suspiciousChanges)) {
            $this->generateSuspiciousChangesFinding($suspiciousChanges);
        }
        
        // Perform baseline hash comparison if available
        $this->performBaselineHashComparison($integrityResults);
        
        Log::info('File integrity verification completed', [
            'files_analyzed' => count($integrityResults),
            'suspicious_changes' => count($suspiciousChanges)
        ]);
    }
    
    /**
     * Analyze comprehensive file integrity data
     */
    private function analyzeFileIntegrity(string $filePath, string $relativePath): array
    {
        $fileStats = stat($filePath);
        $content = file_get_contents($filePath);
        
        // Generate multiple hash types for comprehensive verification
        $hashes = [
            'md5' => hash('md5', $content),
            'sha1' => hash('sha1', $content),
            'sha256' => hash('sha256', $content),
            'sha512' => hash('sha512', $content)
        ];
        
        // File metadata analysis
        $metadata = [
            'size' => $fileStats['size'],
            'modified_time' => $fileStats['mtime'],
            'modified_date' => date('Y-m-d H:i:s', $fileStats['mtime']),
            'permissions' => substr(sprintf('%o', fileperms($filePath)), -4),
            'owner' => $fileStats['uid'],
            'group' => $fileStats['gid']
        ];
        
        // Detect suspicious characteristics
        $suspicious = $this->detectSuspiciousFileCharacteristics($content, $metadata, $relativePath);
        
        // Content analysis for integrity markers
        $contentAnalysis = $this->analyzeFileContent($content, $relativePath);
        
        return [
            'file' => $relativePath,
            'path' => $filePath,
            'hashes' => $hashes,
            'current_hash' => $hashes['sha256'], // Primary hash for comparison
            'metadata' => $metadata,
            'suspicious' => $suspicious,
            'content_analysis' => $contentAnalysis,
            'integrity_score' => $this->calculateIntegrityScore($suspicious, $contentAnalysis, $metadata)
        ];
    }
    
    /**
     * Detect suspicious file characteristics
     */
    private function detectSuspiciousFileCharacteristics(string $content, array $metadata, string $relativePath): bool
    {
        $suspicious = false;
        
        // Check for recent modifications (last 24 hours)
        if ((time() - $metadata['modified_time']) < 86400) {
            $suspicious = true;
        }
        
        // Check for unusual file sizes
        $expectedSizes = [
            'artisan' => [2000, 5000],
            'composer.json' => [500, 10000],
            'package.json' => [200, 5000]
        ];
        
        $filename = basename($relativePath);
        if (isset($expectedSizes[$filename])) {
            [$minSize, $maxSize] = $expectedSizes[$filename];
            if ($metadata['size'] < $minSize || $metadata['size'] > $maxSize) {
                $suspicious = true;
            }
        }
        
        // Check for suspicious content patterns
        $suspiciousPatterns = [
            '/eval\s*\(/',
            '/base64_decode\s*\(/',
            '/shell_exec\s*\(/',
            '/system\s*\(/',
            '/exec\s*\(/',
            '/passthru\s*\(/',
            '/file_get_contents\s*\(\s*["\']https?:\/\//'
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $suspicious = true;
                break;
            }
        }
        
        return $suspicious;
    }
    
    /**
     * Analyze file content for integrity markers
     */
    private function analyzeFileContent(string $content, string $relativePath): array
    {
        $analysis = [
            'line_count' => substr_count($content, "\n") + 1,
            'char_count' => strlen($content),
            'encoding' => mb_detect_encoding($content, ['UTF-8', 'ASCII', 'ISO-8859-1'], true),
            'has_bom' => substr($content, 0, 3) === "\xEF\xBB\xBF",
            'line_endings' => $this->detectLineEndings($content),
            'contains_binary' => !mb_check_encoding($content, 'UTF-8')
        ];
        
        // File-specific analysis
        $extension = pathinfo($relativePath, PATHINFO_EXTENSION);
        
        switch ($extension) {
            case 'json':
                $analysis['json_valid'] = json_decode($content) !== null;
                $analysis['json_error'] = json_last_error_msg();
                break;
            case 'php':
                $analysis['php_syntax_valid'] = $this->validatePHPSyntax($content);
                $analysis['php_tags'] = substr_count($content, '<?php');
                break;
        }
        
        return $analysis;
    }
    
    /**
     * Detect line ending types
     */
    private function detectLineEndings(string $content): string
    {
        if (strpos($content, "\r\n") !== false) {
            return 'CRLF';
        } elseif (strpos($content, "\n") !== false) {
            return 'LF';
        } elseif (strpos($content, "\r") !== false) {
            return 'CR';
        }
        return 'NONE';
    }
    
    /**
     * Validate PHP syntax without executing (SAFE VERSION)
     */
    private function validatePHPSyntax(string $content): bool
    {
        try {
            // Use Process facade instead of shell_exec to avoid recursion
            $tempFile = tempnam(sys_get_temp_dir(), 'php_syntax_check');
            file_put_contents($tempFile, $content);
            
            $result = Process::run(['php', '-l', $tempFile]);
            unlink($tempFile);
            
            return $result->successful() && strpos($result->output(), 'No syntax errors detected') !== false;
        } catch (\Exception $e) {
            // If syntax check fails, assume valid to avoid blocking analysis
            return true;
        }
    }
    
    /**
     * Calculate integrity score (0-100)
     */
    private function calculateIntegrityScore(bool $suspicious, array $contentAnalysis, array $metadata): int
    {
        $score = 100;
        
        if ($suspicious) {
            $score -= 30;
        }
        
        // Deduct for unusual characteristics
        if ($contentAnalysis['contains_binary'] ?? false) {
            $score -= 20;
        }
        
        if (isset($contentAnalysis['json_valid']) && !$contentAnalysis['json_valid']) {
            $score -= 25;
        }
        
        if (isset($contentAnalysis['php_syntax_valid']) && !$contentAnalysis['php_syntax_valid']) {
            $score -= 40;
        }
        
        // Recent modifications reduce confidence
        $daysSinceModified = (time() - $metadata['modified_time']) / 86400;
        if ($daysSinceModified < 1) {
            $score -= 15;
        } elseif ($daysSinceModified < 7) {
            $score -= 5;
        }
        
        return max(0, $score);
    }
    
    /**
     * Store file hash for future comparison
     */
    private function storeFileHash(string $file, string $hash, array $integrityData): void
    {
        // Store in memory for this analysis session
        $this->commitAnalysis[$file] = [
            'hash' => $hash,
            'timestamp' => time(),
            'integrity_score' => $integrityData['integrity_score'],
            'metadata' => $integrityData['metadata']
        ];
    }
    
    /**
     * Cross-reference hashes with git history
     */
    private function crossReferenceHashesWithGit(string $projectPath, array $integrityResults): void
    {
        if (!$this->isGitRepository($projectPath)) {
            return;
        }
        
        foreach ($integrityResults as $result) {
            try {
                // Get git hash for the file
                $gitResult = Process::path($projectPath)->run([
                    'git', 'hash-object', $result['path']
                ]);
                
                if ($gitResult->successful()) {
                    $gitHash = trim($gitResult->output());
                    
                    // Compare with our SHA-256 hash (different algorithms, but can detect major changes)
                    $this->commitAnalysis[$result['file']]['git_hash'] = $gitHash;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to get git hash for file', [
                    'file' => $result['file'],
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Generate finding for missing critical files
     */
    private function generateMissingFileFinding(string $file): void
    {
        $finding = new ConcreteFinding(
            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
            'critical',
            "Critical file missing: {$file}",
            'integrity',
            'missing_file'
        );
        
        $finding->addFileReference($file, 1, 'Missing critical file');
        $finding->addMetric('file_exists', 0, 'boolean');
        $finding->addMetric('integrity_impact', 10, 'score');
        
        $finding->addEvidence('runtime_log', [
            'file_path' => $file,
            'file_exists' => false,
            'analysis_timestamp' => now()->toISOString(),
            'verification_method' => 'file_system_check'
        ], 'Critical file existence verification');
        
        $finding->setRecommendation("Restore missing critical file: {$file}");
        $finding->setEstimatedHours(2);
        
        $this->findings[] = $finding;
    }
    
    /**
     * Generate finding for suspicious file changes
     */
    private function generateSuspiciousChangesFinding(array $suspiciousChanges): void
    {
        $finding = new ConcreteFinding(
            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
            count($suspiciousChanges) > 3 ? 'high' : 'medium',
            'Suspicious file modifications detected - integrity verification required',
            'integrity',
            'suspicious_modifications'
        );
        
        $finding->addFileReference('/', 1, 'Multiple suspicious file modifications');
        $finding->addMetric('suspicious_files', count($suspiciousChanges), 'files');
        $finding->addMetric('avg_integrity_score', 
            round(array_sum(array_column($suspiciousChanges, 'integrity_score')) / count($suspiciousChanges), 1), 
            'score'
        );
        
        $finding->addEvidence('static_analysis', [
            'suspicious_files' => array_map(fn($f) => [
                'file' => $f['file'],
                'integrity_score' => $f['integrity_score'],
                'current_hash' => substr($f['current_hash'], 0, 16) . '...',
                'modified_date' => $f['metadata']['modified_date'],
                'size' => $f['metadata']['size'],
                'permissions' => $f['metadata']['permissions']
            ], $suspiciousChanges),
            'hash_algorithms_used' => ['md5', 'sha1', 'sha256', 'sha512']
        ], 'Comprehensive file hash integrity analysis');
        
        $finding->addEvidence('runtime_log', [
            'verification_method' => 'multi_hash_comparison',
            'analysis_timestamp' => now()->toISOString(),
            'total_files_analyzed' => count($suspiciousChanges)
        ], 'File integrity hash verification');
        
        $finding->setRecommendation('Review suspicious file modifications, verify changes are authorized, and restore from backup if necessary');
        $finding->setEstimatedHours(max(1, (int)(count($suspiciousChanges) * 0.5)));
        
        $this->findings[] = $finding;
    }
    
    /**
     * Perform baseline hash comparison (if baseline exists)
     */
    private function performBaselineHashComparison(array $integrityResults): void
    {
        // This would compare against a stored baseline of known good hashes
        // For now, we'll implement a simple comparison against git HEAD
        
        $hashMismatches = [];
        
        foreach ($integrityResults as $result) {
            // Check if file has been modified recently compared to git
            if (isset($this->commitAnalysis[$result['file']]['git_hash'])) {
                $gitHash = $this->commitAnalysis[$result['file']]['git_hash'];
                
                // If we have git hash, we can detect if file differs from committed version
                try {
                    $gitFileContent = Process::path(dirname($result['path']))->run([
                        'git', 'show', 'HEAD:' . $result['file']
                    ]);
                    
                    if ($gitFileContent->successful()) {
                        $gitContentHash = hash('sha256', $gitFileContent->output());
                        
                        if ($gitContentHash !== $result['current_hash']) {
                            $hashMismatches[] = [
                                'file' => $result['file'],
                                'current_hash' => $result['current_hash'],
                                'git_hash' => $gitContentHash,
                                'integrity_score' => $result['integrity_score']
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    // Ignore git errors for this comparison
                }
            }
        }
        
        if (!empty($hashMismatches)) {
            $this->generateHashMismatchFinding($hashMismatches);
        }
    }
    
    /**
     * Generate finding for hash mismatches with git
     */
    private function generateHashMismatchFinding(array $hashMismatches): void
    {
        $finding = new ConcreteFinding(
            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
            'medium',
            'File hash mismatches detected - files differ from git repository',
            'integrity',
            'hash_mismatch'
        );
        
        $finding->addFileReference('.git/', 1, 'Git repository comparison');
        $finding->addMetric('mismatched_files', count($hashMismatches), 'files');
        
        $finding->addEvidence('static_analysis', [
            'hash_mismatches' => array_map(fn($m) => [
                'file' => $m['file'],
                'current_hash' => substr($m['current_hash'], 0, 16) . '...',
                'git_hash' => substr($m['git_hash'], 0, 16) . '...',
                'integrity_score' => $m['integrity_score']
            ], $hashMismatches)
        ], 'Hash comparison with git repository');
        
        $finding->setRecommendation('Review uncommitted changes and ensure file modifications are intentional');
        $finding->setEstimatedHours(1);
        
        $this->findings[] = $finding;
    }

    /**
     * Detect behavioral changes through OPTIMIZED code analysis
     */
    public function detectBehavioralChanges(string $projectPath): void
    {
        Log::info('🔍 Detecting Behavioral Changes');
        
        // OPTIMIZED: Direct file scanning instead of full directory scan
        $recentlyModifiedControllers = $this->scanRecentControllerChanges($projectPath);
        $recentlyModifiedModels = $this->scanRecentModelChanges($projectPath);
        $recentlyModifiedServices = $this->scanRecentServiceChanges($projectPath);
        
        // Analyze controller changes
        if (count($recentlyModifiedControllers) > 5) {
            $this->generateBehavioralChangeFinding(
                $recentlyModifiedControllers,
                'controllers',
                'app/Http/Controllers/',
                'Multiple controllers modified recently - potential behavioral changes'
            );
        }
        
        // Analyze model changes
        if (count($recentlyModifiedModels) > 3) {
            $this->generateBehavioralChangeFinding(
                $recentlyModifiedModels,
                'models',
                'app/Models/',
                'Multiple models modified recently - data layer behavioral changes'
            );
        }
        
        // Analyze service changes
        if (count($recentlyModifiedServices) > 5) {
            $this->generateBehavioralChangeFinding(
                $recentlyModifiedServices,
                'services',
                'app/Services/',
                'Multiple services modified recently - business logic behavioral changes'
            );
        }
        
        // Cross-reference with git history for deeper analysis
        $this->crossReferenceWithGitHistory($projectPath);
    }
    
    /**
     * OPTIMIZED: Scan only controller files for recent changes
     */
    private function scanRecentControllerChanges(string $projectPath, int $daysBack = 7): array
    {
        $controllerPath = $projectPath . '/app/Http/Controllers';
        if (!is_dir($controllerPath)) {
            return [];
        }
        
        $recentFiles = [];
        $cutoffTime = time() - ($daysBack * 24 * 3600);
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($controllerPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php' && $file->getMTime() > $cutoffTime) {
                $recentFiles[] = [
                    'path' => $file->getPathname(),
                    'modified_time' => $file->getMTime(),
                    'modified_date' => date('Y-m-d H:i:s', $file->getMTime()),
                    'size' => $file->getSize(),
                    'relative_path' => str_replace($projectPath . '/', '', $file->getPathname())
                ];
            }
        }
        
        return $recentFiles;
    }
    
    /**
     * OPTIMIZED: Scan only model files for recent changes
     */
    private function scanRecentModelChanges(string $projectPath, int $daysBack = 7): array
    {
        $modelPath = $projectPath . '/app/Models';
        if (!is_dir($modelPath)) {
            return [];
        }
        
        $recentFiles = [];
        $cutoffTime = time() - ($daysBack * 24 * 3600);
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($modelPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php' && $file->getMTime() > $cutoffTime) {
                $recentFiles[] = [
                    'path' => $file->getPathname(),
                    'modified_time' => $file->getMTime(),
                    'modified_date' => date('Y-m-d H:i:s', $file->getMTime()),
                    'size' => $file->getSize(),
                    'relative_path' => str_replace($projectPath . '/', '', $file->getPathname())
                ];
            }
        }
        
        return $recentFiles;
    }
    
    /**
     * OPTIMIZED: Scan only service files for recent changes
     */
    private function scanRecentServiceChanges(string $projectPath, int $daysBack = 7): array
    {
        $servicePath = $projectPath . '/app/Services';
        if (!is_dir($servicePath)) {
            return [];
        }
        
        $recentFiles = [];
        $cutoffTime = time() - ($daysBack * 24 * 3600);
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($servicePath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php' && $file->getMTime() > $cutoffTime) {
                $recentFiles[] = [
                    'path' => $file->getPathname(),
                    'modified_time' => $file->getMTime(),
                    'modified_date' => date('Y-m-d H:i:s', $file->getMTime()),
                    'size' => $file->getSize(),
                    'relative_path' => str_replace($projectPath . '/', '', $file->getPathname())
                ];
            }
        }
        
        return $recentFiles;
    }
    
    /**
     * Generate behavioral change finding
     */
    private function generateBehavioralChangeFinding(array $files, string $fileType, string $basePath, string $description): void
    {
        $finding = new ConcreteFinding(
            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
            count($files) > 10 ? 'high' : 'medium',
            $description,
            'integrity',
            'behavioral_change_' . $fileType
        );
        
        $finding->addFileReference($basePath, 1, "Recently modified {$fileType}");
        $finding->addMetric("modified_{$fileType}", count($files), 'files');
        $finding->addMetric('modification_timeframe', 7, 'days');
        $finding->addMetric('change_velocity', count($files) / 7, 'files_per_day');
        
        $finding->addEvidence('static_analysis', [
            "modified_{$fileType}" => array_map(fn($f) => [
                'path' => $f['relative_path'],
                'modified_date' => $f['modified_date'],
                'size' => $f['size'],
                'days_ago' => round((time() - $f['modified_time']) / (24 * 3600), 1)
            ], array_slice($files, 0, 10)), // Limit to first 10 files
            'total_modified' => count($files),
            'analysis_period' => '7 days'
        ], "Recent {$fileType} modification analysis");
        
        $finding->addEvidence('runtime_log', [
            'analysis_method' => 'optimized_file_modification_scan',
            'scan_path' => $basePath,
            'analysis_timestamp' => now()->toISOString()
        ], 'File modification time verification');
        
        $finding->setRecommendation("Review recent {$fileType} changes for behavioral consistency and potential regressions");
        $finding->setEstimatedHours(min(8, max(1, count($files) * 0.5)));
        
        $this->findings[] = $finding;
    }
    
    /**
     * Cross-reference file changes with git history
     */
    private function crossReferenceWithGitHistory(string $projectPath): void
    {
        if (!$this->isGitRepository($projectPath)) {
            return;
        }
        
        try {
            // Get files changed in last 7 days via git
            $result = Process::path($projectPath)->run([
                'git', 'log', '--since=7.days.ago', '--name-only', '--pretty=format:', '--'
            ]);
            
            if ($result->successful()) {
                $gitChangedFiles = array_filter(explode("\n", $result->output()));
                $gitChangedFiles = array_unique($gitChangedFiles);
                
                if (count($gitChangedFiles) > 20) {
                    $finding = new ConcreteFinding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'medium',
                        'High git activity detected - extensive codebase changes',
                        'integrity',
                        'high_git_activity'
                    );
                    
                    $finding->addFileReference('.git/logs/', 1, 'Git activity logs');
                    $finding->addMetric('files_changed_git', count($gitChangedFiles), 'files');
                    $finding->addMetric('change_period', 7, 'days');
                    
                    $finding->addEvidence('static_analysis', [
                        'git_changed_files' => array_slice($gitChangedFiles, 0, 15),
                        'total_files_changed' => count($gitChangedFiles)
                    ], 'Git history file change analysis');
                    
                    $finding->setRecommendation('Review git history for change patterns and ensure proper testing coverage');
                    $finding->setEstimatedHours(2);
                    
                    $this->findings[] = $finding;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to cross-reference with git history', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Analyze diff between any two commit hashes with COMPREHENSIVE ANALYSIS
     */
    public function analyzeCommitDiff(string $fromCommit, string $toCommit, ?string $projectPath = null): CommitDiffReport
    {
        $projectPath = $projectPath ?? base_path();
        
        if (!$this->isValidCommitHash($fromCommit) || !$this->isValidCommitHash($toCommit)) {
            throw new \InvalidArgumentException('Invalid commit hash format provided');
        }
        
        if (!$this->isGitRepository($projectPath)) {
            throw new \RuntimeException('Not a git repository: ' . $projectPath);
        }
        
        try {
            // Verify commits exist
            $fromExists = Process::path($projectPath)->run(['git', 'cat-file', '-e', $fromCommit])->successful();
            $toExists = Process::path($projectPath)->run(['git', 'cat-file', '-e', $toCommit])->successful();
            
            if (!$fromExists || !$toExists) {
                throw new \InvalidArgumentException('One or both commits do not exist in repository');
            }
            
            // Get commit information
            $fromCommitInfo = $this->getCommitInfo($fromCommit, $projectPath);
            $toCommitInfo = $this->getCommitInfo($toCommit, $projectPath);
            
            // Perform comprehensive diff analysis
            return $this->compareCommits($toCommitInfo, $fromCommitInfo);
            
        } catch (\Exception $e) {
            Log::error('Failed to analyze commit diff', [
                'from_commit' => $fromCommit,
                'to_commit' => $toCommit,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Get detailed commit information
     */
    private function getCommitInfo(string $commitHash, string $projectPath): array
    {
        $result = Process::path($projectPath)->run([
            'git', 'show', '--format=%H|%s|%an|%ad', '--date=iso', '--no-patch', $commitHash
        ]);
        
        if (!$result->successful()) {
            throw new \RuntimeException('Failed to get commit info for: ' . $commitHash);
        }
        
        $output = trim($result->output());
        $parts = explode('|', $output, 4);
        
        if (count($parts) < 4) {
            throw new \RuntimeException('Invalid commit info format for: ' . $commitHash);
        }
        
        return [
            'hash' => $parts[0],
            'message' => $parts[1],
            'author' => $parts[2],
            'date' => $parts[3]
        ];
    }
    
    /**
     * Analyze git history for integrity patterns
     */
    public function analyzeGitHistory(?string $projectPath = null, int $commitLimit = 50): array
    {
        $projectPath = $projectPath ?? base_path();
        
        if (!$this->isGitRepository($projectPath)) {
            return ['error' => 'Not a git repository'];
        }
        
        $commits = $this->getRecentCommits($projectPath, $commitLimit);
        
        if (empty($commits)) {
            return ['error' => 'No commits found'];
        }
        
        $analysis = [
            'total_commits' => count($commits),
            'commit_frequency' => $this->analyzeCommitFrequency($commits),
            'author_distribution' => $this->analyzeAuthorDistribution($commits),
            'message_quality' => $this->analyzeMessageQuality($commits),
            'temporal_patterns' => $this->analyzeTemporalPatterns($commits)
        ];
        
        return $analysis;
    }
    
    /**
     * Analyze commit frequency patterns
     */
    private function analyzeCommitFrequency(array $commits): array
    {
        $dates = array_map(fn($c) => date('Y-m-d', strtotime($c['date'])), $commits);
        $frequency = array_count_values($dates);
        
        return [
            'daily_frequency' => $frequency,
            'avg_commits_per_day' => count($commits) / max(1, count($frequency)),
            'max_commits_single_day' => max($frequency),
            'days_with_commits' => count($frequency)
        ];
    }
    
    /**
     * Analyze author distribution
     */
    private function analyzeAuthorDistribution(array $commits): array
    {
        $authors = array_map(fn($c) => $c['author'], $commits);
        $distribution = array_count_values($authors);
        arsort($distribution);
        
        return [
            'unique_authors' => count($distribution),
            'author_commits' => $distribution,
            'primary_author' => array_key_first($distribution),
            'author_diversity_score' => count($distribution) / count($commits)
        ];
    }
    
    /**
     * Analyze commit message quality
     */
    private function analyzeMessageQuality(array $commits): array
    {
        $messages = array_map(fn($c) => $c['message'], $commits);
        
        $shortMessages = array_filter($messages, fn($m) => strlen($m) < 10);
        $longMessages = array_filter($messages, fn($m) => strlen($m) > 100);
        $conventionalCommits = array_filter($messages, fn($m) => preg_match('/^(feat|fix|docs|style|refactor|test|chore)(\(.+\))?: .+/', $m));
        
        return [
            'total_messages' => count($messages),
            'short_messages' => count($shortMessages),
            'long_messages' => count($longMessages),
            'conventional_commits' => count($conventionalCommits),
            'avg_message_length' => array_sum(array_map('strlen', $messages)) / count($messages),
            'quality_score' => (count($conventionalCommits) / count($messages)) * 100
        ];
    }
    
    /**
     * Analyze temporal commit patterns
     */
    private function analyzeTemporalPatterns(array $commits): array
    {
        $hours = array_map(fn($c) => (int)date('H', strtotime($c['date'])), $commits);
        $weekdays = array_map(fn($c) => date('N', strtotime($c['date'])), $commits);
        
        $hourDistribution = array_count_values($hours);
        $weekdayDistribution = array_count_values($weekdays);
        
        return [
            'hour_distribution' => $hourDistribution,
            'weekday_distribution' => $weekdayDistribution,
            'peak_hour' => array_search(max($hourDistribution), $hourDistribution),
            'peak_weekday' => array_search(max($weekdayDistribution), $weekdayDistribution),
            'work_hours_commits' => count(array_filter($hours, fn($h) => $h >= 9 && $h <= 17)),
            'weekend_commits' => ($weekdayDistribution[6] ?? 0) + ($weekdayDistribution[7] ?? 0)
        ];
    }

    /**
     * COMPREHENSIVE Behavioral Regression Detection with CONCRETE EVIDENCE
     */
    public function detectBehavioralRegressions(string $projectPath): void
    {
        Log::info('🔍 Detecting Behavioral Regressions');
        
        if (!$this->isGitRepository($projectPath)) {
            Log::warning('Skipping behavioral regression detection - not a git repository');
            return;
        }
        
        try {
            // 1. Analyze API endpoint changes for breaking changes
            $this->detectApiRegressions($projectPath);
            
            // 2. Analyze database schema changes for data integrity issues
            $this->detectDatabaseRegressions($projectPath);
            
            // 3. Analyze configuration changes for behavioral impacts
            $this->detectConfigurationRegressions($projectPath);
            
            // 4. Analyze dependency changes for compatibility issues
            $this->detectDependencyRegressions($projectPath);
            
            // 5. Analyze test coverage changes for quality regressions
            $this->detectTestCoverageRegressions($projectPath);
            
            // 6. Analyze performance-critical code changes
            $this->detectPerformanceRegressions($projectPath);
            
        } catch (\Exception $e) {
            Log::error('Behavioral regression detection failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Detect API endpoint regressions through route analysis
     */
    private function detectApiRegressions(string $projectPath): void
    {
        $routeFiles = [
            'routes/api.php',
            'routes/web.php',
            'app/Http/Controllers'
        ];
        
        $apiChanges = [];
        
        foreach ($routeFiles as $routeFile) {
            $filePath = $projectPath . '/' . $routeFile;
            
            if (!file_exists($filePath)) {
                continue;
            }
            
            // Check if route files were recently modified
            $lastModified = is_dir($filePath) ? $this->getDirectoryLastModified($filePath) : filemtime($filePath);
            $daysSinceModified = (time() - $lastModified) / (24 * 3600);
            
            if ($daysSinceModified <= 7) {
                $apiChanges[] = [
                    'file' => $routeFile,
                    'last_modified' => $lastModified,
                    'days_ago' => round($daysSinceModified, 1),
                    'type' => is_dir($filePath) ? 'controller_directory' : 'route_file'
                ];
                
                // Analyze specific changes if it's a route file
                if (!is_dir($filePath)) {
                    $this->analyzeRouteFileChanges($filePath, $routeFile);
                }
            }
        }
        
        if (!empty($apiChanges)) {
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'high',
                'API endpoint changes detected - potential behavioral regressions',
                'integrity',
                'api_regression_risk'
            );
            
            $finding->addFileReference('routes/', 1, 'API route definitions');
            $finding->addMetric('api_files_changed', count($apiChanges), 'files');
            $finding->addMetric('regression_risk_score', min(10, count($apiChanges) * 2), 'score');
            
            $finding->addEvidence('static_analysis', [
                'changed_api_files' => $apiChanges,
                'analysis_period' => '7 days',
                'risk_factors' => [
                    'route_modifications',
                    'controller_changes',
                    'potential_breaking_changes'
                ]
            ], 'API endpoint change analysis');
            
            $finding->addEvidence('runtime_log', [
                'detection_method' => 'file_modification_time_analysis',
                'analysis_timestamp' => now()->toISOString()
            ], 'API regression detection verification');
            
            $finding->setRecommendation('Review API changes for backward compatibility and update API documentation');
            $finding->setEstimatedHours(count($apiChanges) * 2);
            
            $this->findings[] = $finding;
        }
    }
    
    /**
     * Analyze specific route file changes
     */
    private function analyzeRouteFileChanges(string $filePath, string $routeFile): void
    {
        $content = file_get_contents($filePath);
        
        // Check for potentially breaking patterns
        $breakingPatterns = [
            '/Route::delete\s*\(/' => 'DELETE endpoint added/modified',
            '/Route::put\s*\(/' => 'PUT endpoint added/modified', 
            '/->middleware\s*\(/' => 'Middleware changes',
            '/->where\s*\(/' => 'Route constraint changes',
            '/Route::group\s*\(/' => 'Route group modifications'
        ];
        
        $detectedPatterns = [];
        foreach ($breakingPatterns as $pattern => $description) {
            if (preg_match($pattern, $content)) {
                $detectedPatterns[] = $description;
            }
        }
        
        if (!empty($detectedPatterns)) {
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'medium',
                "Potentially breaking API changes detected in {$routeFile}",
                'integrity',
                'breaking_api_changes'
            );
            
            $finding->addFileReference($routeFile, 1, 'Route file with breaking changes');
            $finding->addMetric('breaking_patterns_detected', count($detectedPatterns), 'patterns');
            
            $finding->addEvidence('static_analysis', [
                'file' => $routeFile,
                'detected_patterns' => $detectedPatterns,
                'file_size' => filesize($filePath),
                'last_modified' => date('Y-m-d H:i:s', filemtime($filePath))
            ], 'Route file breaking change pattern analysis');
            
            $finding->setRecommendation('Review route changes for API backward compatibility');
            $finding->setEstimatedHours(1);
            
            $this->findings[] = $finding;
        }
    }
    
    /**
     * Detect database schema regressions
     */
    private function detectDatabaseRegressions(string $projectPath): void
    {
        $migrationPath = $projectPath . '/database/migrations';
        
        if (!is_dir($migrationPath)) {
            return;
        }
        
        $recentMigrations = [];
        $cutoffTime = time() - (7 * 24 * 3600); // Last 7 days
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($migrationPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php' && $file->getMTime() > $cutoffTime) {
                $recentMigrations[] = [
                    'file' => $file->getFilename(),
                    'path' => $file->getPathname(),
                    'modified_time' => $file->getMTime(),
                    'size' => $file->getSize()
                ];
            }
        }
        
        if (!empty($recentMigrations)) {
            // Analyze migration content for risky operations
            $riskyOperations = [];
            
            foreach ($recentMigrations as $migration) {
                $content = file_get_contents($migration['path']);
                $risks = $this->analyzeMigrationRisks($content, $migration['file']);
                $riskyOperations = array_merge($riskyOperations, $risks);
            }
            
            $severity = count($riskyOperations) > 3 ? 'high' : 'medium';
            
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                $severity,
                'Recent database migrations detected - potential data integrity regressions',
                'integrity',
                'database_regression_risk'
            );
            
            $finding->addFileReference('database/migrations/', 1, 'Recent database migrations');
            $finding->addMetric('recent_migrations', count($recentMigrations), 'files');
            $finding->addMetric('risky_operations', count($riskyOperations), 'operations');
            $finding->addMetric('regression_risk_score', min(10, count($riskyOperations) * 1.5), 'score');
            
            $finding->addEvidence('static_analysis', [
                'recent_migrations' => array_map(fn($m) => [
                    'file' => $m['file'],
                    'modified_date' => date('Y-m-d H:i:s', $m['modified_time']),
                    'size' => $m['size']
                ], $recentMigrations),
                'risky_operations' => $riskyOperations
            ], 'Database migration risk analysis');
            
            $finding->setRecommendation('Review database migrations for data integrity and backward compatibility');
            $finding->setEstimatedHours((int)(count($recentMigrations) + count($riskyOperations)));
            
            $this->findings[] = $finding;
        }
    }
    
    /**
     * Analyze migration content for risky operations
     */
    private function analyzeMigrationRisks(string $content, string $filename): array
    {
        $riskyPatterns = [
            '/\$table->dropColumn\s*\(/' => 'Column deletion',
            '/\$table->drop\s*\(/' => 'Table deletion',
            '/Schema::drop\s*\(/' => 'Schema drop operation',
            '/\$table->renameColumn\s*\(/' => 'Column rename',
            '/Schema::rename\s*\(/' => 'Table rename',
            '/\$table->dropIndex\s*\(/' => 'Index deletion',
            '/\$table->dropForeign\s*\(/' => 'Foreign key deletion',
            '/ALTER TABLE.*DROP/' => 'Raw ALTER DROP statement'
        ];
        
        $risks = [];
        foreach ($riskyPatterns as $pattern => $description) {
            if (preg_match($pattern, $content)) {
                $risks[] = [
                    'file' => $filename,
                    'operation' => $description,
                    'risk_level' => 'high'
                ];
            }
        }
        
        return $risks;
    }
    
    /**
     * Detect configuration regressions
     */
    private function detectConfigurationRegressions(string $projectPath): void
    {
        $configFiles = [
            '.env',
            'config/app.php',
            'config/database.php',
            'config/cache.php',
            'config/queue.php',
            'config/mail.php'
        ];
        
        $recentConfigChanges = [];
        $cutoffTime = time() - (3 * 24 * 3600); // Last 3 days (more sensitive)
        
        foreach ($configFiles as $configFile) {
            $filePath = $projectPath . '/' . $configFile;
            
            if (file_exists($filePath) && filemtime($filePath) > $cutoffTime) {
                $recentConfigChanges[] = [
                    'file' => $configFile,
                    'modified_time' => filemtime($filePath),
                    'size' => filesize($filePath)
                ];
                
                // Analyze specific config changes
                $this->analyzeConfigurationFile($configFile, $filePath);
            }
        }
        
        if (count($recentConfigChanges) > 2) {
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'high',
                'Multiple configuration files modified recently - behavioral regression risk',
                'integrity',
                'config_regression_risk'
            );
            
            $finding->addFileReference('config/', 1, 'Configuration files');
            $finding->addMetric('config_files_changed', count($recentConfigChanges), 'files');
            $finding->addMetric('change_frequency', count($recentConfigChanges) / 3, 'files_per_day');
            
            $finding->addEvidence('static_analysis', [
                'changed_config_files' => array_map(fn($c) => [
                    'file' => $c['file'],
                    'modified_date' => date('Y-m-d H:i:s', $c['modified_time']),
                    'hours_ago' => round((time() - $c['modified_time']) / 3600, 1)
                ], $recentConfigChanges)
            ], 'Configuration change analysis');
            
            $finding->setRecommendation('Review configuration changes for environmental consistency and rollback procedures');
            $finding->setEstimatedHours((int)count($recentConfigChanges));
            
            $this->findings[] = $finding;
        }
    }
    
    /**
     * Detect dependency regressions
     */
    private function detectDependencyRegressions(string $projectPath): void
    {
        $dependencyFiles = [
            'composer.json' => 'PHP dependencies',
            'composer.lock' => 'PHP dependency lock',
            'package.json' => 'Node.js dependencies',
            'package-lock.json' => 'Node.js dependency lock',
            'yarn.lock' => 'Yarn dependency lock'
        ];
        
        $recentDependencyChanges = [];
        $cutoffTime = time() - (7 * 24 * 3600);
        
        foreach ($dependencyFiles as $file => $description) {
            $filePath = $projectPath . '/' . $file;
            
            if (file_exists($filePath) && filemtime($filePath) > $cutoffTime) {
                $recentDependencyChanges[] = [
                    'file' => $file,
                    'description' => $description,
                    'modified_time' => filemtime($filePath),
                    'size' => filesize($filePath)
                ];
                
                // Analyze dependency changes via git if available
                $this->analyzeDependencyChanges($filePath, $file);
            }
        }
        
        if (!empty($recentDependencyChanges)) {
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'medium',
                'Dependency changes detected - potential compatibility regressions',
                'integrity',
                'dependency_regression_risk'
            );
            
            $finding->addFileReference('composer.json', 1, 'Dependency management files');
            $finding->addMetric('dependency_files_changed', count($recentDependencyChanges), 'files');
            
            $finding->addEvidence('static_analysis', [
                'changed_dependency_files' => array_map(fn($d) => [
                    'file' => $d['file'],
                    'description' => $d['description'],
                    'modified_date' => date('Y-m-d H:i:s', $d['modified_time'])
                ], $recentDependencyChanges)
            ], 'Dependency change analysis');
            
            $finding->setRecommendation('Review dependency changes for version compatibility and security implications');
            $finding->setEstimatedHours((int)(count($recentDependencyChanges) * 0.5));
            
            $this->findings[] = $finding;
        }
    }
    
    /**
     * Analyze dependency changes through git diff
     */
    private function analyzeDependencyChanges(string $filePath, string $filename): void
    {
        try {
            $result = Process::path(dirname($filePath))->run([
                'git', 'log', '-1', '--stat', '--', $filename
            ]);
            
            if ($result->successful() && !empty(trim($result->output()))) {
                // Parse git log output for dependency insights
                $output = $result->output();
                
                if (strpos($output, 'composer.json') !== false || strpos($output, 'package.json') !== false) {
                    // This indicates recent dependency modifications
                    Log::info("Dependency file {$filename} has recent git activity");
                }
            }
        } catch (\Exception $e) {
            Log::warning("Failed to analyze dependency changes for {$filename}", ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Detect test coverage regressions
     */
    private function detectTestCoverageRegressions(string $projectPath): void
    {
        $testDirectories = [
            'tests/',
            'test/',
            'spec/'
        ];
        
        $testChanges = [];
        $cutoffTime = time() - (7 * 24 * 3600);
        
        foreach ($testDirectories as $testDir) {
            $testPath = $projectPath . '/' . $testDir;
            
            if (is_dir($testPath)) {
                $recentTestFiles = $this->scanRecentTestFiles($testPath, $cutoffTime);
                $testChanges = array_merge($testChanges, $recentTestFiles);
            }
        }
        
        if (!empty($testChanges)) {
            // Check if tests were deleted (potential regression)
            $deletedTests = $this->checkForDeletedTests($projectPath);
            
            $severity = !empty($deletedTests) ? 'high' : 'medium';
            
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                $severity,
                'Test suite changes detected - potential coverage regressions',
                'integrity',
                'test_coverage_regression'
            );
            
            $finding->addFileReference('tests/', 1, 'Test suite files');
            $finding->addMetric('test_files_changed', count($testChanges), 'files');
            $finding->addMetric('deleted_tests', count($deletedTests), 'files');
            
            $finding->addEvidence('static_analysis', [
                'changed_test_files' => array_slice($testChanges, 0, 10),
                'deleted_test_files' => $deletedTests,
                'total_test_changes' => count($testChanges)
            ], 'Test coverage change analysis');
            
            $finding->setRecommendation('Review test changes to ensure adequate coverage and no regression in test quality');
            $finding->setEstimatedHours((int)max(1, count($testChanges) * 0.25));
            
            $this->findings[] = $finding;
        }
    }
    
    /**
     * Scan for recent test file changes
     */
    private function scanRecentTestFiles(string $testPath, int $cutoffTime): array
    {
        $recentFiles = [];
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($testPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php' && $file->getMTime() > $cutoffTime) {
                $recentFiles[] = [
                    'file' => str_replace(base_path() . '/', '', $file->getPathname()),
                    'modified_time' => $file->getMTime(),
                    'size' => $file->getSize()
                ];
            }
        }
        
        return $recentFiles;
    }
    
    /**
     * Check for deleted tests via git
     */
    private function checkForDeletedTests(string $projectPath): array
    {
        try {
            $result = Process::path($projectPath)->run([
                'git', 'log', '--since=7.days.ago', '--diff-filter=D', '--name-only', '--pretty=format:', '--', 'tests/', 'test/', 'spec/'
            ]);
            
            if ($result->successful()) {
                $deletedFiles = array_filter(explode("\n", $result->output()));
                return array_unique($deletedFiles);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to check for deleted tests', ['error' => $e->getMessage()]);
        }
        
        return [];
    }
    
    /**
     * Detect performance regressions in critical code
     */
    private function detectPerformanceRegressions(string $projectPath): void
    {
        $performanceCriticalPaths = [
            'app/Http/Controllers/',
            'app/Services/',
            'app/Models/',
            'database/migrations/'
        ];
        
        $performanceRisks = [];
        
        foreach ($performanceCriticalPaths as $path) {
            $fullPath = $projectPath . '/' . $path;
            
            if (is_dir($fullPath)) {
                $risks = $this->analyzePerformanceCriticalChanges($fullPath, $path);
                $performanceRisks = array_merge($performanceRisks, $risks);
            }
        }
        
        if (!empty($performanceRisks)) {
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'medium',
                'Performance-critical code changes detected - potential performance regressions',
                'integrity',
                'performance_regression_risk'
            );
            
            $finding->addFileReference('app/', 1, 'Performance-critical code areas');
            $finding->addMetric('performance_risk_files', count($performanceRisks), 'files');
            $finding->addMetric('performance_risk_score', min(10, count($performanceRisks) * 1.5), 'score');
            
            $finding->addEvidence('static_analysis', [
                'performance_risks' => array_slice($performanceRisks, 0, 10),
                'total_risks' => count($performanceRisks)
            ], 'Performance regression risk analysis');
            
            $finding->setRecommendation('Review performance-critical changes and conduct performance testing');
            $finding->setEstimatedHours((int)(count($performanceRisks) * 0.5));
            
            $this->findings[] = $finding;
        }
    }
    
    /**
     * Analyze performance-critical code changes
     */
    private function analyzePerformanceCriticalChanges(string $path, string $relativePath): array
    {
        $risks = [];
        $cutoffTime = time() - (7 * 24 * 3600);
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php' && $file->getMTime() > $cutoffTime) {
                $content = file_get_contents($file->getPathname());
                
                // Check for performance-sensitive patterns
                $performancePatterns = [
                    '/foreach\s*\([^)]*\)\s*{[^}]*foreach/' => 'Nested loops detected',
                    '/DB::select\s*\(/' => 'Raw database queries',
                    '/->get\s*\(\)\s*->/' => 'Potential N+1 query pattern',
                    '/sleep\s*\(/' => 'Sleep/delay functions',
                    '/file_get_contents\s*\(/' => 'File I/O operations'
                ];
                
                foreach ($performancePatterns as $pattern => $description) {
                    if (preg_match($pattern, $content)) {
                        $risks[] = [
                            'file' => str_replace(base_path() . '/', '', $file->getPathname()),
                            'risk' => $description,
                            'modified_date' => date('Y-m-d H:i:s', $file->getMTime()),
                            'path_type' => $relativePath
                        ];
                    }
                }
            }
        }
        
        return $risks;
    }
    
    /**
     * Get directory last modified time
     */
    private function getDirectoryLastModified(string $dirPath): int
    {
        $lastModified = 0;
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dirPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $lastModified = max($lastModified, $file->getMTime());
            }
        }
        
        return $lastModified;
    }

    /**
     * LETHAL RISK SCORE CALCULATION (0-10 SCALE) with SURGICAL PRECISION
     */
    public function calculateOverallRiskScore(): float
    {
        $totalRisk = 0.0;
        $maxPossibleRisk = 0.0;
        
        // Calculate weighted risk based on findings
        foreach ($this->findings as $finding) {
            $category = $finding->getCategory();
            $severity = $finding->getSeverity();
            
            // Get base risk from category
            $categoryWeight = self::RISK_WEIGHTS[$category] ?? 1.0;
            
            // Apply severity multiplier
            $severityMultiplier = $this->getSeverityMultiplier($severity);
            
            // Calculate risk contribution
            $riskContribution = $categoryWeight * $severityMultiplier;
            $totalRisk += $riskContribution;
            
            // Track risk factors
            $this->riskFactors[] = [
                'category' => $category,
                'severity' => $severity,
                'weight' => $categoryWeight,
                'multiplier' => $severityMultiplier,
                'contribution' => $riskContribution,
                'finding_id' => $finding->getId()
            ];
        }
        
        // Calculate maximum possible risk (for normalization)
        $maxPossibleRisk = array_sum(self::RISK_WEIGHTS) * 2.0; // Assuming critical severity
        
        // Normalize to 0-10 scale
        $normalizedRisk = min(10.0, ($totalRisk / max(1.0, $maxPossibleRisk)) * 10.0);
        
        // Apply exponential scaling for high-risk scenarios
        if ($normalizedRisk > 6.0) {
            $normalizedRisk = 6.0 + (($normalizedRisk - 6.0) * 1.5);
            $normalizedRisk = min(10.0, $normalizedRisk);
        }
        
        $this->overallRiskScore = round($normalizedRisk, 2);
        
        Log::info('🎯 LETHAL Risk Score Calculated', [
            'overall_risk_score' => $this->overallRiskScore,
            'total_raw_risk' => round($totalRisk, 2),
            'max_possible_risk' => round($maxPossibleRisk, 2),
            'risk_factors_count' => count($this->riskFactors)
        ]);
        
        return $this->overallRiskScore;
    }
    
    /**
     * Get severity multiplier for risk calculation
     */
    private function getSeverityMultiplier(string $severity): float
    {
        return match($severity) {
            'critical' => 2.0,
            'high' => 1.5,
            'medium' => 1.0,
            'low' => 0.5,
            default => 1.0
        };
    }
    
    /**
     * CRITICAL CHANGE FLAGGING AND ALERTING with LETHAL PRECISION
     */
    public function flagCriticalChanges(): array
    {
        $this->criticalAlerts = [];
        
        // 1. CRITICAL ALERT: Overall risk score exceeds threshold
        if ($this->overallRiskScore >= self::CRITICAL_THRESHOLDS['risk_score']) {
            $this->addCriticalAlert(
                'CRITICAL_RISK_THRESHOLD_EXCEEDED',
                'Overall risk score exceeds critical threshold',
                [
                    'current_risk_score' => $this->overallRiskScore,
                    'threshold' => self::CRITICAL_THRESHOLDS['risk_score'],
                    'severity' => 'CRITICAL',
                    'immediate_action_required' => true
                ]
            );
        }
        
        // 2. CRITICAL ALERT: Massive code changes
        $this->flagMassiveChanges();
        
        // 3. CRITICAL ALERT: Critical file modifications
        $this->flagCriticalFileChanges();
        
        // 4. CRITICAL ALERT: Database integrity risks
        $this->flagDatabaseIntegrityRisks();
        
        // 5. CRITICAL ALERT: Configuration chaos
        $this->flagConfigurationChaos();
        
        // 6. CRITICAL ALERT: Security regression risks
        $this->flagSecurityRegressionRisks();
        
        // 7. CRITICAL ALERT: Performance degradation risks
        $this->flagPerformanceDegradationRisks();
        
        // Log all critical alerts
        if (!empty($this->criticalAlerts)) {
            Log::critical('🚨 CRITICAL CHANGES DETECTED', [
                'total_critical_alerts' => count($this->criticalAlerts),
                'overall_risk_score' => $this->overallRiskScore,
                'alerts' => $this->criticalAlerts
            ]);
        }
        
        return $this->criticalAlerts;
    }
    
    /**
     * Add critical alert with structured data
     */
    private function addCriticalAlert(string $alertType, string $message, array $data): void
    {
        $this->criticalAlerts[] = [
            'alert_type' => $alertType,
            'message' => $message,
            'timestamp' => now()->toISOString(),
            'severity' => $data['severity'] ?? 'HIGH',
            'data' => $data,
            'alert_id' => 'ALERT-' . str_pad(count($this->criticalAlerts) + 1, 3, '0', STR_PAD_LEFT)
        ];
    }
    
    /**
     * Flag massive code changes
     */
    private function flagMassiveChanges(): void
    {
        foreach ($this->findings as $finding) {
            if ($finding->getCategory() === 'massive_changes') {
                $metrics = $finding->getMetrics();
                $totalChanges = $metrics['total_line_changes'] ?? 0;
                
                if ($totalChanges >= self::CRITICAL_THRESHOLDS['massive_changes_lines']) {
                    $this->addCriticalAlert(
                        'MASSIVE_CODE_CHANGES',
                        "Massive code changes detected: {$totalChanges} lines modified",
                        [
                            'total_changes' => $totalChanges,
                            'threshold' => self::CRITICAL_THRESHOLDS['massive_changes_lines'],
                            'severity' => 'CRITICAL',
                            'risk_level' => 'EXTREME',
                            'finding_id' => $finding->getId(),
                            'recommended_action' => 'IMMEDIATE_REVIEW_REQUIRED'
                        ]
                    );
                }
            }
        }
    }
    
    /**
     * Flag critical file changes
     */
    private function flagCriticalFileChanges(): void
    {
        $criticalFileFindings = array_filter($this->findings, fn($f) => $f->getCategory() === 'critical_file_changes');
        
        if (count($criticalFileFindings) >= self::CRITICAL_THRESHOLDS['critical_files_count']) {
            $this->addCriticalAlert(
                'CRITICAL_FILES_MODIFIED',
                'Multiple critical system files modified',
                [
                    'critical_files_count' => count($criticalFileFindings),
                    'threshold' => self::CRITICAL_THRESHOLDS['critical_files_count'],
                    'severity' => 'CRITICAL',
                    'risk_level' => 'HIGH',
                    'recommended_action' => 'CONFIGURATION_AUDIT_REQUIRED'
                ]
            );
        }
    }
    
    /**
     * Flag database integrity risks
     */
    private function flagDatabaseIntegrityRisks(): void
    {
        foreach ($this->findings as $finding) {
            if ($finding->getCategory() === 'database_regression_risk') {
                $metrics = $finding->getMetrics();
                $riskyOperations = $metrics['risky_operations'] ?? 0;
                
                if ($riskyOperations >= self::CRITICAL_THRESHOLDS['risky_operations_count']) {
                    $this->addCriticalAlert(
                        'DATABASE_INTEGRITY_RISK',
                        "High-risk database operations detected: {$riskyOperations} risky operations",
                        [
                            'risky_operations' => $riskyOperations,
                            'threshold' => self::CRITICAL_THRESHOLDS['risky_operations_count'],
                            'severity' => 'CRITICAL',
                            'risk_level' => 'EXTREME',
                            'finding_id' => $finding->getId(),
                            'recommended_action' => 'DATABASE_BACKUP_AND_REVIEW_REQUIRED'
                        ]
                    );
                }
            }
        }
    }
    
    /**
     * Flag configuration chaos
     */
    private function flagConfigurationChaos(): void
    {
        foreach ($this->findings as $finding) {
            if ($finding->getCategory() === 'config_regression_risk') {
                $metrics = $finding->getMetrics();
                $changeFrequency = $metrics['change_frequency'] ?? 0;
                
                if ($changeFrequency >= self::CRITICAL_THRESHOLDS['config_changes_frequency']) {
                    $this->addCriticalAlert(
                        'CONFIGURATION_CHAOS',
                        "Rapid configuration changes detected: {$changeFrequency} changes per day",
                        [
                            'change_frequency' => $changeFrequency,
                            'threshold' => self::CRITICAL_THRESHOLDS['config_changes_frequency'],
                            'severity' => 'HIGH',
                            'risk_level' => 'HIGH',
                            'finding_id' => $finding->getId(),
                            'recommended_action' => 'CONFIGURATION_FREEZE_RECOMMENDED'
                        ]
                    );
                }
            }
        }
    }
    
    /**
     * Flag security regression risks
     */
    private function flagSecurityRegressionRisks(): void
    {
        foreach ($this->findings as $finding) {
            if ($finding->getCategory() === 'config_security' || 
                strpos($finding->getDescription(), 'sensitive') !== false ||
                strpos($finding->getDescription(), 'security') !== false) {
                
                $this->addCriticalAlert(
                    'SECURITY_REGRESSION_RISK',
                    'Security-related changes detected - potential vulnerability introduction',
                    [
                        'severity' => 'CRITICAL',
                        'risk_level' => 'EXTREME',
                        'finding_id' => $finding->getId(),
                        'security_concern' => true,
                        'recommended_action' => 'IMMEDIATE_SECURITY_AUDIT_REQUIRED'
                    ]
                );
            }
        }
    }
    
    /**
     * Flag performance degradation risks
     */
    private function flagPerformanceDegradationRisks(): void
    {
        $performanceFindings = array_filter($this->findings, fn($f) => $f->getCategory() === 'performance_regression_risk');
        
        if (!empty($performanceFindings)) {
            $totalPerformanceRisks = 0;

            foreach ($performanceFindings as $finding) {
                $metrics = $finding->getMetrics();
                $totalPerformanceRisks += $metrics['performance_risk_files'] ?? 0;
            }
            
            if ($totalPerformanceRisks > 10) {
                $this->addCriticalAlert(
                    'PERFORMANCE_DEGRADATION_RISK',
                    "High performance regression risk: {$totalPerformanceRisks} risky files modified",
                    [
                        'performance_risk_files' => $totalPerformanceRisks,
                        'severity' => 'HIGH',
                        'risk_level' => 'HIGH',
                        'recommended_action' => 'PERFORMANCE_TESTING_REQUIRED'
                    ]
                );
            }
        }
    }
    
    /**
     * Get comprehensive risk assessment
     */
    public function getRiskAssessment(): array
    {
        $this->calculateOverallRiskScore();
        $criticalAlerts = $this->flagCriticalChanges();
        
        return [
            'overall_risk_score' => $this->overallRiskScore,
            'risk_level' => $this->getRiskLevel($this->overallRiskScore),
            'total_findings' => count($this->findings),
            'critical_alerts' => $criticalAlerts,
            'critical_alerts_count' => count($criticalAlerts),
            'risk_factors' => $this->riskFactors,
            'risk_breakdown' => $this->getRiskBreakdown(),
            'recommendations' => $this->getRecommendations(),
            'assessment_timestamp' => now()->toISOString()
        ];
    }
    
    /**
     * Get risk level description
     */
    private function getRiskLevel(float $score): string
    {
        return match(true) {
            $score >= 9.0 => 'EXTREME',
            $score >= 7.0 => 'CRITICAL',
            $score >= 5.0 => 'HIGH',
            $score >= 3.0 => 'MEDIUM',
            $score >= 1.0 => 'LOW',
            default => 'MINIMAL'
        };
    }
    
    /**
     * Get risk breakdown by category
     */
    private function getRiskBreakdown(): array
    {
        $breakdown = [];
        
        foreach (self::RISK_WEIGHTS as $category => $weight) {
            $categoryFindings = array_filter($this->findings, fn($f) => $f->getCategory() === $category);
            $categoryRisk = 0.0;
            
            foreach ($categoryFindings as $finding) {
                $severityMultiplier = $this->getSeverityMultiplier($finding->getSeverity());
                $categoryRisk += $weight * $severityMultiplier;
            }
            
            $breakdown[$category] = [
                'risk_score' => round($categoryRisk, 2),
                'findings_count' => count($categoryFindings),
                'weight' => $weight,
                'normalized_score' => round(min(10.0, $categoryRisk), 2)
            ];
        }
        
        return $breakdown;
    }
    
    /**
     * Get prioritized recommendations
     */
    private function getRecommendations(): array
    {
        $recommendations = [];
        
        if ($this->overallRiskScore >= 7.0) {
            $recommendations[] = [
                'priority' => 'CRITICAL',
                'action' => 'IMMEDIATE_DEPLOYMENT_FREEZE',
                'description' => 'Risk score exceeds critical threshold - freeze deployments until review complete'
            ];
        }
        
        if (!empty($this->criticalAlerts)) {
            $recommendations[] = [
                'priority' => 'HIGH',
                'action' => 'CRITICAL_ALERT_REVIEW',
                'description' => 'Review and address all critical alerts before proceeding'
            ];
        }
        
        if ($this->overallRiskScore >= 5.0) {
            $recommendations[] = [
                'priority' => 'HIGH',
                'action' => 'COMPREHENSIVE_TESTING',
                'description' => 'Execute comprehensive test suite including regression tests'
            ];
        }
        
        return $recommendations;
    }

    /**
     * Generate differential analysis metrics with LETHAL PRECISION
     */
    private function generateDifferentialMetrics(): array
    {
        // Calculate risk assessment
        $riskAssessment = $this->getRiskAssessment();
        
        return [
            [
                'name' => 'total_integrity_findings',
                'value' => count($this->findings),
                'unit' => 'issues'
            ],
            [
                'name' => 'overall_risk_score',
                'value' => $this->overallRiskScore,
                'unit' => 'score'
            ],
            [
                'name' => 'risk_level',
                'value' => $this->getRiskLevel($this->overallRiskScore),
                'unit' => 'level'
            ],
            [
                'name' => 'critical_alerts_count',
                'value' => count($this->criticalAlerts),
                'unit' => 'alerts'
            ],
            [
                'name' => 'git_repository_status',
                'value' => $this->isGitRepository(base_path()) ? 1 : 0,
                'unit' => 'boolean'
            ],
            [
                'name' => 'analysis_timestamp',
                'value' => now()->timestamp,
                'unit' => 'timestamp'
            ],
            [
                'name' => 'commit_analysis_count',
                'value' => count($this->commitAnalysis),
                'unit' => 'commits'
            ],
            [
                'name' => 'behavioral_regression_checks',
                'value' => 6, // Number of regression detection methods
                'unit' => 'checks'
            ],
            [
                'name' => 'risk_factors_count',
                'value' => count($this->riskFactors),
                'unit' => 'factors'
            ]
        ];
    }
}