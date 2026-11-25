<?php

namespace App\Filament\Widgets\Analytics;

use App\Enums\SiteEventType;
use App\Support\Analytics\SiteStatistics;

class OutboundLinks extends RankingWidget
{
    protected function heading(): string
    {
        return 'Enlaces a otros sitios';
    }

    protected function description(): ?string
    {
        return 'Sitios externos a los que el portal envía visitantes (gob.pe, MIDAGRI, SENASA…).';
    }

    protected function itemLabel(): string
    {
        return 'Sitio';
    }

    protected function valueLabel(): string
    {
        return 'Clics';
    }

    protected function rows(): array
    {
        return collect(SiteStatistics::eventCounts($this->period(), SiteEventType::Outbound))
            ->map(fn (int $clicks, string $host): array => ['label' => $host, 'value' => $clicks])
            ->values()
            ->all();
    }
}
