<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Stages of a claim under DS N° 007-2020-PCM (arts. 13 and 16).
 */
enum ComplaintStatus: string implements HasColor, HasLabel
{
    case Registrado = 'registrado';
    case EnAtencion = 'en-atencion';
    case Derivado = 'derivado';
    case Aceptado = 'aceptado';
    case Denegado = 'denegado';
    case Archivado = 'archivado';

    public function getLabel(): string
    {
        return match ($this) {
            self::Registrado => 'Registrado',
            self::EnAtencion => 'En atención',
            self::Derivado => 'Derivado a otra entidad',
            self::Aceptado => 'Respondido: aceptado',
            self::Denegado => 'Respondido: denegado',
            self::Archivado => 'Archivado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Registrado => 'warning',
            self::EnAtencion => 'info',
            self::Derivado, self::Archivado => 'gray',
            self::Aceptado => 'success',
            self::Denegado => 'danger',
        };
    }

    /**
     * Whether the entity still owes the citizen an answer.
     */
    public function isOpen(): bool
    {
        return in_array($this, [self::Registrado, self::EnAtencion], true);
    }

    /**
     * @return list<self>
     */
    public static function open(): array
    {
        return [self::Registrado, self::EnAtencion];
    }
}
