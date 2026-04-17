<?php

namespace Database\Factories;

use App\Models\Questionnaire;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Questionnaire>
 */
class QuestionnaireFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'start_date' => now()->subDays(3),
            'end_date' => now()->addDays(10),
            'status' => fake()->randomElement(['draft', 'active', 'closed']),
            'created_by' => User::factory(),
        ];
    }
}
