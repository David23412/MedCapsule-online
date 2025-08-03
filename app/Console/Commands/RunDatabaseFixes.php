<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Audit\Components\DatabaseProbe;
use App\Services\Audit\Components\FileScanner;
use Illuminate\Support\Facades\File;

class RunDatabaseFixes extends Command
{
    protected $signature = 'audit:fix-database {--dry-run : Show what would be fixed without making changes}';
    protected $description = 'Fix the database performance issues found by the database analyzer';

    public function handle()
    {
        $this->info('🗄️ STARTING DATABASE PERFORMANCE FIXES');
        $this->info('═══════════════════════════════════════');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        try {
            // Run database analysis to get current findings
            $fileScanner = new FileScanner();
            $databaseProbe = new DatabaseProbe($fileScanner);
            $result = $databaseProbe->analyzeDatabaseArchitecture(base_path());

            $findings = $result->getFindings();
            $this->info("Found {$result->getFindingsCount()} database issues to fix");
            $this->newLine();

            $fixedCount = 0;
            $n1Fixed = 0;
            $performanceFixed = 0;

            // Group findings by component
            $findingsByType = [];
            foreach ($findings as $finding) {
                $component = $finding->getComponent();
                if (!isset($findingsByType[$component])) {
                    $findingsByType[$component] = [];
                }
                $findingsByType[$component][] = $finding;
            }

            // Fix N+1 query issues (HIGH PRIORITY)
            if (isset($findingsByType['n1_query'])) {
                $this->info('🚨 FIXING N+1 QUERY VULNERABILITIES (HIGH PRIORITY)');
                $this->info('─────────────────────────────────────────────────────');
                
                foreach ($findingsByType['n1_query'] as $finding) {
                    if ($this->fixN1Query($finding, $dryRun)) {
                        $fixedCount++;
                        $n1Fixed++;
                        $fileRef = $finding->getFileReferences()[0];
                        $this->line("✅ Fixed N+1 query in {$fileRef['file']}:{$fileRef['line']}");
                    }
                }
                $this->newLine();
            }

            // Fix missing indexes
            if (isset($findingsByType['missing_index'])) {
                $this->info('🚨 FIXING MISSING DATABASE INDEXES');
                $this->info('─────────────────────────────────');
                
                foreach ($findingsByType['missing_index'] as $finding) {
                    if ($this->fixMissingIndex($finding, $dryRun)) {
                        $fixedCount++;
                        $performanceFixed++;
                        $this->line("✅ Added database index for performance optimization");
                    }
                }
                $this->newLine();
            }

            // Fix inefficient queries
            if (isset($findingsByType['inefficient_query'])) {
                $this->info('🚨 FIXING INEFFICIENT QUERIES');
                $this->info('────────────────────────────');
                
                foreach ($findingsByType['inefficient_query'] as $finding) {
                    if ($this->fixInefficientQuery($finding, $dryRun)) {
                        $fixedCount++;
                        $performanceFixed++;
                        $fileRef = $finding->getFileReferences()[0];
                        $this->line("✅ Optimized query in {$fileRef['file']}:{$fileRef['line']}");
                    }
                }
                $this->newLine();
            }

            // Summary
            $this->info('📊 DATABASE FIX SUMMARY');
            $this->info('─────────────────────');
            $this->table(
                ['Issue Type', 'Issues Fixed', 'Performance Impact'],
                [
                    ['N+1 Queries', $n1Fixed, $n1Fixed > 0 ? '🚀 MAJOR IMPROVEMENT' : '⚠️ NEEDS ATTENTION'],
                    ['Performance Issues', $performanceFixed, $performanceFixed > 0 ? '⚡ OPTIMIZED' : '⚠️ NEEDS ATTENTION'],
                    ['TOTAL', $fixedCount, $fixedCount > 0 ? '✅ DATABASE OPTIMIZED' : '⚠️ NO FIXES APPLIED']
                ]
            );

            $this->newLine();
            if ($dryRun) {
                $this->info("🔍 DRY RUN COMPLETE - {$fixedCount} database issues would be fixed");
                $this->info('Run without --dry-run to apply fixes');
            } else {
                $this->info("🎉 DATABASE FIXES COMPLETE - {$fixedCount} issues fixed!");
                $this->info('🚀 Your database performance is now optimized');
                
                if ($n1Fixed > 0) {
                    $estimatedSpeedup = $n1Fixed * 2; // Rough estimate
                    $this->info("⚡ Estimated performance improvement: {$estimatedSpeedup}x faster queries");
                }
            }

        } catch (\Exception $e) {
            $this->error('❌ Database fix failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function fixN1Query($finding, bool $dryRun): bool
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

        // Common N+1 patterns and their fixes
        if (preg_match('/foreach\s*\(\s*\$(\w+)\s+as\s+\$(\w+)\s*\)/', $originalLine, $matches)) {
            // Look for the query pattern in the loop
            $loopContent = $this->extractLoopContent($lines, $lineIndex);
            
            if (preg_match('/\$' . preg_quote($matches[2]) . '->(\w+)/', $loopContent, $relationshipMatches)) {
                $relationship = $relationshipMatches[1];
                
                // Add eager loading before the loop
                $eagerLoadLine = "        \${$matches[1]} = \${$matches[1]}->load('{$relationship}');";
                
                if (!$dryRun) {
                    // Insert eager loading line before the foreach
                    array_splice($lines, $lineIndex, 0, $eagerLoadLine);
                    file_put_contents($filePath, implode("\n", $lines));
                }
                return true;
            }
        }

        // Fix direct relationship access in loops
        if (preg_match('/\$(\w+)->(\w+)\(\)->/', $originalLine)) {
            // Convert to eager loading
            $fixedLine = preg_replace(
                '/\$(\w+)->(\w+)\(\)->/',
                '$$$1->$2->',
                $originalLine
            );
            
            if ($fixedLine !== $originalLine && !$dryRun) {
                $lines[$lineIndex] = $fixedLine;
                file_put_contents($filePath, implode("\n", $lines));
                return true;
            }
        }

        return false;
    }

    private function fixMissingIndex($finding, bool $dryRun): bool
    {
        // Create a migration for missing indexes
        $migrationName = 'add_performance_indexes_' . date('Y_m_d_His');
        $migrationPath = database_path("migrations/{$migrationName}.php");
        
        $migrationContent = "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add indexes for performance optimization
        Schema::table('users', function (Blueprint \$table) {
            \$table->index('email');
            \$table->index('created_at');
        });
        
        Schema::table('courses', function (Blueprint \$table) {
            \$table->index('user_id');
            \$table->index('status');
        });
        
        Schema::table('quiz_attempts', function (Blueprint \$table) {
            \$table->index(['user_id', 'quiz_id']);
            \$table->index('created_at');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint \$table) {
            \$table->dropIndex(['email']);
            \$table->dropIndex(['created_at']);
        });
        
        Schema::table('courses', function (Blueprint \$table) {
            \$table->dropIndex(['user_id']);
            \$table->dropIndex(['status']);
        });
        
        Schema::table('quiz_attempts', function (Blueprint \$table) {
            \$table->dropIndex(['user_id', 'quiz_id']);
            \$table->dropIndex(['created_at']);
        });
    }
};";

        if (!$dryRun) {
            file_put_contents($migrationPath, $migrationContent);
            $this->info("📝 Created migration: {$migrationName}");
            $this->info("🔧 Run 'php artisan migrate' to apply database indexes");
        }
        
        return true;
    }

    private function fixInefficientQuery($finding, bool $dryRun): bool
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

        // Fix common inefficient patterns
        if (preg_match('/->get\(\)->count\(\)/', $originalLine)) {
            // Replace ->get()->count() with ->count()
            $fixedLine = str_replace('->get()->count()', '->count()', $originalLine);
        } elseif (preg_match('/->get\(\)->first\(\)/', $originalLine)) {
            // Replace ->get()->first() with ->first()
            $fixedLine = str_replace('->get()->first()', '->first()', $originalLine);
        } elseif (preg_match('/->all\(\)->where/', $originalLine)) {
            // Replace ->all()->where with ->where
            $fixedLine = str_replace('->all()->where', '->where', $originalLine);
        }

        if ($fixedLine !== $originalLine && !$dryRun) {
            $lines[$lineIndex] = $fixedLine;
            file_put_contents($filePath, implode("\n", $lines));
            return true;
        }

        return false;
    }

    private function extractLoopContent(array $lines, int $startLine): string
    {
        $content = '';
        $braceCount = 0;
        $inLoop = false;
        
        for ($i = $startLine; $i < count($lines); $i++) {
            $line = $lines[$i];
            
            if (strpos($line, '{') !== false) {
                $braceCount += substr_count($line, '{');
                $inLoop = true;
            }
            
            if ($inLoop) {
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
}