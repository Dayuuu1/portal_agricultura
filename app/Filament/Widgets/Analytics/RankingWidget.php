<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Concerns\ShowsSiteStatistics;
use App\Support\Analytics\SiteStatistics;
use Filament\Widgets\Widget;

/**
 * A ranked list with a proportional bar behind each row, the usual shape of web statistics (pages, sources,
 * searches…). Subclasses only say what to rank.
 */
abstract class RankingWidget extends Widget
{
    use ShowsSiteStatistics;

    protected static bool $isDiscovered = false;

    protected string $view = 'filament.analytics.ranking';

    abstract protected function heading(): string;

    /**
     * Header of the label column ("Página", "Término buscado"…).
     */
    abstract protected function itemLabel(): string;

    /**
     * Header of the figure column ("Vistas", "Búsquedas"…).
     */
    abstract protected function valueLabel(): string;

    /**
     * @return list<array{label: string, hint?: ?string, value: int, url?: ?string}>
     */
    abstract protected function rows(): array;

    protected function description(): ?string
    {
        return null;
    }

    protected function emptyMessage(): string
    {
        return 'Todavía no hay datos en el periodo elegido.';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $rows = $this->rows();
        $max = max([1, ...array_column($rows, 'value')]);

        return [
            'heading' => $this->heading(),
            'description' => $this->description(),
            'itemLabel' => $this->itemLabel(),
            'valueLabel' => $this->valueLabel(),
            'emptyMessage' => $this->emptyMessage(),
            'rows' => array_map(fn (array $row): array => [
                ...$row,
                'display' => SiteStatistics::formatNumber($row['value']),
                'share' => round($row['value'] / $max * 100, 1),
            ], $rows),
        ];
    }
}
