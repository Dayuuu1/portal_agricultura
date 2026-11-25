<?php

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * How the announcement window arranges the image and the text.
 * Without an image the window is always an official notice (text only).
 */
enum AnnouncementLayout: string implements HasDescription, HasIcon, HasLabel
{
    case Card = 'card';
    case Poster = 'poster';

    public function getLabel(): string
    {
        return match ($this) {
            self::Card => 'Imagen y texto',
            self::Poster => 'Afiche',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Card => 'Foto horizontal arriba y el texto debajo. Para comunicados, eventos y noticias.',
            self::Poster => 'La imagen completa, sin recortar (afiches o flyers ya diseñados). El texto se usa como descripción.',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Card => Heroicon::OutlinedNewspaper,
            self::Poster => Heroicon::OutlinedPhoto,
        };
    }
}
