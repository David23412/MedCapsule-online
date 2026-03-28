<?php

use Illuminate\Database\Seeder;
use App\Models\Question;
use App\Models\Topic;
use Illuminate\Support\Facades\DB;

class HighYieldTestDataSeeder extends Seeder
{
    /**
     * Create high-yield test data to demonstrate the premium features.
     */
    public function run(): void
    {
        $this->command->info('🔥 Creating high-yield test data...');
        
        // Create exam topics
        $examTopics = [
            ['name' => 'Final Exam 2022', 'is_exam' => true],
            ['name' => 'Mid Term 2023', 'is_exam' => true],
            ['name' => 'Final Exam 2023', 'is_exam' => true],
            ['name' => 'Mock Exam 2024', 'is_exam' => true],
        ];
        
        $createdExamTopics = [];
        foreach ($examTopics as $examTopic) {
            $topic = Topic::firstOrCreate(
                ['name' => $examTopic['name']],
                [
                    'course_id' => 1,
                    'case_type' => 'quiz',
                    'display_order' => 900 + count($createdExamTopics),
                ]
            );
            $createdExamTopics[] = $topic;
            $this->command->info("✅ Created exam topic: {$topic->name}");
        }
        
        // Get existing questions to link to exams
        $questions = Question::take(10)->get();
        
        $highYieldCount = 0;
        $repeatedCount = 0;
        
        foreach ($questions as $index => $question) {
            // Make first 3 questions HIGH YIELD (appear in 3+ exams)
            if ($index < 3) {
                $examSubset = array_slice($createdExamTopics, 0, 3);
                $extraTopicIds = $examSubset->pluck('id')->implode(',');
                $question->update(['extra_topic' => $extraTopicIds]);
                $highYieldCount++;
            }
            // Make next 2 questions REPEATED (appear in 2 exams)
            elseif ($index < 5) {
                $examSubset = array_slice($createdExamTopics, 0, 2);
                $extraTopicIds = $examSubset->pluck('id')->implode(',');
                $question->update(['extra_topic' => $extraTopicIds]);
                $repeatedCount++;
            }
            // Make next 2 questions appear in 1 exam only
            elseif ($index < 7) {
                $examTopic = $createdExamTopics[0];
                $question->update(['extra_topic' => $examTopic->id]);
            }
        }
        
        // Update exam frequency scores
        foreach ($questions as $question) {
            $question->updateExamFrequencyScore();
        }
        
        $this->command->info("🎯 Created {$highYieldCount} HIGH YIELD questions");
        $this->command->info("📈 Created {$repeatedCount} REPEATED questions");
        $this->command->info("🔥 Premium features are now ready for testing!");
        
        // Test the features
        $this->demonstrateFeatures();
    }
    
    /**
     * Demonstrate the premium features.
     */
    private function demonstrateFeatures(): void
    {
        $this->command->info("\n🎪 DEMONSTRATING PREMIUM FEATURES:");
        
        // Show high-yield questions
        $highYieldQuestions = Question::where('exam_appearances_count', '>=', 3)->get();
        $this->command->info("🔥 HIGH YIELD Questions ({$highYieldQuestions->count()} found):");
        foreach ($highYieldQuestions as $question) {
            $badges = implode(', ', $question->enhanced_source_badges);
            $this->command->info("   - Q{$question->id}: {$badges} (Score: {$question->exam_frequency_score})");
        }
        
        // Show repeated questions
        $repeatedQuestions = Question::where('exam_appearances_count', '>=', 2)->get();
        $this->command->info("\n📈 REPEATED Questions ({$repeatedQuestions->count()} found):");
        foreach ($repeatedQuestions as $question) {
            $badges = implode(', ', $question->enhanced_source_badges);
            $this->command->info("   - Q{$question->id}: {$badges} (Appeared {$question->exam_appearances_count} times)");
        }
        
        $this->command->info("\n✨ Students will LOVE these psychological indicators!");
    }
}
