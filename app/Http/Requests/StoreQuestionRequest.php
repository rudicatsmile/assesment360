<?php

namespace App\Http\Requests;

use App\Models\Questionnaire;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $questionnaire = $this->route('questionnaire');

        if (! $questionnaire instanceof Questionnaire) {
            return false;
        }

        return (bool) $this->user()?->can('update', $questionnaire);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return self::baseRules();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public static function baseRules(): array
    {
        return [
            'question_text' => ['required', 'string', 'max:5000'],
            'type' => ['required', Rule::in(['single_choice', 'essay', 'combined'])],
            'is_required' => ['required', 'boolean'],
            'options' => ['array'],
            'options.*.id' => ['nullable', 'integer'],
            'options.*.option_text' => ['nullable', 'string', 'max:255'],
            'options.*.score' => ['nullable', 'integer'],
        ];
    }
}
