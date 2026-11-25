<?php

namespace App\Filament\Widgets\Analytics;

use App\Support\Analytics\SiteStatistics;

class SearchesWithoutResults extends RankingWidget
{
    protected function heading(): string
    {
        return 'Búsquedas sin resultados';
    }

    protected function description(): ?string
    {
        return 'Lo que la ciudadanía busca y no encuentra: pistas para publicar o renombrar contenido.';
    }

    protected function itemLabel(): string
    {
        return 'Término buscado';
    }

    protected function valueLabel(): string
    {
        return 'Búsquedas';
    }

    protected function emptyMessage(): string
    {
        return 'Ninguna búsqueda se quedó sin resultados en el periodo elegido.';
    }

    protected function rows(): array
    {
        return array_map(fn (array $search): array => [
            'label' => $search['term'],
            'hint' => 'En '.SearchTerms::scopes($search['scopes']),
            'value' => $search['searches'],
        ], SiteStatistics::searches($this->period(), withoutResults: true));
    }
}
