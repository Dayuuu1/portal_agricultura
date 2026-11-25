<?php

namespace App\Filament\Widgets\Analytics;

use App\Support\Analytics\SiteStatistics;

class TrafficSources extends RankingWidget
{
    protected function heading(): string
    {
        return 'De dónde llegan';
    }

    protected function description(): ?string
    {
        return 'Cómo empezó cada visita: escribiendo la dirección, desde Google, desde redes sociales u otros sitios.';
    }

    protected function itemLabel(): string
    {
        return 'Origen';
    }

    protected function valueLabel(): string
    {
        return 'Visitas';
    }

    protected function rows(): array
    {
        return array_map(fn (array $source): array => [
            'label' => $source['source']->getLabel(),
            'hint' => $source['topSite'] ? 'Sobre todo desde '.$source['topSite'] : null,
            'value' => $source['visits'],
        ], SiteStatistics::sources($this->period()));
    }
}
