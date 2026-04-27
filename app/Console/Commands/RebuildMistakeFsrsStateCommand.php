<?php

namespace App\Console\Commands;

use App\Models\Mistake;
use App\Models\MistakeReviewEvent;
use App\Services\Learning\FsrsScheduler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildMistakeFsrsStateCommand extends Command
{
    protected $signature = 'mistakes:rebuild-fsrs
        {--user= : Limit rebuild to one user id}
        {--question= : Limit rebuild to one question id}
        {--dry-run : Compute without saving}';

    protected $description = 'Cold-reinitialize mistake cards into canonical FSRS state by replaying review history.';

    public function handle(FsrsScheduler $scheduler): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $userId = $this->option('user');
        $questionId = $this->option('question');

        $query = Mistake::query()
            ->when($userId !== null, fn ($q) => $q->where('user_id', (int) $userId))
            ->when($questionId !== null, fn ($q) => $q->where('question_id', (int) $questionId))
            ->orderBy('id');

        $processed = 0;

        $query->chunkById(100, function ($mistakes) use ($scheduler, $dryRun, &$processed) {
            foreach ($mistakes as $mistake) {
                $events = MistakeReviewEvent::query()
                    ->where('mistake_id', $mistake->id)
                    ->orderBy('reviewed_at')
                    ->orderBy('id')
                    ->get();

                $runner = function () use ($scheduler, $mistake, $events, $dryRun, &$processed) {
                    $scheduler->rebuildStateFromHistory($mistake, $events, now());

                    if (!$dryRun) {
                        $mistake->save();
                    }

                    $processed++;
                };

                if ($dryRun) {
                    $runner();
                } else {
                    DB::transaction($runner);
                }
            }
        });

        $this->info(sprintf(
            '%s %d mistake card(s) into FSRS state.',
            $dryRun ? 'Simulated rebuild for' : 'Rebuilt',
            $processed
        ));

        return self::SUCCESS;
    }
}
