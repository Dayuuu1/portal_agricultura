<?php

namespace App\Http\Requests\MesaPartes;

use App\Http\Requests\Concerns\SpanishValidationMessages;
use App\Support\MesaPartes\MesaPartesConfig;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Contact data and the authorization for electronic notifications, which the citizen may grant or revoke.
 * Identity data and the e-mail are not edited here: they identify who filed each document.
 */
class UpdateCitizenProfileRequest extends FormRequest
{
    use SpanishValidationMessages;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9 +()-]{6,20}$/'],
            'department' => ['required', Rule::in(MesaPartesConfig::DEPARTMENTS)],
            'province' => ['required', 'string', 'max:80'],
            'district' => ['required', 'string', 'max:80'],
            'address' => ['required', 'string', 'max:255'],
            'e_notifications' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'phone' => 'teléfono celular',
            'department' => 'departamento',
            'province' => 'provincia',
            'district' => 'distrito',
            'address' => 'dirección',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->spanishMessages(),
            'phone.regex' => 'El teléfono solo puede contener números y los signos + ( ) -.',
        ];
    }
}
