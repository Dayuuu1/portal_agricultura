<?php

namespace App\Filament\Widgets\Analytics;

use App\Support\Analytics\SiteStatistics;
use App\Support\Analytics\StatisticsReport;

class SearchTerms extends RankingWidget
{
    protected function heading(): string
    {
        return 'Lo que más buscan';
    }

    protected function description(): ?string
    {
        $totals = SiteStatistics::searchTotals($this->period());

        return $totals['searches'] === 0
            ? 'Búsquedas en el buscador general, en noticias y en documentos.'
            : SiteStatistics::formatNumber($totals['searches']).' búsquedas en el periodo; el '
                .SiteStatistics::formatNumber($totals['withoutResults'] / $totals['searches'] * 100).' % no encontró nada.';
    }

    protected function itemLabel(): string
    {
        return 'Término buscado';
    }

    protected function valueLabel(): string
    {
        return 'Búsquedas';
    }

    protected function rows(): array
    {
        return array_map(fn (array $search): array => [
            'label' => $search['term'],
            'hint' => 'En '.self::scopes($search['scopes']).' · '.($search['results'] > 0 ? SiteStatistics::formatNumber($search['results']).' resultados' : 'sin resultados'),
            'value' => $search['searches'],
        ], SiteStatistics::searches($this->period()));
    }

    /**
     * "buscador general, documentos" from the stored scopes ("portal,documentos").
     */
    public static function scopes(string $scopes): string
    {
        return mb_strtolower(StatisticsReport::scopes($scopes));
    }
}
