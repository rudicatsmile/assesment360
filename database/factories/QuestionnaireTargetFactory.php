<?php

namespace Database\Factories;

use App\Models\QuestionnaireTarget;
use App\Models\Questionnaire;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionnaireTarget>
 */
class QuestionnaireTargetFactory extends Factory
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
            'target_group' => fake()->randomElement(['guru', 'tata_usaha', 'orang_tua']),
        ];
    }
}
