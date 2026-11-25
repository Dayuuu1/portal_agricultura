<?php

namespace App\Filament\Widgets\Analytics;

use App\Support\Analytics\SiteStatistics;

class Sections extends RankingWidget
{
    protected function heading(): string
    {
        return 'Secciones del portal';
    }

    protected function description(): ?string
    {
        return 'Qué áreas consulta más la ciudadanía.';
    }

    protected function itemLabel(): string
    {
        return 'Sección';
    }

    protected function valueLabel(): string
    {
        return 'Vistas';
    }

    protected function rows(): array
    {
        return array_map(fn (array $section): array => [
            'label' => $section['section']->getLabel(),
            'hint' => SiteStatistics::formatNumber($section['visitors']).' visitantes',
            'value' => $section['views'],
        ], SiteStatistics::sections($this->period()));
    }
}
