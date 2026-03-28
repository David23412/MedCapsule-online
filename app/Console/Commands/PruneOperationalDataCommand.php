<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PruneOperationalDataCommand extends Command
{
    protected $signature = 'db:prune-operational-data
                            {--dry-run : Show what would be deleted without deleting it}
                            {--session-minutes= : Delete sessions older than this many minutes}
                            {--password-hours=24 : Delete password reset tokens older than this many hours}
                            {--security-days=90 : Delete session security logs older than this many days}
                            {--performance-days=30 : Delete performance metrics older than this many days}
                            {--survey-draft-days=30 : Delete survey drafts older than this many days}
                            {--failed-job-days=30 : Delete failed jobs older than this many days}
                            {--job-batch-days=14 : Delete completed/cancelled job batches older than this many days}';

    protected $description = 'Prune expired operational data and internal telemetry from the database';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $now = now();
        $nowTimestamp = $now->timestamp;
        $sessionMinutes = max(1, (int) ($this->option('session-minutes') ?: config('session.lifetime', 120)));
        $passwordHours = max(1, (int) $this->option('password-hours'));
        $securityDays = max(1, (int) $this->option('security-days'));
        $performanceDays = max(1, (int) $this->option('performance-days'));
        $surveyDraftDays = max(1, (int) $this->option('survey-draft-days'));
        $failedJobDays = max(1, (int) $this->option('failed-job-days'));
        $jobBatchDays = max(1, (int) $this->option('job-batch-days'));

        $rows = [];

        $this->components->info($dryRun ? 'Inspecting prunable operational data' : 'Pruning operational data');

        $this->prune(
            $rows,
            'Expired cache entries',
            'cache',
            "expiration < {$nowTimestamp}",
            fn (): Builder => DB::table('cache')->where('expiration', '<', $nowTimestamp),
            $dryRun
        );

        $this->prune(
            $rows,
            'Expired cache locks',
            'cache_locks',
            "expiration < {$nowTimestamp}",
            fn (): Builder => DB::table('cache_locks')->where('expiration', '<', $nowTimestamp),
            $dryRun
        );

        $sessionCutoff = $now->copy()->subMinutes($sessionMinutes)->timestamp;
        $this->prune(
            $rows,
            'Expired sessions',
            'sessions',
            "last_activity older than {$sessionMinutes} minutes",
            fn (): Builder => DB::table('sessions')->where('last_activity', '<', $sessionCutoff),
            $dryRun
        );

        $passwordCutoff = $now->copy()->subHours($passwordHours);
        $this->prune(
            $rows,
            'Expired password reset tokens',
            'password_reset_tokens',
            "created_at older than {$passwordHours} hours",
            fn (): Builder => DB::table('password_reset_tokens')->where('created_at', '<', $passwordCutoff),
            $dryRun
        );

        $surveyDraftCutoff = $now->copy()->subDays($surveyDraftDays);
        $this->prune(
            $rows,
            'Stale survey drafts',
            'survey_drafts',
            "last_saved_at / updated_at older than {$surveyDraftDays} days",
            fn (): Builder => DB::table('survey_drafts')
                ->whereRaw('COALESCE(last_saved_at, updated_at, created_at) < ?', [$surveyDraftCutoff]),
            $dryRun
        );

        $performanceCutoff = $now->copy()->subDays($performanceDays);
        $this->prune(
            $rows,
            'Old performance metrics',
            'performance_metrics',
            "created_at older than {$performanceDays} days",
            fn (): Builder => DB::table('performance_metrics')->where('created_at', '<', $performanceCutoff),
            $dryRun
        );

        $securityCutoff = $now->copy()->subDays($securityDays);
        $this->prune(
            $rows,
            'Old session security logs',
            'session_security_logs',
            "created_at older than {$securityDays} days",
            fn (): Builder => DB::table('session_security_logs')->where('created_at', '<', $securityCutoff),
            $dryRun
        );

        $failedJobCutoff = $now->copy()->subDays($failedJobDays);
        $this->prune(
            $rows,
            'Old failed jobs',
            'failed_jobs',
            "failed_at older than {$failedJobDays} days",
            fn (): Builder => DB::table('failed_jobs')->where('failed_at', '<', $failedJobCutoff),
            $dryRun
        );

        $jobBatchCutoff = $now->copy()->subDays($jobBatchDays)->timestamp;
        $this->prune(
            $rows,
            'Finished job batches',
            'job_batches',
            "pending_jobs = 0 and finished/cancelled older than {$jobBatchDays} days",
            fn (): Builder => DB::table('job_batches')
                ->where('pending_jobs', '=', 0)
                ->where(function (Builder $query) use ($jobBatchCutoff): void {
                    $query->where(function (Builder $finished) use ($jobBatchCutoff): void {
                        $finished->whereNotNull('finished_at')
                            ->where('finished_at', '<', $jobBatchCutoff);
                    })->orWhere(function (Builder $cancelled) use ($jobBatchCutoff): void {
                        $cancelled->whereNotNull('cancelled_at')
                            ->where('cancelled_at', '<', $jobBatchCutoff);
                    });
                }),
            $dryRun
        );

        $this->newLine();
        $this->table(
            ['Target', 'Candidates', $dryRun ? 'Deleted' : 'Deleted', 'Retention'],
            array_map(static fn (array $row): array => [
                $row['label'],
                (string) $row['candidates'],
                (string) $row['deleted'],
                $row['retention'],
            ], $rows)
        );

        $candidates = array_sum(array_column($rows, 'candidates'));
        $deleted = array_sum(array_column($rows, 'deleted'));

        $this->newLine();
        $this->components->info(sprintf(
            '%s complete. %d candidate row(s), %d row(s) %s.',
            $dryRun ? 'Inspection' : 'Prune',
            $candidates,
            $deleted,
            $dryRun ? 'would be deleted' : 'deleted'
        ));

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array{label:string,candidates:int,deleted:int,retention:string}>  $rows
     * @param  callable(): Builder  $queryFactory
     */
    private function prune(array &$rows, string $label, string $table, string $retention, callable $queryFactory, bool $dryRun): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $query = $queryFactory();
        $candidates = (clone $query)->count();
        $deleted = 0;

        if (!$dryRun && $candidates > 0) {
            $deleted = $query->delete();
        }

        $rows[] = [
            'label' => $label,
            'candidates' => $candidates,
            'deleted' => $deleted,
            'retention' => $retention,
        ];
    }
}
