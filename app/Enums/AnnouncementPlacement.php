<?php

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

/**
 * Pages where the announcement window opens.
 */
enum AnnouncementPlacement: string implements HasDescription, HasLabel
{
    case Home = 'home';
    case Everywhere = 'everywhere';

    public function getLabel(): string
    {
        return match ($this) {
            self::Home => 'Solo en la portada',
            self::Everywhere => 'En todo el portal',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Home => 'Recomendado. Aparece al entrar a la página de inicio.',
            self::Everywhere => 'Aparece en la primera página que abra el visitante, también si llega desde un buscador. Nunca en los formularios (Mesa de Partes, reclamaciones, contacto).',
        };
    }
}
