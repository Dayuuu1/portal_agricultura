<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Concerns\ShowsSiteStatistics;
use App\Support\Analytics\SiteStatistics;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/**
 * Visitors and page views day by day (hour by hour for a single day), with the previous period as reference.
 */
class VisitorsTrend extends ChartWidget
{
    use ShowsSiteStatistics;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Evolución de las visitas';

    protected ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = 'full';

    public function getDescription(): ?string
    {
        return $this->period()->isSingleDay()
            ? 'Hora por hora. La línea punteada es el día anterior.'
            : 'Día por día. La línea punteada son los visitantes del periodo anterior, para comparar.';
    }

    protected function getData(): array
    {
        $period = $this->period();
        $trend = SiteStatistics::trend($period);
        $previous = array_column(SiteStatistics::trend($period->previous()), 'visitors');

        $labels = $period->isSingleDay()
            ? array_map(fn (string $hour): string => str_pad($hour, 2, '0', STR_PAD_LEFT).':00', array_keys($trend))
            : array_map(fn (string $day): string => Carbon::parse($day)->translatedFormat($period->days() > 120 ? 'M Y' : 'd M'), array_keys($trend));

        return [
            'datasets' => [
                [
                    'label' => 'Visitantes',
                    'data' => array_column($trend, 'visitors'),
                    'borderColor' => '#1c7c45',
                    'backgroundColor' => 'rgb(28 124 69 / 0.12)',
                    'fill' => true,
                    'tension' => 0.3,
                    'pointRadius' => $period->days() > 45 ? 0 : 3,
                ],
                [
                    'label' => 'Páginas vistas',
                    'data' => array_column($trend, 'views'),
                    'borderColor' => '#d9961f',
                    'backgroundColor' => '#ebb43a',
                    'tension' => 0.3,
                    'pointRadius' => $period->days() > 45 ? 0 : 3,
                ],
                [
                    'label' => 'Visitantes (periodo anterior)',
                    'data' => array_slice(array_pad($previous, count($trend), 0), 0, count($trend)),
                    'borderColor' => '#94a3b8',
                    'backgroundColor' => '#cbd5e1',
                    'borderDash' => [5, 5],
                    'tension' => 0.3,
                    'pointRadius' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['position' => 'bottom']],
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }

    /**
     * The chart in words, for screen readers.
     */
    public function getChartAssistiveContent(): string
    {
        $summary = SiteStatistics::summary($this->period());

        return sprintf(
            '%s: %s visitantes y %s páginas vistas.',
            $this->period()->label(),
            SiteStatistics::formatNumber($summary['visitors']),
            SiteStatistics::formatNumber($summary['views']),
        );
    }
}
