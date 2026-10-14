<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\QuizAttempt;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<QuizAttempt> */
class QuizAttemptFactory extends Factory
{
    protected $model = QuizAttempt::class;

    public function definition(): array
    {
        $total = fake()->numberBetween(5, 20);
        $correct = fake()->numberBetween(0, $total);

        return [
            'user_id' => User::factory(),
            'topic_id' => Topic::factory(),
            'quiz_type' => 'normal',
            'study_mode' => 'quiz',
            'score' => $correct,
            'total_questions' => $total,
            'percentage_grade' => round($correct / $total * 100, 2),
            'duration_seconds' => fake()->numberBetween(60, 1200),
            'created_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'updated_at' => now(),
        ];
    }

    public function completed(): static
    {
        // The quiz_attempts table stores completed attempts only.
        return $this;
    }

    public function highScore(): static
    {
        return $this->state(function (): array {
            $total = fake()->numberBetween(8, 15);
            $correct = fake()->numberBetween((int) ceil($total * 0.8), $total);
            return ['total_questions' => $total, 'score' => $correct, 'percentage_grade' => round($correct / $total * 100, 2)];
        });
    }

    public function lowScore(): static
    {
        return $this->state(function (): array {
            $total = fake()->numberBetween(8, 15);
            $correct = fake()->numberBetween(0, (int) floor($total * 0.3));
            return ['total_questions' => $total, 'score' => $correct, 'percentage_grade' => round($correct / $total * 100, 2)];
        });
    }

    public function assignment(): static
    {
        return $this->state(fn (): array => ['quiz_type' => 'assignment', 'study_mode' => 'assignment', 'assignment_id' => Assignment::factory()]);
    }

    public function timedOut(): static
    {
        return $this->state(fn (): array => ['duration_seconds' => fake()->numberBetween(300, 7200)]);
    }

    public function practice(): static
    {
        return $this->state(fn (): array => ['quiz_type' => 'normal', 'study_mode' => 'quiz']);
    }

    public function forUserAndTopic(User $user, Topic $topic): static
    {
        return $this->state(fn (): array => ['user_id' => $user->id, 'topic_id' => $topic->id]);
    }

    public function withPerformance(int $totalQuestions, int $correctAnswers, float $avgResponseTime): static
    {
        if ($totalQuestions < 1 || $correctAnswers < 0 || $correctAnswers > $totalQuestions || $avgResponseTime < 0) {
            throw new \InvalidArgumentException('Quiz performance requires a positive question count and valid score and duration.');
        }

        return $this->state(fn (): array => [
            'total_questions' => $totalQuestions,
            'score' => $correctAnswers,
            'percentage_grade' => round($correctAnswers / $totalQuestions * 100, 2),
            'duration_seconds' => (int) round($avgResponseTime * $totalQuestions),
        ]);
    }
}
