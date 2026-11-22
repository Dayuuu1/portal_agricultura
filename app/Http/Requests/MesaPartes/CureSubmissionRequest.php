<?php

namespace App\Http\Requests\MesaPartes;

use App\Http\Requests\Concerns\SpanishValidationMessages;
use App\Support\MesaPartes\MesaPartesConfig;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Answer to an observation: what was corrected and, if needed, the missing files.
 */
class CureSubmissionRequest extends FormRequest
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
        $maxKb = MesaPartesConfig::maxFileKilobytes();

        return [
            'note' => ['required', 'string', 'min:5', 'max:3000'],
            'files' => ['nullable', 'array', 'max:5'],
            'files.*' => ['file', 'mimes:pdf,jpg,jpeg,png', "max:{$maxKb}"],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['note' => 'detalle de la subsanación', 'files' => 'archivos', 'files.*' => 'archivo'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->spanishMessages(),
            'files.max' => 'Puede adjuntar como máximo :max archivos.',
            'files.*.mimes' => 'Los archivos deben ser PDF, JPG o PNG.',
            'files.*.max' => 'Cada archivo debe pesar como máximo '.(MesaPartesConfig::maxFileKilobytes() / 1024).' MB.',
        ];
    }
}
