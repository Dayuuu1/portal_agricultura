<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Aspects a claim may be about, as listed in art. 4.2 of DS N° 007-2020-PCM.
 */
enum ComplaintReason: string implements HasLabel
{
    case TratoProfesional = 'trato-profesional';
    case Informacion = 'informacion';
    case TiempoAtencion = 'tiempo-atencion';
    case AccesoServicio = 'acceso-servicio';
    case Resultado = 'resultado';
    case Confianza = 'confianza';
    case Otro = 'otro';

    public function getLabel(): string
    {
        return match ($this) {
            self::TratoProfesional => 'Trato profesional durante la atención',
            self::Informacion => 'Información brindada',
            self::TiempoAtencion => 'Tiempo de atención',
            self::AccesoServicio => 'Acceso al bien o servicio',
            self::Resultado => 'Resultado de la gestión o atención',
            self::Confianza => 'Confianza en la entidad',
            self::Otro => 'Otro motivo',
        };
    }
}
