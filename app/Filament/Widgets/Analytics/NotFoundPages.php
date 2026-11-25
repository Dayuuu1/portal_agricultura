<?php

namespace App\Filament\Widgets\Analytics;

use App\Enums\SiteEventType;
use App\Support\Analytics\SiteStatistics;

class NotFoundPages extends RankingWidget
{
    protected function heading(): string
    {
        return 'Direcciones que no existen';
    }

    protected function description(): ?string
    {
        return 'Enlaces antiguos o mal escritos que terminan en «Página no encontrada». Puede llevarlos a la página correcta desde «Redirecciones».';
    }

    protected function itemLabel(): string
    {
        return 'Dirección';
    }

    protected function valueLabel(): string
    {
        return 'Visitas';
    }

    protected function emptyMessage(): string
    {
        return 'Ningún visitante llegó a una página inexistente en el periodo elegido.';
    }

    protected function rows(): array
    {
        return collect(SiteStatistics::eventCounts($this->period(), SiteEventType::NotFound))
            ->map(fn (int $visits, string $path): array => ['label' => $path, 'value' => $visits])
            ->values()
            ->all();
    }
}
