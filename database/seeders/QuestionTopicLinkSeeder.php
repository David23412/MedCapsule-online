<?php

use Illuminate\Database\Seeder;
use App\Models\Question;
use App\Models\Topic;
use Illuminate\Support\Facades\DB;

class QuestionTopicLinkSeeder extends Seeder
{
    /**
     * Run the database seeds to test the many-to-many functionality.
     */
    public function run(): void
    {
        // Clear existing links for clean testing
        DB::table('question_topic_links')->truncate();
        
        // Get some existing questions and topics for testing
        $questions = Question::take(5)->get();
        $topics = Topic::take(3)->get();
        
        if ($questions->isEmpty() || $topics->isEmpty()) {
            $this->command->info('⚠️  No questions or topics found to create test links');
            return;
        }
        
        $linksCreated = 0;
        
        foreach ($questions as $question) {
            // Skip if question doesn't have a main topic
            if (!$question->topic_id) continue;
            
            // Add 1-2 additional topic links per question
            $additionalTopics = $topics->where('id', '!=', $question->topic_id)->random(2);
            
            foreach ($additionalTopics as $topic) {
                // Create the many-to-many link
                DB::table('question_topic_links')->insert([
                    'question_id' => $question->id,
                    'topic_id' => $topic->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $linksCreated++;
            }
        }
        
        $this->command->info("✅ Created {$linksCreated} question-topic links for testing");
        
        // Test the relationships
        $this->testRelationships();
    }
    
    /**
     * Test the new many-to-many relationships.
     */
    private function testRelationships(): void
    {
        $this->command->info("\n🔍 Testing many-to-many relationships...");
        
        // Test Question model relationships
        $testQuestion = Question::with('topics', 'additionalTopics')->first();
        if ($testQuestion) {
            $this->command->info("📝 Question '{$testQuestion->id}' belongs to {$testQuestion->topics->count()} topics total");
            $this->command->info("📝 Question '{$testQuestion->id}' has {$testQuestion->additionalTopics->count()} additional topics");
            
            if ($testQuestion->is_from_exam) {
                $this->command->info("🏷️  Source badges: " . implode(', ', $testQuestion->source_badges));
            }
        }
        
        // Test Topic model relationships
        $testTopic = Topic::with('linkedQuestions')->first();
        if ($testTopic) {
            $this->command->info("📚 Topic '{$testTopic->name}' has {$testTopic->linkedQuestions->count()} linked questions");
            $this->command->info("📚 Topic '{$testTopic->name}' has {$testTopic->allQuestions()->count()} total questions (direct + linked)");
        }
        
        $this->command->info("✅ Relationship tests completed successfully!");
    }
}
