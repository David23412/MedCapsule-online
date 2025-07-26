<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Question;
use App\Models\Topic;
use App\Models\Course;
use Illuminate\Support\Facades\Log;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        try {
            // First ensure we have the Internal Medicine course
            $course = Course::firstOrCreate(
                ['name' => 'Internal Medicine'],
                [
                    'description' => 'Comprehensive course covering internal medicine topics',
                    'image_path' => 'courses/internal-medicine.jpg',
                    'price' => 99.99
                ]
            );

            // Create or get the topic
            $topic = Topic::firstOrCreate(
                [
                    'course_id' => $course->id,
                    'name' => 'Topic 1 for Internal Medicine'
                ],
                [
                    'case_type' => 'quiz',
                    'display_order' => 1
                ]
            );

            $questions = [
                // Basic multiple choice question
                [
                    'topic_id' => $topic->id,
                    'question_type' => Question::TYPE_MULTIPLE_CHOICE,
                    'question' => 'Which of the following is the most common cause of community-acquired pneumonia?',
                    'option_a' => 'Streptococcus pneumoniae',
                    'option_b' => 'Haemophilus influenzae',
                    'option_c' => 'Mycoplasma pneumoniae',
                    'option_d' => 'Legionella pneumophila',
                    'correct_answer' => 'A',
                    'explanation' => 'Streptococcus pneumoniae (pneumococcus) is the most common cause of community-acquired pneumonia in adults.',
                    'source' => 'Assuit'
                ],
                // Multiple choice with image - using placeholder
                [
                    'topic_id' => $topic->id,
                    'question_type' => Question::TYPE_MULTIPLE_CHOICE,
                    'question' => 'What is the most likely diagnosis based on this chest X-ray?',
                    'option_a' => 'Lobar pneumonia',
                    'option_b' => 'Pulmonary edema',
                    'option_c' => 'Pleural effusion',
                    'option_d' => 'Normal chest X-ray',
                    'correct_answer' => 'A',
                    'explanation' => 'The X-ray shows a dense consolidation in the right lower lobe with air bronchograms, characteristic of lobar pneumonia.',
                    'source' => 'Cairo',
                    'image_url' => null // Temporarily removing image until we have proper assets
                ],
                // Written question with multiple acceptable answers
                [
                    'topic_id' => $topic->id,
                    'question_type' => Question::TYPE_WRITTEN,
                    'question' => 'Describe the pathophysiology of diabetic ketoacidosis (DKA) and list three key clinical manifestations.',
                    'option_a' => null,
                    'option_b' => null,
                    'option_c' => null,
                    'option_d' => null,
                    'correct_answer' => 'DKA occurs due to insulin deficiency leading to hyperglycemia, ketone production, and metabolic acidosis. Key manifestations: 1) Polyuria/polydipsia 2) Kussmaul breathing 3) Nausea/vomiting',
                    'alternative_answers' => json_encode([
                        'DKA pathophysiology involves severe insulin deficiency causing hyperglycemia and ketogenesis. Clinical signs: polyuria, Kussmaul respirations, vomiting',
                        'In DKA, lack of insulin leads to high blood sugar and ketone formation. Main symptoms are excessive thirst/urination, deep rapid breathing, and GI symptoms'
                    ]),
                    'explanation' => 'DKA is a serious complication of diabetes characterized by the triad of hyperglycemia, ketosis, and acidosis. Understanding the pathophysiology and clinical manifestations is crucial for prompt diagnosis and treatment.',
                    'source' => 'Assuit'
                ],
                // Written question with image - using placeholder
                [
                    'topic_id' => $topic->id,
                    'question_type' => Question::TYPE_WRITTEN,
                    'question' => 'Describe the ECG abnormalities shown in this image and provide the most likely diagnosis.',
                    'option_a' => null,
                    'option_b' => null,
                    'option_c' => null,
                    'option_d' => null,
                    'correct_answer' => 'ST-segment elevation in leads V1-V4 with reciprocal ST depression in leads II, III, aVF, consistent with anterior STEMI (ST-elevation myocardial infarction)',
                    'alternative_answers' => json_encode([
                        'Anterior STEMI with ST elevation in V1-V4 and reciprocal changes',
                        'ST elevation in anterior leads (V1-V4) indicating anterior wall MI'
                    ]),
                    'explanation' => 'The ECG shows classic findings of an anterior wall STEMI with ST-segment elevation in the precordial leads. Prompt recognition is essential for timely reperfusion therapy.',
                    'source' => 'Assuit',
                    'image_url' => null // Temporarily removing image until we have proper assets
                ],
                // Multiple choice question
                [
                    'topic_id' => $topic->id,
                    'question_type' => Question::TYPE_MULTIPLE_CHOICE,
                    'question' => 'What is the first-line treatment for type 2 diabetes mellitus?',
                    'option_a' => 'Metformin',
                    'option_b' => 'Insulin',
                    'option_c' => 'Sulfonylureas',
                    'option_d' => 'DPP-4 inhibitors',
                    'correct_answer' => 'A',
                    'explanation' => 'Metformin is the first-line medication for type 2 diabetes due to its efficacy, safety profile, and cost-effectiveness.',
                    'source' => 'Assuit'
                ],
                // Written question
                [
                    'topic_id' => $topic->id,
                    'question_type' => Question::TYPE_WRITTEN,
                    'question' => 'Name three symptoms of acute appendicitis.',
                    'option_a' => null,
                    'option_b' => null,
                    'option_c' => null,
                    'option_d' => null,
                    'correct_answer' => 'Right lower quadrant pain, nausea/vomiting, fever',
                    'alternative_answers' => json_encode([
                        'Abdominal pain, nausea, fever',
                        'RLQ pain, vomiting, elevated temperature',
                        'McBurney point tenderness, nausea, pyrexia'
                    ]),
                    'explanation' => 'The classic triad of appendicitis includes right lower quadrant pain (or pain migration to RLQ), nausea/vomiting, and fever. However, not all patients present with all symptoms.',
                    'source' => 'Cairo'
                ]
            ];

            $createdCount = 0;
            $errorCount = 0;

            foreach ($questions as $questionData) {
                try {
                    Question::create($questionData);
                    $createdCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error('Failed to create question', [
                        'error' => $e->getMessage(),
                        'question' => $questionData['question']
                    ]);
                }
            }

            $this->command->info("Successfully created {$createdCount} questions.");
            if ($errorCount > 0) {
                $this->command->warn("Failed to create {$errorCount} questions. Check the logs for details.");
            }

        } catch (\Exception $e) {
            Log::error('Error in QuestionSeeder:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->command->error('Failed to seed questions: ' . $e->getMessage());
        }
    }
}
