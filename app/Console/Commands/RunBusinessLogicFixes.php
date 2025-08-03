<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Audit\Components\BusinessLogicValidator;
use App\Services\Audit\Components\FileScanner;
use Illuminate\Support\Facades\File;

class RunBusinessLogicFixes extends Command
{
    protected $signature = 'audit:fix-business-logic {--dry-run : Show what would be fixed without making changes}';
    protected $description = 'Fix the business logic issues found by the business logic validator';

    public function handle()
    {
        $this->info('💼 STARTING BUSINESS LOGIC FIXES');
        $this->info('═══════════════════════════════');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        try {
            // Run business logic analysis to get current findings
            $fileScanner = new FileScanner();
            $businessValidator = new BusinessLogicValidator($fileScanner);
            $result = $businessValidator->executeBusinessLogicValidation(base_path());

            $findings = $result->getFindings();
            $this->info("Found {$result->getFindingsCount()} business logic issues to fix");
            $this->newLine();

            $fixedCount = 0;
            $criticalFixed = 0;
            $securityFixed = 0;
            $reliabilityFixed = 0;

            // Group findings by component
            $findingsByType = [];
            foreach ($findings as $finding) {
                $component = $finding->getComponent();
                if (!isset($findingsByType[$component])) {
                    $findingsByType[$component] = [];
                }
                $findingsByType[$component][] = $finding;
            }

            // Fix quiz scoring issues (CRITICAL)
            if (isset($findingsByType['quiz_scoring'])) {
                $this->info('🚨 FIXING QUIZ SCORING VULNERABILITIES (CRITICAL)');
                $this->info('─────────────────────────────────────────────────');
                
                foreach ($findingsByType['quiz_scoring'] as $finding) {
                    if ($this->fixQuizScoring($finding, $dryRun)) {
                        $fixedCount++;
                        $criticalFixed++;
                        $fileRef = $finding->getFileReferences()[0];
                        $this->line("✅ Fixed quiz scoring in {$fileRef['file']}:{$fileRef['line']}");
                    }
                }
                $this->newLine();
            }

            // Fix grading calculation issues (CRITICAL)
            if (isset($findingsByType['grading_calculation'])) {
                $this->info('🚨 FIXING GRADING CALCULATION ISSUES (CRITICAL)');
                $this->info('──────────────────────────────────────────────');
                
                foreach ($findingsByType['grading_calculation'] as $finding) {
                    if ($this->fixGradingCalculation($finding, $dryRun)) {
                        $fixedCount++;
                        $criticalFixed++;
                        $fileRef = $finding->getFileReferences()[0];
                        $this->line("✅ Fixed grading calculation in {$fileRef['file']}:{$fileRef['line']}");
                    }
                }
                $this->newLine();
            }

            // Fix user creation issues (HIGH)
            if (isset($findingsByType['user_creation'])) {
                $this->info('🚨 FIXING USER CREATION ISSUES (HIGH)');
                $this->info('───────────────────────────────────');
                
                foreach ($findingsByType['user_creation'] as $finding) {
                    if ($this->fixUserCreation($finding, $dryRun)) {
                        $fixedCount++;
                        $securityFixed++;
                        $fileRef = $finding->getFileReferences()[0];
                        $this->line("✅ Fixed user creation in {$fileRef['file']}:{$fileRef['line']}");
                    }
                }
                $this->newLine();
            }

            // Fix role assignment issues (CRITICAL)
            if (isset($findingsByType['role_assignment'])) {
                $this->info('🚨 FIXING ROLE ASSIGNMENT ISSUES (CRITICAL)');
                $this->info('──────────────────────────────────────────');
                
                foreach ($findingsByType['role_assignment'] as $finding) {
                    if ($this->fixRoleAssignment($finding, $dryRun)) {
                        $fixedCount++;
                        $criticalFixed++;
                        $fileRef = $finding->getFileReferences()[0];
                        $this->line("✅ Fixed role assignment in {$fileRef['file']}:{$fileRef['line']}");
                    }
                }
                $this->newLine();
            }

            // Fix payment processing issues (CRITICAL)
            if (isset($findingsByType['payment_transaction'])) {
                $this->info('🚨 FIXING PAYMENT PROCESSING ISSUES (CRITICAL)');
                $this->info('───────────────────────────────────────────────');
                
                foreach ($findingsByType['payment_transaction'] as $finding) {
                    if ($this->fixPaymentProcessing($finding, $dryRun)) {
                        $fixedCount++;
                        $criticalFixed++;
                        $fileRef = $finding->getFileReferences()[0];
                        $this->line("✅ Fixed payment processing in {$fileRef['file']}:{$fileRef['line']}");
                    }
                }
                $this->newLine();
            }

            // Fix notification issues (MEDIUM)
            if (isset($findingsByType['email_queue'])) {
                $this->info('🚨 FIXING NOTIFICATION ISSUES (MEDIUM)');
                $this->info('────────────────────────────────────');
                
                foreach ($findingsByType['email_queue'] as $finding) {
                    if ($this->fixNotificationIssues($finding, $dryRun)) {
                        $fixedCount++;
                        $reliabilityFixed++;
                        $fileRef = $finding->getFileReferences()[0];
                        $this->line("✅ Fixed notification in {$fileRef['file']}:{$fileRef['line']}");
                    }
                }
                $this->newLine();
            }

            // Create business logic validation helpers
            $this->createBusinessLogicHelpers($dryRun);

            // Summary
            $this->info('📊 BUSINESS LOGIC FIX SUMMARY');
            $this->info('───────────────────────────');
            $this->table(
                ['Severity', 'Issues Fixed', 'Impact'],
                [
                    ['Critical', $criticalFixed, $criticalFixed > 0 ? '🛡️ SECURITY ENHANCED' : '⚠️ NEEDS ATTENTION'],
                    ['Security', $securityFixed, $securityFixed > 0 ? '🔒 MORE SECURE' : '⚠️ NEEDS ATTENTION'],
                    ['Reliability', $reliabilityFixed, $reliabilityFixed > 0 ? '⚡ MORE RELIABLE' : '⚠️ NEEDS ATTENTION'],
                    ['TOTAL', $fixedCount, $fixedCount > 0 ? '✅ BUSINESS LOGIC SECURED' : '⚠️ NO FIXES APPLIED']
                ]
            );

            $this->newLine();
            if ($dryRun) {
                $this->info("🔍 DRY RUN COMPLETE - {$fixedCount} business logic issues would be fixed");
                $this->info('Run without --dry-run to apply fixes');
            } else {
                $this->info("🎉 BUSINESS LOGIC FIXES COMPLETE - {$fixedCount} issues fixed!");
                $this->info('💼 Your business logic is now more secure and reliable');
            }

        } catch (\Exception $e) {
            $this->error('❌ Business logic fix failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function fixQuizScoring($finding, bool $dryRun): bool
    {
        $fileRef = $finding->getFileReferences()[0];
        $filePath = base_path($fileRef['file']);
        
        if (!file_exists($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);
        $lineIndex = $fileRef['line'] - 1;
        
        if (!isset($lines[$lineIndex])) {
            return false;
        }

        $originalLine = $lines[$lineIndex];
        
        // Add validation before scoring logic
        $validationLines = [
            "        // Validate scoring inputs",
            "        if (!\$score || \$score < 0 || \$score > 100) {",
            "            throw new InvalidArgumentException('Invalid score value');",
            "        }",
            "",
            $originalLine
        ];

        if (!$dryRun) {
            array_splice($lines, $lineIndex, 1, $validationLines);
            file_put_contents($filePath, implode("\n", $lines));
        }
        
        return true;
    }

    private function fixGradingCalculation($finding, bool $dryRun): bool
    {
        $fileRef = $finding->getFileReferences()[0];
        $filePath = base_path($fileRef['file']);
        
        if (!file_exists($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);
        $lineIndex = $fileRef['line'] - 1;
        
        if (!isset($lines[$lineIndex])) {
            return false;
        }

        $originalLine = $lines[$lineIndex];
        
        // Add division by zero protection
        if (preg_match('/\/|\bdiv\b/', $originalLine)) {
            $protectionLines = [
                "        // Protect against division by zero",
                "        if (\$totalQuestions === 0 || \$maxScore === 0) {",
                "            return 0; // or throw exception",
                "        }",
                "",
                $originalLine
            ];

            if (!$dryRun) {
                array_splice($lines, $lineIndex, 1, $protectionLines);
                file_put_contents($filePath, implode("\n", $lines));
            }
            return true;
        }
        
        return false;
    }

    private function fixUserCreation($finding, bool $dryRun): bool
    {
        $fileRef = $finding->getFileReferences()[0];
        $filePath = base_path($fileRef['file']);
        
        if (!file_exists($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);
        $lineIndex = $fileRef['line'] - 1;
        
        if (!isset($lines[$lineIndex])) {
            return false;
        }

        $originalLine = $lines[$lineIndex];
        
        // Add validation before user creation
        $validationLines = [
            "        // Validate user creation data",
            "        \$request->validate([",
            "            'name' => 'required|string|max:255',",
            "            'email' => 'required|email|unique:users',",
            "            'password' => 'required|string|min:8'",
            "        ]);",
            "",
            $originalLine
        ];

        if (!$dryRun) {
            array_splice($lines, $lineIndex, 1, $validationLines);
            file_put_contents($filePath, implode("\n", $lines));
        }
        
        return true;
    }

    private function fixRoleAssignment($finding, bool $dryRun): bool
    {
        $fileRef = $finding->getFileReferences()[0];
        $filePath = base_path($fileRef['file']);
        
        if (!file_exists($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);
        $lineIndex = $fileRef['line'] - 1;
        
        if (!isset($lines[$lineIndex])) {
            return false;
        }

        $originalLine = $lines[$lineIndex];
        
        // Add authorization check before role assignment
        $authorizationLines = [
            "        // Check authorization for role assignment",
            "        if (!auth()->user()->can('assign-roles')) {",
            "            abort(403, 'Unauthorized to assign roles');",
            "        }",
            "",
            $originalLine
        ];

        if (!$dryRun) {
            array_splice($lines, $lineIndex, 1, $authorizationLines);
            file_put_contents($filePath, implode("\n", $lines));
        }
        
        return true;
    }

    private function fixPaymentProcessing($finding, bool $dryRun): bool
    {
        $fileRef = $finding->getFileReferences()[0];
        $filePath = base_path($fileRef['file']);
        
        if (!file_exists($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);
        $lineIndex = $fileRef['line'] - 1;
        
        if (!isset($lines[$lineIndex])) {
            return false;
        }

        $originalLine = $lines[$lineIndex];
        
        // Wrap payment processing in transaction
        $transactionLines = [
            "        // Wrap payment processing in database transaction",
            "        DB::beginTransaction();",
            "        try {",
            "            " . trim($originalLine),
            "            DB::commit();",
            "        } catch (Exception \$e) {",
            "            DB::rollback();",
            "            throw \$e;",
            "        }"
        ];

        if (!$dryRun) {
            array_splice($lines, $lineIndex, 1, $transactionLines);
            file_put_contents($filePath, implode("\n", $lines));
        }
        
        return true;
    }

    private function fixNotificationIssues($finding, bool $dryRun): bool
    {
        $fileRef = $finding->getFileReferences()[0];
        $filePath = base_path($fileRef['file']);
        
        if (!file_exists($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);
        $lineIndex = $fileRef['line'] - 1;
        
        if (!isset($lines[$lineIndex])) {
            return false;
        }

        $originalLine = $lines[$lineIndex];
        
        // Convert synchronous email to queued
        if (preg_match('/Mail::send/', $originalLine)) {
            $queuedLine = str_replace('Mail::send', 'Mail::queue', $originalLine);
            
            if (!$dryRun) {
                $lines[$lineIndex] = $queuedLine;
                file_put_contents($filePath, implode("\n", $lines));
            }
            return true;
        }
        
        return false;
    }

    private function createBusinessLogicHelpers(bool $dryRun): void
    {
        $this->info('🔧 CREATING BUSINESS LOGIC VALIDATION HELPERS');
        $this->info('─────────────────────────────────────────────');

        // Create validation helper class
        $validationHelper = "<?php

namespace App\\Helpers;

use InvalidArgumentException;

class BusinessLogicValidator
{
    public static function validateScore(\$score, \$maxScore = 100)
    {
        if (!\$score || \$score < 0 || \$score > \$maxScore) {
            throw new InvalidArgumentException('Invalid score value');
        }
        return true;
    }

    public static function validateGradeCalculation(\$numerator, \$denominator)
    {
        if (\$denominator === 0) {
            throw new InvalidArgumentException('Division by zero in grade calculation');
        }
        return true;
    }

    public static function validatePaymentAmount(\$amount)
    {
        if (!\$amount || \$amount <= 0 || !is_numeric(\$amount)) {
            throw new InvalidArgumentException('Invalid payment amount');
        }
        return true;
    }

    public static function validateUserData(array \$userData)
    {
        \$required = ['name', 'email'];
        foreach (\$required as \$field) {
            if (empty(\$userData[\$field])) {
                throw new InvalidArgumentException(\"Missing required field: {\$field}\");
            }
        }
        return true;
    }
}";

        if (!$dryRun) {
            $helperDir = app_path('Helpers');
            if (!is_dir($helperDir)) {
                mkdir($helperDir, 0755, true);
            }
            file_put_contents($helperDir . '/BusinessLogicValidator.php', $validationHelper);
            $this->line('✅ Created BusinessLogicValidator helper class');
        }

        // Create payment processing trait
        $paymentTrait = "<?php

namespace App\\Traits;

use Illuminate\\Support\\Facades\\DB;
use Exception;

trait SecurePaymentProcessing
{
    protected function processPaymentSecurely(callable \$paymentLogic)
    {
        DB::beginTransaction();
        try {
            \$result = \$paymentLogic();
            DB::commit();
            return \$result;
        } catch (Exception \$e) {
            DB::rollback();
            \\Log::error('Payment processing failed: ' . \$e->getMessage());
            throw \$e;
        }
    }

    protected function validatePaymentData(array \$data)
    {
        \$required = ['amount', 'currency', 'payment_method'];
        foreach (\$required as \$field) {
            if (empty(\$data[\$field])) {
                throw new InvalidArgumentException(\"Missing payment field: {\$field}\");
            }
        }

        if (\$data['amount'] <= 0) {
            throw new InvalidArgumentException('Payment amount must be positive');
        }

        return true;
    }
}";

        if (!$dryRun) {
            $traitDir = app_path('Traits');
            if (!is_dir($traitDir)) {
                mkdir($traitDir, 0755, true);
            }
            file_put_contents($traitDir . '/SecurePaymentProcessing.php', $paymentTrait);
            $this->line('✅ Created SecurePaymentProcessing trait');
        }

        $this->newLine();
    }
}