<?php

namespace Database\Factories;

use App\Models\Response;
use App\Models\Questionnaire;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Response>
 */
class ResponseFactory extends Factory
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
            'user_id' => User::factory(),
            'submitted_at' => now(),
            'status' => fake()->randomElement(['draft', 'submitted']),
        ];
    }
}
