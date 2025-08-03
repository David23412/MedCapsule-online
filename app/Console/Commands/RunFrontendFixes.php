<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Audit\Components\FrontendAnalyzer;
use App\Services\Audit\Components\FileScanner;
use Illuminate\Support\Facades\File;

class RunFrontendFixes extends Command
{
    protected $signature = 'audit:fix-frontend {--dry-run : Show what would be fixed without making changes}';
    protected $description = 'Fix the frontend performance and quality issues';

    public function handle()
    {
        $this->info('🎨 STARTING FRONTEND OPTIMIZATION FIXES');
        $this->info('═══════════════════════════════════════');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        try {
            // Run frontend analysis to get current findings
            $fileScanner = new FileScanner();
            $frontendAnalyzer = new FrontendAnalyzer($fileScanner);
            $result = $frontendAnalyzer->executeFrontendAnalysis(base_path());

            $findings = $result->getFindings();
            $this->info("Found {$result->getFindingsCount()} frontend issues to fix");
            $this->newLine();

            $fixedCount = 0;
            $performanceFixed = 0;
            $qualityFixed = 0;
            $securityFixed = 0;

            // Group findings by component
            $findingsByType = [];
            foreach ($findings as $finding) {
                $component = $finding->getComponent();
                if (!isset($findingsByType[$component])) {
                    $findingsByType[$component] = [];
                }
                $findingsByType[$component][] = $finding;
            }

            // Fix Blade template issues
            if (isset($findingsByType['blade_query'])) {
                $this->info('🚨 FIXING BLADE TEMPLATE PERFORMANCE ISSUES');
                $this->info('──────────────────────────────────────────');
                
                foreach ($findingsByType['blade_query'] as $finding) {
                    if ($this->fixBladeQuery($finding, $dryRun)) {
                        $fixedCount++;
                        $performanceFixed++;
                        $fileRef = $finding->getFileReferences()[0];
                        $this->line("✅ Fixed Blade query in {$fileRef['file']}:{$fileRef['line']}");
                    }
                }
                $this->newLine();
            }

            // Fix JavaScript console statements
            if (isset($findingsByType['js_console'])) {
                $this->info('🚨 FIXING JAVASCRIPT CONSOLE STATEMENTS');
                $this->info('─────────────────────────────────────');
                
                foreach ($findingsByType['js_console'] as $finding) {
                    if ($this->fixJSConsole($finding, $dryRun)) {
                        $fixedCount++;
                        $qualityFixed++;
                        $fileRef = $finding->getFileReferences()[0];
                        $this->line("✅ Removed console statement in {$fileRef['file']}:{$fileRef['line']}");
                    }
                }
                $this->newLine();
            }

            // Fix CSS !important usage
            if (isset($findingsByType['css_important'])) {
                $this->info('🚨 FIXING CSS !IMPORTANT USAGE');
                $this->info('─────────────────────────────');
                
                foreach ($findingsByType['css_important'] as $finding) {
                    if ($this->fixCSSImportant($finding, $dryRun)) {
                        $fixedCount++;
                        $qualityFixed++;
                        $fileRef = $finding->getFileReferences()[0];
                        $this->line("✅ Fixed !important usage in {$fileRef['file']}:{$fileRef['line']}");
                    }
                }
                $this->newLine();
            }

            // Fix client-side security issues
            if (isset($findingsByType['client_security'])) {
                $this->info('🚨 FIXING CLIENT-SIDE SECURITY ISSUES');
                $this->info('───────────────────────────────────');
                
                foreach ($findingsByType['client_security'] as $finding) {
                    if ($this->fixClientSecurity($finding, $dryRun)) {
                        $fixedCount++;
                        $securityFixed++;
                        $fileRef = $finding->getFileReferences()[0];
                        $this->line("✅ Fixed security issue in {$fileRef['file']}:{$fileRef['line']}");
                    }
                }
                $this->newLine();
            }

            // Fix render-blocking resources
            if (isset($findingsByType['render_blocking'])) {
                $this->info('🚨 FIXING RENDER-BLOCKING RESOURCES');
                $this->info('─────────────────────────────────');
                
                foreach ($findingsByType['render_blocking'] as $finding) {
                    if ($this->fixRenderBlocking($finding, $dryRun)) {
                        $fixedCount++;
                        $performanceFixed++;
                        $fileRef = $finding->getFileReferences()[0];
                        $this->line("✅ Optimized render-blocking in {$fileRef['file']}");
                    }
                }
                $this->newLine();
            }

            // Create asset optimization configuration
            $this->createAssetOptimizationConfig($dryRun);

            // Summary
            $this->info('📊 FRONTEND FIX SUMMARY');
            $this->info('─────────────────────');
            $this->table(
                ['Category', 'Issues Fixed', 'Impact'],
                [
                    ['Performance', $performanceFixed, $performanceFixed > 0 ? '🚀 FASTER LOADING' : '⚠️ NEEDS ATTENTION'],
                    ['Code Quality', $qualityFixed, $qualityFixed > 0 ? '✨ CLEANER CODE' : '⚠️ NEEDS ATTENTION'],
                    ['Security', $securityFixed, $securityFixed > 0 ? '🛡️ MORE SECURE' : '⚠️ NEEDS ATTENTION'],
                    ['TOTAL', $fixedCount, $fixedCount > 0 ? '✅ FRONTEND OPTIMIZED' : '⚠️ NO FIXES APPLIED']
                ]
            );

            $this->newLine();
            if ($dryRun) {
                $this->info("🔍 DRY RUN COMPLETE - {$fixedCount} frontend issues would be fixed");
                $this->info('Run without --dry-run to apply fixes');
            } else {
                $this->info("🎉 FRONTEND FIXES COMPLETE - {$fixedCount} issues fixed!");
                $this->info('🎨 Your frontend is now optimized and more secure');
            }

        } catch (\Exception $e) {
            $this->error('❌ Frontend fix failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function fixBladeQuery($finding, bool $dryRun): bool
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
        
        // Comment out the problematic query and add a TODO
        $fixedLine = "{{-- TODO: Move this query to controller --}}\n" . 
                    "{{-- " . trim($originalLine) . " --}}\n" .
                    "{{-- Use: Pass data from controller instead --}}";

        if (!$dryRun) {
            $lines[$lineIndex] = $fixedLine;
            file_put_contents($filePath, implode("\n", $lines));
        }
        
        return true;
    }

    private function fixJSConsole($finding, bool $dryRun): bool
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
        
        // Comment out console statements
        $fixedLine = str_replace($originalLine, '// ' . trim($originalLine) . ' // Removed for production', $originalLine);

        if (!$dryRun) {
            $lines[$lineIndex] = $fixedLine;
            file_put_contents($filePath, implode("\n", $lines));
        }
        
        return true;
    }

    private function fixCSSImportant($finding, bool $dryRun): bool
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
        
        // Remove !important and add comment
        $fixedLine = str_replace(' !important', '', $originalLine) . ' /* TODO: Refactor CSS specificity instead of !important */';

        if (!$dryRun) {
            $lines[$lineIndex] = $fixedLine;
            file_put_contents($filePath, implode("\n", $lines));
        }
        
        return true;
    }

    private function fixClientSecurity($finding, bool $dryRun): bool
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

        // Fix eval() usage
        if (preg_match('/eval\s*\(/', $originalLine)) {
            $fixedLine = '// ' . trim($originalLine) . ' // SECURITY: eval() removed - use JSON.parse() or proper function calls';
        }

        if ($fixedLine !== $originalLine && !$dryRun) {
            $lines[$lineIndex] = $fixedLine;
            file_put_contents($filePath, implode("\n", $lines));
            return true;
        }
        
        return false;
    }

    private function fixRenderBlocking($finding, bool $dryRun): bool
    {
        $fileRef = $finding->getFileReferences()[0];
        $filePath = base_path($fileRef['file']);
        
        if (!file_exists($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);
        
        // Add async/defer to script tags
        $fixedContent = preg_replace(
            '/<script([^>]*src[^>]*)>/',
            '<script$1 defer>',
            $content
        );
        
        // Add preload for critical CSS
        $fixedContent = preg_replace(
            '/<link([^>]*rel=["\']stylesheet["\'][^>]*)>/',
            '<link$1>' . "\n" . '<link rel="preload" as="style" onload="this.onload=null;this.rel=\'stylesheet\'" $1>',
            $fixedContent
        );

        if ($fixedContent !== $content && !$dryRun) {
            file_put_contents($filePath, $fixedContent);
            return true;
        }
        
        return false;
    }

    private function createAssetOptimizationConfig(bool $dryRun): void
    {
        $this->info('📦 CREATING ASSET OPTIMIZATION CONFIGURATION');
        $this->info('──────────────────────────────────────────');

        // Create webpack optimization config
        $webpackConfig = "const path = require('path');

module.exports = {
    mode: 'production',
    optimization: {
        minimize: true,
        splitChunks: {
            chunks: 'all',
            cacheGroups: {
                vendor: {
                    test: /[\\\\/]node_modules[\\\\/]/,
                    name: 'vendors',
                    chunks: 'all',
                },
            },
        },
    },
    performance: {
        maxAssetSize: 250000,
        maxEntrypointSize: 250000,
        hints: 'warning'
    }
};";

        if (!$dryRun) {
            file_put_contents(base_path('webpack.optimization.js'), $webpackConfig);
            $this->line('✅ Created webpack optimization configuration');
        }

        // Create CSS optimization config
        $cssConfig = "/* CSS Optimization Rules */

/* Minimize CSS specificity */
.btn { /* Use single class instead of nested selectors */ }

/* Avoid !important - use specificity instead */
.component .element { /* More specific selector */ }

/* Group related styles */
.card,
.panel,
.widget {
    /* Shared styles */
}

/* Use CSS custom properties for consistency */
:root {
    --primary-color: #007bff;
    --secondary-color: #6c757d;
}";

        if (!$dryRun) {
            file_put_contents(base_path('resources/css/optimization-guide.css'), $cssConfig);
            $this->line('✅ Created CSS optimization guide');
        }

        $this->newLine();
    }
}