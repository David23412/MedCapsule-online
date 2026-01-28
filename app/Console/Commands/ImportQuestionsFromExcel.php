<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Models\Course;
use App\Models\Topic;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportQuestionsFromExcel extends Command
{
    protected $signature = 'questions:import 
                            {file : Path to Excel file}
                            {--course= : Course ID}
                            {--images= : Path to images directory}
                            {--dry-run : Preview without importing}';

    protected $description = 'Import questions from Excel with automatic image linking';

    private $stats = [
        'imported' => 0,
        'updated' => 0,
        'skipped' => 0,
        'images_linked' => 0,
        'images_missing' => [],
        'errors' => []
    ];

    public function handle()
    {
        $filePath = $this->argument('file');
        $courseId = $this->option('course');
        $imagesPath = $this->option('images');
        $dryRun = $this->option('dry-run');

        // Validate file exists
        if (!file_exists($filePath)) {
            $this->error("❌ File not found: {$filePath}");
            return 1;
        }

        // Validate course
        if ($courseId) {
            $course = Course::find($courseId);
            if (!$course) {
                $this->error("❌ Course not found: {$courseId}");
                return 1;
            }
            $this->info("📚 Importing to course: {$course->name}");
        }

        // Validate images directory
        if ($imagesPath && !is_dir($imagesPath)) {
            $this->error("❌ Images directory not found: {$imagesPath}");
            return 1;
        }

        if ($dryRun) {
            $this->warn("🔍 DRY RUN MODE - No changes will be made");
        }

        $this->info("📖 Reading Excel file...");
        
        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Get headers from first row
            $headers = array_map('strtolower', array_map('trim', $rows[0]));
            $this->info("✅ Found " . count($rows) - 1 . " questions");

            // Process each row
            $this->withProgressBar(array_slice($rows, 1), function ($row) use ($headers, $courseId, $imagesPath, $dryRun) {
                $this->processRow($row, $headers, $courseId, $imagesPath, $dryRun);
            });

            $this->newLine(2);
            $this->displayStats();

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error reading Excel file: " . $e->getMessage());
            return 1;
        }
    }

    private function processRow($row, $headers, $courseId, $imagesPath, $dryRun)
    {
        try {
            // Map row to associative array
            $data = array_combine($headers, $row);

            // Skip empty rows
            if (empty(trim($data['question_text'] ?? ''))) {
                $this->stats['skipped']++;
                return;
            }

            // Prepare question data
            $questionData = [
                'question_text' => trim($data['question_text']),
                'option_a' => trim($data['option_a'] ?? ''),
                'option_b' => trim($data['option_b'] ?? ''),
                'option_c' => trim($data['option_c'] ?? ''),
                'option_d' => trim($data['option_d'] ?? ''),
                'option_e' => trim($data['option_e'] ?? ''),
                'correct_answer' => strtoupper(trim($data['correct_answer'] ?? 'A')),
                'explanation' => trim($data['explanation'] ?? ''),
                'difficulty' => trim($data['difficulty'] ?? 'medium'),
                'course_id' => $courseId ?? ($data['course_id'] ?? null),
                'topic_id' => $data['topic_id'] ?? null,
            ];

            // Handle image
            $imageFilename = trim($data['image_filename'] ?? '');
            if ($imageFilename && $imagesPath) {
                $imagePath = $imagesPath . '/' . $imageFilename;
                
                if (file_exists($imagePath)) {
                    // Copy image to storage
                    $storagePath = "question-images/course-{$questionData['course_id']}/{$imageFilename}";
                    
                    if (!$dryRun) {
                        Storage::disk('public')->put($storagePath, file_get_contents($imagePath));
                    }
                    
                    $questionData['image_url'] = $storagePath;
                    $this->stats['images_linked']++;
                } else {
                    $this->stats['images_missing'][] = $imageFilename;
                }
            }

            // Import or update question
            if (!$dryRun) {
                // Check if question exists (by text)
                $existing = Question::where('question_text', $questionData['question_text'])
                    ->where('course_id', $questionData['course_id'])
                    ->first();

                if ($existing) {
                    $existing->update($questionData);
                    $this->stats['updated']++;
                } else {
                    Question::create($questionData);
                    $this->stats['imported']++;
                }
            } else {
                $this->stats['imported']++;
            }

        } catch (\Exception $e) {
            $this->stats['errors'][] = "Row error: " . $e->getMessage();
        }
    }

    private function displayStats()
    {
        $this->info("📊 Import Statistics:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Questions Imported', $this->stats['imported']],
                ['Questions Updated', $this->stats['updated']],
                ['Questions Skipped', $this->stats['skipped']],
                ['Images Linked', $this->stats['images_linked']],
                ['Images Missing', count($this->stats['images_missing'])],
                ['Errors', count($this->stats['errors'])],
            ]
        );

        if (!empty($this->stats['images_missing'])) {
            $this->warn("\n⚠️  Missing Images:");
            foreach (array_slice($this->stats['images_missing'], 0, 10) as $filename) {
                $this->line("   - {$filename}");
            }
            if (count($this->stats['images_missing']) > 10) {
                $this->line("   ... and " . (count($this->stats['images_missing']) - 10) . " more");
            }
        }

        if (!empty($this->stats['errors'])) {
            $this->error("\n❌ Errors:");
            foreach (array_slice($this->stats['errors'], 0, 5) as $error) {
                $this->line("   - {$error}");
            }
        }

        if ($this->stats['imported'] > 0 || $this->stats['updated'] > 0) {
            $this->info("\n✅ Import completed successfully!");
        }
    }
}
