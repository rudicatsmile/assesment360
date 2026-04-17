<?php

namespace Database\Factories;

use App\Models\Answer;
use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\Response;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Answer>
 */
class AnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'response_id' => Response::factory(),
            'question_id' => Question::factory(),
            'department_id' => null,
            'answer_option_id' => AnswerOption::factory(),
            'essay_answer' => null,
            'calculated_score' => fake()->numberBetween(1, 5),
        ];
    }
}
