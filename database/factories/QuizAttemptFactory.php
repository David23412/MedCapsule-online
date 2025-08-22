<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Topic;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalQuestions = fake()->numberBetween(5, 20);
        $correctAnswers = fake()->numberBetween(0, $totalQuestions);
        $score = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0;

        return [
            'user_id' => User::factory(),
            'topic_id' => Topic::factory(),
            'correct_answers' => $correctAnswers,
            'total_questions' => $totalQuestions,
            'score' => $score,
            'percentage_grade' => $score,
            'created_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'updated_at' => now(),
        ];
    }

    /**
     * Generate detailed question results for the attempt
     */
    private function generateQuestionResults(int $totalQuestions, int $correctAnswers): array
    {
        $results = [];
        $correctIndices = fake()->randomElements(range(0, $totalQuestions - 1), $correctAnswers);

        for ($i = 0; $i < $totalQuestions; $i++) {
            $isCorrect = in_array($i, $correctIndices);
            $results[] = [
                'question_id' => fake()->numberBetween(1, 1000),
                'is_correct' => $isCorrect,
                'user_answer' => $this->generateAnswer($isCorrect),
                'correct_answer' => $this->generateCorrectAnswer(),
                'response_time' => fake()->numberBetween(5, 120),
                'difficulty' => fake()->randomElement(['easy', 'medium', 'hard']),
                'topic' => fake()->randomElement(['anatomy', 'pathology', 'pharmacology', 'clinical']),
            ];
        }

        return $results;
    }

    /**
     * Generate mistake summary data
     */
    private function generateMistakeSummary(): array
    {
        return [
            'total_mistakes' => fake()->numberBetween(0, 10),
            'common_mistake_types' => fake()->randomElements([
                'miscalculation',
                'wrong formula',
                'incorrect interpretation',
                'missing information',
                'time pressure'
            ], fake()->numberBetween(0, 3)),
            'learning_recommendations' => fake()->randomElements([
                'Review basic concepts',
                'Practice similar problems',
                'Focus on calculation accuracy',
                'Study related topics',
                'Time management improvement'
            ], fake()->numberBetween(0, 3)),
            'strength_areas' => fake()->randomElements([
                'Good understanding of concepts',
                'Quick problem solving',
                'Accurate calculations',
                'Strong analytical skills'
            ], fake()->numberBetween(0, 2)),
        ];
    }

    /**
     * Generate a user answer (correct or incorrect)
     */
    private function generateAnswer(bool $isCorrect): string
    {
        if ($isCorrect) {
            return $this->generateCorrectAnswer();
        }

        // Generate common wrong answers
        return fake()->randomElement([
            'A', 'B', 'C', 'D',
            'Incorrect calculation',
            'Wrong interpretation',
            'Missing step in process',
            'Confused terminology'
        ]);
    }

    /**
     * Generate a correct answer
     */
    private function generateCorrectAnswer(): string
    {
        return fake()->randomElement([
            'A', 'B', 'C', 'D',
            'Correct calculation: 42',
            'The answer is option B',
            'Based on the clinical presentation',
            'Following standard protocol'
        ]);
    }

    /**
     * Create a completed quiz attempt
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'is_completed' => true,
            'completion_date' => fake()->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Create a high-scoring quiz attempt
     */
    public function highScore(): static
    {
        return $this->state(function (array $attributes) {
            $totalQuestions = fake()->numberBetween(8, 15);
            $correctAnswers = fake()->numberBetween(ceil($totalQuestions * 0.8), $totalQuestions);
            $score = round(($correctAnswers / $totalQuestions) * 100, 2);

            return [
                'total_questions' => $totalQuestions,
                'correct_answers' => $correctAnswers,
                'score' => $score,
                'percentage_grade' => $score,
                'accuracy_rate' => $score,
            ];
        });
    }

    /**
     * Create a low-scoring quiz attempt
     */
    public function lowScore(): static
    {
        return $this->state(function (array $attributes) {
            $totalQuestions = fake()->numberBetween(8, 15);
            $correctAnswers = fake()->numberBetween(0, floor($totalQuestions * 0.3));
            $score = round(($correctAnswers / $totalQuestions) * 100, 2);

            return [
                'total_questions' => $totalQuestions,
                'correct_answers' => $correctAnswers,
                'score' => $score,
                'percentage_grade' => $score,
                'accuracy_rate' => $score,
                'needs_review' => true,
            ];
        });
    }

    /**
     * Create an assignment-related quiz attempt
     */
    public function assignment(): static
    {
        return $this->state(fn (array $attributes) => [
            'quiz_type' => 'assignment',
            'assignment_id' => fake()->numberBetween(1, 100),
            'is_practice_mode' => false,
        ]);
    }

    /**
     * Create a timed-out quiz attempt
     */
    public function timedOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'timed_out',
            'is_completed' => false,
            'duration_seconds' => fake()->numberBetween(300, 7200), // 5 min to 2 hours
        ]);
    }

    /**
     * Create a practice mode quiz attempt
     */
    public function practice(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_practice_mode' => true,
            'quiz_type' => 'practice',
        ]);
    }

    /**
     * Create a quiz attempt with specific user and topic
     */
    public function forUserAndTopic(User $user, Topic $topic): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'topic_id' => $topic->id,
        ]);
    }

    /**
     * Create a quiz attempt with specific performance metrics
     */
    public function withPerformance(int $totalQuestions, int $correctAnswers, float $avgResponseTime): static
    {
        $score = round(($correctAnswers / $totalQuestions) * 100, 2);

        return $this->state(fn (array $attributes) => [
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctAnswers,
            'score' => $score,
            'percentage_grade' => $score,
            'accuracy_rate' => $score,
            'average_response_time' => $avgResponseTime,
            'duration_seconds' => $avgResponseTime * $totalQuestions,
        ]);
    }
}
