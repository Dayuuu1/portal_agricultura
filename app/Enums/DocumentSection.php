<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DocumentSection: string implements HasLabel
{
    case Normativa = 'normativa';
    case Gestion = 'gestion';
    case InformacionAgraria = 'informacion-agraria';

    public function getLabel(): string
    {
        return match ($this) {
            self::Normativa => 'Normas y resoluciones',
            self::Gestion => 'Gestión institucional y transparencia',
            self::InformacionAgraria => 'Información agraria',
        };
    }
}
