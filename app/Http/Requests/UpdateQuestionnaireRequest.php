<?php

namespace App\Http\Requests;

use App\Models\Questionnaire;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionnaireRequest extends FormRequest
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
        return StoreQuestionnaireRequest::baseRules();
    }
}
