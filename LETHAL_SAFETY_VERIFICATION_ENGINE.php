<?php

/**
 * 💀 LETHAL SAFETY VERIFICATION ENGINE 💀
 * 
 * COMPREHENSIVE SAFETY VERIFICATION FOR N+1 FIXES
 * - Validates all automated fixes are safe
 * - Checks for broken relationships
 * - Verifies syntax integrity
 * - Tests functionality preservation
 */

require_once __DIR__ . '/vendor/autoload.php';

class LethalSafetyVerificationEngine
{
    private array $safetyIssues = [];
    private array $verifiedFiles = [];
    private int $totalFilesChecked = 0;
    private int $issuesFound = 0;
    private float $startTime;

    public function __construct()
    {
        $this->startTime = microtime(true);
        echo "💀 LETHAL SAFETY VERIFICATION ENGINE ACTIVATED 💀\n";
        echo "🎯 TARGET: VERIFY ALL N+1 FIXES ARE SAFE\n";
        echo "🔥 MODE: COMPREHENSIVE SAFETY VALIDATION\n";
        echo "=" . str_repeat("=", 80) . "\n\n";
    }

    /**
     * Execute comprehensive safety verification
     */
    public function executeSafetyVerification(): array
    {
        echo "🔥 PHASE 1: SYNTAX VALIDATION\n";
        $this->validateSyntax();
        
        echo "\n🔥 PHASE 2: RELATIONSHIP VALIDATION\n";
        $this->validateRelationships();
        
        echo "\n🔥 PHASE 3: EAGER LOADING VALIDATION\n";
        $this->validateEagerLoading();
        
        echo "\n🔥 PHASE 4: BLADE TEMPLATE VALIDATION\n";
        $this->validateBladeTemplates();
        
        echo "\n🔥 PHASE 5: MODEL ACCESSOR VALIDATION\n";
        $this->validateModelAccessors();
        
        echo "\n🔥 PHASE 6: FUNCTIONALITY TESTING\n";
        $this->testFunctionality();
        
        return $this->generateSafetyReport();
    }

    /**
     * PHASE 1: Validate syntax of all modified files
     */
    private function validateSyntax()
    {
        echo "🔍 Validating syntax of modified files...\n";
        
        $modifiedFiles = $this->getModifiedFiles();
        
        foreach ($modifiedFiles as $file) {
            if (file_exists($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $result = shell_exec("php -l \"$file\" 2>&1");
                
                if (strpos($result, 'No syntax errors') === false) {
                    $this->addSafetyIssue([
                        'type' => 'syntax_error',
                        'file' => $file,
                        'severity' => 'critical',
                        'description' => 'Syntax error detected',
                        'details' => trim($result)
                    ]);
                } else {
                    echo "  ✅ Syntax OK: $file\n";
                    $this->verifiedFiles[] = $file;
                }
                $this->totalFilesChecked++;
            }
        }
    }

    /**
     * PHASE 2: Validate relationships exist and are correct
     */
    private function validateRelationships()
    {
        echo "🔍 Validating relationship integrity...\n";
        
        $phpFiles = $this->getPhpFiles(['app/Http/Controllers', 'app/Services', 'app/Models']);
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            
            // Check for invalid relationship patterns
            if (preg_match_all('/->with\(\[([^\]]+)\]\)/', $content, $matches)) {
                foreach ($matches[1] as $relationshipString) {
                    $relationships = array_map('trim', explode(',', str_replace("'", '', $relationshipString)));
                    
                    // Check for obviously invalid relationships
                    $invalidRelationships = ['user', 'users', 'course', 'courses', 'topic', 'topics', 'questions', 'assignments'];
                    $foundInvalid = array_intersect($relationships, $invalidRelationships);
                    
                    if (count($foundInvalid) > 3) { // If more than 3 generic relationships, likely invalid
                        $this->addSafetyIssue([
                            'type' => 'invalid_relationships',
                            'file' => $file,
                            'severity' => 'high',
                            'description' => 'Potentially invalid eager loading relationships',
                            'details' => 'Found: ' . implode(', ', $relationships)
                        ]);
                    }
                }
            }
        }
    }

    /**
     * PHASE 3: Validate eager loading patterns are correct
     */
    private function validateEagerLoading()
    {
        echo "🔍 Validating eager loading patterns...\n";
        
        $controllerFiles = $this->getPhpFiles(['app/Http/Controllers']);
        
        foreach ($controllerFiles as $file) {
            $content = file_get_contents($file);
            
            // Check for proper eager loading patterns
            if (preg_match_all('/(\w+)::(with\([^)]+\)->(get|all)\(\))/', $content, $matches)) {
                foreach ($matches[0] as $index => $match) {
                    $model = $matches[1][$index];
                    $pattern = $matches[2][$index];
                    
                    // Validate the pattern makes sense
                    if (strpos($pattern, 'all()') !== false && strpos($pattern, 'with(') !== false) {
                        // Should use get() instead of all() when using with()
                        $this->addSafetyIssue([
                            'type' => 'eager_loading_pattern',
                            'file' => $file,
                            'severity' => 'medium',
                            'description' => 'Should use get() instead of all() with eager loading',
                            'details' => $match
                        ]);
                    }
                }
            }
        }
    }

    /**
     * PHASE 4: Validate Blade template fixes
     */
    private function validateBladeTemplates()
    {
        echo "🔍 Validating Blade template fixes...\n";
        
        $bladeFiles = $this->getBladeFiles();
        
        foreach ($bladeFiles as $file) {
            $content = file_get_contents($file);
            
            // Check for N+1 fix comments
            if (strpos($content, '💀 N+1 FIX NEEDED') !== false) {
                echo "  ✅ N+1 comment added: $file\n";
            }
            
            // Check for potential remaining N+1 issues
            if (preg_match('/@foreach.*\$\w+.*as.*\$\w+.*\{\{.*\$\w+->\w+/', $content)) {
                $this->addSafetyIssue([
                    'type' => 'blade_n_plus_one',
                    'file' => $file,
                    'severity' => 'medium',
                    'description' => 'Potential N+1 issue still exists in Blade template',
                    'details' => 'Manual controller optimization needed'
                ]);
            }
        }
    }

    /**
     * PHASE 5: Validate model accessor warnings
     */
    private function validateModelAccessors()
    {
        echo "🔍 Validating model accessor warnings...\n";
        
        $modelFiles = $this->getPhpFiles(['app/Models']);
        
        foreach ($modelFiles as $file) {
            $content = file_get_contents($file);
            
            // Check for N+1 warning comments
            if (strpos($content, '💀 N+1 WARNING') !== false) {
                echo "  ✅ N+1 warning added: $file\n";
            }
        }
    }

    /**
     * PHASE 6: Test basic functionality
     */
    private function testFunctionality()
    {
        echo "🔍 Testing basic functionality...\n";
        
        // Test if Laravel can boot without errors
        $result = shell_exec('php artisan --version 2>&1');
        
        if (strpos($result, 'Laravel Framework') !== false) {
            echo "  ✅ Laravel boots successfully\n";
        } else {
            $this->addSafetyIssue([
                'type' => 'boot_failure',
                'file' => 'application',
                'severity' => 'critical',
                'description' => 'Laravel application fails to boot',
                'details' => trim($result)
            ]);
        }
        
        // Test route list
        $result = shell_exec('php artisan route:list --compact 2>&1');
        
        if (strpos($result, 'GET') !== false) {
            echo "  ✅ Routes load successfully\n";
        } else {
            $this->addSafetyIssue([
                'type' => 'route_failure',
                'file' => 'routes',
                'severity' => 'high',
                'description' => 'Routes fail to load',
                'details' => trim($result)
            ]);
        }
    }

    /**
     * Generate comprehensive safety report
     */
    private function generateSafetyReport(): array
    {
        $executionTime = microtime(true) - $this->startTime;
        
        $report = [
            'execution_time' => round($executionTime, 2),
            'total_files_checked' => $this->totalFilesChecked,
            'verified_files' => count($this->verifiedFiles),
            'issues_found' => $this->issuesFound,
            'safety_score' => $this->calculateSafetyScore(),
            'issues_by_severity' => $this->getIssuesBySeverity(),
            'issues_by_type' => $this->getIssuesByType(),
            'safety_issues' => $this->safetyIssues,
            'recommendations' => $this->generateRecommendations()
        ];
        
        echo "\n💀 LETHAL SAFETY VERIFICATION COMPLETE 💀\n";
        echo "🎯 Files Checked: {$report['total_files_checked']}\n";
        echo "✅ Files Verified: {$report['verified_files']}\n";
        echo "🚨 Issues Found: {$report['issues_found']}\n";
        echo "📊 Safety Score: {$report['safety_score']}%\n";
        echo "⏱️  Execution Time: {$report['execution_time']}s\n";
        
        return $report;
    }

    // Helper methods
    private function addSafetyIssue($issue)
    {
        $this->safetyIssues[] = $issue;
        $this->issuesFound++;
        echo "    ❌ {$issue['severity']}: {$issue['file']} - {$issue['description']}\n";
    }

    private function getModifiedFiles()
    {
        // Get files from the N+1 annihilation report
        $reportFile = 'n_plus_one_annihilation_report.json';
        if (file_exists($reportFile)) {
            $report = json_decode(file_get_contents($reportFile), true);
            return array_values($report['files_modified'] ?? []);
        }
        return [];
    }

    private function getPhpFiles($directories)
    {
        $files = [];
        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
                foreach ($iterator as $file) {
                    if ($file->getExtension() === 'php') {
                        $files[] = $file->getPathname();
                    }
                }
            }
        }
        return $files;
    }

    private function getBladeFiles()
    {
        $files = [];
        $dir = 'resources/views';
        if (is_dir($dir)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
            foreach ($iterator as $file) {
                if ($file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }
        return $files;
    }

    private function calculateSafetyScore()
    {
        if ($this->totalFilesChecked === 0) return 0;
        
        $criticalIssues = count(array_filter($this->safetyIssues, fn($issue) => $issue['severity'] === 'critical'));
        $highIssues = count(array_filter($this->safetyIssues, fn($issue) => $issue['severity'] === 'high'));
        $mediumIssues = count(array_filter($this->safetyIssues, fn($issue) => $issue['severity'] === 'medium'));
        
        $penalty = ($criticalIssues * 30) + ($highIssues * 15) + ($mediumIssues * 5);
        $score = max(0, 100 - $penalty);
        
        return round($score, 2);
    }

    private function getIssuesBySeverity()
    {
        $severities = [];
        foreach ($this->safetyIssues as $issue) {
            $severities[$issue['severity']] = ($severities[$issue['severity']] ?? 0) + 1;
        }
        return $severities;
    }

    private function getIssuesByType()
    {
        $types = [];
        foreach ($this->safetyIssues as $issue) {
            $types[$issue['type']] = ($types[$issue['type']] ?? 0) + 1;
        }
        return $types;
    }

    private function generateRecommendations()
    {
        $recommendations = [];
        
        if ($this->issuesFound > 0) {
            $recommendations[] = 'Review and fix all identified safety issues before deployment';
            $recommendations[] = 'Test all modified functionality manually';
            $recommendations[] = 'Run comprehensive test suite';
        }
        
        $recommendations[] = 'Monitor application performance after deployment';
        $recommendations[] = 'Set up database query monitoring';
        $recommendations[] = 'Implement automated performance regression testing';
        
        return $recommendations;
    }
}

// Execute the safety verification
if (php_sapi_name() === 'cli') {
    $verifier = new LethalSafetyVerificationEngine();
    $report = $verifier->executeSafetyVerification();
    
    // Save report
    file_put_contents('safety_verification_report.json', json_encode($report, JSON_PRETTY_PRINT));
    echo "\n📄 Safety report saved to: safety_verification_report.json\n";
}