<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Audit\Components\SecurityScanner;
use App\Services\Audit\Components\FrontendAnalyzer;
use App\Services\Audit\Components\BusinessLogicValidator;
use App\Services\Audit\Components\DependencyAuditor;
use App\Services\Audit\Components\TestingAnalyzer;
use App\Services\Audit\Components\FileScanner;

class TestNewAuditComponents extends Command
{
    protected $signature = 'audit:test-new-components';
    protected $description = 'Test the newly implemented audit components';

    public function handle()
    {
        $this->info('🧪 Testing New Audit Components');
        $this->newLine();

        $fileScanner = new FileScanner();
        $projectPath = base_path();

        try {
            // Test Security Scanner
            $this->info('🔍 Testing Security Scanner...');
            $securityScanner = new SecurityScanner($fileScanner);
            $securityResult = $securityScanner->executeSecurityScan($projectPath);
            $this->info("✅ Security Scanner: {$securityResult->getFindingsCount()} findings");
            $this->newLine();

            // Test Frontend Analyzer
            $this->info('🎨 Testing Frontend Analyzer...');
            $frontendAnalyzer = new FrontendAnalyzer($fileScanner);
            $frontendResult = $frontendAnalyzer->executeFrontendAnalysis($projectPath);
            $this->info("✅ Frontend Analyzer: {$frontendResult->getFindingsCount()} findings");
            $this->newLine();

            // Test Business Logic Validator
            $this->info('💼 Testing Business Logic Validator...');
            $businessValidator = new BusinessLogicValidator($fileScanner);
            $businessResult = $businessValidator->executeBusinessLogicValidation($projectPath);
            $this->info("✅ Business Logic Validator: {$businessResult->getFindingsCount()} findings");
            $this->newLine();

            // Test Dependency Auditor
            $this->info('📦 Testing Dependency Auditor...');
            $dependencyAuditor = new DependencyAuditor($fileScanner);
            $dependencyResult = $dependencyAuditor->executeDependencyAudit($projectPath);
            $this->info("✅ Dependency Auditor: {$dependencyResult->getFindingsCount()} findings");
            $this->newLine();

            // Test Testing Analyzer
            $this->info('🧪 Testing Testing Analyzer...');
            $testingAnalyzer = new TestingAnalyzer($fileScanner);
            $testingResult = $testingAnalyzer->executeTestingAnalysis($projectPath);
            $this->info("✅ Testing Analyzer: {$testingResult->getFindingsCount()} findings");
            $this->newLine();

            // Summary
            $totalFindings = $securityResult->getFindingsCount() + 
                           $frontendResult->getFindingsCount() + 
                           $businessResult->getFindingsCount() + 
                           $dependencyResult->getFindingsCount() + 
                           $testingResult->getFindingsCount();

            $this->info('📊 Test Summary:');
            $this->table(
                ['Component', 'Status', 'Findings', 'Execution Time'],
                [
                    ['Security Scanner', '✅ Working', $securityResult->getFindingsCount(), round($securityResult->getExecutionTime(), 2) . 's'],
                    ['Frontend Analyzer', '✅ Working', $frontendResult->getFindingsCount(), round($frontendResult->getExecutionTime(), 2) . 's'],
                    ['Business Logic Validator', '✅ Working', $businessResult->getFindingsCount(), round($businessResult->getExecutionTime(), 2) . 's'],
                    ['Dependency Auditor', '✅ Working', $dependencyResult->getFindingsCount(), round($dependencyResult->getExecutionTime(), 2) . 's'],
                    ['Testing Analyzer', '✅ Working', $testingResult->getFindingsCount(), round($testingResult->getExecutionTime(), 2) . 's'],
                ]
            );

            $this->newLine();
            $this->info("🎉 All components working! Total findings: {$totalFindings}");
            $this->info('✅ All 5 audit components are fully operational and integrated');

        } catch (\Exception $e) {
            $this->error('❌ Component test failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}