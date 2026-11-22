<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SubmissionFileKind: string implements HasLabel
{
    case Principal = 'principal';
    case Anexo = 'anexo';
    case Comprobante = 'comprobante';
    case Subsanacion = 'subsanacion';
    case Respuesta = 'respuesta';

    public function getLabel(): string
    {
        return match ($this) {
            self::Principal => 'Documento principal',
            self::Anexo => 'Anexo',
            self::Comprobante => 'Comprobante de pago',
            self::Subsanacion => 'Subsanación',
            self::Respuesta => 'Respuesta de la entidad',
        };
    }
}
