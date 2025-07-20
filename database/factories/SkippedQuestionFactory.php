<?php

namespace Database\Factories;

use App\Models\SkippedQuestion;
use App\Models\User;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

class SkippedQuestionFactory extends Factory
{
    protected $model = SkippedQuestion::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->create(['role' => 'student'])->id,
            'question_id' => Question::factory()->create()->id,
            'skipped_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'session_type' => $this->faker->randomElement([
                SkippedQuestion::SESSION_LEARN_MISTAKES,
                SkippedQuestion::SESSION_QUIZ,
                SkippedQuestion::SESSION_REVIEW
            ])
        ];
    }

    public function learnMistakes(): static
    {
        return $this->state(fn (array $attributes) => [
            'session_type' => SkippedQuestion::SESSION_LEARN_MISTAKES
        ]);
    }

    public function quiz(): static
    {
        return $this->state(fn (array $attributes) => [
            'session_type' => SkippedQuestion::SESSION_QUIZ
        ]);
    }

    public function review(): static
    {
        return $this->state(fn (array $attributes) => [
            'session_type' => SkippedQuestion::SESSION_REVIEW
        ]);
    }

    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'skipped_at' => $this->faker->dateTimeBetween('-7 days', 'now')
        ]);
    }

    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'skipped_at' => $this->faker->dateTimeBetween('-60 days', '-30 days')
        ]);
    }
}