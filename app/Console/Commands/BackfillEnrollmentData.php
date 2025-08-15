<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Payment;

class BackfillEnrollmentData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'state:backfill-enrollment 
                            {--chunk=1000 : Number of records to process per chunk}
                            {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Backfill enrollment data to ensure consistency between payments and course_user table';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $chunkSize = (int) $this->option('chunk');
        $dryRun = $this->option('dry-run');
        
        $this->info('Starting enrollment data backfill...');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }
        
        $stats = [
            'processed' => 0,
            'updated' => 0,
            'created' => 0,
            'inconsistencies' => 0,
            'errors' => 0,
        ];
        
        // Process completed payments that should have enrollment records
        $this->info('Processing completed payments...');
        
        Payment::where('status', 'completed')
            ->whereNotNull('paid_at')
            ->chunk($chunkSize, function ($payments) use (&$stats, $dryRun) {
                foreach ($payments as $payment) {
                    $stats['processed']++;
                    
                    try {
                        $enrollment = DB::table('course_user')
                            ->where('user_id', $payment->user_id)
                            ->where('course_id', $payment->course_id)
                            ->first();
                            
                        if (!$enrollment) {
                            // Create missing enrollment record
                            if (!$dryRun) {
                                DB::table('course_user')->insert([
                                    'user_id' => $payment->user_id,
                                    'course_id' => $payment->course_id,
                                    'is_enrolled' => true,
                                    'enrolled_at' => $payment->paid_at,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                            $stats['created']++;
                            $this->line("Created enrollment for user {$payment->user_id}, course {$payment->course_id}");
                            
                        } elseif ($enrollment->enrolled_at === null) {
                            // Update existing enrollment with timestamp
                            if (!$dryRun) {
                                DB::table('course_user')
                                    ->where('user_id', $payment->user_id)
                                    ->where('course_id', $payment->course_id)
                                    ->update([
                                        'enrolled_at' => $payment->paid_at,
                                        'is_enrolled' => true,
                                        'updated_at' => now(),
                                    ]);
                            }
                            $stats['updated']++;
                            $this->line("Updated enrollment timestamp for user {$payment->user_id}, course {$payment->course_id}");
                            
                        } elseif ($enrollment->is_enrolled == 0) {
                            // Fix inconsistent enrollment status
                            if (!$dryRun) {
                                DB::table('course_user')
                                    ->where('user_id', $payment->user_id)
                                    ->where('course_id', $payment->course_id)
                                    ->update([
                                        'is_enrolled' => true,
                                        'updated_at' => now(),
                                    ]);
                            }
                            $stats['inconsistencies']++;
                            $this->warn("Fixed inconsistent enrollment status for user {$payment->user_id}, course {$payment->course_id}");
                        }
                        
                    } catch (\Exception $e) {
                        $stats['errors']++;
                        $this->error("Error processing payment {$payment->id}: " . $e->getMessage());
                    }
                }
                
                $this->info("Processed {$stats['processed']} payments...");
            });
            
        // Check for enrollments without corresponding payments (potential issues)
        $this->info('Checking for enrollments without payments...');
        
        $orphanedEnrollments = DB::table('course_user')
            ->leftJoin('payments', function ($join) {
                $join->on('course_user.user_id', '=', 'payments.user_id')
                     ->on('course_user.course_id', '=', 'payments.course_id')
                     ->where('payments.status', '=', 'completed');
            })
            ->leftJoin('courses', 'course_user.course_id', '=', 'courses.id')
            ->where('course_user.is_enrolled', true)
            ->whereNull('payments.id')
            ->where(function ($query) {
                $query->where('courses.price', '>', 0)
                      ->orWhereNull('courses.price');
            })
            ->select('course_user.*', 'courses.name as course_name', 'courses.price')
            ->get();
            
        if ($orphanedEnrollments->count() > 0) {
            $this->warn("Found {$orphanedEnrollments->count()} enrollments without corresponding payments:");
            
            // Export to CSV for manual review
            $csvPath = storage_path('logs/orphaned_enrollments_' . now()->format('Y-m-d_H-i-s') . '.csv');
            $csvFile = fopen($csvPath, 'w');
            fputcsv($csvFile, ['user_id', 'course_id', 'course_name', 'course_price', 'enrolled_at', 'is_enrolled']);
            
            foreach ($orphanedEnrollments as $enrollment) {
                fputcsv($csvFile, [
                    $enrollment->user_id,
                    $enrollment->course_id,
                    $enrollment->course_name,
                    $enrollment->price,
                    $enrollment->enrolled_at,
                    $enrollment->is_enrolled,
                ]);
            }
            
            fclose($csvFile);
            $this->warn("Exported orphaned enrollments to: {$csvPath}");
        }
        
        // Display final statistics
        $this->info('Backfill completed!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Processed Payments', $stats['processed']],
                ['Created Enrollments', $stats['created']],
                ['Updated Enrollments', $stats['updated']],
                ['Fixed Inconsistencies', $stats['inconsistencies']],
                ['Errors', $stats['errors']],
                ['Orphaned Enrollments', $orphanedEnrollments->count()],
            ]
        );
        
        if ($stats['errors'] > 0) {
            $this->error('Backfill completed with errors. Please review the error messages above.');
            return 1;
        }
        
        return 0;
    }
}