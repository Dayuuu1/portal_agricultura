<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ContactMessageType: string implements HasLabel
{
    case Consulta = 'consulta';
    case Sugerencia = 'sugerencia';
    case Felicitacion = 'felicitacion';

    public function getLabel(): string
    {
        return match ($this) {
            self::Consulta => 'Consulta',
            self::Sugerencia => 'Sugerencia',
            self::Felicitacion => 'Felicitación',
        };
    }
}
