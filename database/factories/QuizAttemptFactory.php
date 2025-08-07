<?php

namespace Database\Factories;

use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuizAttemptFactory extends Factory
{
    protected $model = QuizAttempt::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'topic_id' => Topic::factory(),        }

            'score' => $this->faker->numberBetween(0, 10),
            'total_questions' => 10,
            'duration_seconds' => $this->faker->numberBetween(60, 1200),
            'percentage_grade' => $this->faker->randomFloat(2, 0, 100),
            'quiz_type' => 'normal',
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
} 