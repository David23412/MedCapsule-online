<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;

class TestQuestionsSeeder extends Seeder
{
    public function run()
    {
        // Get or create a test course
        $course = Course::firstOrCreate(
            ['name' => 'Medical Test Course'],
            [
                'type' => 'medical',
                'price' => 0
            ]
        );

        // Get or create test topics
        $topics = [
            'Cardiovascular System',
            'Respiratory System', 
            'Nervous System',
            'Digestive System',
            'Endocrine System'
        ];

        foreach ($topics as $topicName) {
            $topic = Topic::firstOrCreate(
                ['name' => $topicName, 'course_id' => $course->id],
                [
                    'case_type' => 'quiz',
                    'display_order' => Topic::where('course_id', $course->id)->count() + 1
                ]
            );

            // Add test questions for each topic
            $this->createQuestionsForTopic($topic);
        }

        $this->command->info('✅ Test questions seeded successfully!');
    }

    private function createQuestionsForTopic(Topic $topic)
    {
        $questions = [
            [
                'question' => "What is the main function of the {$topic->name}?",
                'option_a' => 'Primary function A',
                'option_b' => 'Primary function B', 
                'option_c' => 'Primary function C',
                'option_d' => 'Primary function D',
                'correct_answer' => 'A',
                'explanation' => "The {$topic->name} primarily performs function A.",
                'source' => 'Test Source 1'
            ],
            [
                'question' => "Which component is most important in the {$topic->name}?",
                'option_a' => 'Component Alpha',
                'option_b' => 'Component Beta',
                'option_c' => 'Component Gamma', 
                'option_d' => 'Component Delta',
                'correct_answer' => 'B',
                'explanation' => "Component Beta is the most critical for {$topic->name} function.",
                'source' => 'Test Source 2'
            ],
            [
                'question' => "What happens when the {$topic->name} malfunctions?",
                'option_a' => 'Symptom 1 occurs',
                'option_b' => 'Symptom 2 occurs',
                'option_c' => 'Symptom 3 occurs',
                'option_d' => 'All of the above',
                'correct_answer' => 'D',
                'explanation' => "Multiple symptoms can occur when the {$topic->name} malfunctions.",
                'source' => 'Test Source 1'
            ],
            [
                'question' => "How is the {$topic->name} regulated?",
                'option_a' => 'Hormonal regulation',
                'option_b' => 'Neural regulation',
                'option_c' => 'Chemical regulation',
                'option_d' => 'Mechanical regulation',
                'correct_answer' => 'A',
                'explanation' => "The {$topic->name} is primarily regulated through hormonal mechanisms.",
                'source' => 'Test Source 2'
            ],
            [
                'question' => "What is the clinical significance of {$topic->name} disorders?",
                'option_a' => 'Minor health impact',
                'option_b' => 'Moderate health impact',
                'option_c' => 'Major health impact',
                'option_d' => 'Life-threatening impact',
                'correct_answer' => 'C',
                'explanation' => "Disorders of the {$topic->name} typically have major health implications.",
                'source' => 'Test Source 1'
            ]
        ];

        foreach ($questions as $questionData) {
            Question::firstOrCreate(
                [
                    'topic_id' => $topic->id,
                    'question' => $questionData['question']
                ],
                $questionData
            );
        }
    }
}
