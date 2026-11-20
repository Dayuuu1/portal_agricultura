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

    /**
     * One-line summary shown on the public documents overview.
     */
    public function description(): string
    {
        return match ($this) {
            self::Normativa => 'Resoluciones directorales y documentos de saneamiento físico legal.',
            self::Gestion => 'Instrumentos de gestión, planeamiento, presupuesto, control y rendición de cuentas.',
            self::InformacionAgraria => 'Estadística agrícola y pecuaria, boletines, información agroindustrial y precios de mercado.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Normativa => 'heroicon-o-scale',
            self::Gestion => 'heroicon-o-folder',
            self::InformacionAgraria => 'heroicon-o-chart-bar',
        };
    }
}
