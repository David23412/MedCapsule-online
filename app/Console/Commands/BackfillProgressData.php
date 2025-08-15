<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\QuizAttempt;
use App\Support\State\UserProgressService;

class BackfillProgressData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'state:backfill-progress 
                            {--chunk=100 : Number of users to process per chunk}
                            {--dry-run : Show what would be updated without making changes}
                            {--fix-duplicates : Remove duplicate correct_answers field from quiz_attempts}';

    /**
     * The console command description.
     */
    protected $description = 'Backfill user progress data and fix quiz attempt inconsistencies';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $chunkSize = (int) $this->option('chunk');
        $dryRun = $this->option('dry-run');
        $fixDuplicates = $this->option('fix-duplicates');
        
        $this->info('Starting progress data backfill...');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }
        
        $stats = [
            'users_processed' => 0,
            'users_synced' => 0,
            'quiz_attempts_fixed' => 0,
            'errors' => 0,
        ];
        
        // Fix quiz attempts where correct_answers != score
        if ($fixDuplicates) {
            $this->info('Fixing quiz attempt inconsistencies...');
            
            $inconsistentAttempts = QuizAttempt::whereColumn('correct_answers', '!=', 'score')
                ->orWhereNull('correct_answers')
                ->get();
                
            $this->info("Found {$inconsistentAttempts->count()} inconsistent quiz attempts");
            
            foreach ($inconsistentAttempts as $attempt) {
                try {
                    if (!$dryRun) {
                        // Use score as the single source of truth
                        $attempt->update(['correct_answers' => $attempt->score]);
                    }
                    $stats['quiz_attempts_fixed']++;
                    
                    if ($stats['quiz_attempts_fixed'] % 100 === 0) {
                        $this->line("Fixed {$stats['quiz_attempts_fixed']} quiz attempts...");
                    }
                } catch (\Exception $e) {
                    $stats['errors']++;
                    $this->error("Error fixing quiz attempt {$attempt->id}: " . $e->getMessage());
                }
            }
        }
        
        // Sync user progress counts
        $this->info('Syncing user progress counts...');
        
        User::chunk($chunkSize, function ($users) use (&$stats, $dryRun) {
            foreach ($users as $user) {
                $stats['users_processed']++;
                
                try {
                    $cachedCount = $user->correct_answers_count ?? 0;
                    $actualCount = UserProgressService::calculateTotalCorrectAnswers($user);
                    
                    if ($cachedCount !== $actualCount) {
                        if (!$dryRun) {
                            $user->update(['correct_answers_count' => $actualCount]);
                        }
                        $stats['users_synced']++;
                        
                        $this->line("Synced user {$user->id}: {$cachedCount} -> {$actualCount}");
                    }
                    
                } catch (\Exception $e) {
                    $stats['errors']++;
                    $this->error("Error processing user {$user->id}: " . $e->getMessage());
                }
            }
            
            $this->info("Processed {$stats['users_processed']} users...");
        });
        
        // Verify data integrity
        $this->info('Verifying data integrity...');
        
        $integrityIssues = [];
        
        // Check for users with negative progress
        $negativeProgress = User::where('correct_answers_count', '<', 0)->count();
        if ($negativeProgress > 0) {
            $integrityIssues[] = "Found {$negativeProgress} users with negative correct_answers_count";
        }
        
        // Check for quiz attempts with invalid scores
        $invalidScores = QuizAttempt::where('score', '<', 0)
            ->orWhereColumn('score', '>', 'total_questions')
            ->count();
        if ($invalidScores > 0) {
            $integrityIssues[] = "Found {$invalidScores} quiz attempts with invalid scores";
        }
        
        // Check for quiz attempts with mismatched data
        $mismatchedData = QuizAttempt::whereRaw('
            JSON_LENGTH(question_data) > 0 AND 
            JSON_LENGTH(question_data) != total_questions
        ')->count();
        if ($mismatchedData > 0) {
            $integrityIssues[] = "Found {$mismatchedData} quiz attempts with mismatched question_data";
        }
        
        if (!empty($integrityIssues)) {
            $this->warn('Data integrity issues found:');
            foreach ($integrityIssues as $issue) {
                $this->warn("  - {$issue}");
            }
        } else {
            $this->info('No data integrity issues found');
        }
        
        // Display final statistics
        $this->info('Progress backfill completed!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Users Processed', $stats['users_processed']],
                ['Users Synced', $stats['users_synced']],
                ['Quiz Attempts Fixed', $stats['quiz_attempts_fixed']],
                ['Errors', $stats['errors']],
                ['Integrity Issues', count($integrityIssues)],
            ]
        );
        
        if ($stats['errors'] > 0) {
            $this->error('Backfill completed with errors. Please review the error messages above.');
            return 1;
        }
        
        if (!empty($integrityIssues)) {
            $this->warn('Backfill completed but data integrity issues were found. Consider running with --fix-duplicates or manual intervention.');
            return 1;
        }
        
        return 0;
    }
}