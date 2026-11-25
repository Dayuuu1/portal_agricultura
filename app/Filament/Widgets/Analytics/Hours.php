<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Concerns\ShowsSiteStatistics;
use App\Support\Analytics\SiteStatistics;
use Filament\Widgets\ChartWidget;

/**
 * Page views by hour of the day: when to publish and when to keep the services available.
 */
class Hours extends ChartWidget
{
    use ShowsSiteStatistics;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Horas de mayor visita';

    protected ?string $maxHeight = '260px';

    public function getDescription(): ?string
    {
        $hours = SiteStatistics::hours($this->period());

        if (array_sum($hours) === 0) {
            return 'Páginas vistas en cada hora del día (hora de Perú).';
        }

        $peak = array_search(max($hours), $hours, true);

        return 'Hora pico: de '.str_pad((string) $peak, 2, '0', STR_PAD_LEFT).':00 a '.str_pad((string) (($peak + 1) % 24), 2, '0', STR_PAD_LEFT).':00 (hora de Perú). Buen momento para publicar.';
    }

    protected function getData(): array
    {
        $hours = SiteStatistics::hours($this->period());
        $peak = max($hours);

        return [
            'datasets' => [[
                'label' => 'Páginas vistas',
                'data' => array_values($hours),
                'backgroundColor' => array_map(fn (int $views): string => $peak > 0 && $views === $peak ? '#ebb43a' : '#1c7c45', $hours),
                'borderRadius' => 3,
            ]],
            'labels' => array_map(fn (int $hour): string => str_pad((string) $hour, 2, '0', STR_PAD_LEFT).'h', array_keys($hours)),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }
}
