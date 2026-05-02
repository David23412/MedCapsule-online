<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\Topic;
use Illuminate\Database\Seeder;

class TutorModeFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $topic = Topic::find(1);

        if (!$topic) {
            $this->command?->error('Topic 1 was not found. Aborting TutorModeFeatureSeeder.');
            return;
        }

        $questions = [
            [
                'question' => 'Which heart chamber has the thickest wall?',
                'option_a' => 'Right atrium',
                'option_b' => 'Left atrium',
                'option_c' => 'Right ventricle',
                'option_d' => 'Left ventricle',
                'correct_answer' => 'D',
                'explanation' => 'The left ventricle has the thickest wall because it pumps blood into the systemic circulation at the highest pressure.',
            ],
            [
                'question' => 'Which vessel carries oxygenated blood from the lungs to the heart?',
                'option_a' => 'Pulmonary artery',
                'option_b' => 'Pulmonary vein',
                'option_c' => 'Superior vena cava',
                'option_d' => 'Aorta',
                'correct_answer' => 'B',
                'explanation' => 'Pulmonary veins return oxygenated blood from the lungs to the left atrium.',
            ],
            [
                'question' => 'Which valve lies between the left atrium and the left ventricle?',
                'option_a' => 'Tricuspid valve',
                'option_b' => 'Pulmonary valve',
                'option_c' => 'Mitral valve',
                'option_d' => 'Aortic valve',
                'correct_answer' => 'C',
                'explanation' => 'The mitral valve, also called the bicuspid valve, separates the left atrium from the left ventricle.',
            ],
            [
                'question' => 'What is the normal pacemaker of the heart?',
                'option_a' => 'Atrioventricular node',
                'option_b' => 'Sinoatrial node',
                'option_c' => 'Bundle of His',
                'option_d' => 'Purkinje fibers',
                'correct_answer' => 'B',
                'explanation' => 'The sinoatrial node is the normal pacemaker because it has the fastest spontaneous depolarization rate.',
            ],
            [
                'question' => 'Which ECG wave represents ventricular depolarization?',
                'option_a' => 'P wave',
                'option_b' => 'T wave',
                'option_c' => 'QRS complex',
                'option_d' => 'U wave',
                'correct_answer' => 'C',
                'explanation' => 'The QRS complex corresponds to ventricular depolarization.',
            ],
            [
                'question' => 'Which blood pressure value is considered the systolic pressure?',
                'option_a' => 'The lower value',
                'option_b' => 'The upper value',
                'option_c' => 'The average value',
                'option_d' => 'The pulse pressure',
                'correct_answer' => 'B',
                'explanation' => 'Systolic pressure is the upper blood pressure reading and reflects arterial pressure during ventricular contraction.',
            ],
            [
                'question' => 'Which coronary artery usually supplies the anterior wall of the left ventricle?',
                'option_a' => 'Left anterior descending artery',
                'option_b' => 'Right coronary artery',
                'option_c' => 'Posterior descending artery',
                'option_d' => 'Circumflex artery only',
                'correct_answer' => 'A',
                'explanation' => 'The left anterior descending artery commonly supplies the anterior wall and anterior interventricular septum.',
            ],
            [
                'question' => 'Which ion is most directly responsible for the plateau phase of the ventricular action potential?',
                'option_a' => 'Sodium',
                'option_b' => 'Potassium',
                'option_c' => 'Calcium',
                'option_d' => 'Chloride',
                'correct_answer' => 'C',
                'explanation' => 'The plateau phase is maintained mainly by calcium influx through L-type calcium channels.',
            ],
            [
                'question' => 'Which condition classically causes crushing central chest pain radiating to the left arm?',
                'option_a' => 'Stable angina only',
                'option_b' => 'Myocardial infarction',
                'option_c' => 'Pericarditis only',
                'option_d' => 'Mitral stenosis',
                'correct_answer' => 'B',
                'explanation' => 'Crushing central chest pain radiating to the left arm is a classic presentation of myocardial infarction.',
            ],
            [
                'question' => 'Which bedside sign is commonly associated with right-sided heart failure?',
                'option_a' => 'Jaundice only',
                'option_b' => 'Neck stiffness',
                'option_c' => 'Raised jugular venous pressure',
                'option_d' => 'Kernig sign',
                'correct_answer' => 'C',
                'explanation' => 'Raised jugular venous pressure reflects systemic venous congestion and is a classic sign of right-sided heart failure.',
            ],
        ];

        $created = 0;

        foreach ($questions as $questionData) {
            $question = Question::firstOrCreate(
                [
                    'topic_id' => $topic->id,
                    'question' => $questionData['question'],
                ],
                [
                    'source' => 'Tutor Feature Pack',
                    'question_type' => Question::TYPE_MULTIPLE_CHOICE,
                    'option_a' => $questionData['option_a'],
                    'option_b' => $questionData['option_b'],
                    'option_c' => $questionData['option_c'],
                    'option_d' => $questionData['option_d'],
                    'correct_answer' => $questionData['correct_answer'],
                    'explanation' => $questionData['explanation'],
                ]
            );

            if ($question->wasRecentlyCreated) {
                $created++;
            }
        }

        $this->command?->info("TutorModeFeatureSeeder complete for topic {$topic->id}. Added {$created} new questions.");
    }
}
