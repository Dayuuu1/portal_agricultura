<?php

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

/**
 * How much the banner background photo is darkened so the white text stays readable.
 */
enum BannerOverlay: string implements HasDescription, HasLabel
{
    case Light = 'light';
    case Medium = 'medium';
    case Strong = 'strong';

    public function getLabel(): string
    {
        return match ($this) {
            self::Light => 'Suave',
            self::Medium => 'Media',
            self::Strong => 'Fuerte',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Light => 'Para fotos oscuras: se ve más la imagen.',
            self::Medium => 'Recomendado para la mayoría de fotos.',
            self::Strong => 'Para fotos muy claras o con mucho detalle.',
        };
    }

    /**
     * CSS gradient (institutional dark green) from the text side (left) to the photo side (right).
     * Used both on the portal and in the panel preview, so they always match.
     */
    public function gradientStyle(): string
    {
        [$left, $middle, $right] = match ($this) {
            self::Light => [0.8, 0.45, 0.1],
            self::Medium => [0.9, 0.65, 0.25],
            self::Strong => [0.95, 0.85, 0.6],
        };

        return "background-image: linear-gradient(to right, rgb(8 36 23 / {$left}), rgb(8 36 23 / {$middle}) 55%, rgb(8 36 23 / {$right}));";
    }
}
