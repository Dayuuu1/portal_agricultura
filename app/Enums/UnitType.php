<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UnitType: string implements HasLabel
{
    case AltaDireccion = 'alta-direccion';
    case Control = 'control';
    case Asesoramiento = 'asesoramiento';
    case Apoyo = 'apoyo';
    case Linea = 'linea';
    case Desconcentrado = 'desconcentrado';
    case Unidad = 'unidad';

    public function getLabel(): string
    {
        return match ($this) {
            self::AltaDireccion => 'Alta Dirección',
            self::Control => 'Órgano de Control',
            self::Asesoramiento => 'Órgano de Asesoramiento',
            self::Apoyo => 'Órgano de Apoyo',
            self::Linea => 'Órgano de Línea',
            self::Desconcentrado => 'Órgano Desconcentrado',
            self::Unidad => 'Unidad',
        };
    }
}
