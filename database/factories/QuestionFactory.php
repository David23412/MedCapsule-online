<?php

namespace Database\Factories;

use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $questionTypes = ['multiple_choice', 'written'];
        
        $questionType = $this->faker->randomElement($questionTypes);
        
        $questionData = [
            'topic_id' => Topic::factory(),
            'question' => $this->generateQuestionText($questionType),
            'explanation' => $this->faker->optional(0.8)->paragraph(),
            'image_url' => $this->faker->optional(0.3)->imageUrl(640, 480, 'medical'),
            'source' => $this->faker->optional(0.7)->randomElement(['Assuit', 'Cairo', 'Alexandria', 'Mansoura', 'Ain Shams']),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'question_type' => $questionType,
        ];

        // Add type-specific data
        if ($questionType === 'multiple_choice') {
            $options = $this->generateMultipleChoiceOptions();
            $questionData['option_a'] = $options[0];
            $questionData['option_b'] = $options[1];
            $questionData['option_c'] = $options[2];
            $questionData['option_d'] = $options[3];
            $questionData['correct_answer'] = $this->faker->randomElement(['A', 'B', 'C', 'D']);
        } elseif ($questionType === 'written') {
            $questionData['correct_answer'] = $this->generateEssayAnswer();
        }

        return $questionData;
    }

    /**
     * Generate question text based on type.
     */
    private function generateQuestionText(string $type): string
    {
        $medicalQuestions = [
            'multiple_choice' => [
                'What is the primary function of the heart?',
                'Which of the following is a symptom of diabetes?',
                'What is the normal range for blood pressure?',
                'Which organ is responsible for filtering blood?',
                'What is the main component of blood?',
                'Which hormone regulates blood sugar levels?',
                'What is the function of white blood cells?',
                'Which system controls body temperature?',
                'What is the role of the pancreas?',
                'Which vitamin is essential for blood clotting?'
            ],
            'written' => [
                'Explain the cardiovascular system and its main components.',
                'Describe the pathophysiology of diabetes mellitus.',
                'Discuss the importance of blood pressure regulation.',
                'Explain the role of the kidneys in maintaining homeostasis.',
                'Describe the different types of blood cells and their functions.',
                'Discuss the endocrine system and hormone regulation.',
                'Explain the immune system and its defense mechanisms.',
                'Describe the nervous system and its control functions.',
                'Discuss the digestive system and nutrient absorption.',
                'Explain the importance of vitamins and minerals in health.'
            ]
        ];

        return $this->faker->randomElement($medicalQuestions[$type]);
    }

    /**
     * Generate multiple choice options.
     */
    private function generateMultipleChoiceOptions(): array
    {
        $optionsSets = [
            [
                'To pump blood throughout the body',
                'To digest food',
                'To produce hormones',
                'To filter waste'
            ],
            [
                'Increased thirst and urination',
                'Improved vision',
                'Increased energy',
                'Better sleep'
            ],
            [
                'Less than 120/80 mmHg',
                '120-129/80-84 mmHg',
                '130-139/85-89 mmHg',
                '140/90 mmHg or higher'
            ],
            [
                'Heart',
                'Liver',
                'Kidneys',
                'Lungs'
            ],
            [
                'Plasma',
                'Red blood cells',
                'White blood cells',
                'Platelets'
            ]
        ];

        return $this->faker->randomElement($optionsSets);
    }

    /**
     * Generate essay answer.
     */
    private function generateEssayAnswer(): string
    {
        $answers = [
            'The cardiovascular system consists of the heart, blood vessels, and blood. The heart pumps oxygenated blood to tissues and returns deoxygenated blood to the lungs for reoxygenation.',
            'Diabetes mellitus is characterized by elevated blood glucose levels due to insufficient insulin production or insulin resistance, leading to various complications.',
            'Blood pressure regulation is crucial for maintaining adequate tissue perfusion and preventing cardiovascular complications.',
            'The kidneys filter waste products from the blood, regulate fluid and electrolyte balance, and help maintain acid-base homeostasis.',
            'Blood contains red blood cells (oxygen transport), white blood cells (immune defense), and platelets (clotting).'
        ];

        return $this->faker->randomElement($answers);
    }

    /**
     * Create a question for a specific topic.
     */
    public function forTopic(Topic $topic): static
    {
        return $this->state(fn (array $attributes) => [
            'topic_id' => $topic->id,
        ]);
    }

    /**
     * Create a multiple choice question.
     */
    public function multipleChoice(): static
    {
        $options = $this->generateMultipleChoiceOptions();
        return $this->state(fn (array $attributes) => [
            'question' => $this->generateQuestionText('multiple_choice'),
            'question_type' => 'multiple_choice',
            'option_a' => $options[0],
            'option_b' => $options[1],
            'option_c' => $options[2],
            'option_d' => $options[3],
            'correct_answer' => $this->faker->randomElement(['A', 'B', 'C', 'D']),
        ]);
    }

    /**
     * Create an essay question.
     */
    public function essay(): static
    {
        return $this->state(fn (array $attributes) => [
            'question' => $this->generateQuestionText('written'),
            'question_type' => 'written',
            'correct_answer' => $this->generateEssayAnswer(),
        ]);
    }
}
