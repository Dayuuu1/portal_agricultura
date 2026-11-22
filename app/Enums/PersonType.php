<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PersonType: string implements HasLabel
{
    case Natural = 'natural';
    case Juridica = 'juridica';

    public function getLabel(): string
    {
        return match ($this) {
            self::Natural => 'Persona natural',
            self::Juridica => 'Persona jurídica',
        };
    }
}
