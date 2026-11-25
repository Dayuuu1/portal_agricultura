<?php

namespace App\Filament\Widgets\Analytics;

use App\Support\Analytics\SiteStatistics;

class TopPages extends RankingWidget
{
    protected function heading(): string
    {
        return 'Páginas más vistas';
    }

    protected function description(): ?string
    {
        return 'Con el tiempo medio de lectura de cada una.';
    }

    protected function itemLabel(): string
    {
        return 'Página';
    }

    protected function valueLabel(): string
    {
        return 'Vistas';
    }

    protected function rows(): array
    {
        return array_map(fn (array $page): array => [
            'label' => $page['title'] ?? $page['path'],
            'hint' => $page['path'].' · '.SiteStatistics::formatDuration($page['duration']).' de media',
            'value' => $page['views'],
            'url' => url($page['path']),
        ], SiteStatistics::topPages($this->period()));
    }
}
