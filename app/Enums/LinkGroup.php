<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum LinkGroup: string implements HasLabel
{
    case EnlacesInteres = 'enlaces-interes';
    case PortalesEstado = 'portales-estado';
    case Servicios = 'servicios';

    public function getLabel(): string
    {
        return match ($this) {
            self::EnlacesInteres => 'Enlaces de interés (sector agrario)',
            self::PortalesEstado => 'Portales del Estado',
            self::Servicios => 'Servicios en línea',
        };
    }
}
