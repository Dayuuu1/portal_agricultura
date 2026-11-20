<?php

namespace App\Http\Requests;

use App\Enums\ContactMessageType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactMessageRequest extends FormRequest
{
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
            'type' => ['required', Rule::enum(ContactMessageType::class)],
            'full_name' => ['required', 'string', 'max:150'],
            'document_number' => ['nullable', 'digits_between:8,12'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9 +()-]+$/'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
            'privacy' => ['accepted'],
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => 'tipo de mensaje',
            'full_name' => 'nombres y apellidos',
            'document_number' => 'DNI / CE',
            'email' => 'correo electrónico',
            'phone' => 'teléfono',
            'subject' => 'asunto',
            'message' => 'mensaje',
            'privacy' => 'política de privacidad',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'email' => 'Ingrese un :attribute válido.',
            'max' => 'El campo :attribute no debe superar :max caracteres.',
            'min' => 'El campo :attribute debe tener al menos :min caracteres.',
            'digits_between' => 'El :attribute debe tener entre :min y :max dígitos.',
            'regex' => 'El :attribute solo puede contener números y los signos + ( ) -.',
            'enum' => 'Seleccione un :attribute válido.',
            'privacy.accepted' => 'Debe aceptar la política de privacidad.',
            'website.prohibited' => 'No se pudo enviar el mensaje.',
        ];
    }
}
