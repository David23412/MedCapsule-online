<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Audit\Components\SecurityScanner;
use App\Services\Audit\Components\FileScanner;
use Illuminate\Support\Facades\File;

class RunSecurityFixes extends Command
{
    protected $signature = 'audit:fix-security {--dry-run : Show what would be fixed without making changes}';
    protected $description = 'Fix the security vulnerabilities found by the security scanner';

    public function handle()
    {
        $this->info('🔒 STARTING SECURITY VULNERABILITY FIXES');
        $this->info('═══════════════════════════════════════');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        try {
            // Run security scan to get current findings
            $fileScanner = new FileScanner();
            $securityScanner = new SecurityScanner($fileScanner);
            $result = $securityScanner->executeSecurityScan(base_path());

            $findings = $result->getFindings();
            $this->info("Found {$result->getFindingsCount()} security issues to fix");
            $this->newLine();

            $fixedCount = 0;
            $criticalFixed = 0;
            $highFixed = 0;

            // Group findings by component for systematic fixing
            $findingsByType = [];
            foreach ($findings as $finding) {
                $type = $finding->getComponent();
                if (!isset($findingsByType[$type])) {
                    $findingsByType[$type] = [];
                }
                $findingsByType[$type][] = $finding;
            }

            // Fix SQL Injection vulnerabilities first (CRITICAL)
            if (isset($findingsByType['sql_injection'])) {
                $this->info('🚨 FIXING SQL INJECTION VULNERABILITIES (CRITICAL)');
                $this->info('─────────────────────────────────────────────────');
                
                foreach ($findingsByType['sql_injection'] as $finding) {
                    if ($this->fixSQLInjection($finding, $dryRun)) {
                        $fixedCount++;
                        $criticalFixed++;
                        $this->line("✅ Fixed SQL injection in {$finding->getFileReferences()[0]['file']}:{$finding->getFileReferences()[0]['line']}");
                    }
                }
                $this->newLine();
            }

            // Fix XSS vulnerabilities (HIGH)
            if (isset($findingsByType['xss_vulnerability'])) {
                $this->info('🚨 FIXING XSS VULNERABILITIES (HIGH)');
                $this->info('──────────────────────────────────');
                
                foreach ($findingsByType['xss_vulnerability'] as $finding) {
                    if ($this->fixXSSVulnerability($finding, $dryRun)) {
                        $fixedCount++;
                        $highFixed++;
                        $this->line("✅ Fixed XSS vulnerability in {$finding->getFileReferences()[0]['file']}:{$finding->getFileReferences()[0]['line']}");
                    }
                }
                $this->newLine();
            }

            // Fix CSRF missing protection (HIGH)
            if (isset($findingsByType['csrf_missing'])) {
                $this->info('🚨 FIXING CSRF PROTECTION ISSUES (HIGH)');
                $this->info('────────────────────────────────────');
                
                foreach ($findingsByType['csrf_missing'] as $finding) {
                    if ($this->fixCSRFProtection($finding, $dryRun)) {
                        $fixedCount++;
                        $highFixed++;
                        $this->line("✅ Fixed CSRF protection in {$finding->getFileReferences()[0]['file']}:{$finding->getFileReferences()[0]['line']}");
                    }
                }
                $this->newLine();
            }

            // Fix input validation issues (MEDIUM)
            if (isset($findingsByType['input_validation'])) {
                $this->info('🚨 FIXING INPUT VALIDATION ISSUES (MEDIUM)');
                $this->info('─────────────────────────────────────────');
                
                foreach ($findingsByType['input_validation'] as $finding) {
                    if ($this->fixInputValidation($finding, $dryRun)) {
                        $fixedCount++;
                        $this->line("✅ Fixed input validation in {$finding->getFileReferences()[0]['file']}:{$finding->getFileReferences()[0]['line']}");
                    }
                }
                $this->newLine();
            }

            // Fix hardcoded credentials (CRITICAL)
            if (isset($findingsByType['hardcoded_credentials'])) {
                $this->info('🚨 FIXING HARDCODED CREDENTIALS (CRITICAL)');
                $this->info('──────────────────────────────────────────');
                
                foreach ($findingsByType['hardcoded_credentials'] as $finding) {
                    if ($this->fixHardcodedCredentials($finding, $dryRun)) {
                        $fixedCount++;
                        $criticalFixed++;
                        $this->line("✅ Fixed hardcoded credentials in {$finding->getFileReferences()[0]['file']}:{$finding->getFileReferences()[0]['line']}");
                    }
                }
                $this->newLine();
            }

            // Fix access control issues (HIGH)
            if (isset($findingsByType['access_control'])) {
                $this->info('🚨 FIXING ACCESS CONTROL ISSUES (HIGH)');
                $this->info('────────────────────────────────────');
                
                foreach ($findingsByType['access_control'] as $finding) {
                    if ($this->fixAccessControl($finding, $dryRun)) {
                        $fixedCount++;
                        $highFixed++;
                        $this->line("✅ Fixed access control for route: {$finding->getMetrics()['route_uri']['value']}");
                    }
                }
                $this->newLine();
            }

            // Fix configuration security (HIGH)
            if (isset($findingsByType['config_security'])) {
                $this->info('🚨 FIXING CONFIGURATION SECURITY (HIGH)');
                $this->info('─────────────────────────────────────');
                
                foreach ($findingsByType['config_security'] as $finding) {
                    if ($this->fixConfigurationSecurity($finding, $dryRun)) {
                        $fixedCount++;
                        $highFixed++;
                        $this->line("✅ Fixed configuration security issue");
                    }
                }
                $this->newLine();
            }

            // Summary
            $this->info('📊 SECURITY FIX SUMMARY');
            $this->info('──────────────────────');
            $this->table(
                ['Severity', 'Issues Fixed', 'Status'],
                [
                    ['Critical', $criticalFixed, $criticalFixed > 0 ? '✅ SECURED' : '⚠️ NEEDS ATTENTION'],
                    ['High', $highFixed, $highFixed > 0 ? '✅ SECURED' : '⚠️ NEEDS ATTENTION'],
                    ['Medium/Low', $fixedCount - $criticalFixed - $highFixed, '✅ IMPROVED'],
                    ['TOTAL', $fixedCount, $fixedCount > 0 ? '✅ SECURITY ENHANCED' : '⚠️ NO FIXES APPLIED']
                ]
            );

            $this->newLine();
            if ($dryRun) {
                $this->info("🔍 DRY RUN COMPLETE - {$fixedCount} issues would be fixed");
                $this->info('Run without --dry-run to apply fixes');
            } else {
                $this->info("🎉 SECURITY FIXES COMPLETE - {$fixedCount} vulnerabilities fixed!");
                $this->info('🛡️ Your application is now more secure');
            }

        } catch (\Exception $e) {
            $this->error('❌ Security fix failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function fixSQLInjection($finding, bool $dryRun): bool
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
        $fixedLine = $originalLine;

        // Fix common SQL injection patterns
        if (preg_match('/DB::raw\s*\(\s*["\'].*\$.*["\']/', $originalLine)) {
            // Convert DB::raw with variables to parameterized query
            $fixedLine = preg_replace(
                '/DB::raw\s*\(\s*["\'](.+?)\$(\w+)(.+?)["\']/',
                'DB::raw(\'$1?\', [$$$2])',
                $originalLine
            );
        } elseif (preg_match('/whereRaw\s*\(\s*["\'].*\$.*["\']/', $originalLine)) {
            // Fix whereRaw with variables
            $fixedLine = preg_replace(
                '/whereRaw\s*\(\s*["\'](.+?)\$(\w+)(.+?)["\']/',
                'whereRaw(\'$1?\', [$$$2])',
                $originalLine
            );
        } elseif (preg_match('/query\s*\(\s*["\'].*\$.*["\']/', $originalLine)) {
            // Fix direct query calls
            $fixedLine = preg_replace(
                '/query\s*\(\s*["\'](.+?)\$(\w+)(.+?)["\']/',
                'query(\'$1?\', [$$$2])',
                $originalLine
            );
        }

        if ($fixedLine !== $originalLine) {
            if (!$dryRun) {
                $lines[$lineIndex] = $fixedLine;
                file_put_contents($filePath, implode("\n", $lines));
            }
            return true;
        }

        return false;
    }

    private function fixXSSVulnerability($finding, bool $dryRun): bool
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
        $fixedLine = $originalLine;

        // Fix XSS vulnerabilities
        if (preg_match('/\{\!\!\s*(\$[^}]+)\s*\!\!\}/', $originalLine, $matches)) {
            // Convert {!! $var !!} to {{ $var }} for safe output
            $fixedLine = str_replace($matches[0], '{{ ' . trim($matches[1]) . ' }}', $originalLine);
        } elseif (preg_match('/echo\s+(\$[^;]+);/', $originalLine, $matches)) {
            // Convert echo $var; to echo htmlspecialchars($var);
            $fixedLine = str_replace($matches[0], 'echo htmlspecialchars(' . trim($matches[1]) . ');', $originalLine);
        }

        if ($fixedLine !== $originalLine) {
            if (!$dryRun) {
                $lines[$lineIndex] = $fixedLine;
                file_put_contents($filePath, implode("\n", $lines));
            }
            return true;
        }

        return false;
    }

    private function fixCSRFProtection($finding, bool $dryRun): bool
    {
        $fileRef = $finding->getFileReferences()[0];
        $filePath = base_path($fileRef['file']);
        
        if (!file_exists($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);
        
        // Add CSRF token to forms
        if (strpos($fileRef['content'], '<form') !== false && strpos($fileRef['content'], 'method="post"') !== false) {
            // Find the form and add @csrf after the opening tag
            $fixedContent = preg_replace(
                '/(<form[^>]*method\s*=\s*["\']post["\'][^>]*>)/',
                '$1' . "\n    @csrf",
                $content
            );
            
            if ($fixedContent !== $content && !$dryRun) {
                file_put_contents($filePath, $fixedContent);
                return true;
            }
        }

        return false;
    }

    private function fixInputValidation($finding, bool $dryRun): bool
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
        
        // Extract parameter name from the finding
        $paramName = '';
        if (isset($finding->getMetrics()['parameter_name'])) {
            $paramName = $finding->getMetrics()['parameter_name']['value'];
        }

        if ($paramName) {
            // Add validation before the parameter usage
            $validationLine = "        \$request->validate(['{$paramName}' => 'required|string|max:255']);";
            
            if (!$dryRun) {
                // Insert validation line before the current line
                array_splice($lines, $lineIndex, 0, $validationLine);
                file_put_contents($filePath, implode("\n", $lines));
            }
            return true;
        }

        return false;
    }

    private function fixHardcodedCredentials($finding, bool $dryRun): bool
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
        $fixedLine = $originalLine;

        // Replace hardcoded credentials with environment variables
        if (preg_match('/password\s*=\s*["\']([^"\']+)["\']/', $originalLine, $matches)) {
            $fixedLine = str_replace($matches[0], "password = env('DB_PASSWORD')", $originalLine);
        } elseif (preg_match('/api_key\s*=\s*["\']([^"\']+)["\']/', $originalLine, $matches)) {
            $fixedLine = str_replace($matches[0], "api_key = env('API_KEY')", $originalLine);
        }

        if ($fixedLine !== $originalLine) {
            if (!$dryRun) {
                $lines[$lineIndex] = $fixedLine;
                file_put_contents($filePath, implode("\n", $lines));
            }
            return true;
        }

        return false;
    }

    private function fixAccessControl($finding, bool $dryRun): bool
    {
        // This would require modifying route files to add auth middleware
        // For now, we'll just report that it needs manual attention
        $this->warn("⚠️ Access control fix requires manual route middleware configuration");
        return false;
    }

    private function fixConfigurationSecurity($finding, bool $dryRun): bool
    {
        // Fix debug mode in .env
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $content = file_get_contents($envPath);
            $fixedContent = preg_replace('/APP_DEBUG\s*=\s*true/i', 'APP_DEBUG=false', $content);
            
            if ($fixedContent !== $content && !$dryRun) {
                file_put_contents($envPath, $fixedContent);
                return true;
            }
        }
        
        return false;
    }
}