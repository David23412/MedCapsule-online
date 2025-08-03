<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Audit\Components\SecurityScanner;
use App\Services\Audit\Components\FrontendAnalyzer;
use App\Services\Audit\Components\BusinessLogicValidator;
use App\Services\Audit\Components\DependencyAuditor;
use App\Services\Audit\Components\FileScanner;

class VerifySecurityFixes extends Command
{
    protected $signature = 'audit:verify-fixes';
    protected $description = 'Verify that security fixes have been applied successfully';

    public function handle()
    {
        $this->info('🔍 VERIFYING SECURITY FIXES');
        $this->info('═══════════════════════════');
        $this->newLine();

        $fileScanner = new FileScanner();
        $totalOriginal = 4281;
        $totalFixed = 0;
        $remainingIssues = 0;

        try {
            // Check Security Scanner
            $this->info('🔒 Verifying Security Fixes...');
            $securityScanner = new SecurityScanner($fileScanner);
            $securityResult = $securityScanner->executeSecurityScan(base_path());
            $securityRemaining = $securityResult->getFindingsCount();
            $securityFixed = 768 - $securityRemaining;
            $this->line("✅ Security: {$securityFixed} fixed, {$securityRemaining} remaining");

            // Check Frontend Analyzer
            $this->info('🎨 Verifying Frontend Fixes...');
            $frontendAnalyzer = new FrontendAnalyzer($fileScanner);
            $frontendResult = $frontendAnalyzer->executeFrontendAnalysis(base_path());
            $frontendRemaining = $frontendResult->getFindingsCount();
            $frontendFixed = 847 - $frontendRemaining;
            $this->line("✅ Frontend: {$frontendFixed} fixed, {$frontendRemaining} remaining");

            // Check Business Logic Validator
            $this->info('💼 Verifying Business Logic Fixes...');
            $businessValidator = new BusinessLogicValidator($fileScanner);
            $businessResult = $businessValidator->executeBusinessLogicValidation(base_path());
            $businessRemaining = $businessResult->getFindingsCount();
            $businessFixed = 1590 - $businessRemaining;
            $this->line("✅ Business Logic: {$businessFixed} fixed, {$businessRemaining} remaining");

            // Check Dependency Auditor
            $this->info('📦 Verifying Dependency Status...');
            $dependencyAuditor = new DependencyAuditor($fileScanner);
            $dependencyResult = $dependencyAuditor->executeDependencyAudit(base_path());
            $dependencyRemaining = $dependencyResult->getFindingsCount();
            $dependencyFixed = 715 - $dependencyRemaining;
            $this->line("✅ Dependencies: {$dependencyFixed} fixed, {$dependencyRemaining} remaining");

            $totalFixed = $securityFixed + $frontendFixed + $businessFixed + $dependencyFixed;
            $remainingIssues = $securityRemaining + $frontendRemaining + $businessRemaining + $dependencyRemaining;

            $this->newLine();
            $this->info('📊 SECURITY FIX VERIFICATION SUMMARY');
            $this->info('═══════════════════════════════════');
            
            $this->table(
                ['Category', 'Original', 'Fixed', 'Remaining', 'Fix Rate'],
                [
                    ['🔒 Security', '768', $securityFixed, $securityRemaining, round(($securityFixed/768)*100, 1) . '%'],
                    ['🎨 Frontend', '847', $frontendFixed, $frontendRemaining, round(($frontendFixed/847)*100, 1) . '%'],
                    ['💼 Business Logic', '1,590', $businessFixed, $businessRemaining, round(($businessFixed/1590)*100, 1) . '%'],
                    ['📦 Dependencies', '715', $dependencyFixed, $dependencyRemaining, round(($dependencyFixed/715)*100, 1) . '%'],
                    ['TOTAL', '4,281', $totalFixed, $remainingIssues, round(($totalFixed/4281)*100, 1) . '%']
                ]
            );

            $this->newLine();
            $securityImprovement = round(($totalFixed / $totalOriginal) * 100, 1);
            
            if ($securityImprovement >= 80) {
                $this->info("🎉 EXCELLENT SECURITY IMPROVEMENT: {$securityImprovement}%");
                $this->info('🛡️ Your application is significantly more secure!');
            } elseif ($securityImprovement >= 60) {
                $this->info("✅ GOOD SECURITY IMPROVEMENT: {$securityImprovement}%");
                $this->info('🔒 Your application security has been enhanced');
            } elseif ($securityImprovement >= 40) {
                $this->warn("⚠️ MODERATE SECURITY IMPROVEMENT: {$securityImprovement}%");
                $this->warn('🔧 More fixes needed for optimal security');
            } else {
                $this->error("❌ LIMITED SECURITY IMPROVEMENT: {$securityImprovement}%");
                $this->error('🚨 Additional security work required');
            }

            $this->newLine();
            $this->info('🎯 NEXT STEPS:');
            if ($remainingIssues > 0) {
                $this->line("• {$remainingIssues} issues still need attention");
                $this->line('• Review manual fixes for testing and dependencies');
                $this->line('• Update package dependencies for remaining vulnerabilities');
            }
            $this->line('• Run comprehensive tests to verify fixes');
            $this->line('• Deploy to production with enhanced security');

        } catch (\Exception $e) {
            $this->error('❌ Verification failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}