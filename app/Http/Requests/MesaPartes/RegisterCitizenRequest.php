<?php

namespace App\Http\Requests\MesaPartes;

use App\Enums\IdentityDocumentType;
use App\Enums\PersonType;
use App\Http\Requests\Concerns\SpanishValidationMessages;
use App\Support\MesaPartes\MesaPartesConfig;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Account of the Mesa de Partes: the data a written request needs (TUO de la Ley N° 27444, art. 124), the consent
 * to process personal data (Ley N° 29733) and, optionally, the authorization for electronic notifications (art. 20.4).
 */
class RegisterCitizenRequest extends FormRequest
{
    use SpanishValidationMessages;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'document_number' => strtoupper(trim((string) $this->input('document_number'))),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isLegalEntity = $this->input('person_type') === PersonType::Juridica->value;
        $documentType = IdentityDocumentType::tryFrom((string) $this->input('document_type'));

        return [
            'person_type' => ['required', Rule::in(MesaPartesConfig::allowsLegalEntities() ? [PersonType::Natural->value, PersonType::Juridica->value] : [PersonType::Natural->value])],
            'document_type' => ['required', Rule::in($isLegalEntity
                ? [IdentityDocumentType::Ruc->value]
                : [IdentityDocumentType::Dni->value, IdentityDocumentType::CarneExtranjeria->value, IdentityDocumentType::Pasaporte->value])],
            'document_number' => ['required', 'string', $documentType?->numberRule() ?? 'max:20',
                Rule::unique('citizens', 'document_number')->where('document_type', $documentType?->value)],
            'first_name' => ['nullable', Rule::requiredIf(! $isLegalEntity), 'string', 'max:100'],
            'last_name' => ['nullable', Rule::requiredIf(! $isLegalEntity), 'string', 'max:100'],
            'business_name' => ['nullable', Rule::requiredIf($isLegalEntity), 'string', 'max:200'],
            'representative_name' => ['nullable', Rule::requiredIf($isLegalEntity), 'string', 'max:150'],
            'representative_document_number' => ['nullable', Rule::requiredIf($isLegalEntity), 'string', 'regex:/^[A-Za-z0-9]{8,12}$/'],
            'email' => ['required', 'email', 'max:150', Rule::unique('citizens', 'email')],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9 +()-]{6,20}$/'],
            'department' => ['required', Rule::in(MesaPartesConfig::DEPARTMENTS)],
            'province' => ['required', 'string', 'max:80'],
            'district' => ['required', 'string', 'max:80'],
            'address' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'privacy' => ['accepted'],
            'truthful' => ['accepted'],
            'e_notifications' => ['nullable', 'boolean'],
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'person_type' => 'tipo de persona',
            'document_type' => 'tipo de documento',
            'document_number' => 'número de documento',
            'first_name' => 'nombres',
            'last_name' => 'apellidos',
            'business_name' => 'razón social',
            'representative_name' => 'nombre del representante legal',
            'representative_document_number' => 'documento del representante legal',
            'email' => 'correo electrónico',
            'phone' => 'teléfono celular',
            'department' => 'departamento',
            'province' => 'provincia',
            'district' => 'distrito',
            'address' => 'dirección',
            'password' => 'contraseña',
            'privacy' => 'el tratamiento de sus datos personales',
            'truthful' => 'la declaración de veracidad',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->spanishMessages(),
            'document_number.regex' => 'El número de documento no tiene un formato válido para el tipo seleccionado.',
            'document_number.unique' => 'Ya existe una cuenta con este documento. Ingrese con su correo o recupere su contraseña.',
            'email.unique' => 'Ya existe una cuenta con este correo. Ingrese o recupere su contraseña.',
            'phone.regex' => 'El teléfono solo puede contener números y los signos + ( ) -.',
            'privacy.accepted' => 'Debe aceptar el tratamiento de sus datos personales para crear la cuenta.',
            'truthful.accepted' => 'Debe declarar que los datos consignados son verdaderos.',
        ];
    }
}
