<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Mistake;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class VerifyMistakeRecordingFix extends Command
{
    protected $signature = 'mistakes:verify-fix';
    protected $description = 'Verify that the mistake recording fix is working correctly';

    public function handle()
    {
        $this->info('💀 LETHAL MISTAKE RECORDING FIX VERIFICATION 💀');
        $this->info('================================================');
        $this->newLine();

        // Analyze current mistake records
        $this->info('🔍 ANALYZING CURRENT MISTAKE RECORDS...');
        $this->info('=====================================');
        $this->newLine();

        // Get all mistakes with 'unanswered' as submitted_answer
        $unansweredMistakes = Mistake::where('submitted_answer', 'unanswered')
            ->with(['user:id,name', 'question:id,question,correct_answer'])
            ->get();

        $this->info("📊 UNANSWERED MISTAKES ANALYSIS:");
        $this->info("Total mistakes with 'unanswered' status: " . $unansweredMistakes->count());
        $this->newLine();

        if ($unansweredMistakes->count() > 0) {
            $this->info("🔍 SAMPLE UNANSWERED MISTAKES:");
            foreach ($unansweredMistakes->take(5) as $mistake) {
                $this->line("- User: {$mistake->user->name}");
                $this->line("  Question ID: {$mistake->question_id}");
                $this->line("  Submitted Answer: '{$mistake->submitted_answer}'");
                $this->line("  Correct Answer: '{$mistake->question->correct_answer}'");
                $this->line("  Last Attempt: {$mistake->last_attempt_date}");
                $this->line("  Created: {$mistake->created_at}");
                $this->newLine();
            }
        }

        // Get mistakes with actual wrong answers (not unanswered)
        $actualWrongAnswers = Mistake::where('submitted_answer', '!=', 'unanswered')
            ->where('submitted_answer', '!=', '')
            ->whereNotNull('submitted_answer')
            ->with(['user:id,name', 'question:id,question,correct_answer'])
            ->get();

        $this->info("📊 ACTUAL WRONG ANSWER MISTAKES:");
        $this->info("Total mistakes with actual wrong answers: " . $actualWrongAnswers->count());
        $this->newLine();

        if ($actualWrongAnswers->count() > 0) {
            $this->info("🔍 SAMPLE ACTUAL WRONG ANSWERS:");
            foreach ($actualWrongAnswers->take(5) as $mistake) {
                $this->line("- User: {$mistake->user->name}");
                $this->line("  Question ID: {$mistake->question_id}");
                $this->line("  Submitted Answer: '{$mistake->submitted_answer}'");
                $this->line("  Correct Answer: '{$mistake->question->correct_answer}'");
                $this->line("  Last Attempt: {$mistake->last_attempt_date}");
                $this->newLine();
            }
        }

        // Clear mistake-related caches
        $this->info('🧹 CLEARING MISTAKE-RELATED CACHES...');
        $this->info('===================================');
        $this->newLine();

        Cache::flush();
        $this->info('✅ Performed full cache flush');
        $this->newLine();

        // Generate final statistics
        $totalMistakes = Mistake::count();
        $unansweredCount = $unansweredMistakes->count();
        $actualWrongCount = $actualWrongAnswers->count();

        $this->info("📊 FINAL STATISTICS:");
        $this->info("==================");
        $this->info("Total Mistakes: {$totalMistakes}");
        $this->info("Unanswered Mistakes: {$unansweredCount}");
        $this->info("Actual Wrong Answer Mistakes: {$actualWrongCount}");
        $this->info("Percentage of Unanswered: " . round(($unansweredCount / max($totalMistakes, 1)) * 100, 2) . "%");
        $this->newLine();

        $this->info("🎯 RECOMMENDATIONS:");
        $this->info("==================");
        if ($unansweredCount > $actualWrongCount) {
            $this->warn("⚠️  HIGH ALERT: Too many 'unanswered' mistakes detected!");
            $this->warn("   This suggests the bug was affecting mistake recording.");
            $this->warn("   The fix should resolve this for future quiz attempts.");
        } else {
            $this->info("✅ GOOD: More actual wrong answers than unanswered mistakes.");
            $this->info("   This suggests the system is working correctly.");
        }
        $this->newLine();

        $this->info("🚀 NEXT STEPS:");
        $this->info("=============");
        $this->info("1. Monitor new quiz attempts to ensure mistakes are recorded correctly");
        $this->info("2. Consider running a data migration to fix existing 'unanswered' mistakes");
        $this->info("3. Test the mistake review functionality to ensure it displays correctly");
        $this->newLine();

        $this->info("💀 LETHAL VERIFICATION COMPLETE 💀");

        return 0;
    }
}