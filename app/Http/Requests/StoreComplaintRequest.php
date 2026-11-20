<?php

namespace App\Http\Requests;

use App\Enums\ComplaintReason;
use App\Enums\IdentityDocumentType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Hoja de reclamación (DS N° 007-2020-PCM, art. 15.4): enough data to identify the person,
 * the service received and the facts. Attachments are optional (art. 15.5).
 */
class StoreComplaintRequest extends FormRequest
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
        $documentType = IdentityDocumentType::tryFrom((string) $this->input('document_type'));

        return [
            'full_name' => ['required', 'string', 'max:150'],
            'document_type' => ['required', Rule::enum(IdentityDocumentType::class)],
            'document_number' => ['required', 'string', $documentType?->numberRule() ?? 'max:20'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9 +()-]{6,20}$/'],
            'email' => ['nullable', 'required_if_accepted:notify_by_email', 'email', 'max:150'],
            'is_minor' => ['nullable', 'boolean'],
            'guardian_name' => ['nullable', 'required_if_accepted:is_minor', 'string', 'max:150'],
            'guardian_document_number' => ['nullable', 'required_if_accepted:is_minor', 'string', 'max:20'],

            'incident_date' => ['required', 'date', 'before_or_equal:today', 'after:'.now()->subYears(2)->toDateString()],
            'agency_id' => ['nullable', Rule::exists('agencies', 'id')->where('is_active', true)],
            'organizational_unit_id' => ['nullable', Rule::exists('organizational_units', 'id')],
            'reason' => ['required', Rule::enum(ComplaintReason::class)],
            'description' => ['required', 'string', 'min:20', 'max:4000'],
            'request' => ['nullable', 'string', 'max:2000'],
            'attachments' => ['nullable', 'array', 'max:3'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],

            'notify_by_email' => ['nullable', 'boolean'],
            'truthful' => ['accepted'],
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
            'full_name' => 'nombres y apellidos',
            'document_type' => 'tipo de documento',
            'document_number' => 'número de documento',
            'address' => 'domicilio',
            'phone' => 'teléfono celular',
            'email' => 'correo electrónico',
            'guardian_name' => 'nombre del padre, madre o apoderado',
            'guardian_document_number' => 'documento del padre, madre o apoderado',
            'incident_date' => 'fecha del hecho',
            'agency_id' => 'sede',
            'organizational_unit_id' => 'oficina',
            'reason' => 'motivo del reclamo',
            'description' => 'detalle del reclamo',
            'request' => 'pedido',
            'attachments' => 'archivos adjuntos',
            'attachments.*' => 'archivo adjunto',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'required_if_accepted' => 'El campo :attribute es obligatorio.',
            'email.required_if_accepted' => 'Indique su correo electrónico para recibir la respuesta por ese medio.',
            'email' => 'Ingrese un :attribute válido.',
            'max' => 'El campo :attribute no debe superar :max caracteres.',
            'min' => 'El campo :attribute debe tener al menos :min caracteres.',
            'document_number.regex' => 'El número de documento no tiene un formato válido para el tipo seleccionado.',
            'phone.regex' => 'El teléfono solo puede contener números y los signos + ( ) -.',
            'enum' => 'Seleccione un :attribute válido.',
            'exists' => 'Seleccione un valor válido para :attribute.',
            'incident_date.before_or_equal' => 'La fecha del hecho no puede ser posterior a hoy.',
            'incident_date.after' => 'La fecha del hecho no puede tener más de dos años de antigüedad.',
            'attachments.max' => 'Puede adjuntar como máximo :max archivos.',
            'attachments.*.mimes' => 'Los archivos adjuntos deben ser PDF, JPG o PNG.',
            'attachments.*.max' => 'Cada archivo adjunto debe pesar como máximo 5 MB.',
            'truthful.accepted' => 'Debe declarar que los datos consignados son verdaderos.',
            'privacy.accepted' => 'Debe aceptar el tratamiento de sus datos personales.',
            'website.prohibited' => 'No se pudo registrar el reclamo.',
        ];
    }
}
