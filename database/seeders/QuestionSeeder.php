<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Question;
use App\Models\Topic;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        // Get the Internal Medicine topic ID 1
        $internalMedicineTopic = Topic::where('name', 'Topic 1 for Internal Medicine')->first();
        
        if (!$internalMedicineTopic) {
            $this->command->error('Topic 1 for Internal Medicine not found!');
            return;
        }

        $questions = [
            // Multiple choice questions
            [
                'topic_id' => $internalMedicineTopic->id,
                'question_type' => 'multiple_choice',
                'question' => 'Which of the following is the most common cause of community-acquired pneumonia?',
                'option_a' => 'Streptococcus pneumoniae',
                'option_b' => 'Haemophilus influenzae',
                'option_c' => 'Mycoplasma pneumoniae',
                'option_d' => 'Legionella pneumophila',
                'correct_answer' => 'A',
                'explanation' => 'Streptococcus pneumoniae (pneumococcus) is the most common cause of community-acquired pneumonia in adults.',
                'source' => 'Assuit'
            ],
            [
                'topic_id' => $internalMedicineTopic->id,
                'question_type' => 'multiple_choice',
                'question' => 'What is the first-line treatment for type 2 diabetes mellitus?',
                'option_a' => 'Metformin',
                'option_b' => 'Insulin',
                'option_c' => 'Sulfonylureas',
                'option_d' => 'DPP-4 inhibitors',
                'correct_answer' => 'A',
                'explanation' => 'Metformin is the first-line medication for type 2 diabetes due to its efficacy, safety profile, and cost-effectiveness.',
                'source' => 'Assuit'
            ],
            // Written questions
            [
                'topic_id' => $internalMedicineTopic->id,
                'question_type' => 'written',
                'question' => 'Describe the pathophysiology of diabetic ketoacidosis (DKA) and list three key clinical manifestations.',
                'option_a' => '',
                'option_b' => '',
                'option_c' => '',
                'option_d' => '',
                'correct_answer' => 'DKA occurs due to insulin deficiency leading to hyperglycemia, ketone production, and metabolic acidosis. Key manifestations: 1) Polyuria/polydipsia 2) Kussmaul breathing 3) Nausea/vomiting',
                'alternative_answers' => json_encode([
                    'DKA pathophysiology involves severe insulin deficiency causing hyperglycemia and ketogenesis. Clinical signs: polyuria, Kussmaul respirations, vomiting',
                    'In DKA, lack of insulin leads to high blood sugar and ketone formation. Main symptoms are excessive thirst/urination, deep rapid breathing, and GI symptoms'
                ]),
                'explanation' => 'DKA is a serious complication of diabetes characterized by the triad of hyperglycemia, ketosis, and acidosis. Understanding the pathophysiology and clinical manifestations is crucial for prompt diagnosis and treatment.',
                'source' => 'Assuit'
            ],
            // Image-based multiple choice
            [
                'topic_id' => $internalMedicineTopic->id,
                'question_type' => 'multiple_choice',
                'question' => 'What is the most likely diagnosis based on this chest X-ray?',
                'option_a' => 'Lobar pneumonia',
                'option_b' => 'Pulmonary edema',
                'option_c' => 'Pleural effusion',
                'option_d' => 'Normal chest X-ray',
                'correct_answer' => 'A',
                'explanation' => 'The X-ray shows a dense consolidation in the right lower lobe with air bronchograms, characteristic of lobar pneumonia.',
                'source' => 'Cairo'
            ],
            // Image-based written
            [
                'topic_id' => $internalMedicineTopic->id,
                'question_type' => 'written',
                'question' => 'Describe the ECG abnormalities shown in this image and provide the most likely diagnosis.',
                'option_a' => '',
                'option_b' => '',
                'option_c' => '',
                'option_d' => '',
                'correct_answer' => 'ST-segment elevation in leads V1-V4 with reciprocal ST depression in leads II, III, aVF, consistent with anterior STEMI (ST-elevation myocardial infarction)',
                'alternative_answers' => json_encode([
                    'Anterior STEMI with ST elevation in V1-V4 and reciprocal changes',
                    'ST elevation in anterior leads (V1-V4) indicating anterior wall MI'
                ]),
                'explanation' => 'The ECG shows classic findings of an anterior wall STEMI with ST-segment elevation in the precordial leads. Prompt recognition is essential for timely reperfusion therapy.',
                'source' => 'Assuit'
            ]
        ];

        foreach ($questions as $question) {
            Question::create($question);
        }

        $this->command->info('Added ' . count($questions) . ' questions including multiple choice and written questions with images');
    }
}
