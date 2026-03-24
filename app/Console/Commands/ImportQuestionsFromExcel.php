<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Models\Course;
use App\Models\Topic;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
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
                'question' => trim($data['question_text'] ?? $data['question'] ?? ''),
                'option_a' => trim($data['option_a'] ?? ''),
                'option_b' => trim($data['option_b'] ?? ''),
                'option_c' => trim($data['option_c'] ?? ''),
                'option_d' => trim($data['option_d'] ?? ''),
                'correct_answer' => strtoupper(trim($data['correct_answer'] ?? 'A')),
                'explanation' => trim($data['explanation'] ?? ''),
                'source' => trim($data['source'] ?? ''),
                'concept' => trim($data['concept'] ?? ''),
                'question_type' => trim($data['question_type'] ?? 'multiple_choice'),
                'topic_id' => $data['topic_id'] ?? null,
            ];

            // Handle extra topics for many-to-many relationships
            $extraTopics = trim($data['extra_topics'] ?? '');
            $extraTopicIds = [];
            
            if ($extraTopics) {
                // Split by comma and process each topic (legacy support for topic names)
                $topicNames = array_map('trim', explode(',', $extraTopics));
                
                foreach ($topicNames as $topicName) {
                    if (empty($topicName)) continue;
                    
                    // Find topic by name (case-insensitive)
                    $topic = Topic::where('name', 'LIKE', $topicName)->first();
                    if ($topic) {
                        $extraTopicIds[] = $topic->id;
                    } else {
                        $this->stats['errors'][] = "Topic not found: {$topicName}";
                    }
                }
            }

            // Handle extra_topic_ids column (new preferred method)
            $extraTopicIdsColumn = trim($data['extra_topic_ids'] ?? '');
            $extraTopicIdsFromColumn = [];
            
            if ($extraTopicIdsColumn) {
                // Split by comma and process each topic ID
                $topicIdStrings = array_map('trim', explode(',', $extraTopicIdsColumn));
                
                foreach ($topicIdStrings as $topicIdString) {
                    if (empty($topicIdString)) continue;
                    
                    // Validate that it's a valid integer
                    if (!is_numeric($topicIdString)) {
                        $this->stats['errors'][] = "Invalid topic ID format: {$topicIdString}";
                        continue;
                    }
                    
                    $topicId = (int) $topicIdString;
                    
                    // Validate that the topic exists
                    if (Topic::where('id', $topicId)->exists()) {
                        $extraTopicIdsFromColumn[] = $topicId;
                    } else {
                        $this->stats['errors'][] = "Topic ID not found: {$topicId}";
                    }
                }
            }
            
            // Merge extra topic IDs from both methods, removing duplicates
            $allExtraTopicIds = array_unique(array_merge($extraTopicIds, $extraTopicIdsFromColumn));
            
            // Remove the main topic ID from extra topics to avoid redundancy
            if (!empty($questionData['topic_id'])) {
                $allExtraTopicIds = array_diff($allExtraTopicIds, [(int)$questionData['topic_id']]);
            }

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
                $existing = Question::where('question', $questionData['question'])
                    ->first();

                if ($existing) {
                    // ARCHITECTURE RULE: Prefer Exam topic as the canonical topic_id
                    $currentTopic = Topic::find($existing->topic_id);
                    $newTopic = Topic::find($questionData['topic_id']);
                    
                    $isCurrentExam = $currentTopic && $currentTopic->is_exam;
                    $isNewExam = $newTopic && $newTopic->is_exam;
                    
                    if ($isCurrentExam && !$isNewExam) {
                        // Keep current exam topic as main, add new lecture topic to pivot
                        $allExtraTopicIds[] = $questionData['topic_id'];
                        unset($questionData['topic_id']);
                    } elseif (!$isCurrentExam && $isNewExam) {
                        // Promote to exam topic as main, move old lecture topic to pivot
                        $allExtraTopicIds[] = $existing->topic_id;
                    } elseif ($existing->topic_id != $questionData['topic_id']) {
                        // Same priority, just move old one to pivot
                        $allExtraTopicIds[] = $existing->topic_id;
                    }
                    
                    $existing->update(array_filter($questionData));
                    $question = $existing;
                    $this->stats['updated']++;
                } else {
                    $question = Question::create($questionData);
                    $this->stats['imported']++;
                }

                // Handle extra topic relationships with cleaner Eloquent sync
                if (!empty($allExtraTopicIds)) {
                    $validExtraTopicIds = array_filter(array_unique($allExtraTopicIds));
                    if (!empty($validExtraTopicIds)) {
                        $question->topics()->syncWithoutDetaching($validExtraTopicIds);
                    }
                }
                
                // Update exam frequency score after linking
                $question->updateExamFrequencyScore();
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
