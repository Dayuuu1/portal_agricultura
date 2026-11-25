<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DeviceType: string implements HasLabel
{
    case Mobile = 'mobile';
    case Tablet = 'tablet';
    case Desktop = 'desktop';

    public function getLabel(): string
    {
        return match ($this) {
            self::Mobile => 'Celular',
            self::Tablet => 'Tableta',
            self::Desktop => 'Computadora',
        };
    }
}
