<?php

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

/**
 * How often the same visitor sees an announcement again after closing it.
 * Remembered only in the visitor's browser; editing the announcement shows it again to everyone.
 */
enum AnnouncementFrequency: string implements HasDescription, HasLabel
{
    case Once = 'once';
    case Daily = 'daily';
    case EveryVisit = 'visit';

    public function getLabel(): string
    {
        return match ($this) {
            self::Once => 'Una sola vez',
            self::Daily => 'Una vez al día',
            self::EveryVisit => 'En cada visita',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Once => 'Recomendado. Al cerrarlo no vuelve a aparecer, salvo que usted lo modifique.',
            self::Daily => 'Vuelve a aparecer al día siguiente. Para campañas o plazos que vencen pronto.',
            self::EveryVisit => 'Cada vez que el visitante abre el portal de nuevo. Solo para avisos urgentes.',
        };
    }
}
