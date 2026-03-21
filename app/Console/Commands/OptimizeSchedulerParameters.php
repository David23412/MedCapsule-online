<?php

namespace App\Console\Commands;

use App\Models\MistakeReviewEvent;
use Illuminate\Console\Command;
use App\Services\Learning\ParameterOptimizer;
use Illuminate\Support\Facades\DB;

class OptimizeSchedulerParameters extends Command
{
    protected $signature = 'scheduler:optimize-parameters 
                            {--user= : Specific user ID to optimize}
                            {--mode=spaced : Mode to optimize (spaced, quiz, quick, all)}
                            {--force : Force re-optimization even if recent}';

    protected $description = 'Refresh live scheduler parameters from historical review data';

    public function handle()
    {
        $optimizer = new ParameterOptimizer();
        $userId = $this->option('user');
        $mode = $this->option('mode');
        $force = $this->option('force');
        
        if ($force) {
            $this->info('Force flag enabled - clearing cached optimizations...');
        }
        
        if ($userId) {
            $this->optimizeForUser((int) $userId, $mode, $force, $optimizer);
        } else {
            $this->optimizeForAllUsers($mode, $force, $optimizer);
        }
        
        return 0;
    }

    private function optimizeForUser(int $userId, string $mode, bool $force, ParameterOptimizer $optimizer): void
    {
        $modes = $mode === 'all' ? ['spaced', 'quiz', 'quick'] : [$mode];
        
        foreach ($modes as $m) {
            $this->info("Optimizing parameters for user {$userId} in mode '{$m}'...");
            
            if ($force) {
                $optimizer->scheduleOptimization($userId, $m);
            }
            
            $params = $optimizer->getOptimizedParameters($userId, $m);
            
            $this->line("  ✓ Optimized {$m} mode");
            $this->line("    Sample parameters:");
            $this->line("      response_sensitivity: " . round($params['response_sensitivity'], 3));
            $this->line("      confidence_sensitivity: " . round($params['confidence_sensitivity'], 3));
            $this->line("      relearn_scale: " . round($params['relearn_scale'], 3));
            if ($m === 'spaced') {
                $this->line("      confidence_reliability: " . round($params['confidence_reliability'], 3));
            }
        }
    }

    private function optimizeForAllUsers(string $mode, bool $force, ParameterOptimizer $optimizer): void
    {
        $this->info('Optimizing parameters for all users with sufficient review history...');
        
        $modes = $mode === 'all' ? ['spaced', 'quiz', 'quick'] : [$mode];
        
        foreach ($modes as $m) {
            $this->info("\nMode: {$m}");

            $modeParams = $optimizer->refreshModeOptimization($m);
            $this->line("  response_sensitivity: " . round($modeParams['response_sensitivity'], 3));
            $this->line("  confidence_sensitivity: " . round($modeParams['confidence_sensitivity'], 3));
            $this->line("  relearn_scale: " . round($modeParams['relearn_scale'], 3));

            if ($m !== 'spaced') {
                continue;
            }

            $users = DB::table('mistake_review_events')
                ->select('user_id')
                ->where('review_mode', $m)
                ->where('event_type', MistakeReviewEvent::EVENT_TYPE_REVIEW)
                ->whereNotNull('confidence_level')
                ->where('reviewed_at', '>=', now()->subDays(180))
                ->groupBy('user_id')
                ->havingRaw('COUNT(*) >= 12')
                ->pluck('user_id');

            if ($users->isEmpty()) {
                $this->warn("  No spaced users with enough confidence-tagged reviews");
                continue;
            }

            $this->info("  Refreshing {$users->count()} user-specific spaced profiles");

            $bar = $this->output->createProgressBar($users->count());
            $bar->start();

            foreach ($users as $userId) {
                if ($force) {
                    $optimizer->scheduleOptimization($userId, $m);
                }

                $optimizer->getOptimizedParameters($userId, $m);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
        }
        
        $this->info("\n✓ Parameter optimization complete");
    }
}
