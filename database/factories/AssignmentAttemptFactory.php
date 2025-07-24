<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\AssignmentAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssignmentAttemptFactory extends Factory
{
    protected $model = AssignmentAttempt::class;

    public function definition(): array
    {
        $questionsAnswered = $this->faker->numberBetween(5, 20);
        $correctAnswers = $this->faker->numberBetween(0, $questionsAnswered);
        $scorePercentage = $questionsAnswered > 0 ? ($correctAnswers / $questionsAnswered) * 100 : 0;
        $timeSpentSeconds = $this->faker->numberBetween(300, 3600); // 5 minutes to 1 hour
        $startedAt = $this->faker->dateTimeBetween('-1 month', 'now');
        $completedAt = (clone $startedAt)->modify("+{$timeSpentSeconds} seconds");
        
        // Generate fake answers
        $answers = [];
        for ($i = 1; $i <= $questionsAnswered; $i++) {
            $answers[$i] = $this->faker->randomElement(['A', 'B', 'C', 'D']);
        }

        return [
            'assignment_id' => Assignment::factory(),
            'user_id' => User::factory(),
            'source' => $this->faker->randomElement(['Kaplan', 'Practice Sheet', 'UWorld', 'First Aid', 'all']),
            'questions_answered' => $questionsAnswered,
            'correct_answers' => $correctAnswers,
            'score_percentage' => $scorePercentage,
            'time_spent_seconds' => $timeSpentSeconds,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'auto_submitted' => $this->faker->boolean(20), // 20% chance of auto-submission
            'answers' => $answers,
        ];
    }

    /**
     * Indicate that the attempt was auto-submitted
     */
    public function autoSubmitted(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'auto_submitted' => true,
            ];
        });
    }

    /**
     * Indicate that the attempt was successful (>= 70% correct)
     */
    public function successful(): self
    {
        return $this->state(function (array $attributes) {
            $questionsAnswered = $attributes['questions_answered'] ?? $this->faker->numberBetween(5, 20);
            $correctAnswers = ceil($questionsAnswered * 0.7); // At least 70% correct
            
            return [
                'questions_answered' => $questionsAnswered,
                'correct_answers' => $correctAnswers,
                'score_percentage' => ($correctAnswers / $questionsAnswered) * 100,
            ];
        });
    }

    /**
     * Indicate that the attempt was unsuccessful (< 70% correct)
     */
    public function unsuccessful(): self
    {
        return $this->state(function (array $attributes) {
            $questionsAnswered = $attributes['questions_answered'] ?? $this->faker->numberBetween(5, 20);
            $correctAnswers = floor($questionsAnswered * 0.5); // At most 50% correct
            
            return [
                'questions_answered' => $questionsAnswered,
                'correct_answers' => $correctAnswers,
                'score_percentage' => ($correctAnswers / $questionsAnswered) * 100,
            ];
        });
    }
}