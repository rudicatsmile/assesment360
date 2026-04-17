<?php

namespace Database\Factories;

use App\Models\AnswerOption;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnswerOption>
 */
class AnswerOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'department_id' => null,
            'option_text' => fake()->sentence(2),
            'score' => fake()->numberBetween(1, 5),
            'order' => fake()->numberBetween(1, 6),
        ];
    }
}
