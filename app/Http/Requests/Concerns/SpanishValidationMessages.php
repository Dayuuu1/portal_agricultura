<?php

namespace App\Http\Requests\Concerns;

/**
 * Validation messages in Spanish for the public forms (the project has no Spanish language files).
 */
trait SpanishValidationMessages
{
    /**
     * @return array<string, string>
     */
    protected function spanishMessages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'required_if' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute no es válido.',
            'email' => 'Ingrese un :attribute válido.',
            'max' => 'El campo :attribute no debe superar :max caracteres.',
            'min' => 'El campo :attribute debe tener al menos :min caracteres.',
            'in' => 'Seleccione un valor válido para :attribute.',
            'enum' => 'Seleccione un valor válido para :attribute.',
            'exists' => 'Seleccione un valor válido para :attribute.',
            'unique' => 'Ya existe una cuenta registrada con este :attribute.',
            'accepted' => 'Debe aceptar :attribute.',
            'confirmed' => 'La confirmación de :attribute no coincide.',
            'regex' => 'El campo :attribute no tiene un formato válido.',
            'file' => 'El :attribute debe ser un archivo.',
            'mimes' => 'El :attribute debe ser un archivo de tipo: :values.',
            'array' => 'El campo :attribute no es válido.',
            'prohibited' => 'No se pudo procesar el formulario.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
            'password.letters' => 'La contraseña debe incluir letras.',
            'password.mixed' => 'La contraseña debe incluir mayúsculas y minúsculas.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
            'password.uncompromised' => 'Esta contraseña apareció en filtraciones de datos. Elija otra.',
        ];
    }
}
