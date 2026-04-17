<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->role === 'admin';
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return self::baseRules(requirePassword: true);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public static function baseRules(bool $requirePassword): array
    {
        $passwordRule = $requirePassword
            ? ['required', 'string', 'min:8', 'max:100']
            : ['nullable', 'string', 'min:8', 'max:100'];

        return [
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'email' => ['required', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'phone_number' => ['nullable', 'string', 'max:25', 'regex:/^[0-9+\-\s()]+$/'],
            'password' => $passwordRule,
            'role' => ['required', Rule::in(['admin', 'guru', 'tata_usaha', 'orang_tua'])],
            'department_id' => ['nullable', 'integer', 'exists:departements,id'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => strtolower(trim((string) $this->input('email'))),
            'phone_number' => trim((string) $this->input('phone_number')),
            'department_id' => $this->input('department_id') !== '' ? (int) $this->input('department_id') : null,
        ]);
    }
}
