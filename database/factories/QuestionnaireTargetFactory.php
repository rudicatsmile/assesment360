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
        $targetGroups = array_values(array_filter((array) config('rbac.questionnaire_target_slugs', [])));
        $defaultTarget = (string) ($targetGroups[0] ?? '');

        return [
            'questionnaire_id' => Questionnaire::factory(),
            'target_group' => $targetGroups !== [] ? fake()->randomElement($targetGroups) : $defaultTarget,
        ];
    }
}
