<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class CleanupPaymentProofs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:cleanup-proofs 
                            {--days=60 : Number of days to keep original images before compressing}
                            {--archive-days=90 : Number of days to keep compressed images before archiving}
                            {--thumbnail-width=300 : Width for thumbnails in pixels}
                            {--quality=70 : Compression quality (1-100)}
                            {--limit=100 : Maximum number of images to process in one run}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup and compress old payment proof images to save storage space';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int)$this->option('days');
        $archiveDays = (int)$this->option('archive-days');
        $thumbnailWidth = (int)$this->option('thumbnail-width');
        $quality = (int)$this->option('quality');
        $limit = (int)$this->option('limit');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info("Running in DRY RUN mode - no changes will be made");
        }

        $this->info("Starting payment proof cleanup process");
        $this->info("Compression threshold: {$days} days");
        $this->info("Archive threshold: {$archiveDays} days");

        // 1. Find payments with original proof images older than threshold
        $compressionDate = Carbon::now()->subDays($days);
        $this->processCompression($compressionDate, $quality, $limit, $dryRun);

        // 2. Find payments with compressed images older than archive threshold
        $archiveDate = Carbon::now()->subDays($archiveDays);
        $this->processArchiving($archiveDate, $thumbnailWidth, $limit, $dryRun);

        // 3. Generate storage report
        $this->generateStorageReport();

        return 0;
    }

    /**
     * Process images for compression
     */
    protected function processCompression($compressionDate, $quality, $limit, $dryRun)
    {
        $this->info("Looking for payments with original images older than {$compressionDate->format('Y-m-d')}");

        try {
            // Find payments with original images older than the threshold
            $payments = Payment::where('status', 'completed')
                ->where('paid_at', '<', $compressionDate)
                ->whereRaw("JSON_EXTRACT(payment_data, '$.proof_path') IS NOT NULL")
                ->whereRaw("JSON_EXTRACT(payment_data, '$.storage_status') IS NULL OR 
                           JSON_EXTRACT(payment_data, '$.storage_status') = '\"original\"'")
                ->limit($limit)
                ->get();

            $count = $payments->count();
            $this->info("Found {$count} payments with original images eligible for compression");

            if ($count == 0) {
                return;
            }

            $progressBar = $this->output->createProgressBar($count);
            $progressBar->start();

            $compressed = 0;
            $errors = 0;

            foreach ($payments as $payment) {
                try {
                    // Extract the proof path
                    $proofPath = $payment->payment_data['proof_path'] ?? null;
                    
                    if (!$proofPath || !Storage::disk('public')->exists($proofPath)) {
                        $this->updatePaymentStorageStatus($payment, 'missing', $dryRun);
                        $progressBar->advance();
                        continue;
                    }

                    if (!$dryRun) {
                        // Compress the image
                        $compressedPath = $this->compressImage($proofPath, $quality);
                        
                        // Update payment record with compressed path and status
                        $payment->payment_data = array_merge($payment->payment_data, [
                            'original_proof_path' => $proofPath,
                            'proof_path' => $compressedPath,
                            'storage_status' => 'compressed',
                            'compressed_at' => Carbon::now()->toDateTimeString(),
                            'compression_quality' => $quality
                        ]);
                        $payment->save();
                    }

                    $compressed++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::error("Error compressing payment proof", [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage()
                    ]);
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();

            if ($dryRun) {
                $this->info("DRY RUN: Would have compressed {$count} images");
            } else {
                $this->info("Successfully compressed {$compressed} images with {$errors} errors");
            }
        } catch (\Exception $e) {
            $this->error("Error processing images for compression: " . $e->getMessage());
            Log::error("Error in CleanupPaymentProofs compression", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Process images for archiving (creating thumbnails and marking for potential removal)
     */
    protected function processArchiving($archiveDate, $thumbnailWidth, $limit, $dryRun)
    {
        $this->info("Looking for payments with compressed images older than {$archiveDate->format('Y-m-d')}");

        try {
            // Find payments with compressed images older than the archive threshold
            $payments = Payment::where('status', 'completed')
                ->where('paid_at', '<', $archiveDate)
                ->whereRaw("JSON_EXTRACT(payment_data, '$.storage_status') = '\"compressed\"'")
                ->limit($limit)
                ->get();

            $count = $payments->count();
            $this->info("Found {$count} payments with compressed images eligible for archiving");

            if ($count == 0) {
                return;
            }

            $progressBar = $this->output->createProgressBar($count);
            $progressBar->start();

            $archived = 0;
            $errors = 0;

            foreach ($payments as $payment) {
                try {
                    // Extract the proof path
                    $proofPath = $payment->payment_data['proof_path'] ?? null;
                    
                    if (!$proofPath || !Storage::disk('public')->exists($proofPath)) {
                        $this->updatePaymentStorageStatus($payment, 'missing', $dryRun);
                        $progressBar->advance();
                        continue;
                    }

                    if (!$dryRun) {
                        // Create thumbnail
                        $thumbnailPath = $this->createThumbnail($proofPath, $thumbnailWidth);
                        
                        // Update payment record with thumbnail path and archived status
                        $payment->payment_data = array_merge($payment->payment_data, [
                            'compressed_proof_path' => $proofPath,
                            'proof_path' => $thumbnailPath,
                            'storage_status' => 'archived',
                            'archived_at' => Carbon::now()->toDateTimeString(),
                            'thumbnail_width' => $thumbnailWidth
                        ]);
                        $payment->save();
                    }

                    $archived++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::error("Error archiving payment proof", [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage()
                    ]);
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();

            if ($dryRun) {
                $this->info("DRY RUN: Would have archived {$count} images");
            } else {
                $this->info("Successfully archived {$archived} images with {$errors} errors");
            }
        } catch (\Exception $e) {
            $this->error("Error processing images for archiving: " . $e->getMessage());
            Log::error("Error in CleanupPaymentProofs archiving", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Compress an image and return the new path
     */
    protected function compressImage($imagePath, $quality)
    {
        // Get the full path to the image
        $fullPath = Storage::disk('public')->path($imagePath);
        
        // Generate a compressed path
        $pathInfo = pathinfo($imagePath);
        $compressedFilename = $pathInfo['filename'] . '_compressed.' . $pathInfo['extension'];
        $compressedPath = $pathInfo['dirname'] . '/' . $compressedFilename;
        $fullCompressedPath = Storage::disk('public')->path($compressedPath);
        
        // Compress the image
        Image::make($fullPath)
            ->resize(1000, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })
            ->save($fullCompressedPath, $quality);
        
        return $compressedPath;
    }

    /**
     * Create a thumbnail of an image and return the new path
     */
    protected function createThumbnail($imagePath, $width)
    {
        // Get the full path to the image
        $fullPath = Storage::disk('public')->path($imagePath);
        
        // Generate a thumbnail path
        $pathInfo = pathinfo($imagePath);
        $thumbnailFilename = $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
        $thumbnailPath = $pathInfo['dirname'] . '/' . $thumbnailFilename;
        $fullThumbnailPath = Storage::disk('public')->path($thumbnailPath);
        
        // Create the thumbnail
        Image::make($fullPath)
            ->resize($width, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })
            ->save($fullThumbnailPath, 60);
        
        return $thumbnailPath;
    }

    /**
     * Update payment storage status without changing the file
     */
    protected function updatePaymentStorageStatus(Payment $payment, $status, $dryRun)
    {
        if (!$dryRun) {
            $payment->payment_data = array_merge($payment->payment_data, [
                'storage_status' => $status,
                'status_updated_at' => Carbon::now()->toDateTimeString(),
            ]);
            $payment->save();
        }
    }

    /**
     * Generate a storage report
     */
    protected function generateStorageReport()
    {
        $this->info("Generating storage report...");

        try {
            // Count payments by storage status
            $originalCount = Payment::whereRaw("JSON_EXTRACT(payment_data, '$.storage_status') IS NULL OR 
                                              JSON_EXTRACT(payment_data, '$.storage_status') = '\"original\"'")->count();
            
            $compressedCount = Payment::whereRaw("JSON_EXTRACT(payment_data, '$.storage_status') = '\"compressed\"'")->count();
            
            $archivedCount = Payment::whereRaw("JSON_EXTRACT(payment_data, '$.storage_status') = '\"archived\"'")->count();
            
            $missingCount = Payment::whereRaw("JSON_EXTRACT(payment_data, '$.storage_status') = '\"missing\"'")->count();
            
            $totalCount = $originalCount + $compressedCount + $archivedCount + $missingCount;
            
            // Calculate storage usage (approximate)
            $totalOriginalSize = 0;
            $totalCompressedSize = 0;
            $totalArchivedSize = 0;
            
            // Sample a few payments from each category to get average size
            $sampleSize = 10;
            
            // Original images
            $originalSamples = Payment::whereRaw("JSON_EXTRACT(payment_data, '$.storage_status') IS NULL OR 
                                                 JSON_EXTRACT(payment_data, '$.storage_status') = '\"original\"'")
                                     ->limit($sampleSize)
                                     ->get();
            
            foreach ($originalSamples as $payment) {
                $proofPath = $payment->payment_data['proof_path'] ?? null;
                if ($proofPath && Storage::disk('public')->exists($proofPath)) {
                    $totalOriginalSize += Storage::disk('public')->size($proofPath);
                }
            }
            
            $avgOriginalSize = $originalSamples->count() > 0 ? $totalOriginalSize / $originalSamples->count() : 0;
            $estimatedOriginalSize = $avgOriginalSize * $originalCount;
            
            // Compressed images
            $compressedSamples = Payment::whereRaw("JSON_EXTRACT(payment_data, '$.storage_status') = '\"compressed\"'")
                                       ->limit($sampleSize)
                                       ->get();
            
            foreach ($compressedSamples as $payment) {
                $proofPath = $payment->payment_data['proof_path'] ?? null;
                if ($proofPath && Storage::disk('public')->exists($proofPath)) {
                    $totalCompressedSize += Storage::disk('public')->size($proofPath);
                }
            }
            
            $avgCompressedSize = $compressedSamples->count() > 0 ? $totalCompressedSize / $compressedSamples->count() : 0;
            $estimatedCompressedSize = $avgCompressedSize * $compressedCount;
            
            // Archived images
            $archivedSamples = Payment::whereRaw("JSON_EXTRACT(payment_data, '$.storage_status') = '\"archived\"'")
                                     ->limit($sampleSize)
                                     ->get();
            
            foreach ($archivedSamples as $payment) {
                $proofPath = $payment->payment_data['proof_path'] ?? null;
                if ($proofPath && Storage::disk('public')->exists($proofPath)) {
                    $totalArchivedSize += Storage::disk('public')->size($proofPath);
                }
            }
            
            $avgArchivedSize = $archivedSamples->count() > 0 ? $totalArchivedSize / $archivedSamples->count() : 0;
            $estimatedArchivedSize = $avgArchivedSize * $archivedCount;
            
            // Calculate total estimated size
            $totalEstimatedSize = $estimatedOriginalSize + $estimatedCompressedSize + $estimatedArchivedSize;
            
            // Output the report
            $this->table(
                ['Status', 'Count', 'Avg Size (KB)', 'Est. Total (MB)'],
                [
                    ['Original', $originalCount, round($avgOriginalSize / 1024, 2), round($estimatedOriginalSize / (1024 * 1024), 2)],
                    ['Compressed', $compressedCount, round($avgCompressedSize / 1024, 2), round($estimatedCompressedSize / (1024 * 1024), 2)],
                    ['Archived', $archivedCount, round($avgArchivedSize / 1024, 2), round($estimatedArchivedSize / (1024 * 1024), 2)],
                    ['Missing', $missingCount, 0, 0],
                    ['Total', $totalCount, '', round($totalEstimatedSize / (1024 * 1024), 2)]
                ]
            );
            
            // Calculate storage savings
            if ($compressedCount > 0 || $archivedCount > 0) {
                $potentialSize = ($avgOriginalSize * $totalCount);
                $actualSize = $estimatedOriginalSize + $estimatedCompressedSize + $estimatedArchivedSize;
                $savedSize = $potentialSize - $actualSize;
                
                $this->info("Estimated storage saved: " . round($savedSize / (1024 * 1024), 2) . " MB");
                $this->info("Storage reduction: " . round(($savedSize / $potentialSize) * 100, 2) . "%");
            }
        } catch (\Exception $e) {
            $this->error("Error generating storage report: " . $e->getMessage());
            Log::error("Error generating storage report", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
} 