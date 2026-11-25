<?php

namespace App\Filament\Widgets\Analytics;

use App\Support\Analytics\SiteStatistics;

class Technology extends RankingWidget
{
    protected function heading(): string
    {
        return 'Navegadores';
    }

    protected function description(): ?string
    {
        $systems = SiteStatistics::technology($this->period(), 'os');
        $total = array_sum($systems);

        if ($total === 0) {
            return null;
        }

        return 'Sistemas: '.collect($systems)
            ->take(4)
            ->map(fn (int $visitors, string $system): string => $system.' '.SiteStatistics::formatNumber($visitors / $total * 100).' %')
            ->implode(' · ');
    }

    protected function itemLabel(): string
    {
        return 'Navegador';
    }

    protected function valueLabel(): string
    {
        return 'Visitantes';
    }

    protected function rows(): array
    {
        return collect(SiteStatistics::technology($this->period(), 'browser'))
            ->take(8)
            ->map(fn (int $visitors, string $browser): array => ['label' => $browser, 'value' => $visitors])
            ->values()
            ->all();
    }
}
