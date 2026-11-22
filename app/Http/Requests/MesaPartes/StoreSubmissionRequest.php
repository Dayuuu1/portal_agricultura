<?php

namespace App\Http\Requests\MesaPartes;

use App\Http\Requests\Concerns\SpanishValidationMessages;
use App\Models\ProcedureType;
use App\Support\MesaPartes\MesaPartesConfig;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A document for the Mesa de Partes. Only technical checks block the filing (format and size); anything else
 * is observed afterwards, because the entity cannot refuse to receive a document (TUO de la Ley N° 27444, art. 136).
 */
class StoreSubmissionRequest extends FormRequest
{
    use SpanishValidationMessages;

    public function authorize(): bool
    {
        return true;
    }

    public function procedureType(): ?ProcedureType
    {
        return ProcedureType::query()->active()->find($this->input('procedure_type_id'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxKb = MesaPartesConfig::maxFileKilobytes();

        return [
            'procedure_type_id' => ['required', Rule::exists('procedure_types', 'id')->where('is_active', true)],
            'subject' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'related_code' => ['nullable', 'string', 'max:60'],
            'target_unit_id' => ['nullable', Rule::exists('organizational_units', 'id')->where('is_active', true)],
            'principal' => ['required', 'file', 'mimes:pdf', "max:{$maxKb}"],
            'attachments' => ['nullable', 'array', 'max:'.MesaPartesConfig::maxAttachments()],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png', "max:{$maxKb}"],
            'payment_proof' => [Rule::requiredIf(fn (): bool => (bool) $this->procedureType()?->requires_payment_proof), 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', "max:{$maxKb}"],
            'truthful' => ['accepted'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $files = array_filter([$this->file('principal'), ...($this->file('attachments') ?? []), $this->file('payment_proof')]);
                $totalKb = array_sum(array_map(fn (UploadedFile $file): float => $file->getSize() / 1024, $files));

                if ($totalKb > MesaPartesConfig::maxTotalKilobytes()) {
                    $validator->errors()->add('attachments', 'Los archivos superan en conjunto los '.(MesaPartesConfig::maxTotalKilobytes() / 1024).' MB permitidos por trámite.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'procedure_type_id' => 'tipo de trámite',
            'subject' => 'asunto',
            'description' => 'fundamentos o descripción',
            'related_code' => 'número de expediente relacionado',
            'target_unit_id' => 'oficina destinataria',
            'principal' => 'documento principal',
            'attachments' => 'anexos',
            'attachments.*' => 'anexo',
            'payment_proof' => 'comprobante de pago',
            'truthful' => 'la declaración jurada',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxMb = MesaPartesConfig::maxFileKilobytes() / 1024;

        return [
            ...$this->spanishMessages(),
            'principal.mimes' => 'El documento principal debe estar en formato PDF.',
            'principal.max' => "El documento principal debe pesar como máximo {$maxMb} MB.",
            'attachments.max' => 'Puede adjuntar como máximo :max anexos.',
            'attachments.*.mimes' => 'Los anexos deben ser PDF, JPG o PNG.',
            'attachments.*.max' => "Cada anexo debe pesar como máximo {$maxMb} MB.",
            'payment_proof.mimes' => 'El comprobante de pago debe ser PDF, JPG o PNG.',
            'payment_proof.max' => "El comprobante de pago debe pesar como máximo {$maxMb} MB.",
            'truthful.accepted' => 'Debe aceptar la declaración jurada para enviar el documento.',
        ];
    }
}
