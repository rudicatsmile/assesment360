<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Questionnaire;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
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
        return [
            'questionnaire_id' => Questionnaire::factory(),
            'question_text' => fake()->sentence(8),
            'type' => fake()->randomElement(['single_choice', 'essay', 'combined']),
            'is_required' => fake()->boolean(80),
            'order' => fake()->numberBetween(1, 20),
        ];
    }
}
