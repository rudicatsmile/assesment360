<?php

namespace App\Http\Requests;

use App\Models\Questionnaire;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionnaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Questionnaire::class);
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'],
            'status' => ['required', Rule::in(['draft', 'active', 'closed'])],
            'target_groups' => ['required', 'array', 'min:1'],
            'target_groups.*' => ['required', 'string', 'distinct', Rule::in(Questionnaire::targetGroups())],
        ];
    }
}
